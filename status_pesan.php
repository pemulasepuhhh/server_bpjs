<?php
session_start();
require '../config/koneksi.php';

// Pastikan user login
if (!isset($_SESSION['username'])) {
    die("Akses ditolak. Silakan login.");
}
$username = $_SESSION['username'];

// Ambil cabang_id user dari tabel users
$sqlCabang = "SELECT cabang_id FROM users WHERE username = ?";
$stmtCabang = $conn->prepare($sqlCabang);
$stmtCabang->bind_param("s", $username);
$stmtCabang->execute();
$resultCabang = $stmtCabang->get_result();
if ($resultCabang->num_rows === 0) {
    die("Cabang ID user tidak ditemukan.");
}
$rowCabang = $resultCabang->fetch_assoc();
$cabang_id = $rowCabang['cabang_id'];

// Ambil data status_pesan, kecuali yang "Disetujui"
$query = "
    SELECT sp.id, sp.pelaporan_id, p.cabang_id, sp.tanggal, sp.kode_aset, sp.nama_aset,
           sp.keterangan_kerusakan, sp.pesan_pengajuan, sp.status, sp.catatan,
           sp.tindak_lanjut, sp.created_at
    FROM status_pesan sp
    JOIN pelaporan p ON sp.pelaporan_id = p.id
    WHERE p.cabang_id = ?
      AND LOWER(sp.status) <> 'disetujui'
    ORDER BY sp.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $cabang_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Data Status Laporan</title>
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

        .card {
            border-radius: 12px;
            overflow: hidden;
        }
        table {
            border-radius: 10px;
            overflow: hidden;
        }
        thead {
            background-color: #212529;
            color: white;
        }
        tbody tr:hover {
            background-color: #f1f1f1;
            transition: background-color 0.2s ease;
        }
        .badge-status {
            padding: 6px 10px;
            font-size: 0.85rem;
            border-radius: 6px;
        }
        .badge-ditolak {
            background-color: #dc3545;
            color: white;
        }
        .badge-proses {
            background-color: #6c757d;
            color: white;
        }
        .badge-lain {
            background-color: #0d6efd;
            color: white;
        }
        .hidden-col {
            display: none;
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
        <a href="up_rencana.php"><i class="bi bi-clock-history"></i> <span>Riwayat Laporan</span></a>
        <a href="feedback_admin.php"><i class="bi bi-clipboard-check"></i> <span>Keputusan</span></a>
        <a href="status_pesan.php" class="active"><i class="bi bi-envelope"></i> <span>Pesan</span></a>
        <a href="../logout.php"><i class="bi bi-box-arrow-right"></i> <span>Logout</span></a>
    </div>

    <nav class="navbar navbar-custom shadow-sm">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h5">
                <i class="bi bi-list-check"></i> Daftar Status Laporan - Cabang <?= htmlspecialchars($cabang_id) ?>
            </span>
            <span class="text-muted">Halo, <strong><?= htmlspecialchars($username) ?></strong></span>
        </div>
    </nav>

    <div class="main">
        <div class="container-fluid">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="hidden-col">ID</th>
                                    <th class="hidden-col">Pelaporan ID</th>
                                    <th class="hidden-col">Cabang ID</th>
                                    <th>Tanggal</th>
                                    <th>Kode Aset</th>
                                    <th>Nama Aset</th>
                                    <th>Keterangan Kerusakan</th>
                                    <th>Pesan Pengajuan</th>
                                    <th>Status</th>
                                    <th>Catatan</th>
                                    <th>Tindak Lanjut</th>
                                    <th>Tanggal Dibuat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0) : ?>
                                    <?php while ($row = $result->fetch_assoc()) : ?>
                                        <tr>
                                            <td class="hidden-col"><?= htmlspecialchars($row['id']) ?></td>
                                            <td class="hidden-col"><?= htmlspecialchars($row['pelaporan_id']) ?></td>
                                            <td class="hidden-col"><?= htmlspecialchars($row['cabang_id']) ?></td>
                                            <td><?= htmlspecialchars($row['tanggal']) ?></td>
                                            <td><?= htmlspecialchars($row['kode_aset']) ?></td>
                                            <td><?= htmlspecialchars($row['nama_aset']) ?></td>
                                            <td><?= htmlspecialchars($row['keterangan_kerusakan']) ?></td>
                                            <td><?= htmlspecialchars($row['pesan_pengajuan']) ?></td>
                                            <td>
                                                <?php
                                                $status = strtolower($row['status']);
                                                if ($status === 'ditolak') {
                                                    echo '<span class="badge-status badge-ditolak">Ditolak</span>';
                                                } elseif ($status === 'proses') {
                                                    echo '<span class="badge-status badge-proses">Proses</span>';
                                                } else {
                                                    echo '<span class="badge-status badge-lain">' . htmlspecialchars($row['status']) . '</span>';
                                                }
                                                ?>
                                            </td>
                                            <td><?= htmlspecialchars($row['catatan']) ?></td>
                                            <td><?= htmlspecialchars($row['tindak_lanjut']) ?></td>
                                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="12" class="text-center">Tidak ada data</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
