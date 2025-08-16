<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "tenaga_sehat");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$id = $_GET['id'] ?? 0;

// Ambil data aset
$sql = "SELECT * FROM aset WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$aset = $result->fetch_assoc();

if (!$aset) {
    die("Aset tidak ditemukan");
}

// Update data jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama_aset'];
    $deskripsi = $_POST['deskripsi'];
    $kondisi = $_POST['kondisi'];

    $update = $conn->prepare("UPDATE aset SET nama_aset=?, deskripsi=?, kondisi=? WHERE id=?");
    $update->bind_param("sssi", $nama, $deskripsi, $kondisi, $id);

    if ($update->execute()) {
        header("Location: data_aset.php?status=success");
        exit;
    } else {
        echo "<script>alert('Gagal memperbarui data aset');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Edit Aset</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>

<body class="bg-light">

    <?php if (isset($_GET['status']) && $_GET['status'] === 'success') : ?>
        <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999;" role="alert">
            ✅ Data aset berhasil diperbarui.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>


    <div class="container py-4">
        <h3 class="mb-4">Edit Aset</h3>
        <form method="POST">
            <div class="mb-3">
                <label>Nama Aset</label>
                <input type="text" name="nama_aset" value="<?= htmlspecialchars($aset['nama_aset']) ?>" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Deskripsi</label>
                <textarea name="deskripsi" class="form-control" required><?= htmlspecialchars($aset['deskripsi']) ?></textarea>
            </div>
            <div class="mb-3">
                <label>Kondisi</label>
                <select name="kondisi" class="form-select" required>
                    <option value="baik" <?= $aset['kondisi'] == 'baik' ? 'selected' : '' ?>>Baik</option>
                    <option value="rusak" <?= $aset['kondisi'] == 'rusak' ? 'selected' : '' ?>>Rusak</option>
                    <option value="perlu perbaikan" <?= $aset['kondisi'] == 'perlu perbaikan' ? 'selected' : '' ?>>Perlu Perbaikan</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="data_aset.php" class="btn btn-secondary"><i class="bi bi-arrow-left-circle"></i> Kembali</a>
        </form>
    </div>
</body>

</html>