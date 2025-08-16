<?php
// tolak_laporan.php

// Koneksi ke database
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "tenaga_sehat";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Pastikan ada ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID notifikasi tidak ditemukan.");
}

$id_notif = intval($_GET['id']);

// Ambil data laporan berdasarkan id notifikasi
$sql = "
    SELECT 
        p.id AS pelaporan_id,
        p.cabang_id,
        p.tanggal,
        c.nama_cabang,
        a.nama_aset,
        a.kode_aset,
        p.keterangan_kerusakan,
        p.pesan_pengajuan,
        a.kondisi
    FROM notifikasi n
    JOIN pelaporan p ON n.pelaporan_id = p.id
    JOIN cabang c ON p.cabang_id = c.id
    JOIN aset a ON p.kode_aset = a.kode_aset
    WHERE n.id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_notif);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Data laporan tidak ditemukan.");
}

$data = $result->fetch_assoc();
$stmt->close();

// Masukkan ke tabel riwayat_laporan dengan pelaporan_id dan cabang_id
$sql_insert = "
    INSERT INTO riwayat_laporan 
    (pelaporan_id, cabang_id, tanggal, nama_cabang, nama_aset, kode_aset, keterangan_kerusakan, pesan_pengajuan, kondisi, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Ditolak')
";
$stmt_insert = $conn->prepare($sql_insert);
$stmt_insert->bind_param(
    "iisssssss",
    $data['pelaporan_id'],
    $data['cabang_id'],
    $data['tanggal'],
    $data['nama_cabang'],
    $data['nama_aset'],
    $data['kode_aset'],
    $data['keterangan_kerusakan'],
    $data['pesan_pengajuan'],
    $data['kondisi']
);
$stmt_insert->execute();
$stmt_insert->close();

// Update status di notifikasi menjadi "dibaca"
$sql_update_notif = "UPDATE notifikasi SET status = 'dibaca' WHERE id = ?";
$stmt_update_notif = $conn->prepare($sql_update_notif);
$stmt_update_notif->bind_param("i", $id_notif);
$stmt_update_notif->execute();
$stmt_update_notif->close();

// Update status di pelaporan menjadi "dibaca"
$sql_update_pelaporan = "UPDATE pelaporan SET status = 'dibaca' WHERE id = ?";
$stmt_update_pelaporan = $conn->prepare($sql_update_pelaporan);
$stmt_update_pelaporan->bind_param("i", $data['pelaporan_id']);
$stmt_update_pelaporan->execute();
$stmt_update_pelaporan->close();

// Redirect kembali ke halaman notifikasi
header("Location: notifikasi.php?msg=laporan_ditolak");
exit;
