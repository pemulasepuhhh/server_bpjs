<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "apk_bpjs");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$notif_id = intval($_GET['notif_id']);
$pelaporan_id = intval($_GET['pelaporan_id']);
$redirect = $_GET['redirect'] ?? 'notifikasi.php';

// Update status notifikasi
$conn->query("UPDATE notifikasi SET status='dibaca' WHERE id=$notif_id");

// Update status pelaporan
$conn->query("UPDATE pelaporan SET status='dibaca' WHERE id=$pelaporan_id");

// Redirect ke halaman target
header("Location: $redirect?id=$pelaporan_id");
exit;
