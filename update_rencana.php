<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

// Koneksi database
$conn = new mysqli("localhost", "root", "", "tenaga_sehat");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// Ambil ID dari URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo "<div class='alert alert-danger'>Parameter ID tidak valid.</div>";
    exit;
}

// Ambil data rencana_pengadaan (by id)
$sql = "SELECT * FROM rencana_pengadaan WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo "<div class='alert alert-danger'>Data tidak ditemukan.</div>";
    exit;
}
$data = $res->fetch_assoc();
$stmt->close();

$pelaporan_id = $data['pelaporan_id'];
$cabang_id    = $data['cabang_id'];

// (Opsional) Ambil data riwayat_laporan terbaru untuk keperluan lain jika diperlukan
$riw = null;
$sqlR = "SELECT * FROM riwayat_laporan WHERE pelaporan_id = ? ORDER BY id DESC LIMIT 1";
$stmtR = $conn->prepare($sqlR);
$stmtR->bind_param("i", $pelaporan_id);
$stmtR->execute();
$rRes = $stmtR->get_result();
if ($rRes && $rRes->num_rows > 0) {
    $riw = $rRes->fetch_assoc();
}
$stmtR->close();

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $catatan       = trim($_POST['catatan'] ?? '');
    $tindak_lanjut = trim($_POST['tindak_lanjut'] ?? '');

    if ($catatan === '' || $tindak_lanjut === '') {
        echo "<script>alert('Catatan dan Tindak Lanjut wajib diisi.');</script>";
    } else {
        // Mulai transaksi agar atomic
        $conn->begin_transaction();
        try {
            // 1) INSERT ke up_laporan (dengan cabang_id & status Disetujui)
            $sqlInsertUp = "INSERT INTO up_laporan
                (pelaporan_id, cabang_id, nama_cabang, tanggal, nama_aset, kode_aset,
                 keterangan_kerusakan, pesan_pengajuan, catatan, tindak_lanjut, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Disetujui', NOW())";
            $stmtUp = $conn->prepare($sqlInsertUp);
            $stmtUp->bind_param(
                "iissssssss",
                $pelaporan_id,
                $cabang_id,
                $data['nama_cabang'],
                $data['tanggal'],
                $data['nama_aset'],
                $data['kode_aset'],
                $data['keterangan_kerusakan'],
                $data['pesan_pengajuan'],
                $catatan,
                $tindak_lanjut
            );
            if (!$stmtUp->execute()) {
                throw new Exception("Gagal insert up_laporan: " . $stmtUp->error);
            }
            $stmtUp->close();

            // 2) UPDATE status_pesan (catatan, tindak_lanjut, status=Disetujui, simpan cabang_id)
            $sqlUpdSP = "UPDATE status_pesan
                         SET catatan = ?, tindak_lanjut = ?, status = 'Disetujui', cabang_id = ?
                         WHERE pelaporan_id = ?";
            $stmtSP = $conn->prepare($sqlUpdSP);
            $stmtSP->bind_param("ssii", $catatan, $tindak_lanjut, $cabang_id, $pelaporan_id);
            $stmtSP->execute();

            // Jika belum ada baris status_pesan utk pelaporan_id tsb, buat baru (supaya robust)
            if ($stmtSP->affected_rows === 0) {
                $stmtSP->close();
                $sqlInsSP = "INSERT INTO status_pesan
                    (pelaporan_id, cabang_id, nama_cabang, tanggal, kode_aset, nama_aset,
                     keterangan_kerusakan, pesan_pengajuan, status, catatan, tindak_lanjut, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Disetujui', ?, ?, NOW())";
                $stmtSPIns = $conn->prepare($sqlInsSP);
                $stmtSPIns->bind_param(
                    "iisssssss",
                    $pelaporan_id,
                    $cabang_id,
                    $data['nama_cabang'],
                    $data['tanggal'],
                    $data['kode_aset'],
                    $data['nama_aset'],
                    $data['keterangan_kerusakan'],
                    $data['pesan_pengajuan'],
                    $catatan,
                    $tindak_lanjut
                );
                if (!$stmtSPIns->execute()) {
                    throw new Exception("Gagal insert status_pesan: " . $stmtSPIns->error);
                }
                $stmtSPIns->close();
            } else {
                $stmtSP->close();
            }

            // 3) UPDATE rencana_pengadaan (catatan, tindak_lanjut, status=Disetujui)
            $sqlUpdRP = "UPDATE rencana_pengadaan
                        SET catatan = ?, tindak_lanjut = ?, status = 'Disetujui'
                        WHERE id = ?";
            $stmtRP = $conn->prepare($sqlUpdRP);
            $stmtRP->bind_param("ssi", $catatan, $tindak_lanjut, $id);
            if (!$stmtRP->execute()) {
                throw new Exception("Gagal update rencana_pengadaan: " . $stmtRP->error);
            }
            $stmtRP->close();

            // Commit semua perubahan
            $conn->commit();

            echo "<script>alert('Data berhasil dikirim, catatan & tindak lanjut diperbarui, dan status Disetujui.'); window.location='rencana_pengadaan.php';</script>";
            exit;
        } catch (Exception $e) {
            // Rollback jika gagal
            $conn->rollback();
            echo "<div class='alert alert-danger'>Terjadi kesalahan: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Update Rencana Pengadaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="card shadow border-0">
            <div class="card-header bg-dark text-light">
                <h4 class="mb-0"><i class="bi bi-pencil-square"></i> Update Rencana Pengadaan</h4>
            </div>
            <div class="card-body">
                <form method="post" autocomplete="off">
                    <!-- Data otomatis (readonly) -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Cabang</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($data['nama_cabang']); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($data['tanggal']); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Aset</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($data['nama_aset']); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kode Aset</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($data['kode_aset']); ?>" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Keterangan Kerusakan</label>
                            <textarea class="form-control" rows="2" readonly><?= htmlspecialchars($data['keterangan_kerusakan']); ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Pesan Pengajuan</label>
                            <textarea class="form-control" rows="2" readonly><?= htmlspecialchars($data['pesan_pengajuan']); ?></textarea>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Input manual -->
                    <div class="mb-3">
                        <label class="form-label">Catatan <span class="text-danger">*</span></label>
                        <input type="text" name="catatan" class="form-control" value="<?= htmlspecialchars($data['catatan'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Tindak Lanjut <span class="text-danger">*</span></label>
                        <input type="text" name="tindak_lanjut" class="form-control" value="<?= htmlspecialchars($data['tindak_lanjut'] ?? ''); ?>" required>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save"></i> Kirim & Setujui
                    </button>
                    <a href="rencana_pengadaan.php" class="btn btn-secondary">
                        Kembali
                    </a>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>