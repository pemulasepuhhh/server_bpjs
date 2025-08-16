<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'User') {
    exit("Akses ditolak");
}

$cabangId = $_SESSION['cabang'] ?? 0;
if (!$cabangId) {
    exit("Cabang tidak ditemukan");
}

$conn = new mysqli("localhost", "root", "", "tenaga_sehat");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$sql = "SELECT aset.*, kategori_aset.kategori 
        FROM aset
        LEFT JOIN kategori_aset ON aset.id_kategori = kategori_aset.id
        WHERE aset.cabang_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $cabangId);
$stmt->execute();
$result = $stmt->get_result();

function getBadgeColor($kondisi)
{
    switch (strtolower($kondisi)) {
        case 'baik':
            return 'success';
        case 'rusak':
            return 'danger';
        case 'habis':
            return 'warning';
        case 'service':
            return 'primary';
        default:
            return 'secondary';
    }
}

while ($row = $result->fetch_assoc()) {
    $badge = getBadgeColor($row['kondisi']);
    echo "<tr>
            <td>" . htmlspecialchars($row['kode_aset']) . "</td>
            <td>" . htmlspecialchars($row['nama_aset']) . "</td>
            <td>" . htmlspecialchars($row['kategori']) . "</td>
            <td>" . htmlspecialchars($row['deskripsi']) . "</td>
            <td><span class='badge bg-$badge'>" . htmlspecialchars($row['kondisi']) . "</span></td>
        </tr>";
}

$stmt->close();
$conn->close();
