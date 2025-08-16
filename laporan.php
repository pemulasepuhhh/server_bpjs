<?php
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ====== CEK LOGIN ======
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'User') {
    header("Location: ../login.php");
    exit;
}

// ====== KONEKSI DB ======
$conn = new mysqli("localhost", "root", "", "tenaga_sehat");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

$cabang_id = isset($_SESSION['cabang']) ? (int) $_SESSION['cabang'] : 0;
$username  = $_SESSION['username'] ?? '';

// ====== AMBIL ASET KONDISI BAIK ======
$asets_stmt = $conn->prepare("
    SELECT kode_aset, nama_aset 
    FROM aset 
    WHERE cabang_id = ? AND kondisi = 'baik'
");
$asets_stmt->bind_param("i", $cabang_id);
$asets_stmt->execute();
$asets = $asets_stmt->get_result();
$asets_stmt->close();

$pesan_sukses = "";

// ====== PROSES SUBMIT ======
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $kode_aset       = $_POST["kode_aset"] ?? '';
    $tanggal         = $_POST["tanggal"] ?? date('Y-m-d');
    $keterangan      = $_POST["keterangan"] ?? '';
    $pesan_pengajuan = $_POST["pesan_pengajuan"] ?? '';
    $status_baru     = $_POST["kondisi"] ?? '';
    $file_pdf_name   = null; // default NULL

    // ====== PROSES UPLOAD PDF ======
    if (!empty($_FILES["file_pdf"]["name"]) && $_FILES["file_pdf"]["error"] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES["file_pdf"]["name"], PATHINFO_EXTENSION));
        if ($ext === "pdf") {
            $upload_dir = __DIR__ . "/../admin/uploads/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_pdf_name = time() . "_" . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $_FILES["file_pdf"]["name"]);
            move_uploaded_file($_FILES["file_pdf"]["tmp_name"], $upload_dir . $file_pdf_name);
        } else {
            $pesan_sukses = "❌ File harus format PDF!";
        }
    }

    if (empty($pesan_sukses)) {
        try {
            $conn->begin_transaction();

            // Cek laporan duplikat
            $stmtCek = $conn->prepare("
                SELECT id FROM pelaporan 
                WHERE kode_aset = ? AND tanggal = ? AND cabang_id = ? AND keterangan_kerusakan = ?
                LIMIT 1
            ");
            $stmtCek->bind_param("ssis", $kode_aset, $tanggal, $cabang_id, $keterangan);
            $stmtCek->execute();
            $stmtCek->store_result();

            if ($stmtCek->num_rows > 0) {
                $stmtCek->close();
                $conn->rollback();
                $pesan_sukses = "⚠️ Laporan ini sudah pernah dibuat!";
            } else {
                $stmtCek->close();

                // Insert laporan
                $status_laporan = 'belum_dibaca';
                $stmt = $conn->prepare("
                    INSERT INTO pelaporan (kode_aset, tanggal, keterangan_kerusakan, pesan_pengajuan, cabang_id, status, file_pdf) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("ssssiss", $kode_aset, $tanggal, $keterangan, $pesan_pengajuan, $cabang_id, $status_laporan, $file_pdf_name);
                $stmt->execute();
                $id_laporan = (int)$conn->insert_id;
                $stmt->close();

                $id_laporan = (int)$conn->insert_id;

                //testing
                $stmtNotif = $conn->prepare("
                    INSERT INTO notifikasi (isi_pesan, waktu, status, pelaporan_id, nama_cabang, cabang_id)
                    SELECT 
                        CONCAT('📩 Laporan baru dari Cabang ', c.nama_cabang, 
                            ' terkait aset ', p.kode_aset, 
                            ' kerusakan ', p.keterangan_kerusakan,
                            ' | Lampiran: ', IFNULL(p.file_pdf, 'Tanpa Lampiran')) AS isi_pesan,
                        NOW() AS waktu,
                        'belum_dibaca' AS status,
                        p.id AS pelaporan_id,
                        c.nama_cabang,
                        p.cabang_id
                    FROM pelaporan p
                    JOIN cabang c ON p.cabang_id = c.id
                    WHERE p.id = ?
                    ON DUPLICATE KEY UPDATE waktu = NOW(), status = 'belum_dibaca'
                ");
                $stmtNotif->bind_param("i", $id_laporan);
                $stmtNotif->execute();
                $stmtNotif->close();

                // Ambil nama cabang
                $stmtCabang = $conn->prepare("SELECT nama_cabang FROM cabang WHERE id = ?");
                $stmtCabang->bind_param("i", $cabang_id);
                $stmtCabang->execute();
                $stmtCabang->bind_result($nama_cabang);
                $stmtCabang->fetch();
                $stmtCabang->close();
                $nama_cabang = $nama_cabang ?? '';

                // Pesan notif hanya judul PDF (tanpa file asli)
                //$judul_pdf = $file_pdf_name ? pathinfo($file_pdf_name, PATHINFO_FILENAME) : 'Tanpa Lampiran';
                //$pesan_notif  = "📩 Laporan baru dari Cabang <b>$nama_cabang</b> terkait aset <b>$kode_aset</b> kerusakan <b>$keterangan</b> | Lampiran: $judul_pdf";
                //$waktu        = date("Y-m-d H:i:s");
                //$status_notif = 'belum_dibaca';

                // Simpan notifikasi (hanya judul PDF di teks, tidak simpan file asli di sini)
                //$stmtNotif = $conn->prepare("
                  //  INSERT INTO notifikasi (isi_pesan, waktu, status, pelaporan_id, nama_cabang, cabang_id) 
                   // VALUES (?, ?, ?, ?, ?, ?)
                //");
                //$stmtNotif->bind_param("sssisi", $pesan_notif, $waktu, $status_notif, $id_laporan, $nama_cabang, $cabang_id);
                //$stmtNotif->execute();
                //$stmtNotif->close();

                // Update kondisi aset
                $stmtUpdate = $conn->prepare("UPDATE aset SET kondisi = ? WHERE kode_aset = ?");
                $stmtUpdate->bind_param("ss", $status_baru, $kode_aset);
                $stmtUpdate->execute();
                $stmtUpdate->close();

                $conn->commit();
                $pesan_sukses = "✅ Laporan berhasil dikirim!";
            }
        } catch (mysqli_sql_exception $e) {
            if ($conn->in_transaction) $conn->rollback();
            $pesan_sukses = "❌ Gagal mengirim laporan: " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Form Laporan Kerusakan Aset</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
body {
    background: #f4f6f8;
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
}

.sidebar {
    position: fixed;
    width: 250px;
    height: 100vh;
    background-color: #212529;
    padding-top: 1rem;
    box-shadow: 3px 0 10px rgba(0, 0, 0, 0.2);
}

.sidebar .logo {
    text-align: center;
    padding: 1rem 0;
    margin-bottom: 1rem;
    border-bottom: 1px solid #444;
}

.sidebar .logo h4 {
    font-weight: 700;
    color: #fff;
    margin: 0;
}

.sidebar .logo .logo-icon {
    font-size: 2rem;
    color: #6861ce;
    margin-bottom: 0.5rem;
}

.sidebar a {
    display: block;
    padding: 12px 20px;
    color: #fff;
    text-decoration: none;
    transition: all 0.3s ease;
}

.sidebar a:hover {
    background-color: #343a40;
    padding-left: 25px;
}

.sidebar a.active {
    background-color: #343a40;
    border-left: 4px solid #6861ce;
}

.main {
    margin-left: 250px;
    padding: 2rem;
}

.navbar-custom {
    margin-left: 250px;
    background-color: #fff;
    border-bottom: 1px solid #dee2e6;
    padding: 1rem 2rem;
}

.container {
    max-width: 800px;
    margin: 30px auto;
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.4);
}
label {
    display: block;
    margin-top: 15px;
    font-weight: 600;
    color: #333;
}
input, select, textarea {
    width: 100%;
    padding: 10px;
    margin-top: 6px;
    border-radius: 8px;
    border: 1px solid #ccc;
    box-sizing: border-box;
}
button {
    margin-top: 25px;
    padding: 12px 25px;
    background-color: #000;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
}
button:hover {
    background-color: #333;
}
.alert {
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.alert.success { background: #d4edda; color: #155724; }
.alert.error { background: #f8d7da; color: #721c24; }

/* Responsif untuk layar kecil */
@media (max-width: 768px) {
    .sidebar {
        width: 70px;
        overflow: hidden;
    }

    .sidebar .logo h4,
    .sidebar a span {
        display: none;
    }

    .sidebar .logo .logo-icon {
        font-size: 1.5rem;
        margin-bottom: 0;
    }

    .sidebar a i {
        margin-right: 0;
        font-size: 1.2rem;
    }

    .main {
        margin-left: 70px;
    }

    .navbar-custom {
        margin-left: 70px;
    }
}
</style>
</head>
<body>
<div class="sidebar text-white">
    <div class="logo">
        <div class="logo-icon">
            <i class="bi bi-building"></i>
        </div>
        <h4>Panel Cabang</h4>
    </div>
    <a href="dashboard_cabang.php"><i class="bi bi-speedometer2"></i> <span>Dashboard</span></a>
    <a href="aset_saya.php"><i class="bi bi-box"></i> <span>Aset Saya</span></a>
    <a href="laporan.php" class="active"><i class="bi bi-file-earmark-text"></i> <span>Buat Laporan</span></a>
    <a href="riwayat_aset.php"><i class="bi bi-clock-history"></i> <span>Riwayat Aset</span></a>
    <a href="up_rencana.php"><i class="bi bi-clock-history"></i> <span>Riwayat Laporan</span></a>
    <a href="feedback_admin.php"><i class="bi bi-clipboard-check"></i> <span>Keputusan</span></a>
    <a href="status_pesan.php"><i class="bi bi-envelope"></i> <span>Pesan</span></a>
    <a href="../logout.php"><i class="bi bi-box-arrow-right"></i> <span>Logout</span></a>
</div>

<nav class="navbar navbar-custom shadow-sm">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h5">Selamat Datang Di Aplikasi Pemeliharaan</span>
        <span class="text-muted">Halo, <strong><?= htmlspecialchars($username) ?></strong></span>
    </div>
</nav>

<div class="main">
    <div class="container-fluid">
        <div class="container">
            <?php if (!empty($pesan_sukses)) : ?>
                <div class="alert <?= strpos($pesan_sukses, 'Gagal') !== false || strpos($pesan_sukses, '❌') !== false ? 'error' : 'success' ?>">
                    <?= $pesan_sukses ?>
                </div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <label for="tanggal">Tanggal</label>
                <input type="date" name="tanggal" required>

        <label for="kode_aset">Pilih Aset</label>
        <select name="kode_aset" required>
            <option value="">-- Pilih Aset Kondisi Baik --</option>
            <?php while ($a = $asets->fetch_assoc()) : ?>
                <option value="<?= $a['kode_aset'] ?>">
                    <?= htmlspecialchars($a['nama_aset']) ?> (<?= $a['kode_aset'] ?>)
                </option>
            <?php endwhile; ?>
        </select>

        <label for="keterangan">Keterangan Kerusakan</label>
        <textarea name="keterangan" placeholder="Jelaskan kerusakan aset..." required></textarea>

        <label for="pesan_pengajuan">Pesan Pengajuan</label>
        <textarea name="pesan_pengajuan" placeholder="Pesan Pengajuan..." required></textarea>

        <label for="kondisi">Kondisi Aset</label>
        <select name="kondisi" required>
            <option value="">-- Pilih Kondisi --</option>
            <option value="rusak">Rusak</option>
            <option value="habis">Habis</option>
            <option value="service">Service</option>
        </select>

        <label for="file_pdf">Lampirkan PDF</label>
        <input type="file" name="file_pdf" accept="application/pdf">

        <button type="submit">Kirim Laporan</button>
    </form>
</div>
    </div>
</div>
</body>
</html>
