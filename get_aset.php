<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "tenaga_sehat");
if ($conn->connect_error) {
    die(json_encode(['error' => 'Koneksi gagal']));
}

$sql = "SELECT aset.*, kategori_aset.kategori, cabang.nama_cabang 
        FROM aset
        LEFT JOIN kategori_aset ON aset.id_kategori = kategori_aset.id
        LEFT JOIN cabang ON aset.cabang_id = cabang.id";

$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode(['data' => $data]);
$conn->close();
