<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

$username = $_SESSION['username'];

// Koneksi database
$conn = new mysqli("localhost", "root", "", "tenaga_sehat");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Pastikan ID ada di URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID riwayat laporan tidak ditemukan.");
}

$id_laporan = intval($_GET['id']);

// Ambil data laporan + status dari riwayat_laporan
$sql = "SELECT pelaporan_id, cabang_id, tanggal, nama_cabang, nama_aset, kode_aset, 
               keterangan_kerusakan, pesan_pengajuan, status 
        FROM riwayat_laporan 
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_laporan);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Data laporan tidak ditemukan.");
}

$data = $result->fetch_assoc();
$cabang_id = (int)$data['cabang_id'];
$pelaporan_id = (int)$data['pelaporan_id'];
$status_laporan = $data['status']; // status asli dari riwayat_laporan

// Proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = trim($_POST['tanggal']);
    $nama_cabang = trim($_POST['nama_cabang']);
    $nama_aset = trim($_POST['nama_aset']);
    $kode_aset = trim($_POST['kode_aset']);
    $keterangan_kerusakan = trim($_POST['keterangan_kerusakan']);
    $pesan_pengajuan = trim($_POST['pesan_pengajuan']);
    $catatan = trim($_POST['catatan']);
    $tindak_lanjut = trim($_POST['tindak_lanjut']);

    if (empty($catatan) || empty($tindak_lanjut)) {
        echo "<div class='alert alert-danger'>Catatan dan tindak lanjut wajib diisi!</div>";
    } else {
        // Status diambil dari riwayat_laporan
        $status = $status_laporan;

        // Simpan ke status_pesan
        $insert_sql = "INSERT INTO status_pesan
            (pelaporan_id, cabang_id, nama_cabang, tanggal, kode_aset, nama_aset, 
             keterangan_kerusakan, pesan_pengajuan, status, catatan, tindak_lanjut, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param(
            "iisssssssss",
            $pelaporan_id,
            $cabang_id,
            $nama_cabang,
            $tanggal,
            $kode_aset,
            $nama_aset,
            $keterangan_kerusakan,
            $pesan_pengajuan,
            $status,
            $catatan,
            $tindak_lanjut
        );

        if ($insert_stmt->execute()) {
            // Jika status laporan adalah 'Diproses', masukkan juga ke rencana_pengadaan
            if (strtolower($status_laporan) === 'diproses') {
                $insert_pengadaan_sql = "INSERT INTO rencana_pengadaan
                    (pelaporan_id, cabang_id, nama_cabang, tanggal, kode_aset, nama_aset, 
                     keterangan_kerusakan, pesan_pengajuan, status, catatan, tindak_lanjut, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

                $insert_pengadaan_stmt = $conn->prepare($insert_pengadaan_sql);
                $insert_pengadaan_stmt->bind_param(
                    "iisssssssss",
                    $pelaporan_id,
                    $cabang_id,
                    $nama_cabang,
                    $tanggal,
                    $kode_aset,
                    $nama_aset,
                    $keterangan_kerusakan,
                    $pesan_pengajuan,
                    $status,
                    $catatan,
                    $tindak_lanjut
                );
                $insert_pengadaan_stmt->execute();
            }

            // Update icon di riwayat_laporan
            $update_sql = "UPDATE riwayat_laporan SET icon = 1 WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $id_laporan);
            $update_stmt->execute();

            header("Location: riwayat_laporan.php?success=1");
            exit;
        } else {
            echo "<div class='alert alert-danger'>Gagal menyimpan data: " . $conn->error . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Form Kirim Pesan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-header bg-primary text-white text-center rounded-top-4">
                        <h4 class="mb-0"><i class="bi bi-chat-left-text"></i> Form Kirim Pesan</h4>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Nama Cabang</label>
                                <input type="text" class="form-control" name="nama_cabang" value="<?= htmlspecialchars($data['nama_cabang']) ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tanggal</label>
                                <input type="text" class="form-control" name="tanggal" value="<?= htmlspecialchars($data['tanggal']) ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nama Aset</label>
                                <input type="text" class="form-control" name="nama_aset" value="<?= htmlspecialchars($data['nama_aset']) ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kode Aset</label>
                                <input type="text" class="form-control" name="kode_aset" value="<?= htmlspecialchars($data['kode_aset']) ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Keterangan Kerusakan</label>
                                <textarea class="form-control" name="keterangan_kerusakan" rows="3" readonly><?= htmlspecialchars($data['keterangan_kerusakan']) ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Pesan Pengajuan</label>
                                <textarea class="form-control" name="pesan_pengajuan" rows="3" readonly><?= htmlspecialchars($data['pesan_pengajuan']) ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status Laporan</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($status_laporan) ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Catatan</label>
                                <textarea class="form-control" name="catatan" rows="3" placeholder="Tulis catatan..." required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tindak Lanjut</label>
                                <textarea class="form-control" name="tindak_lanjut" rows="3" placeholder="Tulis tindak lanjut..." required></textarea>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-success px-4">
                                    <i class="bi bi-send"></i> Kirim
                                </button>
                                <a href="riwayat_laporan.php" class="btn btn-secondary px-4">
                                    <i class="bi bi-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
