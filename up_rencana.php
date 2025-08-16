<?php
session_start();

// Cek login dan role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'User') {
    header("Location: ../login.php");
    exit;
}

// Ambil data dari session
$cabang_id = $_SESSION['cabang'] ?? 0;
$username  = $_SESSION['username'] ?? '';

if (!$cabang_id) {
    die("Cabang tidak ditemukan");
}

// Koneksi database
$conn = new mysqli("localhost", "root", "", "tenaga_sehat");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil data sesuai cabang_id atau username, dan hanya status Disetujui
$sql = "SELECT * 
        FROM up_laporan 
        WHERE (cabang_id = '$cabang_id' OR nama_cabang = '$username')
          AND status = 'Disetujui'
        ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Data Laporan Cabang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
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
        <a href="laporan.php"><i class="bi bi-file-earmark-text"></i> <span>Buat Laporan</span></a>
        <a href="riwayat_aset.php"><i class="bi bi-clock-history"></i> <span>Riwayat Aset</span></a>
        <a href="up_rencana.php" class="active"><i class="bi bi-clock-history"></i> <span>Riwayat Laporan</span></a>
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
            <div class="card shadow border-0">
                <div class="card-header bg-dark text-white">
                    <h4 class="mb-0"><i class="bi bi-table"></i> Data Laporan Cabang (Disetujui)</h4>
                </div>
                <div class="card-body">
                    <?php if ($result && $result->num_rows > 0) : ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover align-middle">
                                <thead class="table-dark text-center">
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Nama Cabang</th>
                                        <th>Kode Aset</th>
                                        <th>Nama Aset</th>
                                        <th>Keterangan Kerusakan</th>
                                        <th>Pesan Pengajuan</th>
                                        <th>Catatan</th>
                                        <th>Tindak Lanjut</th>
                                        <th>Status</th>
                                        <th>Dikirim</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1;
                                    while ($row = $result->fetch_assoc()) : ?>
                                        <tr>
                                            <td class="text-center"><?= $no++; ?></td>
                                            <td><?= htmlspecialchars($row['tanggal']); ?></td>
                                            <td><?= htmlspecialchars($row['nama_cabang']); ?></td>
                                            <td><?= htmlspecialchars($row['kode_aset']); ?></td>
                                            <td><?= htmlspecialchars($row['nama_aset']); ?></td>
                                            <td><?= htmlspecialchars($row['keterangan_kerusakan']); ?></td>
                                            <td><?= htmlspecialchars($row['pesan_pengajuan']); ?></td>
                                            <td><?= htmlspecialchars($row['catatan']); ?></td>
                                            <td><?= htmlspecialchars($row['tindak_lanjut']); ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-success">Disetujui</span>
                                            </td>
                                            <td><?= htmlspecialchars($row['created_at']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <div class="alert alert-warning">Belum ada data laporan Disetujui.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>

</html>