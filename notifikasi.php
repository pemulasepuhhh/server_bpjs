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

// Ambil data notifikasi
$sql = "
    SELECT n.id AS notif_id, n.waktu, n.status,
           c.nama_cabang,
           a.nama_aset, a.kode_aset,
           p.id AS pelaporan_id
    FROM notifikasi n
    JOIN pelaporan p ON n.pelaporan_id = p.id
    JOIN cabang c ON n.cabang_id = c.id
    JOIN aset a ON p.kode_aset = a.kode_aset
    WHERE p.status != 'dibaca'
    ORDER BY n.waktu DESC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Notifikasi Pelaporan</title>
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

        table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            border-collapse: collapse;
            width: 100%;
        }

        thead {
            background-color: #000000ff;
            color: white;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #000000ff;
            text-align: left;
        }

        tr:hover {
            background-color: #d1d2d2ff;
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
        <a href="notifikasi.php" class="active"><i class="bi bi-check-circle"></i> Notifikasi</a>
        <a href="lampiran.php"><i class="bi bi-paperclip"></i> Lampiran</a>
        <a href="riwayat_laporan.php"><i class="bi bi-book"></i> Riwayat Laporan</a>
        <a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>

    <nav class="navbar navbar-custom shadow-sm">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h5">Notifikasi Laporan</span>
            <span class="text-muted">Halo, <strong><?= htmlspecialchars($username) ?></strong></span>
        </div>
    </nav>

    <div class="main">
        <div class="container-fluid">
            <h4 class="mb-4">Daftar Notifikasi Pelaporan</h4>
            <table id="tabelNotifikasi" class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Nama Cabang</th>
                        <th>Nama Aset</th>
                        <th>Kode Aset</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1;
                    while ($row = $result->fetch_assoc()) : ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['waktu']) ?></td>
                            <td><?= htmlspecialchars($row['nama_cabang']) ?></td>
                            <td><?= htmlspecialchars($row['nama_aset']) ?></td>
                            <td><?= htmlspecialchars($row['kode_aset']) ?></td>
                            <td class="text-center">
                                <button class="btn btn-info btn-sm me-1" onclick="location.href='detail_laporan.php?id=<?= $row['notif_id'] ?>'">
                                    <i class="bi bi-info-circle"></i> Detail
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="hapusData(<?= $row['notif_id'] ?>, <?= $row['pelaporan_id'] ?>)">
                                    <i class="bi bi-trash"></i> Hapus
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function hapusData(notifId, pelaporanId) {
            if (confirm("Yakin ingin menghapus notifikasi ini?")) {
                window.location.href = 'hapus_notifikasi.php?notif_id=' + notifId + '&pelaporan_id=' + pelaporanId;
            }
        }
    </script>
</body>

</html>