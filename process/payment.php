<?php
// Start output buffering
ob_start();

// Disable error display to prevent HTML in JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set JSON content type header
header('Content-Type: application/json; charset=utf-8');

// Function to clean all output buffers
function cleanOutputBuffers() {
    while (ob_get_level()) {
        ob_end_clean();
    }
}

// Function to send JSON response
function sendJsonResponse($success, $message = '', $data = []) {
    // Clean all output buffers
    cleanOutputBuffers();
    
    // Set JSON header
    header('Content-Type: application/json; charset=utf-8');
    
    // Build response
    $response = [
        'success' => (bool)$success,
        'message' => (string)$message,
        'data' => $data ?: (object)[]
    ];
    
    // Output JSON
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Handle any unexpected output
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && ($error['type'] & (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | E_PARSE))) {
        cleanOutputBuffers();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'A server error occurred: ' . $error['message'],
            'data' => ['error' => $error]
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
});



// Include database connection
try {
    require_once __DIR__ . '/./includes/database.php';
    
    // Check database connection
    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new Exception('Database connection failed');
    }
} catch (Exception $e) {
    sendJsonResponse(false, 'Database connection error: ' . $e->getMessage());
}

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Metode request tidak valid');
}

try {
    // Validate required fields
    $required_fields = ['kode_booking', 'bank_tujuan', 'nama_pengirim', 'jumlah_dibayar', 'tanggal_transfer'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field]) && $_POST[$field] !== '0') {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        sendJsonResponse(false, 'Harap lengkapi semua kolom yang wajib diisi: ' . implode(', ', $missing_fields));
    }
    
    // Get form data
    $kode_booking = trim($_POST['kode_booking']);
    $bank_tujuan = trim($_POST['bank_tujuan']);
    $nama_pengirim = trim($_POST['nama_pengirim']);
    $jumlah_dibayar = (float) preg_replace('/[^0-9]/', '', $_POST['jumlah_dibayar']);
    $tanggal_transfer = date('Y-m-d H:i:s', strtotime($_POST['tanggal_transfer']));
    $catatan = !empty($_POST['catatan']) ? trim($_POST['catatan']) : null;
    
    // Get booking details
    $stmt = $conn->prepare("SELECT id_reservasi, total_harga, status_pembayaran FROM reservasi WHERE kode_booking = ?");
    if (!$stmt) {
        throw new Exception("Gagal memeriksa data pemesanan");
    }
    
    $stmt->bind_param('s', $kode_booking);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Booking code not found");
    }
    
    $booking = $result->fetch_assoc();
    $id_reservasi = $booking['id_reservasi'];
    $total_harga = (float) $booking['total_harga'];
    $status_pembayaran = $booking['status_pembayaran'];
    
    // Check if payment has already been verified
    if ($status_pembayaran === 'diverifikasi') {
        sendJsonResponse(false, 'Pembayaran sudah diverifikasi', [
            'status' => $status_pembayaran, 
            'code' => 'PAYMENT_ALREADY_VERIFIED',
            'status_display' => 'Sudah Diverifikasi'
        ]);
        exit;
    }
    
    // Check if payment was rejected
    if ($status_pembayaran === 'ditolak') {
        sendJsonResponse(false, 'Pembayaran sebelumnya ditolak. Silakan unggah bukti pembayaran yang valid.', [
            'status' => 'ditolak',
            'code' => 'PAYMENT_REJECTED',
            'status_display' => 'Ditolak'
        ]);
        exit;
    }
    
    // Validate payment amount (within 10% of total price)
    $min_amount = $total_harga * 0.9;
    $max_amount = $total_harga * 1.1;
    
    if ($jumlah_dibayar < $min_amount || $jumlah_dibayar > $max_amount) {
        throw new Exception("Payment amount must be between " . number_format($min_amount, 0, ',', '.') . " and " . number_format($max_amount, 0, ',', '.') . " (90-110% of total price)");
    }
    
    // Handle file upload
    $bukti_transfer = null;
    if (isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['bukti_transfer'];
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception("Unsupported file format. Please upload a JPG, PNG, or PDF file");
        }
        
        if ($file['size'] > $max_size) {
            throw new Exception("File size is too large. Maximum 2MB");
        }
        
        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $bukti_transfer = 'payment_' . uniqid() . '.' . $file_extension;
        $upload_path = __DIR__ . '/../public/uploads/payments/' . $bukti_transfer;
        
        // Create uploads directory if it doesn't exist
        if (!is_dir(dirname($upload_path))) {
            mkdir(dirname($upload_path), 0755, true);
        }
        
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception("Failed to upload file. Please try again");
        }
    } else {
        throw new Exception("Please upload a payment proof");
    }
    
    // Start transaction
    if (!$conn->begin_transaction()) {
        throw new Exception("Gagal memulai transaksi: " . $conn->error);
    }
    
    try {
        // Check if booking exists and get total amount
        $stmt = $conn->prepare("SELECT id_reservasi, total_harga, status_pembayaran FROM reservasi WHERE kode_booking = ?");
        if (!$stmt) {
            throw new Exception("Gagal memeriksa data pemesanan");
        }
        
        $stmt->bind_param('s', $kode_booking);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Booking code not found");
        }
        
        $booking = $result->fetch_assoc();
        $id_reservasi = $booking['id_reservasi'];
        $total_harga = (float) $booking['total_harga'];
        
        // Check if payment already exists for this booking and is still pending
        $stmt = $conn->prepare("SELECT kp.id, r.status_pembayaran 
                              FROM konfirmasi_pembayaran kp
                              JOIN reservasi r ON kp.kode_booking = r.kode_booking
                              WHERE kp.kode_booking = ?");
        $stmt->bind_param('s', $kode_booking);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $existing = $result->fetch_assoc();
            if ($existing['status_pembayaran'] === 'menunggu') {
                sendJsonResponse(false, 'Bukti pembayaran sudah diunggah dan sedang menunggu verifikasi', [
                    'status' => 'menunggu',
                    'code' => 'PAYMENT_PENDING_VERIFICATION',
                    'status_display' => 'Menunggu Verifikasi'
                ]);
                exit;
            }
        }
        
        // Debug: Tampilkan struktur tabel
        $result = $conn->query("SHOW COLUMNS FROM konfirmasi_pembayaran");
        $columns = [];
        while($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'] . ' (' . $row['Type'] . ')';
        }
        
        // Insert payment confirmation - sesuaikan dengan struktur tabel yang ada
        $stmt = $conn->prepare("INSERT INTO konfirmasi_pembayaran 
            (kode_booking, bank_tujuan, nama_pengirim, jumlah_dibayar, tanggal_transfer, bukti_transfer, catatan, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'menunggu')");
        
        if (!$stmt) {
            throw new Exception("Gagal mempersiapkan pernyataan SQL: " . $conn->error);
        }
        
        // Pastikan catatan tidak null
        $catatan = $catatan ?? '';
        
        // Bind parameter dengan type string yang benar
        $stmt->bind_param('sssdsss', 
            $kode_booking,          // s - string
            $bank_tujuan,           // s - string
            $nama_pengirim,         // s - string
            $jumlah_dibayar,        // d - double
            $tanggal_transfer,      // s - string (date)
            $bukti_transfer,        // s - string
            $catatan                // s - string
        );
        
        
        if (!$stmt->execute()) {
            throw new Exception("Gagal menyimpan konfirmasi pembayaran: " . $conn->error);
        }
        
        // Update booking status to 'Dibayar' after successful payment upload
        $update_stmt = $conn->prepare("UPDATE reservasi SET status_pembayaran = 'Dibayar' WHERE id_reservasi = ?");
        if (!$update_stmt) {
            throw new Exception("Gagal memperbarui status pemesanan");
        }
        
        $update_stmt->bind_param('i', $id_reservasi);
        if (!$update_stmt->execute()) {
            throw new Exception("Gagal memperbarui status pemesanan: " . $update_stmt->error);
        }
        
        // Commit transaction
        if (!$conn->commit()) {
            throw new Exception("Gagal melakukan commit transaksi: " . $conn->error);
        }
        
        sendJsonResponse(true, 'Bukti pembayaran berhasil dikirim. Tim kami akan memverifikasi pembayaran Anda segera.', [
            'status' => 'menunggu',
            'status_display' => 'Menunggu Verifikasi'
        ]);
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($conn) && $conn instanceof mysqli) {
            $conn->rollback();
        }
        
        // Delete uploaded file if exists
        if (isset($upload_path) && file_exists($upload_path)) {
            @unlink($upload_path);
        }
        
        throw $e;
    }
    
} catch (Exception $e) {
    // Check if this is a known error with a specific code
    $errorMessage = $e->getMessage();
    
    if (strpos($errorMessage, 'already been confirmed') !== false) {
        sendJsonResponse(false, 'Pembayaran untuk pemesanan ini sudah dikonfirmasi sebelumnya', [
            'status' => 'error', 
            'code' => 'PAYMENT_ALREADY_CONFIRMED',
            'status_display' => 'Sudah Dikonfirmasi'
        ]);
    } elseif (strpos($errorMessage, 'PAYMENT_PENDING_VERIFICATION') !== false) {
        sendJsonResponse(false, 'Pembayaran sedang menunggu verifikasi', [
            'status' => 'menunggu',
            'code' => 'PAYMENT_PENDING_VERIFICATION',
            'status_display' => 'Menunggu Verifikasi'
        ]);
    } else {
        // Log the error for debugging
        error_log('Payment processing error: ' . $errorMessage);
        
        sendJsonResponse(false, 'Terjadi kesalahan saat memproses pembayaran: ' . $errorMessage, [
            'status' => 'error',
            'code' => 'PAYMENT_PROCESSING_ERROR'
        ]);
    }
}

// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>

