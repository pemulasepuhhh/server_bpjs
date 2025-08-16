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

// Ambil ID dari URL
if (!isset($_GET['id'])) {
    die("ID tidak ditemukan.");
}
$id = intval($_GET['id']);

// Ambil data rencana_pengadaan
$sql = "SELECT * FROM rencana_pengadaan WHERE id = $id";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    die("Data tidak ditemukan.");
}
$data = $result->fetch_assoc();

// Ambil kondisi aset berdasarkan kode_aset
$kondisi_aset = '';
$sql_aset = "SELECT kondisi FROM aset WHERE kode_aset = '" . $conn->real_escape_string($data['kode_aset']) . "' LIMIT 1";
$res_aset = $conn->query($sql_aset);
if ($res_aset->num_rows > 0) {
    $row_aset = $res_aset->fetch_assoc();
    $kondisi_aset = $row_aset['kondisi'];
}

// Proses form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status   = $_POST['status'];
    $catatan  = $_POST['catatan'];
    $keputusan = $_POST['keputusan']; // input manual

    // Insert ke log_keputusan
    $stmt = $conn->prepare("INSERT INTO log_keputusan 
        (pelaporan_id, nama_aset, kode_aset, keterangan_kerusakan, keputusan, kondisi, cabang_id, tanggal_keputusan, catatan, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
    $stmt->bind_param(
        "issssssss",
        $data['pelaporan_id'],
        $data['nama_aset'],
        $data['kode_aset'],
        $data['keterangan_kerusakan'],
        $keputusan,
        $kondisi_aset,
        $data['cabang_id'],
        $catatan,
        $status
    );

    if ($stmt->execute()) {
        // Insert ke status_barang
            $stmt2 = $conn->prepare("INSERT INTO status_barang 
    (pelaporan_id, tanggal, kode_aset, nama_aset, keputusan, catatan, kondisi, status, cabang_id, created_at) 
    VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, NOW())");

    $stmt2->bind_param(
    "issssssi", // <-- total 8 karakter
    $data['pelaporan_id'], // i
    $data['kode_aset'],    // s
    $data['nama_aset'],    // s
    $keputusan,            // s
    $catatan,              // s
    $kondisi_aset,         // s
    $status,               // s
    $data['cabang_id']     // i
);

        $stmt2->execute();
        $stmt2->close();

        header("Location: log_keputusan.php?success=1");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Feedback Pengadaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Sembunyikan opsi Selesai */
        select[name="status"] option[value="Selesai"] {
            display: none;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="card shadow border-0">
            <div class="card-header bg-warning">
                <h4 class="mb-0">Form Feedback Pengadaan</h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label>Cabang ID</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($data['cabang_id']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label>Pelaporan ID</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($data['pelaporan_id']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label>Tanggal</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($data['tanggal']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label>Kode Aset</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($data['kode_aset']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label>Nama Aset</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($data['nama_aset']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label>Keterangan Kerusakan</label>
                        <textarea class="form-control" readonly><?= htmlspecialchars($data['keterangan_kerusakan']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Kondisi Aset</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($kondisi_aset); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label>Keputusan</label>
                        <textarea name="keputusan" class="form-control" placeholder="Tulis keputusan..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Status</label>
                        <select name="status" class="form-select" required>
                            <option value="">-- Pilih Status --</option>
                            <option value="Dikirim">Dikirim</option>
                            <option value="Selesai">Selesai</option>
                            <option value="Pemeriksaan ke lokasi">Pemeriksaan ke lokasi</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Catatan</label>
                        <textarea name="catatan" class="form-control" placeholder="Tulis catatan..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Kirim Feedback</button>
                    <a href="log_keputusan.php" class="btn btn-secondary">Kembali</a>
                </form>
            </div>
        </div>
    </div>
</body>

</html> 