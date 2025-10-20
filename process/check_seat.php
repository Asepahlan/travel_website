<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start output buffering to catch any unwanted output
if (ob_get_level() == 0) {
    ob_start();
}

// Set JSON header
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Function to send JSON response and exit
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    // Include database configuration
    require_once __DIR__ . '/./includes/database.php';
    
    // Get the database connection from config/database.php
    $conn = $GLOBALS['conn'] ?? null;
    
    if (!$conn || $conn->connect_error) {
        throw new Exception('Database connection error: ' . ($conn->connect_error ?? 'No connection'));
    }
    
    // Verify connection is working
    if (!$conn->ping()) {
        throw new Exception('Database ping failed');
    }
} catch (Exception $e) {
    error_log('Database connection error: ' . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'Database connection error',
        'error' => $e->getMessage()
    ], 500);
}

// Get and validate jadwal_id
try {
    $jadwal_id = filter_input(INPUT_GET, 'jadwal_id', FILTER_VALIDATE_INT);
    
    if (!$jadwal_id) {
        throw new InvalidArgumentException('Jadwal ID tidak valid');
    }
    
    /**
     * Get seat availability for a specific schedule
     */
    function getSeatAvailability($conn, $jadwal_id) {
        $sql = "SELECT drk.id_kursi 
                FROM detail_reservasi_kursi drk 
                JOIN reservasi r ON drk.id_reservasi = r.id_reservasi 
                WHERE r.id_jadwal = ? 
                AND r.status IN ('sudah_bayar', 'belum_bayar', 'dikonfirmasi')";
    
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Gagal menyiapkan pernyataan SQL: ' . $conn->error);
        }
    
        $stmt->bind_param("i", $jadwal_id);
    
        if (!$stmt->execute()) {
            throw new RuntimeException('Gagal mengeksekusi query: ' . $stmt->error);
        }
    
        $result = $stmt->get_result();
        $booked_seats = [];
    
        while ($row = $result->fetch_assoc()) {
            $booked_seats[] = (int)$row['id_kursi'];
        }
    
        $stmt->close();
        return $booked_seats;
    }
    
    // Clear any previous output
    if (ob_get_level() > 0) {
        ob_clean();
    }
    
    // Get seat availability
    $booked_seats = getSeatAvailability($conn, $jadwal_id);
    
    // Send success response
    sendJsonResponse([
        'success' => true,
        'booked_seats' => $booked_seats,
        'timestamp' => time(),
        'debug' => [
            'jadwal_id' => $jadwal_id,
            'seats_found' => count($booked_seats)
        ]
    ]);
    
} catch (InvalidArgumentException $e) {
    // Bad request
    sendJsonResponse([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => 'INVALID_INPUT',
        'debug' => ['jadwal_id' => $jadwal_id ?? null]
    ], 400);
    
} catch (RuntimeException $e) {
    // Database or query error
    error_log('Runtime error in check_seat_availability.php: ' . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'Terjadi kesalahan saat memeriksa ketersediaan kursi',
        'error' => 'DATABASE_ERROR',
        'debug' => [
            'message' => $e->getMessage(),
            'code' => $e->getCode()
        ]
    ], 500);
    
} catch (Exception $e) {
    // Other unexpected errors
    error_log('Unexpected error in check_seat_availability.php: ' . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'Terjadi kesalahan tak terduga',
        'error' => 'INTERNAL_ERROR',
        'debug' => [
            'message' => $e->getMessage(),
            'type' => get_class($e)
        ]
    ], 500);
    
} finally {
    // Clean up
    if (isset($conn) && $conn) {
        $conn->close();
    }
    
    // Ensure no output remains in buffers
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}

