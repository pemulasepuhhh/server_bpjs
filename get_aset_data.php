<?php
$conn = new mysqli("localhost", "root", "", "tenaga_sehat");
if ($conn->connect_error) {
    die(json_encode(["error" => "Koneksi gagal"]));
}

$qAset = $conn->query("SELECT COUNT(*) AS total FROM aset");
$qAsetBaik = $conn->query("SELECT COUNT(*) AS total FROM aset WHERE kondisi='baik'");
$qAsetRusak = $conn->query("SELECT COUNT(*) AS total FROM aset WHERE kondisi='rusak'");
$qAsetHabis = $conn->query("SELECT COUNT(*) AS total FROM aset WHERE kondisi='habis'");
$AsetService = $conn->query("SELECT COUNT(*) AS total FROM aset WHERE kondisi='service'");

// Ambil data cabang dan jumlah aset
$sql = "SELECT c.nama_cabang, COUNT(a.id) as jumlah_aset 
        FROM cabang c 
        LEFT JOIN aset a ON c.id = a.cabang_id 
        GROUP BY c.id, c.nama_cabang 
        ORDER BY c.nama_cabang";
$result = $conn->query($sql);

$cabangData = [];
while($row = $result->fetch_assoc()) {
    $cabangData[] = $row;
}

// Ambil semua data aset
$sqlAll = "SELECT a.kode_aset, a.nama_aset, k.kategori, c.nama_cabang, a.kondisi
        FROM aset a
        LEFT JOIN kategori_aset k ON a.id_kategori = k.id
        LEFT JOIN cabang c ON a.cabang_id = c.id
        ORDER BY a.kode_aset";
$resultAll = $conn->query($sqlAll);

$allAsetData = [];
while($row = $resultAll->fetch_assoc()) {
    $allAsetData[] = $row;
}

// Data kategori
$kategoriLabels = ['IT', 'Elektronik', 'Peralatan Kantor', 'Furniture', 'Transportasi', 'Peralatan Medis'];
$kategoriData = [];

foreach ($kategoriLabels as $kategori) {
    $result = $conn->query("SELECT COUNT(*) AS total FROM kategori_aset WHERE kategori='$kategori'");
    $kategoriData[] = $result->fetch_assoc()['total'];
}

echo json_encode([
    "totalAset" => $qAset->fetch_assoc()['total'],
    "chartAset" => [
        $qAsetBaik->fetch_assoc()['total'],
        $qAsetRusak->fetch_assoc()['total'],
        $qAsetHabis->fetch_assoc()['total'],
        $AsetService->fetch_assoc()['total']
    ],
    "cabangData" => $cabangData,
    "allAsetData" => $allAsetData,
    "kategoriData" => $kategoriData
]);
?>
