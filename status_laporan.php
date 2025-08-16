<?php
// Koneksi ke database
$host = "localhost";
$user = "root"; // ganti sesuai username MySQL Anda
$pass = ""; // ganti sesuai password MySQL Anda
$db   = "tenaga_sehat";

$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil data dari tabel status_laporan
$sql = "SELECT * FROM status_pesan ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Data Status Laporan</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        th {
            background-color: #4CAF50;
            color: white;
            text-align: center;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>

    <h2>Daftar Status Laporan</h2>

    <table>
        <tr>
            <th>ID</th>
            <th>ID Cabang</th>
            <th>Nama Cabang</th>
            <th>Nama Barang</th>
            <th>Status</th>
            <th>Catatan</th>
            <th>Tindak Lanjut</th>
            <th>Dibuat Pada</th>
        </tr>
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['cabang_id']}</td>
                <td>{$row['nama_cabang']}</td>
                <td>{$row['nama_barang']}</td>
                <td>{$row['status']}</td>
                <td>{$row['catatan']}</td>
                <td>{$row['tindak_lanjut']}</td>
                <td>{$row['created_at']}</td>
            </tr>";
            }
        } else {
            echo "<tr><td colspan='8' style='text-align:center'>Tidak ada data</td></tr>";
        }
        ?>
    </table>

</body>

</html>

<?php
$conn->close();
?>