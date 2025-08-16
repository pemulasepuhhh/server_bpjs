<?php
session_start();

// Cek login & role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'User') {
    header("Location: ../login.php");
    exit;
}

// Mode debug
$debug = true;
if ($debug) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
}

// Koneksi ke database
$conn = new mysqli("localhost", "root", "", "tenaga_sehat");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Cek parameter
if (!isset($_GET['id']) || !isset($_GET['aksi'])) {
    die("Parameter tidak lengkap.");
}

$id = intval($_GET['id']);
$aksi = $_GET['aksi'];

if ($aksi === 'selesai') {
    // Ambil kode_aset + data lain dari status_barang
    $sqlGet = "SELECT id, kode_aset, pelaporan_id, tanggal, keputusan, catatan, cabang_id 
               FROM status_barang 
               WHERE id = ?";
    $stmtGet = $conn->prepare($sqlGet);
    $stmtGet->bind_param("i", $id);
    $stmtGet->execute();
    $resultGet = $stmtGet->get_result();

    if ($resultGet->num_rows > 0) {
        $data = $resultGet->fetch_assoc();
        $kode_aset = $data['kode_aset'];

        // Ambil nama aset dari tabel aset
        $stmtNama = $conn->prepare("SELECT nama_aset FROM aset WHERE kode_aset = ?");
        $stmtNama->bind_param("s", $kode_aset);
        $stmtNama->execute();
        $resultNama = $stmtNama->get_result();
        $nama_aset  = ($resultNama->num_rows > 0) ? $resultNama->fetch_assoc()['nama_aset'] : '-';

        // Nilai tetap
        $kondisi = 'Baik';
        $status  = 'Selesai';

        // Mulai transaksi
        $conn->begin_transaction();
        try {
            // 1. Update status_barang
            $stmt1 = $conn->prepare("UPDATE status_barang SET status=?, kondisi=? WHERE id=?");
            $stmt1->bind_param("ssi", $status, $kondisi, $id);
            $stmt1->execute();

            // 2. Update log_keputusan
            $stmt2 = $conn->prepare("UPDATE log_keputusan SET status=?, kondisi=? WHERE kode_aset=?");
            $stmt2->bind_param("sss", $status, $kondisi, $kode_aset);
            $stmt2->execute();

            // 3. Update rencana_pengadaan
            $stmt3 = $conn->prepare("UPDATE rencana_pengadaan SET status=? WHERE kode_aset=?");
            $stmt3->bind_param("ss", $status, $kode_aset);
            $stmt3->execute();

            // 3a. Update up_laporan (tambahan baru)
            $stmtUp = $conn->prepare("UPDATE up_laporan SET status=? WHERE kode_aset=?");
            $stmtUp->bind_param("ss", $status, $kode_aset);
            $stmtUp->execute();

            // 4. Update aset (kondisi)
            $stmt4 = $conn->prepare("UPDATE aset SET kondisi=? WHERE kode_aset=?");
            $stmt4->bind_param("ss", $kondisi, $kode_aset);
            $stmt4->execute();

            // 5. Insert ke riwayat_aset
            $pelaporan_id = $data['pelaporan_id'];
            $tanggal      = $data['tanggal'];
            $keputusan    = $data['keputusan'];
            $catatan      = $data['catatan'];
            $cabang_id    = $data['cabang_id'];

            $stmt5 = $conn->prepare("
                INSERT INTO riwayat_aset 
                (pelaporan_id, tanggal, kode_aset, nama_aset, keputusan, catatan, kondisi, status, cabang_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt5->bind_param(
                "isssssssi",
                $pelaporan_id,
                $tanggal,
                $kode_aset,
                $nama_aset,
                $keputusan,
                $catatan,
                $kondisi,
                $status,
                $cabang_id
            );
            $stmt5->execute();

            // Commit transaksi
            $conn->commit();

            header("Location: riwayat_aset.php?msg=update_sukses");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            if ($debug) {
                die("Gagal update: " . $e->getMessage());
            } else {
                die("Gagal update data.");
            }
        }
    } else {
        die("Data tidak ditemukan.");
    }
} else {
    die("Aksi tidak valid.");
}
