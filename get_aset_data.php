<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'User') {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$cabangId = $_SESSION['cabang'];

$conn = new mysqli("localhost", "root", "", "apk_bpjs");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "DB connection failed"]);
    exit;
}

// Data aset
$qBaik  = $conn->query("SELECT COUNT(*) AS total FROM aset WHERE kondisi = 'baik' AND cabang_id = $cabangId")->fetch_assoc()['total'];
$qRusak = $conn->query("SELECT COUNT(*) AS total FROM aset WHERE kondisi = 'rusak' AND cabang_id = $cabangId")->fetch_assoc()['total'];
$qHabis = $conn->query("SELECT COUNT(*) AS total FROM aset WHERE kondisi = 'habis' AND cabang_id = $cabangId")->fetch_assoc()['total'];
$qService = $conn->query("SELECT COUNT(*) AS total FROM aset WHERE kondisi = 'service' AND cabang_id = $cabangId")->fetch_assoc()['total'];

$kategoriLabels = ['IT', 'Elektronik', 'Peralatan Kantor', 'Furniture', 'Transportasi', 'Peralatan Medis'];
$kategoriData = [];

foreach ($kategoriLabels as $kategori) {
    $kategoriData[] = $conn->query("SELECT COUNT(*) AS total FROM kategori_aset WHERE kategori='$kategori'")->fetch_assoc()['total'];
}

echo json_encode([
    "aset" => [$qBaik, $qRusak, $qHabis, $qService],
    "kategori" => $kategoriData
]);
