<?php
session_start();

// Pastikan login sebagai Admin
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

// Ambil data pelaporan dengan file PDF
$sql = "
    SELECT 
        p.tanggal,
        p.kode_aset,
        a.nama_aset,
        c.nama_cabang,
        p.file_pdf
    FROM pelaporan p
    JOIN cabang c ON p.cabang_id = c.id
    JOIN aset a ON p.kode_aset = a.kode_aset
    WHERE p.file_pdf IS NOT NULL AND p.file_pdf != ''
    ORDER BY p.tanggal DESC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Lampiran PDF</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6f8;
            margin: 0;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
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
            background-color: #0d6efd;
        }

        .navbar-custom {
            margin-left: 250px;
            background-color: #fff;
            border-bottom: 1px solid #dee2e6;
            padding: 1rem 2rem;
        }

        .main {
            margin-left: 250px;
            padding: 2rem;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            overflow-x: auto;
            padding: 15px;
        }
    </style>
</head>

<body>
    <div class="sidebar text-white">
        <div class="logo">
            <div class="logo-icon">
                <i class="bi bi-building"></i>
            </div>
            <h4>Panel Admin</h4>
        </div>
        <a href="dashboard_admin.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="data_aset.php"><i class="bi bi-box"></i> Data Aset</a>
        <a href="log_keputusan.php"><i class="bi bi-building"></i> Log Keputusan</a>
        <a href="rencana_pengadaan.php"><i class="bi bi-journal-text"></i> Rencana Pengadaan</a>
        <a href="notifikasi.php"><i class="bi bi-check-circle"></i> Notifikasi</a>
        <a href="lampiran.php" class="active"><i class="bi bi-paperclip"></i> Lampiran</a>
        <a href="riwayat_laporan.php"><i class="bi bi-book"></i> Riwayat Laporan</a>
        <a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>

    <nav class="navbar navbar-custom shadow-sm">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h5">Lampiran PDF</span>
            <span class="text-muted">Halo, <strong><?= htmlspecialchars($username) ?></strong></span>
        </div>
    </nav>

    <div class="main">
        <div class="container-fluid">
            <h4 class="mb-4"><i class="bi bi-paperclip"></i> Lampiran PDF</h4>

            <div class="table-container">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Tanggal</th>
                            <th>Kode Aset</th>
                            <th>Nama Aset</th>
                            <th>Nama Cabang</th>
                            <th>Lampiran PDF</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0) : ?>
                            <?php while ($row = $result->fetch_assoc()) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['tanggal']) ?></td>
                                    <td><?= htmlspecialchars($row['kode_aset']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_aset']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_cabang']) ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($row['file_pdf'])) : ?>
                                            <a href="../admin/uploads/<?= htmlspecialchars($row['file_pdf']) ?>"
                                               class="btn btn-danger btn-sm"
                                               download="<?= htmlspecialchars($row['file_pdf']) ?>">
                                                <i class="bi bi-file-earmark-pdf"></i> Download PDF
                                            </a>
                                        <?php else : ?>
                                            <em>Tidak ada file</em>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada data lampiran</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>