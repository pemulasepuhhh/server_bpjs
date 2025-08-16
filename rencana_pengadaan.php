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
// Ambil data rencana_pengadaan hanya dengan status Disetujui atau Diproses
$sql = "SELECT id, pelaporan_id, cabang_id, nama_cabang, tanggal, kode_aset, nama_aset,
               keterangan_kerusakan, pesan_pengajuan, status, catatan, tindak_lanjut
        FROM rencana_pengadaan
        WHERE status IN ('Disetujui', 'Diproses')
        ORDER BY id DESC";
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Data Rencana Pengadaan</title>
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
            background-color: #0d6efd;
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
        <a href="rencana_pengadaan.php" class="active"><i class="bi bi-journal-text"></i> Rencana Pengadaan</a>
        <a href="notifikasi.php"><i class="bi bi-check-circle"></i> Notifikasi</a>
        <a href="lampiran.php"><i class="bi bi-paperclip"></i> Lampiran</a>
        <a href="riwayat_laporan.php"><i class="bi bi-book"></i> Riwayat Laporan</a>
        <a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
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
                    <h4 class="mb-0"><i class="bi bi-box-seam"></i> Data Rencana Pengadaan</h4>
                </div>
                <div class="card-body">
                    <?php if ($result->num_rows > 0) : ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-primary text-center">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Cabang</th>
                                        <th>Tanggal</th>
                                        <th>Nama Aset</th>
                                        <th>Kode Aset</th>
                                        <th>Keterangan Kerusakan</th>
                                        <th>Pesan Pengajuan</th>
                                        <th>Status</th>
                                        <th>Catatan</th>
                                        <th>Tindak Lanjut</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    while ($row = $result->fetch_assoc()) :
                                    ?>
                                        <tr>
                                            <td class="text-center"><?= $no++; ?></td>
                                            <td><?= htmlspecialchars($row['nama_cabang']); ?></td>
                                            <td><?= htmlspecialchars($row['tanggal']); ?></td>
                                            <td><?= htmlspecialchars($row['nama_aset']); ?></td>
                                            <td><?= htmlspecialchars($row['kode_aset']); ?></td>
                                            <td><?= htmlspecialchars($row['keterangan_kerusakan']); ?></td>
                                            <td><?= htmlspecialchars($row['pesan_pengajuan']); ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-info text-dark"><?= htmlspecialchars($row['status']); ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($row['catatan']); ?></td>
                                            <td><?= htmlspecialchars($row['tindak_lanjut']); ?></td>
                                            <td class="text-center">
                                                <a href="info_cabang.php?id=<?= $row['cabang_id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="bi bi-info-circle"></i>
                                                </a>
                                                <a href="update_rencana.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <div class="alert alert-warning text-center">Belum ada data rencana pengadaan.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>