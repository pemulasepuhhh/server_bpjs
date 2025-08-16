<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'User') {
    header("Location: ../login.php");
    exit;
}

$cabangId = $_SESSION['cabang'] ?? 0;
if (!$cabangId) {
    die("Cabang tidak ditemukan");
}

$username = $_SESSION['username'];

// Koneksi
$conn = new mysqli("localhost", "root", "", "tenaga_sehat");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$sql = "SELECT aset.*, kategori_aset.kategori 
        FROM aset
        LEFT JOIN kategori_aset ON aset.id_kategori = kategori_aset.id
        WHERE aset.cabang_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $cabangId);
$stmt->execute();
$result = $stmt->get_result();
$asetList = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

function getBadgeColor($kondisi)
{
    switch (strtolower($kondisi)) {
        case 'baik':
            return 'success'; // hijau
        case 'rusak':
            return 'danger'; // merah
        case 'habis':
            return 'warning'; // kuning
        case 'service':
            return 'primary'; // biru
        default:
            return 'secondary'; // abu-abu
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Aset Cabang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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

        .table thead {
            background: linear-gradient(to right, #007bff, #00bcd4);
            color: white;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
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
    <a href="aset_saya.php" class="active"><i class="bi bi-box"></i> <span>Aset Saya</span></a>
    <a href="laporan.php"><i class="bi bi-file-earmark-text"></i> <span>Buat Laporan</span></a>
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
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div class="d-flex align-items-center flex-wrap gap-2">
            <h4 class="mb-0">Data Aset</h4>
        </div>
        <a href="tambah_aset.php" class="btn btn-success">+ Tambah Aset</a>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body">
            <div class="table-responsive">
                <table id="asetTable" class="table table-striped table-hover align-middle rounded-3 overflow-hidden">
                    <thead>
                        <tr>
                            <th>Kode Aset</th>
                            <th>Nama Aset</th>
                            <th>Kategori</th>
                            <th>Deskripsi</th>
                            <th>Kondisi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($asetList as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['kode_aset']) ?></td>
                                <td><?= htmlspecialchars($row['nama_aset']) ?></td>
                                <td><?= htmlspecialchars($row['kategori']) ?></td>
                                <td><?= htmlspecialchars($row['deskripsi']) ?></td>
                                <td>
                                    <span class="badge bg-<?= getBadgeColor($row['kondisi']) ?>">
                                        <?= htmlspecialchars($row['kondisi']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    var table = $('#asetTable').DataTable({
        responsive: true
    });

    // Filter berdasarkan cabang
    $('.dropdown-item').click(function(e) {
        e.preventDefault();
-progress44 |         var cabang = $(this).data('cabang');
        if (cabang === '') {
            // Jika memilih "Semua Cabang", tampilkan semua data
            table.search('').columns().search('').draw();
        } else {
            // Filter berdasarkan nilai cabang yang dipilih
            table.column(2).search(cabang).draw();
        }
    });
});
</script>

</body>
</html>
