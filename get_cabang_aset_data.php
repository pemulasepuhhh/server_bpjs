<?php
$conn = new mysqli("localhost", "root", "", "tenaga_sehat");
if ($conn->connect_error) {
    die(json_encode(["error" => "Koneksi gagal"]));
}

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

echo json_encode($cabangData);
?>