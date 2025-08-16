<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "tenaga_sehat");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$notif_id = isset($_GET['notif_id']) ? intval($_GET['notif_id']) : 0;
$pelaporan_id = isset($_GET['pelaporan_id']) ? intval($_GET['pelaporan_id']) : 0;

if ($notif_id > 0 && $pelaporan_id > 0) {
    $conn->begin_transaction();
    try {
        // Hapus dari tabel notifikasi
        $conn->query("DELETE FROM notifikasi WHERE id = $notif_id");

        // Hapus dari tabel pelaporan
        $conn->query("DELETE FROM pelaporan WHERE id = $pelaporan_id");

        $conn->commit();
        header("Location: notifikasi.php?msg=hapus_sukses");
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: notifikasi.php?msg=hapus_gagal");
    }
} else {
    header("Location: notifikasi.php?msg=hapus_gagal");
}
