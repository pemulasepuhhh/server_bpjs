<?php
session_start();

// Pastikan login
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

// Koneksi database
$conn = new mysqli("localhost", "root", "", "tenaga_sehat");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// === PROSES PINDAHKAN DATA KE RIWAYAT_ASET DAN UPDATE STATUS ===
if (isset($_GET['selesai'])) {
    $id = intval($_GET['selesai']);

    // Ambil data dari status_barang
    $sqlGet = "SELECT * FROM status_barang WHERE id = ?";
    $stmt = $conn->prepare($sqlGet);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($data) {
        // Insert ke riwayat_aset
        $sqlInsert = "INSERT INTO riwayat_aset 
            (pelaporan_id, tanggal, kode_aset, nama_aset, keputusan, catatan, kondisi, status, cabang_id, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt2 = $conn->prepare($sqlInsert);
        $stmt2->bind_param(
            "isssssssis",
            $data['pelaporan_id'],
            $data['tanggal'],
            $data['kode_aset'],
            $data['nama_aset'],
            $data['keputusan'],
            $data['catatan'],
            $data['kondisi'],
            $data['status'],
            $data['cabang_id'],
            $data['created_at']
        );
        $stmt2->execute();
        $stmt2->close();

        // Update status di tabel status_barang jadi "Selesai"
        $sqlUpdate = "UPDATE status_barang SET status = 'Selesai' WHERE id = ?";
        $stmt3 = $conn->prepare($sqlUpdate);
        $stmt3->bind_param("i", $id);
        $stmt3->execute();
        $stmt3->close();

        header("Location: feedback_admin.php");
        exit;
    }
}

// Ambil hanya data dengan status Dikirim atau Pemeriksaan ke lokasi
$sql = "SELECT * FROM status_barang 
        WHERE status IN ('Dikirim', 'Pemeriksaan ke lokasi') 
        ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Admin - Status Barang</title>
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

        .table-responsive {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .table th {
            background-color: #0d6efd;
            color: white;
        }

        .card {
            border-radius: 0.75rem;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(120deg, #000000ff, #000000ff);
            color: white;
            padding: 1.5rem;
        }

        .status-badge {
            font-size: 0.85em;
            padding: 0.5em 0.75em;
        }

        .action-btn {
            transition: all 0.2s ease;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.05);
        }

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

            .table-responsive {
                font-size: 0.85rem;
            }

            .btn-action {
                padding: 0.25rem 0.5rem;
                font-size: 0.8rem;
            }

            .card-header {
                padding: 1rem;
            }
        }

        @media (max-width: 576px) {
            .table-responsive {
                font-size: 0.75rem;
            }

            .status-badge {
                font-size: 0.7em;
                padding: 0.25em 0.5em;
            }

            .action-btn span {
                display: none;
            }

            .action-btn i {
                margin-right: 0;
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
        <a href="feedback_admin.php" class="active"><i class="bi bi-clipboard-check"></i> <span>Keputusan</span></a>
        <a href="status_pesan.php"><i class="bi bi-envelope"></i> <span>Pesan</span></a>
        <a href="../logout.php"><i class="bi bi-box-arrow-right"></i> <span>Logout</span></a>
    </div>

    <nav class="navbar navbar-custom shadow-sm">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h5">Selamat Datang Di Aplikasi Pemeliharaan</span>
            <span class="text-muted">Halo, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
        </div>
    </nav>

    <div class="main">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                <h2 class="text-dark fw-bold mb-3 mb-md-0">
                    <i class="bi bi-clipboard-data me-2"></i>Feedback Admin
                </h2>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="javascript:history.back()" class="btn btn-outline-dark">
                        <i class="bi bi-arrow-left"></i> <span class="d-none d-sm-inline">Back</span>
                    </a>
                    <a href="riwayat_aset.php" class="btn btn-outline-dark">
                        <i class="bi bi-clock-history"></i> <span class="d-none d-sm-inline">Riwayat Aset</span>
                    </a>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="bi bi-info-circle"></i> Status Barang
                    </h4>
                    <p class="mb-0 opacity-75">Daftar semua feedback status barang yang perlu ditindaklanjuti</p>
                </div>
                <div class="card-body">
                    <?php if ($result && $result->num_rows > 0) : ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover align-middle">
                                <thead class="table-dark text-center">
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Kode Aset</th>
                                        <th>Nama Aset</th>
                                        <th>Keputusan</th>
                                        <th>Catatan</th>
                                        <th>Kondisi</th>
                                        <th>Status</th>
                                        <th>Cabang ID</th>
                                        <th>Dibuat Pada</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1;
                                    while ($row = $result->fetch_assoc()) : ?>
                                        <tr>
                                            <td class="text-center"><?= $no++; ?></td>
                                            <td><?= htmlspecialchars($row['tanggal']); ?></td>
                                            <td><?= htmlspecialchars($row['kode_aset']); ?></td>
                                            <td><?= htmlspecialchars($row['nama_aset']); ?></td>
                                            <td><?= htmlspecialchars($row['keputusan']); ?></td>
                                            <td><?= htmlspecialchars($row['catatan']); ?></td>
                                            <td><?= htmlspecialchars($row['kondisi']); ?></td>
                                            <td class="text-center">
                                                <?php if (strtolower($row['status']) == 'dikirim') : ?>
                                                    <span class="badge bg-primary status-badge">Dikirim</span>
                                                <?php elseif (strtolower($row['status']) == 'pemeriksaan ke lokasi') : ?>
                                                    <span class="badge bg-warning text-dark status-badge">Pemeriksaan ke lokasi</span>
                                                <?php else : ?>
                                                    <span class="badge bg-secondary status-badge"><?= htmlspecialchars($row['status']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center"><?= htmlspecialchars($row['cabang_id']); ?></td>
                                            <td><?= htmlspecialchars($row['created_at']); ?></td>
                                            <td class="text-center">
                                                <a href="?selesai=<?= $row['id']; ?>" class="btn btn-success btn-sm action-btn" onclick="return confirm('Pindahkan ke Riwayat Aset dan tandai selesai?')">
                                                    <i class="bi bi-check-circle"></i> <span class="d-none d-md-inline">Selesai</span>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <div class="alert alert-warning text-center py-4">
                            <i class="bi bi-exclamation-circle me-2"></i>
                            Tidak ada data dengan status Dikirim atau Pemeriksaan ke lokasi.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>