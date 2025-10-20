<?php
session_start();
require_once __DIR__ . '/./includes/database.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get and validate input
$id = $_POST['id'] ?? 0;
$status = $_POST['status'] ?? '';

if (empty($id) || !in_array($status, ['diverifikasi', 'ditolak', 'dibatalkan'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // 1. Update konfirmasi_pembayaran status
    // If status is 'dibatalkan', set it to 'menunggu' in the database
    $statusToUpdate = $status === 'dibatalkan' ? 'menunggu' : $status;
    $stmt = $conn->prepare("UPDATE konfirmasi_pembayaran SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $statusToUpdate, $id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('No records updated. ID not found or no changes made.');
    }

    // 2. Get the kode_booking for updating reservasi
    $stmt = $conn->prepare("SELECT kode_booking FROM konfirmasi_pembayaran WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    if (!$data) {
        throw new Exception('Booking not found');
    }

    $kode_booking = $data['kode_booking'];

    // 3. Update reservasi status
    $status_reservasi = 'menunggu';
    if ($status === 'diverifikasi') {
        $status_reservasi = 'lunas';
    } elseif ($status === 'ditolak') {
        $status_reservasi = 'dibatalkan';
    }
    
    $stmt = $conn->prepare("UPDATE reservasi SET status_pembayaran = ? WHERE kode_booking = ?");
    $stmt->bind_param("ss", $status_reservasi, $kode_booking);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>

