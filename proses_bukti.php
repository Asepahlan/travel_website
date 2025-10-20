<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON content type
header('Content-Type: application/json');

// Log function
function log_message($message, $data = null) {
    $log = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    if ($data !== null) {
        $log .= 'Data: ' . print_r($data, true) . PHP_EOL;
    }
    file_put_contents('payment_upload.log', $log, FILE_APPEND);
}

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'db_travel';

// Create database connection
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8
    $conn->set_charset("utf8mb4");
    
    // Log request
    log_message('New payment submission', [
        'method' => $_SERVER['REQUEST_METHOD'],
        'post_data' => $_POST,
        'files' => $_FILES
    ]);

    // Check if the request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Start transaction
    $conn->begin_transaction();

    // Get and validate POST data
    $kode_booking = trim($_POST['kode_booking'] ?? '');
    $bank_tujuan = trim($_POST['bank_tujuan'] ?? '');
    $nama_pengirim = trim($_POST['nama_pengirim'] ?? '');
    $jumlah_dibayar = str_replace(['.', ','], '', $_POST['jumlah_dibayar'] ?? '0');
    $waktu_transfer = trim($_POST['waktu_transfer'] ?? '');
    $catatan = trim($_POST['catatan'] ?? '');
    
    // Log received data
    log_message('Processing payment', [
        'kode_booking' => $kode_booking,
        'bank_tujuan' => $bank_tujuan,
        'nama_pengirim' => $nama_pengirim,
        'jumlah_dibayar' => $jumlah_dibayar,
        'waktu_transfer' => $waktu_transfer,
        'catatan' => $catatan
    ]);

    // Validate required fields
    $missing_fields = [];
    if (empty($kode_booking)) $missing_fields[] = 'kode_booking';
    if (empty($bank_tujuan)) $missing_fields[] = 'bank_tujuan';
    if (empty($nama_pengirim)) $missing_fields[] = 'nama_pengirim';
    if (empty($jumlah_dibayar)) $missing_fields[] = 'jumlah_dibayar';
    if (empty($waktu_transfer)) $missing_fields[] = 'waktu_transfer';
    
    if (!empty($missing_fields)) {
        log_message('Missing required fields', $missing_fields);
        throw new Exception('Semua field harus diisi. Field yang kosong: ' . implode(', ', $missing_fields));
    }

    // Check if file was uploaded without errors
    if (!isset($_FILES['bukti_transfer']) || $_FILES['bukti_transfer']['error'] !== UPLOAD_ERR_OK) {
        $error_code = $_FILES['bukti_transfer']['error'] ?? 'unknown';
        $error_message = 'Terjadi kesalahan saat mengunggah file. Kode error: ' . $error_code;
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = 'Ukuran file terlalu besar. Maksimal 2MB';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = 'File hanya terunggah sebagian';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = 'Tidak ada file yang diunggah';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message = 'Folder temporary tidak ditemukan';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message = 'Gagal menulis file ke disk';
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_message = 'Upload dihentikan oleh ekstensi PHP';
                break;
        }
        log_message('File upload error', [
            'error_code' => $error_code,
            'error_message' => $error_message,
            'file_info' => $_FILES['bukti_transfer'] ?? null
        ]);
        throw new Exception($error_message);
    }

    $file = $_FILES['bukti_transfer'];
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $file_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_type, $allowed_types)) {
        throw new Exception('Format file tidak didukung. Harap unggah file JPG, JPEG, atau PNG.');
    }
    
    // Validate file size (max 5MB)
    $max_size = 5 * 1024 * 1024; // 5MB in bytes
    if ($file['size'] > $max_size) {
        throw new Exception('Ukuran file terlalu besar. Maksimal 5MB.');
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'bukti_' . time() . '_' . uniqid() . '.' . $file_extension;
    $upload_dir = __DIR__ . '/../uploads/bukti_transfer/';
    
    // Create uploads directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $target_path = $upload_dir . $new_filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        throw new Exception('Gagal menyimpan file bukti transfer');
    }
    
    // Insert into database
    $stmt = $conn->prepare("INSERT INTO konfirmasi_pembayaran (kode_booking, bank_tujuan, nama_pengirim, jumlah_dibayar, tanggal_transfer, bukti_transfer, status, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, 'menunggu', NOW())");
    
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    // Bind parameters and execute
    $relative_path = 'uploads/bukti_transfer/' . $new_filename;
    $stmt->bind_param('sssdss', 
        $kode_booking,
        $bank_tujuan,
        $nama_pengirim,
        $jumlah_dibayar,
        $waktu_transfer,
        $relative_path
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Gagal menyimpan data ke database: ' . $stmt->error);
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Bukti pembayaran berhasil diupload',
        'data' => [
            'kode_booking' => $kode_booking,
            'bukti_transfer' => $relative_path
        ]
    ]);
    
    // Update status in reservasi table
    $update = $conn->prepare("UPDATE reservasi SET status_pembayaran = 'menunggu_konfirmasi' WHERE kode_booking = ?");
    $update->bind_param('s', $kode_booking);
    
    if (!$update->execute()) {
        throw new Exception('Gagal memperbarui status reservasi: ' . $update->error);
    }
    
    // Commit transaction
    $conn->commit();
    
    // Set success response
    $response = [
        'status' => 'success',
        'message' => 'Bukti pembayaran berhasil dikirim!'
    ];
    
} catch (Exception $e) {
    // Initialize response array
    $response = [
        'status' => 'error',
        'message' => $e->getMessage(),
        'debug' => []
    ];
    
    // Add debug info in development
    if (ini_get('display_errors')) {
        $response['debug'] = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTrace()
        ];
    }
    
    // Rollback transaction if available
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->rollback();
    }
    
    // Log the error with more context
    log_message('Error processing payment', [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'post_data' => $_POST ?? null,
        'files' => array_map(function($file) {
            return [
                'name' => $file['name'] ?? null,
                'type' => $file['type'] ?? null,
                'size' => $file['size'] ?? null,
                'error' => $file['error'] ?? null
            ];
        }, $_FILES ?? [])
    ]);
    
    // Set appropriate HTTP status code
    $http_status = 400; // Bad Request
    if ($e->getCode() >= 400 && $e->getCode() < 600) {
        $http_status = $e->getCode();
    }
    
    http_response_code($http_status);
    header('Content-Type: application/json');
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
    // Exit to prevent any additional output
    exit(1);
}

