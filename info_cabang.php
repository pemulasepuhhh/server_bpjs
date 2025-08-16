<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

// Koneksi database
$conn = new mysqli("localhost", "root", "", "tenaga_sehat");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Pastikan ada parameter id
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID cabang tidak valid.");
}

$cabang_id = intval($_GET['id']);

// Ambil data cabang
$sql = "SELECT nama_cabang, alamat, kabupaten, kelurahan, telpon_cabang, email 
        FROM cabang 
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $cabang_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Data cabang tidak ditemukan.");
}

$data = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Info Cabang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>

<body class="bg-light">

    <div class="container py-5">
        <div class="card shadow border-0">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0"><i class="bi bi-info-circle"></i> Informasi Cabang</h4>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th>Nama Cabang</th>
                        <td><?= htmlspecialchars($data['nama_cabang']); ?></td>
                    </tr>
                    <tr>
                        <th>Alamat</th>
                        <td><?= htmlspecialchars($data['alamat']); ?></td>
                    </tr>
                    <tr>
                        <th>Kabupaten</th>
                        <td><?= htmlspecialchars($data['kabupaten']); ?></td>
                    </tr>
                    <tr>
                        <th>Kelurahan</th>
                        <td><?= htmlspecialchars($data['kelurahan']); ?></td>
                    </tr>
                    <tr>
                        <th>Telepon Cabang</th>
                        <td><?= htmlspecialchars($data['telpon_cabang']); ?></td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td><?= htmlspecialchars($data['email']); ?></td>
                    </tr>
                </table>
                <a href="rencana_pengadaan.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>