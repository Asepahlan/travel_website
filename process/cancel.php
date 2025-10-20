<?php
require_once __DIR__ . 
'/./includes/database.php';
session_start();

// --- CSRF Protection Check ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'Sesi tidak valid atau permintaan tidak sah. Silakan coba lagi.';
        // Redirect back to cek_pesanan page, potentially with the kode_booking if available
        $kode_booking_redirect = isset($_POST['kode_booking']) ? '?kode_booking=' . urlencode($_POST['kode_booking']) : '';
        header('Location: cek_pesanan.php' . $kode_booking_redirect);
        exit;
    }
} else {
    // Redirect if not POST
    header('Location: cek_pesanan.php');
    exit;
}
// --- End CSRF Protection Check ---

$kode_booking = $_POST['kode_booking'] ?? null;

if (empty($kode_booking)) {
    $_SESSION['error_message'] = 'Kode booking tidak ditemukan untuk pembatalan.';
    header('Location: cek_pesanan.php');
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // 1. Find the reservation and lock it
    $sql_find = "SELECT id_reservasi, status_pembayaran FROM reservasi WHERE kode_booking = ? FOR UPDATE";
    $stmt_find = $conn->prepare($sql_find);
    if ($stmt_find === false) throw new Exception('Gagal menyiapkan statement pencarian: ' . $conn->error);
    $stmt_find->bind_param("s", $kode_booking);
    $stmt_find->execute();
    $result_find = $stmt_find->get_result();
    $reservation = $result_find->fetch_assoc();
    $stmt_find->close();

    if (!$reservation) {
        throw new Exception('Reservasi dengan kode booking tersebut tidak ditemukan.');
    }

    // 2. Check if cancellation is allowed (only if 'belum_bayar')
    if ($reservation['status_pembayaran'] !== 'belum_bayar') {
        throw new Exception('Reservasi ini tidak dapat dibatalkan (status: ' . ucwords(str_replace('_', ' ', $reservation['status_pembayaran'])) . ').');
    }

    // 3. Update reservation status to 'dibatalkan'
    $sql_update = "UPDATE reservasi SET status_pembayaran = 'dibatalkan' WHERE id_reservasi = ?";
    $stmt_update = $conn->prepare($sql_update);
    if ($stmt_update === false) throw new Exception('Gagal menyiapkan statement update: ' . $conn->error);
    $stmt_update->bind_param("i", $reservation['id_reservasi']);
    
    if (!$stmt_update->execute()) {
        throw new Exception('Gagal memperbarui status reservasi: ' . $stmt_update->error);
    }
    $stmt_update->close();

    // 4. (Optional but recommended) Delete entries from detail_reservasi_kursi to free up seats immediately
    // Although the check logic usually ignores 'dibatalkan' reservations, explicitly freeing is cleaner.
    $sql_delete_details = "DELETE FROM detail_reservasi_kursi WHERE id_reservasi = ?";
    $stmt_delete = $conn->prepare($sql_delete_details);
    if ($stmt_delete === false) throw new Exception('Gagal menyiapkan statement hapus detail: ' . $conn->error);
    $stmt_delete->bind_param("i", $reservation['id_reservasi']);
    if (!$stmt_delete->execute()) {
        // Log this error but don't necessarily stop the whole process
        error_log("Warning: Gagal menghapus detail kursi untuk reservasi ID {$reservation['id_reservasi']} yang dibatalkan.");
    }
    $stmt_delete->close();

    // If all successful, commit transaction
    $conn->commit();

    $_SESSION['success_message'] = 'Reservasi dengan kode booking ' . htmlspecialchars($kode_booking) . ' berhasil dibatalkan.';
    // Redirect back to the cek_pesanan page showing the cancelled status
    header('Location: cek_pesanan.php?kode_booking=' . urlencode($kode_booking));
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();

    $_SESSION['error_message'] = 'Gagal membatalkan reservasi: ' . $e->getMessage();
    // Redirect back to the cek_pesanan page showing the error
    header('Location: cek_pesanan.php?kode_booking=' . urlencode($kode_booking));
    exit;
} finally {
    $conn->close();
}
?>

