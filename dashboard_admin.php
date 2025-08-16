<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

$username = $_SESSION['username'];

// Koneksi
$conn = new mysqli("localhost", "root", "", "tenaga_sehat");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil data awal untuk load pertama
function getAsetData($conn)
{
    $qAset = $conn->query("SELECT COUNT(*) AS total FROM aset");
    $qAsetBaik = $conn->query("SELECT COUNT(*) AS total FROM aset WHERE kondisi='baik'");
    $qAsetRusak = $conn->query("SELECT COUNT(*) AS total FROM aset WHERE kondisi='rusak'");
    $qAsetHabis = $conn->query("SELECT COUNT(*) AS total FROM aset WHERE kondisi='habis'");
    $AsetService = $conn->query("SELECT COUNT(*) AS total FROM aset WHERE kondisi='service'");

    return [
        "totalAset" => $qAset->fetch_assoc()['total'],
        "chartAset" => [
            $qAsetBaik->fetch_assoc()['total'],
            $qAsetRusak->fetch_assoc()['total'],
            $qAsetHabis->fetch_assoc()['total'],
            $AsetService->fetch_assoc()['total']
        ]
    ];
}

$dataAset = getAsetData($conn);

// Data kategori
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
        <!-- Kartu statistik -->
        <div class="row mb-4" id="card-container">
            <?php
            $cards = [
                ['label' => 'Total Aset', 'value' => $dataAset['totalAset'], 'class' => 'text-primary'],
                ['label' => 'Aset Baik', 'value' => $dataAset['chartAset'][0], 'class' => 'text-success'],
                ['label' => 'Aset Rusak', 'value' => $dataAset['chartAset'][1], 'class' => 'text-danger'],
                ['label' => 'Aset Habis', 'value' => $dataAset['chartAset'][2], 'class' => 'text-warning'],
                ['label' => 'Service', 'value' => $dataAset['chartAset'][3], 'class' => 'text-dark'],
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



        <!-- Grafik -->
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
        let asetChart = new Chart(document.getElementById('asetPieChart'), {
            type: 'pie',
            data: {
                labels: ['Baik', 'Rusak', 'Habis', 'Service'],
                datasets: [{
                    data: <?= json_encode($dataAset['chartAset']) ?>,
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

        let kategoriChart = new Chart(document.getElementById('kategoriChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($kategoriLabels) ?>,
                datasets: [{
                    label: 'Jumlah Aset',
                    data: <?= json_encode($kategoriData) ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(255, 159, 64, 0.8)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: 'rgba(255, 255, 255, 1)',
                        bodyColor: 'rgba(255, 255, 255, 1)',
                        borderColor: 'rgba(0, 0, 0, 0.1)',
                        borderWidth: 1,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: '#666'
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#666'
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                layout: {
                    padding: 10
                }
            }
        });

        // Auto refresh grafik tiap 5 detik
        setInterval(() => {
            fetch('get_aset_data.php')
                .then(res => res.json())
                .then(data => {
                    // Update kartu
                    document.getElementById('card-container').innerHTML = `
                        ${[
                            ['Total Aset', data.totalAset, 'text-primary'],
                            ['Aset Baik', data.chartAset[0], 'text-success'],
                            ['Aset Rusak', data.chartAset[1], 'text-danger'],
                            ['Aset Habis', data.chartAset[2], 'text-warning'],
                            ['Service', data.chartAset[3], 'text-dark']
                        ].map(c => `
                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-3">
                                <div class="card text-center border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <h6 class="${c[2]} fw-bold">${c[0]}</h6>
                                        <h3>${c[1]}</h3>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    `;

                    // Update chart aset
                    asetChart.data.datasets[0].data = data.chartAset;
                    asetChart.update();
                    
                    // Update chart kategori
                    kategoriChart.data.datasets[0].data = data.kategoriData;
                    kategoriChart.update();
                });
        }, 5000);
    </script>
</body>

</html>