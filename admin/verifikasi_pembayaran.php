<?php
session_start();
require_once __DIR__ . '/./includes/database.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Ambil parameter dari URL
$id = $_GET['id'] ?? 0;
$status = $_GET['status'] ?? '';

// Validasi status
if (!in_array($status, ['diverifikasi', 'ditolak'])) {
    $_SESSION['error_message'] = 'Status tidak valid';
    header('Location: konfirmasi_pembayaran.php');
    exit;
}

// Mulai transaksi
$conn->begin_transaction();

try {
    // Update status konfirmasi pembayaran
    $query = "UPDATE konfirmasi_pembayaran SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $status, $id);
    
    if (!$stmt->execute()) {
        throw new Exception("Gagal memperbarui status konfirmasi pembayaran");
    }
    
    // Dapatkan kode booking untuk update tabel reservasi
    $query = "SELECT kode_booking FROM konfirmasi_pembayaran WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    if (!$data) {
        throw new Exception("Data konfirmasi tidak ditemukan");
    }
    
    $kode_booking = $data['kode_booking'];
    
    // Update status pembayaran di tabel reservasi
    $query = "UPDATE reservasi SET status_pembayaran = ? WHERE kode_booking = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $status, $kode_booking);
    
    if (!$stmt->execute()) {
        throw new Exception("Gagal memperbarui status pembayaran reservasi");
    }
    
    // Jika status diverifikasi, update status reservasi menjadi 'dibayar'
    if ($status === 'diverifikasi') {
        $query = "UPDATE reservasi SET status = 'dibayar' WHERE kode_booking = ? AND status = 'menunggu_pembayaran'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $kode_booking);
        
        if (!$stmt->execute()) {
            throw new Exception("Gagal memperbarui status reservasi");
        }
    }
    
    // Commit transaksi
    $conn->commit();
    
    // Set pesan sukses
    $status_text = $status === 'diverifikasi' ? 'diverifikasi' : 'ditolak';
    $_SESSION['success_message'] = "Pembayaran berhasil $status_text";
    
} catch (Exception $e) {
    // Rollback transaksi jika terjadi error
    $conn->rollback();
    $_SESSION['error_message'] = $e->getMessage();
}

// Redirect kembali ke halaman detail konfirmasi
header("Location: detail_konfirmasi.php?id=$id");
exit;
?>

