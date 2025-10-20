<?php
require_once __DIR__ . '/config/database.php';
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Use the global database connection from config/database.php
if (!isset($GLOBALS['conn']) || !($GLOBALS['conn'] instanceof mysqli)) {
    error_log('Database connection not properly initialized');
    sendResponse(false, 'Database connection error. Please try again later.');
}

$conn = $GLOBALS['conn'];

// Check if it's an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

/**
 * Get seat availability for a specific schedule
 */
function getSeatAvailability($conn, $jadwal_id) {
    $stmt = $conn->prepare("SELECT drk.id_kursi 
        FROM detail_reservasi_kursi drk 
        JOIN reservasi r ON drk.id_reservasi = r.id_reservasi 
        WHERE r.id_jadwal = ? 
        AND r.status IN ('pending', 'dibayar')");
    
    $stmt->bind_param("i", $jadwal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booked_seats = [];
    
    while ($row = $result->fetch_assoc()) {
        $booked_seats[] = (int)$row['id_kursi'];
    }
    
    $stmt->close();
    return $booked_seats;
}

/**
 * Send JSON response or redirect with message
 */
function sendResponse($success, $message = '', $data = []) {
    global $isAjax;
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data
    ];
    
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        if (!$success) {
            $_SESSION['error_message'] = $message;
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    }
    exit;
}

// Validate CSRF token
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || 
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    sendResponse(false, 'Token CSRF tidak valid');
}

// Validate seat session
$seat_session_id = $_POST['seat_session_id'] ?? '';
if (empty($seat_session_id) || empty($_SESSION['seat_selection'][$seat_session_id])) {
    sendResponse(false, 'Sesi pemilihan kursi tidak valid');
}

$seat_session = &$_SESSION['seat_selection'][$seat_session_id];

// Check if session is expired
if (time() > $seat_session['expires']) {
    unset($_SESSION['seat_selection'][$seat_session_id]);
    sendResponse(false, 'Sesi pemilihan kursi telah berakhir. Silakan refresh halaman.');
}

// Validate required fields
$required = ['jadwal_id', 'alamat_jemput', 'selected_seats'];
$missing = [];

foreach ($required as $field) {
    if (empty($_POST[$field])) {
        $missing[] = $field;
    }
}

if (!empty($missing)) {
    sendResponse(false, 'Mohon lengkapi semua field yang diperlukan: ' . implode(', ', $missing));
}

try {
    // Database connection is already established at the top of the file
    $conn->set_charset('utf8mb4');
    
    // Get and validate input
    $jadwal_id = (int)$_POST['jadwal_id'];
    $alamat_jemput = trim($_POST['alamat_jemput']);
    $selected_seats = json_decode($_POST['selected_seats'], true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($selected_seats) || empty($selected_seats)) {
        throw new Exception('Pilih setidaknya satu kursi');
    }
    
    // Validate number of seats (max 10 per booking)
    if (count($selected_seats) > 10) {
        throw new Exception('Maksimal pemesanan 10 kursi sekaligus');
    }
    
    // Get schedule details
    $stmt_jadwal = $conn->prepare("SELECT 
            j.*, 
            ka.nama_kota AS nama_kota_asal,
            kca.nama_kecamatan AS nama_kecamatan_asal,
            kt.nama_kota AS nama_kota_tujuan,
            kct.nama_kecamatan AS nama_kecamatan_tujuan,
            j.harga AS harga_per_kursi
        FROM jadwal j
        JOIN kota ka ON j.id_kota_asal = ka.id_kota
        JOIN kecamatan kca ON j.id_kecamatan_asal = kca.id_kecamatan
        JOIN kota kt ON j.id_kota_tujuan = kt.id_kota
        JOIN kecamatan kct ON j.id_kecamatan_tujuan = kct.id_kecamatan
        WHERE j.id_jadwal = ?");
    $stmt_jadwal->bind_param("i", $jadwal_id);
    $stmt_jadwal->execute();
    $jadwal = $stmt_jadwal->get_result()->fetch_assoc();
    $stmt_jadwal->close();
    
    if (!$jadwal) {
        throw new Exception('Jadwal tidak ditemukan');
    }
    
    $harga_per_kursi = $jadwal['harga_per_kursi'];
    $total_harga = count($selected_seats) * $harga_per_kursi;
    
    // Check seat availability
    $booked_seats = getSeatAvailability($conn, $jadwal_id);
    $available_seats = [];
    
    // Validate each selected seat
    foreach ($selected_seats as $seat_id) {
        $seat_id = (int)$seat_id;
        if (in_array($seat_id, $booked_seats)) {
            throw new Exception("Kursi #$seat_id sudah tidak tersedia. Silakan pilih kursi lain.");
        }
        $available_seats[] = $seat_id;
    }
    
    // Double check seat availability right before booking
    $booked_seats_again = getSeatAvailability($conn, $jadwal_id);
    foreach ($available_seats as $seat_id) {
        if (in_array($seat_id, $booked_seats_again)) {
            throw new Exception('Kursi yang dipilih sudah tidak tersedia. Silakan refresh halaman dan coba lagi.');
        }
    }
    
    // Verify seats exist in database
    $placeholders = rtrim(str_repeat('?,', count($available_seats)), ',');
    $stmt_seats = $conn->prepare("SELECT id_kursi, nomor_kursi FROM kursi WHERE id_kursi IN ($placeholders) ORDER BY id_kursi");
    if (!$stmt_seats) {
        throw new Exception('Gagal memeriksa ketersediaan kursi.');
    }
    
    $types = str_repeat('i', count($available_seats));
    $stmt_seats->bind_param($types, ...$available_seats);
    
    if (!$stmt_seats->execute()) {
        throw new Exception('Gagal memeriksa ketersediaan kursi.');
    }
    
    $result = $stmt_seats->get_result();
    $valid_seats = [];
    
    while ($row = $result->fetch_assoc()) {
        $valid_seats[] = (int)$row['id_kursi'];
    }
    
    $stmt_seats->close();
    
    // Check if all seats are valid
    if (count($valid_seats) !== count($available_seats)) {
        throw new Exception('Beberapa kursi yang dipilih tidak valid.');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Generate booking code
        $kode_booking = 'BK' . date('Ymd') . strtoupper(substr(uniqid(), -6));
        $status_pembayaran = 'belum_bayar';
        $tanggal_pemesanan = date('Y-m-d H:i:s');
        
        // Insert into reservasi table
        $stmt_reservasi = $conn->prepare("INSERT INTO reservasi (
            kode_booking, id_jadwal, status, 
            total_harga, alamat_jemput, nama_pemesan, no_hp, email, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        if (!$stmt_reservasi) {
            throw new Exception('Gagal mempersiapkan query reservasi: ' . $conn->error);
        }
        
        // Get user info from session or form
        $nama_pemesan = $_SESSION['user_name'] ?? 'Pelanggan';
        $nomor_telepon = $_SESSION['user_phone'] ?? '';
        $email_pemesan = $_SESSION['user_email'] ?? '';
        $status = 'pending'; // Default status for new reservations
        
        $stmt_reservasi->bind_param("sisdssss", 
            $kode_booking, 
            $jadwal_id, 
            $status,
            $total_harga, 
            $alamat_jemput,
            $nama_pemesan,
            $nomor_telepon,
            $email_pemesan
        );
        
        if (!$stmt_reservasi->execute()) {
            throw new Exception('Gagal menyimpan data reservasi: ' . $stmt_reservasi->error);
        }
        
        $id_reservasi = $conn->insert_id;
        $stmt_reservasi->close();
        
        // Insert seat details
        $stmt_detail = $conn->prepare("INSERT INTO detail_reservasi_kursi (id_reservasi, id_kursi, harga) VALUES (?, ?, ?)");
        if (!$stmt_detail) {
            throw new Exception('Gagal mempersiapkan query detail kursi: ' . $conn->error);
        }
        
        foreach ($available_seats as $id_kursi) {
            $stmt_detail->bind_param("iid", $id_reservasi, $id_kursi, $harga_per_kursi);
            if (!$stmt_detail->execute()) {
                throw new Exception('Gagal menyimpan data kursi: ' . $stmt_detail->error);
            }
        }
        
        $stmt_detail->close();
        
        // Commit transaction
        $conn->commit();
        
        // Store booking data in session for confirmation page
        $_SESSION['booking_data'] = [
            'id_reservasi' => $id_reservasi,
            'kode_booking' => $kode_booking,
            'jadwal_id' => $jadwal_id,
            'alamat_jemput' => $alamat_jemput,
            'selected_seats' => $available_seats,
            'harga_per_kursi' => $harga_per_kursi,
            'total_harga' => $total_harga,
            'status_pembayaran' => $status_pembayaran,
            'tanggal_pemesanan' => $tanggal_pemesanan,
            'expires' => time() + 1800 // 30 minutes
        ];
        
        // Clear seat selection session
        unset($_SESSION['seat_selection'][$seat_session_id]);
        
        // Return success response
        sendResponse(true, 'Pemesanan berhasil', [
            'redirect' => 'booking_success.php',
            'booking_code' => $kode_booking
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    // Log error
    error_log('Error in ./process/seat_selection.php: ' . $e->getMessage());
    
    // Send error response
    sendResponse(false, $e->getMessage());
} finally {
    // Close database connection if it was opened
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>

