<?php
session_start();
require_once __DIR__ . '/./includes/database.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get input data
$id_reservasi = $_POST['id_reservasi'] ?? null;
$status = strtolower(trim($_POST['status'] ?? ''));

// Validate input
if (!$id_reservasi || !$status) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

// List of valid statuses
$valid_statuses = ['menunggu', 'diverifikasi', 'diproses', 'selesai', 'dibatalkan', 'refund'];
if (!in_array($status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Update reservation status
    $stmt = $conn->prepare("UPDATE reservasi SET status = ? WHERE id_reservasi = ?");
    $stmt->bind_param("si", $status, $id_reservasi);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('Tidak ada perubahan data atau reservasi tidak ditemukan');
    }
    
    // If status is 'diverifikasi', also update payment status if exists
    if ($status === 'diverifikasi') {
        $stmt = $conn->prepare("
            UPDATE konfirmasi_pembayaran kp
            JOIN reservasi r ON kp.id_reservasi = r.id_reservasi
            SET kp.status = 'diverifikasi', kp.tanggal_verifikasi = NOW()
            WHERE r.id_reservasi = ? AND kp.status = 'menunggu'");
        $stmt->bind_param("i", $id_reservasi);
        $stmt->execute();
    }
    
    // If status is 'dibatalkan', free up the seats
    if ($status === 'dibatalkan') {
        // Get seat IDs for this reservation
        $seat_ids = [];
        $stmt = $conn->prepare("
            SELECT k.id_kursi 
            FROM detail_reservasi_kursi drk
            JOIN kursi k ON drk.id_kursi = k.id_kursi
            WHERE drk.id_reservasi = ?");
        $stmt->bind_param("i", $id_reservasi);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $seat_ids[] = $row['id_kursi'];
        }
        
        // Mark seats as available again
        if (!empty($seat_ids)) {
            $placeholders = implode(',', array_fill(0, count($seat_ids), '?'));
            $types = str_repeat('i', count($seat_ids));
            $stmt = $conn->prepare("UPDATE kursi SET status = 'tersedia' WHERE id_kursi IN ($placeholders)");
            $stmt->bind_param($types, ...$seat_ids);
            $stmt->execute();
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Log the action
    $admin_id = $_SESSION['user_id'];
    $action = "Mengubah status reservasi #$id_reservasi menjadi $status";
    $stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action) VALUES (?, ?)");
    $stmt->bind_param("is", $admin_id, $action);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Status reservasi berhasil diupdate']);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>

