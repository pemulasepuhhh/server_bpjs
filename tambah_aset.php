<?php
session_start();
require '../config/koneksi.php';

// Cek login dan role admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../../login.php");
    exit;
}

$username = $_SESSION['username'];

// Ambil daftar cabang dari database
$stmtCabang = $conn->prepare("SELECT id, nama_cabang FROM cabang ORDER BY nama_cabang");
$stmtCabang->execute();
$resultCabang = $stmtCabang->get_result();
$cabangList = $resultCabang->fetch_all(MYSQLI_ASSOC);
$stmtCabang->close();

// Daftar kategori manual
$kategoriList = [
    'IT',
    'Elektronik',
    'Peralatan Kantor',
    'Furniture',
    'Transportasi',
    'Peralatan Medis'
];

// Proses simpan data aset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_aset   = $_POST['kode_aset'];
    $nama_aset   = $_POST['nama_aset'];
    $deskripsi   = $_POST['deskripsi'];
    $kategori    = $_POST['kategori'];
    $cabang_id   = $_POST['cabang_id'];
    $kondisi     = "baik"; // otomatis "baik"

    // Pastikan kategori yang dipilih ada di daftar
    if (!in_array($kategori, $kategoriList)) {
        $error = "Kategori tidak valid.";
    } else {
        // Insert ke tabel kategori_aset jika belum ada
        $stmtCheck = $conn->prepare("SELECT id FROM kategori_aset WHERE kategori = ? LIMIT 1");
        $stmtCheck->bind_param("s", $kategori);
        $stmtCheck->execute();
        $stmtCheck->bind_result($id_kategori);
        if ($stmtCheck->fetch()) {
            // Sudah ada
        } else {
            // Tambahkan kategori ke tabel
            $stmtInsertKategori = $conn->prepare("INSERT INTO kategori_aset (kategori) VALUES (?)");
            $stmtInsertKategori->bind_param("s", $kategori);
            $stmtInsertKategori->execute();
            $id_kategori = $stmtInsertKategori->insert_id;
            $stmtInsertKategori->close();
        }
        $stmtCheck->close();

        // Insert ke tabel aset
        $stmt = $conn->prepare("INSERT INTO aset (kode_aset, nama_aset, deskripsi, id_kategori, cabang_id, kondisi) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiss", $kode_aset, $nama_aset, $deskripsi, $id_kategori, $cabang_id, $kondisi);

        if ($stmt->execute()) {
            $success = "Data aset berhasil ditambahkan.";
        } else {
            $error = "Gagal menambahkan data: " . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Tambah Aset</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap + Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            background: linear-gradient(135deg, #fce4ec, #e1f5fe);
            font-family: 'Segoe UI', sans-serif;
        }

        header {
            background: #000000ff;
            color: white;
            padding: 20px;
            text-align: center;
            border-bottom: 5px solid #161d23ff;
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        label {
            font-weight: 500;
        }

        .btn-primary {
            background: #000000ff;
            border: none;
        }

        .btn-primary:hover {
            background: #000000ff;
        }

        .btn-secondary {
            background-color: #9e9e9e;
            border: none;
        }

        .btn-secondary:hover {
            background-color: #757575;
        }
    </style>
</head>

<body>

    <header>
        <h2><i class="fas fa-plus-circle"></i> Tambah Aset</h2>
        <p>Login sebagai: <?= htmlspecialchars($username) ?> (Admin)</p>
    </header>

    <div class="container">
        <div class="card">
            <?php if (isset($success)) : ?>
                <div class="alert alert-success text-center"><?= $success ?></div>
            <?php elseif (isset($error)) : ?>
                <div class="alert alert-danger text-center"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" class="row g-3">
                <div class="col-md-6">
                    <label for="kode_aset">Kode Aset</label>
                    <input type="text" class="form-control" id="kode_aset" name="kode_aset" required>
                </div>

                <div class="col-md-6">
                    <label for="nama_aset">Nama Aset</label>
                    <input type="text" class="form-control" id="nama_aset" name="nama_aset" required>
                </div>

                <div class="col-12">
                    <label for="deskripsi">Deskripsi</label>
                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" required></textarea>
                </div>

                <div class="col-md-6">
                    <label for="kategori">Kategori Aset</label>
                    <select name="kategori" id="kategori" class="form-select" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($kategoriList as $kat) : ?>
                            <option value="<?= htmlspecialchars($kat) ?>"><?= htmlspecialchars($kat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="cabang_id">Cabang</label>
                    <select name="cabang_id" id="cabang_id" class="form-select" required>
                        <option value="">-- Pilih Cabang --</option>
                        <?php foreach ($cabangList as $cabang) : ?>
                            <option value="<?= htmlspecialchars($cabang['id']) ?>"><?= htmlspecialchars($cabang['nama_cabang']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 text-end">
                    <a href="data_aset.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>

</body>

</html>