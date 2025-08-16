<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Redirect jika belum login
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'cabang') {
    header('Location: /login.php');
    exit();
}

require_once '../config/koneksi.php';

// Ambil data dari session
$username = $_SESSION['username'];
$cabang_id = $_SESSION['cabang'];

// Ambil nama cabang dari database
$nama_cabang = 'Cabang';
$stmt = $conn->prepare("SELECT nama_cabang FROM cabang WHERE id = ?");
$stmt->bind_param("i", $cabang_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $nama_cabang = $row['nama_cabang'];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title><?= isset($page_title) ? $page_title : 'Dashboard Cabang' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">

    <style>
        .sidebar {
            width: 240px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #2c3e50;
            color: white;
            padding-top: 60px;
        }

        .sidebar a {
            color: white;
            padding: 10px 20px;
            display: block;
            text-decoration: none;
        }

        .sidebar a:hover {
            background-color: #34495e;
        }

        .main-content {
            margin-left: 240px;
            transition: margin-left 0.3s;
        }

        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
            }
        }

        header {
            background-color: #3498db;
            color: white;
            padding: 20px;
            text-align: center;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar d-none d-lg-block">
        <h4 class="text-center">Menu</h4>
        <a href="dashboard_cabang.php">Dashboard</a>
        <a href="data_aset.php">Data Aset</a>
        <a href="laporan.php">Laporan</a>
        <a href="profile.php">Profil</a>
        <a href="../auth/logout.php" class="text-danger">Logout</a>
    </div>

    <!-- Main content -->
    <div class="main-content">
        <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom shadow-sm mb-3 sticky-top">
            <div class="container-fluid">
                <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarCollapse">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <span class="navbar-brand fw-bold">
                    <?= isset($page_title) ? $page_title : 'Dashboard Cabang' ?>
                </span>
                <div class="d-flex align-items-center ms-auto">
                    <span class="d-none d-lg-inline me-3 text-end">
                        Selamat datang, <b><?= htmlspecialchars($username) ?></b><br>
                        <small class="text-muted">Cabang: <?= htmlspecialchars($nama_cabang) ?></small>
                    </span>
                    <div class="dropdown">
                        <a href="#" class="d-block link-dark text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle fs-4"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownUser">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i> Profil</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Konten Halaman -->
        <div class="container-fluid">
            <h2>Selamat datang di Dashboard Cabang</h2>
            <p>Anda login sebagai <strong><?= htmlspecialchars($username) ?></strong> dari cabang <strong><?= htmlspecialchars($nama_cabang) ?></strong>.</p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>