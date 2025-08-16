<?php
// detail_laporan.php  

// Koneksi ke database apk_bpjs
$host = "localhost";
$user = "root"; // default XAMPP
$pass = ""; // default kosong
$dbname = "tenaga_sehat";

$conn = new mysqli($host, $user, $pass, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Pastikan ada parameter id dari notifikasi
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID laporan tidak ditemukan.");
}

$id_laporan = intval($_GET['id']); // hindari SQL injection

// Ambil data detail laporan dari database
$sql = "
    SELECT 
        n.id AS notif_id,
        p.tanggal,
        c.nama_cabang,
        a.nama_aset,
        a.kode_aset,
        p.keterangan_kerusakan,
        p.pesan_pengajuan,
        a.kondisi,
        p.file_pdf
    FROM notifikasi n
    JOIN pelaporan p ON n.pelaporan_id = p.id
    JOIN cabang c ON p.cabang_id = c.id
    JOIN aset a ON p.kode_aset = a.kode_aset
    WHERE n.id = ?;
";



$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_laporan);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Data laporan tidak ditemukan.");
}

$data = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Detail Laporan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Reset style */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
        }

        .container {
            width: 100%;
            max-width: 850px;
            background: #ffffff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            animation: fadeIn 0.6s ease-in-out;
        }

        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 25px;
            font-size: 28px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 14px;
            border-bottom: 1px solid #eee;
            font-size: 16px;
        }

        td:first-child {
            font-weight: bold;
            background: #f7f9fc;
            width: 35%;
            color: #34495e;
            border-radius: 8px 0 0 8px;
        }

        td:last-child {
            background: #ffffff;
            border-radius: 0 8px 8px 0;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Tombol */
        .button-row {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
        }

        .back-btn {
            padding: 12px 20px;
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 16px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: linear-gradient(135deg, #00c6ff, #0072ff);
            transform: translateY(-2px);
        }

        .aksi-btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            color: white;
            margin-left: 10px;
            transition: all 0.3s ease;
        }

        .setujui {
            background-color: #28a745;
        }

        .setujui:hover {
            background-color: #218838;
        }

        .tolak {
            background-color: #dc3545;
        }

        .tolak:hover {
            background-color: #c82333;
        }

        /* Responsif */
        @media (max-width: 600px) {
            .container {
                padding: 20px;
            }

            td {
                font-size: 14px;
                display: block;
                width: 100%;
            }

            td:first-child,
            td:last-child {
                background: none;
                border-radius: 0;
                padding: 8px 0;
            }

            table tr {
                display: block;
                margin-bottom: 15px;
                border-bottom: 1px solid #eee;
            }

            .button-row {
                flex-direction: column;
                align-items: stretch;
            }

            .aksi-btn {
                margin: 10px 0 0 0;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>📄 Detail Laporan</h2>
        <table>
            <tr>
                <td>Tanggal</td>
                <td><?= htmlspecialchars($data['tanggal']) ?></td>
            </tr>
            <tr>
                <td>Nama Cabang</td>
                <td><?= htmlspecialchars($data['nama_cabang']) ?></td>
            </tr>
            <tr>
                <td>Nama Aset</td>
                <td><?= htmlspecialchars($data['nama_aset']) ?></td>
            </tr>
            <tr>
                <td>Kode Aset</td>
                <td><?= htmlspecialchars($data['kode_aset']) ?></td>
            </tr>
            <tr>
                <td>Keterangan Kerusakan</td>
                <td><?= nl2br(htmlspecialchars($data['keterangan_kerusakan'])) ?></td>
            </tr>
            <tr>
                <td>Pesan Pengajuan</td>
                <td><?= nl2br(htmlspecialchars($data['pesan_pengajuan'])) ?></td>
            </tr>
            <tr>
                <td>Kondisi Aset</td>
                <td><?= htmlspecialchars($data['kondisi']) ?></td>
            </tr>
            <tr>
                <td>File PDF</td>
                <td>
                    <?php if (!empty($data['file_pdf'])) : ?>
                        <a href="uploads/file_pdf<?= htmlspecialchars($data['file_pdf']) ?>" target="_blank">
                            <?= htmlspecialchars($data['file_pdf']) ?>
                        </a>
                    <?php else : ?>
                        <em>Tidak ada file PDF</em>
                    <?php endif; ?>
                </td>
            </tr>


        </table>

        <div class="button-row">
            <a href="notifikasi.php" class="back-btn">← Kembali ke Notifikasi</a>
            <div>
                <button class="aksi-btn setujui" onclick="location.href='setujui_laporan.php?id=<?= $data['notif_id'] ?>'">
                    ✅ Setujui
                </button>

                <button class="aksi-btn tolak" onclick="location.href='tolak_laporan.php?id=<?= $data['notif_id'] ?>'">❌ Tolak</button>
            </div>
        </div>
    </div>
</body>

</html>