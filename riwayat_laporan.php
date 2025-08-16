<?php
// riwayat_pelaporan.php
session_start();

// Pastikan login sebagai Admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}
$username = $_SESSION['username'];

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "tenaga_sehat";
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$sql = "SELECT * FROM riwayat_laporan ORDER BY tanggal DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Pelaporan</title>
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
        
        .btn-primary {
            background: #0d6efd;
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background: #0b5ed7;
        }
        
        .status-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: #28a745;
            border-radius: 50%;
            margin-left: 5px;
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
        <a href="lampiran.php"><i class="bi bi-paperclip"></i> Lampiran</a>
        <a href="riwayat_laporan.php" class="active"><i class="bi bi-book"></i> Riwayat Laporan</a>
        <a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>

    <nav class="navbar navbar-custom shadow-sm">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h5">Riwayat Laporan</span>
            <span class="text-muted">Halo, <strong><?= htmlspecialchars($username) ?></strong></span>
        </div>
    </nav>

    <div class="main">
        <div class="container-fluid">
            <h4 class="mb-4">📜 Riwayat Pelaporan Masuk</h4>

            <div class="table-container">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Tanggal</th>
                            <th>Cabang</th>
                            <th>Nama Aset</th>
                            <th>Kode Aset</th>
                            <th>Keterangan Kerusakan</th>
                            <th>Pesan Pengajuan</th>
                            <th>Kondisi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0) : ?>
                            <?php while ($row = $result->fetch_assoc()) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['tanggal']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_cabang']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_aset']) ?></td>
                                    <td><?= htmlspecialchars($row['kode_aset']) ?></td>
                                    <td><?= htmlspecialchars($row['keterangan_kerusakan']) ?></td>
                                    <td><?= htmlspecialchars($row['pesan_pengajuan']) ?></td>
                                    <td><?= htmlspecialchars($row['kondisi']) ?></td>
                                    <td><?= htmlspecialchars($row['status']) ?></td>
                                    <td class="text-center">
                                        <a href="form_pesan.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">Kirim Pesan</a>
                                        <?php if (!empty($row['icon']) && $row['icon'] == 1) : ?>
                                            <span class="status-dot"></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="9" class="text-center">Tidak ada data laporan</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
