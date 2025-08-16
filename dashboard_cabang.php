<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'User') {
    header("Location: ../login.php");
    exit;
}

$cabangId = $_SESSION['cabang'];
$username = $_SESSION['username'];

// Koneksi
$conn = new mysqli("localhost", "root", "", "tenaga_sehat");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);                          
}

// Data aset
$qTotal = $conn->query("SELECT COUNT(*) AS total FROM aset WHERE cabang_id = $cabangId");
$qBaik  = $conn->query("SELECT COUNT(*) AS total FROM aset WHERE kondisi = 'baik' AND cabang_id = $cabangId");
$qRusak = $conn->query("SELECT COUNT(*) AS total FROM aset WHERE kondisi = 'rusak' AND cabang_id = $cabangId");
$qHabis = $conn->query("SELECT COUNT(*) AS total FROM aset WHERE kondisi = 'habis' AND cabang_id = $cabangId");
$qService = $conn->query("SELECT COUNT(*) AS total FROM aset WHERE kondisi = 'service' AND cabang_id = $cabangId");

$totalAset = $qTotal->fetch_assoc()['total'];
$chartAset = [
    $qBaik->fetch_assoc()['total'],
    $qRusak->fetch_assoc()['total'],
    $qHabis->fetch_assoc()['total'],
    $qService->fetch_assoc()['total']
];

// Data kategori (ganti sesuai data yang dimiliki)
$kategoriLabels = ['IT', 'Elektronik', 'Peralatan Kantor', 'Furniture', 'Transportasi', 'Peralatan Medis'];
$kategoriData = [];

foreach ($kategoriLabels as $kategori) {
    $result = $conn->query("SELECT COUNT(*) AS total FROM kategori_aset WHERE kategori='$kategori'");
    $kategoriData[] = $result->fetch_assoc()['total'];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Memasukkan CSS aplikasi untuk konsistensi tema -->
    <link rel="stylesheet" href="../assets/css/app.css">
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

        canvas {
            max-height: 240px !important;
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
        <a href="dashboard_cabang.php" class="active"><i class="bi bi-speedometer2"></i> <span>Dashboard</span></a>
        <a href="aset_saya.php"><i class="bi bi-box"></i> <span>Aset Saya</span></a>
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
        <div class="row g-3 mb-4">
            <?php
            $cards = [
                ['label' => 'Total Aset', 'value' => $totalAset, 'class' => 'text-primary'],
                ['label' => 'Aset Baik', 'value' => $chartAset[0], 'class' => 'text-success'],
                ['label' => 'Aset Rusak', 'value' => $chartAset[1], 'class' => 'text-danger'],
                ['label' => 'Aset Habis', 'value' => $chartAset[2], 'class' => 'text-warning'],
                ['label' => 'Aset Service', 'value' => $chartAset[3], 'class' => 'text-dark'],
            ];
            foreach ($cards as $card) : ?>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-3">
                    <div class="card text-center border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="<?= $card['class'] ?> fw-bold"><?= $card['label'] ?></h6>
                            <h3><?= $card['value'] ?></h3>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill"></i> Grafik Kondisi Aset</h6>
                        <canvas id="asetPieChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3"><i class="bi bi-bar-chart-fill"></i> Grafik Kategori Aset</h6>
                        <canvas id="kategoriChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        new Chart(document.getElementById('asetPieChart'), {
            type: 'pie',
            data: {
                labels: ['Baik', 'Rusak', 'Habis', 'Service'],
                datasets: [{
                    data: <?= json_encode($chartAset) ?>,
                    backgroundColor: ['#28a745', '#dc3545', '#ffc107', '#9f029cff'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        new Chart(document.getElementById('kategoriChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($kategoriLabels) ?>,
                datasets: [{
                    label: 'Jumlah Aset',
                    data: <?= json_encode($kategoriData) ?>,
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56',
                        '#4BC0C0', '#9966FF', '#FF9F40'
                    ],
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>