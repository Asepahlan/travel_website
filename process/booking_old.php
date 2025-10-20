<?php
// Aktifkan error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mulai session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// Fungsi untuk menghentikan eksekusi dan mengembalikan response JSON
function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Fungsi untuk menangani error
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

// Check if this is an AJAX request
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Set appropriate content type based on request type
if ($is_ajax) {
    header('Content-Type: application/json');
}

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'errors' => [],
    'debug' => []
];

// Log request data
error_log("=== NEW BOOKING REQUEST ===");
error_log("POST Data: " . print_r($_POST, true));
error_log("SESSION Data: " . print_r($_SESSION, true));

try {
    // Validate CSRF token
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed', 405);
    }

    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error_msg = 'Sesi tidak valid atau permintaan tidak sah. Silakan refresh halaman dan coba lagi.';
        error_log("CSRF Validation Failed");
        error_log("Session Token: " . ($_SESSION['csrf_token'] ?? 'Not Set'));
        error_log("POST Token: " . ($_POST['csrf_token'] ?? 'Not Set'));
        throw new Exception($error_msg, 403);
    }

    // Initialize processed_forms array if not exists
    if (!isset($_SESSION['processed_forms'])) {
        $_SESSION['processed_forms'] = [];
    }

    // Validasi input yang diperlukan
    $required_fields = [
        'form_submission_id' => 'ID Pengiriman Form',
        'nama_pemesan' => 'Nama Pemesan',
        'nomor_telepon_pemesan' => 'Nomor Telepon',
        'email_pemesan' => 'Email',
        'jadwal_id' => 'Jadwal',
        'selected_seats' => 'Kursi yang Dipilih'
    ];

    $errors = [];
    $input_data = [];

    // Validasi field yang diperlukan
    foreach ($required_fields as $field => $label) {
        if (empty($_POST[$field])) {
            $errors[] = "$label harus diisi";
        } else {
            $input_data[$field] = trim($_POST[$field]);
        }
    }

    // Validasi format email
    if (!empty($input_data['email_pemesan']) && !filter_var($input_data['email_pemesan'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    }

    // Validasi nomor telepon (minimal 10 digit)
    if (!empty($input_data['nomor_telepon_pemesan']) && !preg_match('/^[0-9]{10,}$/', $input_data['nomor_telepon_pemesan'])) {
        $errors[] = "Nomor telepon harus minimal 10 digit angka";
    }

    // Jika ada error, tampilkan
    if (!empty($errors)) {
        $response['errors'] = $errors;
        $response['message'] = 'Validasi gagal. Silakan periksa input Anda.';
        $response['success'] = false;
        
        if ($is_ajax) {
            json_response($response, 400);
        } else {
            $_SESSION['error_message'] = $response['message'];
            $_SESSION['form_errors'] = $response['errors'];
            header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'index.php');
            exit;
        }
    }

    $form_submission_id = $input_data['form_submission_id'];
    $jadwal_id = (int)$input_data['jadwal_id'];

    // Clean up old form submissions (older than 30 minutes)
    $thirty_minutes_ago = time() - 1800;
    foreach ($_SESSION['processed_forms'] as $id => $data) {
        if (isset($data['timestamp']) && $data['timestamp'] < $thirty_minutes_ago) {
            unset($_SESSION['processed_forms'][$id]);
        }
    }

    // Check for duplicate form submission
    if (isset($_SESSION['processed_forms'][$form_submission_id])) {
        $form_status = $_SESSION['processed_forms'][$form_submission_id]['status'] ?? null;
        $booking_code = $_SESSION['processed_forms'][$form_submission_id]['booking_code'] ?? null;
        
        // Clear old session data older than 5 minutes
        if (isset($_SESSION['processed_forms'][$form_submission_id]['timestamp']) && 
            (time() - $_SESSION['processed_forms'][$form_submission_id]['timestamp']) > 300) {
            unset($_SESSION['processed_forms'][$form_submission_id]);
        }
        // Redirect if form already completed
        else if ($form_status === 'completed' && $booking_code) {
            $response = [
                'success' => false,
                'message' => 'Pesanan Anda sudah diproses. Mengalihkan...',
                'reload' => true,
                'redirect' => 'booking_success.php?code=' . urlencode($booking_code)
            ];
            header('Content-Type: application/json');
            http_response_code(200);
            echo json_encode($response);
            exit;
        }
    }

    // Mark form as processing
    if (!isset($_SESSION['processed_forms'][$form_submission_id])) {
        $_SESSION['processed_forms'][$form_submission_id] = [
            'status' => 'processing',
            'timestamp' => time(),
            'created_at' => date('Y-m-d H:i:s')
        ];
        error_log("Marked form as processing: " . $form_submission_id);
    } else {
        error_log("Form already marked as processing: " . $form_submission_id);
    }

    // Log all POST data for debugging
    error_log("=== DEBUGGING INFO ===");
    error_log("Raw POST data: " . print_r($_POST, true));
    error_log("Session data: " . print_r($_SESSION, true));
    error_log("Is AJAX request: " . ($is_ajax ? 'Yes' : 'No'));
    error_log("Form submission ID: " . $form_submission_id);
    
    // Get and validate form data
    $jadwal_id = $input_data['jadwal_id'] ?? null;
    $selected_seats_json = $input_data['selected_seats'] ?? '';
    
    // Validasi jadwal_id
    if (empty($jadwal_id) || !is_numeric($jadwal_id)) {
        throw new Exception('ID jadwal tidak valid', 400);
    }
    $selected_seats = [];
    
    // Log the raw selected_seats value
    error_log("Raw selected_seats: " . $selected_seats_json);
    
    // Decode the JSON
    if (!empty($selected_seats_json)) {
        $selected_seats = json_decode($selected_seats_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg());
            $selected_seats = [];
        }
    }
    
    // Log the decoded seats
    error_log("Decoded selected_seats: " . print_r($selected_seats, true));
    
    // Get form data
    $nama_pemesan = trim(htmlspecialchars($_POST['nama_pemesan'] ?? ''));
    $nomor_telepon_pemesan = preg_replace('/[^0-9]/', '', $_POST['nomor_telepon_pemesan'] ?? '');
    $email_pemesan = trim($_POST['email_pemesan'] ?? '');
    $catatan_pemesan = trim(htmlspecialchars($_POST['catatan_pemesan'] ?? ''));
    $alamat_jemput = trim(htmlspecialchars($_POST['alamat_jemput'] ?? ''));
    
    // Pastikan alamat jemput tidak kosong dan bukan teks default
    if (empty($alamat_jemput) || $alamat_jemput === 'Alamat penjemputan akan dikonfirmasi melalui WhatsApp') {
        $errors[] = 'Harap isi alamat penjemputan yang valid';
    }

    // Validate required fields
    $validation_errors = [];
    
    if (empty($nama_pemesan) || strlen($nama_pemesan) < 3) {
        $validation_errors[] = 'Nama pemesan minimal 3 karakter';
    }
    
    if (empty($nomor_telepon_pemesan) || !preg_match('/^[0-9]{10,13}$/', $nomor_telepon_pemesan)) {
        $validation_errors[] = 'Nomor telepon harus 10-13 digit angka';
    } elseif (!preg_match('/^8/', $nomor_telepon_pemesan)) {
        $nomor_telepon_pemesan = '8' . ltrim($nomor_telepon_pemesan, '0');
    }
    
    if (empty($email_pemesan) || !filter_var($email_pemesan, FILTER_VALIDATE_EMAIL)) {
        $validation_errors[] = 'Format email tidak valid';
    }
    
    if (empty($alamat_jemput) || strlen($alamat_jemput) < 10) {
        $validation_errors[] = 'Alamat penjemputan minimal 10 karakter';
    }
    
    // Jika ada error validasi, kembalikan response error
    if (!empty($validation_errors)) {
        $response['errors'] = $validation_errors;
        $response['message'] = 'Validasi gagal. Silakan periksa input Anda.';
        $response['success'] = false;
        
        if ($is_ajax) {
            json_response($response, 400);
        } else {
            $_SESSION['error_message'] = $response['message'];
            $_SESSION['form_errors'] = $response['errors'];
            header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'index.php');
            exit;
        }
    }
    
    if (empty($jadwal_id)) {
        $errors[] = 'Jadwal tidak valid';
    }
    
    if (empty($selected_seats) || !is_array($selected_seats)) {
        $errors[] = 'Pilih minimal satu kursi';
    }

    if (!empty($errors)) {
        throw new Exception(implode('\n', $errors), 400);
    }

    // Calculate total price
    $total_harga = count($selected_seats) * $harga_per_kursi;
    $waktu_expired = date('Y-m-d H:i:s', strtotime('+30 minutes'));

    // Start database transaction
    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new Exception('Koneksi database tidak valid');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Log start of transaction
        error_log("Starting database transaction");
        
        // Check if schedule exists and has available seats
        $schedule_sql = "SELECT * FROM jadwal WHERE id_jadwal = ? FOR UPDATE";
        $stmt_schedule = $conn->prepare($schedule_sql);
        if ($stmt_schedule === false) {
            throw new Exception('Gagal memeriksa jadwal: ' . $conn->error);
        }
        
        $stmt_schedule->bind_param('i', $jadwal_id);
        if (!$stmt_schedule->execute()) {
            throw new Exception('Gagal mengeksekusi query jadwal: ' . $stmt_schedule->error);
        }
        
        $schedule = $stmt_schedule->get_result()->fetch_assoc();
        $stmt_schedule->close();
        
        if (!$schedule) {
            throw new Exception('Jadwal tidak ditemukan');
        }
        
        // Parse selected seats
        $selected_seats = [];
        if (!empty($_POST['selected_seats'])) {
            $selected_seats = json_decode($_POST['selected_seats'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Format kursi tidak valid');
            }
        }
        
        if (empty($selected_seats)) {
            throw new Exception('Tidak ada kursi yang dipilih');
        }
        
        // Check seat availability
        $seat_ids = array_column($selected_seats, 'id_kursi');
        
        // Validate that we have seat IDs
        if (empty($seat_ids)) {
            throw new Exception('Tidak ada ID kursi yang valid dalam data yang dipilih');
        }
        
        // Create placeholders for the IN clause
        $placeholders = rtrim(str_repeat('?,', count($seat_ids)), ',');
        
        $seat_check_sql = "SELECT k.id_kursi, k.nomor_kursi, k.status, 
                          EXISTS (
                              SELECT 1 FROM detail_reservasi_kursi drk 
                              JOIN reservasi r ON drk.id_reservasi = r.id_reservasi 
                              WHERE drk.id_kursi = k.id_kursi 
                              AND r.id_jadwal = ? 
                              AND r.status IN ('pending', 'dibayar')
                          ) as is_booked
                          FROM kursi k 
                          WHERE k.id_kursi IN ($placeholders)";
        
        $stmt_seat_check = $conn->prepare($seat_check_sql);
        if ($stmt_seat_check === false) {
            throw new Exception('Gagal memeriksa ketersediaan kursi: ' . $conn->error);
        }
        
        // Bind parameters dynamically
        $param_types = 'i' . str_repeat('i', count($seat_ids));
        $params = array_merge([$jadwal_id], $seat_ids);
        $stmt_seat_check->bind_param($param_types, ...$params);
        
        if (!$stmt_seat_check->execute()) {
            throw new Exception('Gagal mengeksekusi pengecekan kursi: ' . $stmt_seat_check->error);
        }
        
        $seats_result = $stmt_seat_check->get_result();
        $unavailable_seats = [];
        
        while ($seat = $seats_result->fetch_assoc()) {
            if ($seat['status'] !== 'tersedia' || $seat['is_booked']) {
                $unavailable_seats[] = $seat['nomor_kursi'];
            }
        }
        $stmt_seat_check->close();
        
        if (!empty($unavailable_seats)) {
            throw new Exception('Kursi ' . implode(', ', $unavailable_seats) . ' sudah tidak tersedia. Silakan pilih kursi lain.');
        }
        
        // Generate booking code
        $booking_code = 'TRV' . strtoupper(uniqid());
        $now = date('Y-m-d H:i:s');
        
        // Calculate total price
        $total_price = $schedule['harga'] * count($selected_seats);
        
        // Insert booking
        $insert_booking_sql = "INSERT INTO reservasi (
            kode_booking, id_jadwal, nama_pemesan, no_hp, email, 
            alamat_jemput, status, total_harga, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)";
        
        $stmt_booking = $conn->prepare($insert_booking_sql);
        if ($stmt_booking === false) {
            throw new Exception('Gagal menyiapkan pernyataan booking: ' . $conn->error);
        }
        
        // Bind parameters one by one to avoid reference issues
        // Variables are already defined above from $_POST
        
        $stmt_booking->bind_param('sissssdss', 
            $booking_code,
            $jadwal_id,
            $nama_pemesan,
            $nomor_telepon_pemesan,  // Changed from $no_hp to $nomor_telepon_pemesan
            $email_pemesan,          // Changed from $email to $email_pemesan
            $alamat_jemput,
            $total_price,
            $now,
            $now
        );
        
        // Log the values being bound
        error_log("Inserting booking with values: " . print_r([
            'kode_booking' => $booking_code,
            'id_jadwal' => $jadwal_id,
            'nama_pemesan' => $nama_pemesan,
            'no_hp' => $nomor_telepon_pemesan,
            'email' => $email_pemesan,
            'alamat_jemput' => $alamat_jemput,
            'total_harga' => $total_price,
            'created_at' => $now,
            'updated_at' => $now
        ], true));
        
        if (!$stmt_booking->execute()) {
            throw new Exception('Gagal menyimpan data booking: ' . $stmt_booking->error);
        }
        
        $booking_id = $conn->insert_id;
        $stmt_booking->close();
        
        // Insert seat details
        $insert_seat_sql = "INSERT INTO detail_reservasi_kursi (
            id_reservasi, id_kursi, harga, created_at
        ) VALUES (?, ?, ?, ?)";
        
        $stmt_seat = $conn->prepare($insert_seat_sql);
        if ($stmt_seat === false) {
            throw new Exception('Gagal menyiapkan pernyataan kursi: ' . $conn->error);
        }
        
        foreach ($selected_seats as $seat) {
            $stmt_seat->bind_param(
                'iids',
                $booking_id,
                $seat['id_kursi'],
                $schedule['harga'],
                $now
            );
            
            if (!$stmt_seat->execute()) {
                throw new Exception('Gagal menyimpan detail kursi: ' . $stmt_seat->error);
            }
        }
        $stmt_seat->close();
        
        // Update available seats
        $update_seats_sql = "UPDATE jadwal SET kursi_tersedia = kursi_tersedia - ? WHERE id_jadwal = ?";
        $stmt_update = $conn->prepare($update_seats_sql);
        if ($stmt_update === false) {
            throw new Exception('Gagal menyiapkan pernyataan update kursi: ' . $conn->error);
        }
        
        // Execute the update for available seats
        $seat_count = count($selected_seats);
        $stmt_update->bind_param('ii', $seat_count, $jadwal_id);
        if (!$stmt_update->execute()) {
            throw new Exception('Gagal memperbarui kursi tersedia: ' . $stmt_update->error);
        }
        $stmt_update->close();
        
        // Commit the transaction if we reach this point
        $conn->commit();
        
        // Debug: Log booking success
        error_log("Booking successful! Booking code: " . $booking_code);
        
        // Mark form as completed in session
        $_SESSION['processed_forms'][$form_submission_id] = [
            'status' => 'completed',
            'booking_code' => $booking_code,
            'timestamp' => time()
        ];
        
        // Prepare success response
        $response = [
            'success' => true,
            'message' => 'Pemesanan berhasil diproses!',
            'booking_code' => $booking_code,
            'redirect' => 'booking_success.php?code=' . urlencode($booking_code)
        ];
        
        // Set response header
        header('Content-Type: application/json');
        http_response_code(200);
        echo json_encode($response);
        exit;
        // Kode pemrosesan selesai di sini
    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($conn) && $conn instanceof mysqli) {
            $conn->rollback();
        }
        throw $e; // Re-throw to be caught by outer catch
    }
    
} catch (Exception $e) {
    // Clean up session on error
    if (isset($_SESSION['processed_forms'][$form_submission_id])) {
        unset($_SESSION['processed_forms'][$form_submission_id]);
    }
    
    $code = $e->getCode() ?: 500;
    $error_message = $e->getMessage();
    
    // Log the error
    error_log('Booking Error: ' . $error_message . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    
    $response = [
        'success' => false,
        'message' => $error_message,
        'code' => $code
    ];
    
    if (!$is_ajax) {
        $_SESSION['error_message'] = $error_message;
        header('Location: error.php');
        exit;
    }
}

// Pastikan response selalu dalam format JSON untuk AJAX
if ($is_ajax) {
    http_response_code(isset($response['success']) && $response['success'] ? 200 : 400);
    header('Content-Type: application/json');
    
    // Pastikan response memiliki format yang konsisten
    $jsonResponse = [
        'success' => $response['success'] ?? false,
        'message' => $response['message'] ?? 'Terjadi kesalahan. Silakan coba lagi.',
        'booking_code' => $response['booking_code'] ?? null
    ];
    
    // Tambahkan error details jika ada
    if (isset($response['errors'])) {
        $jsonResponse['errors'] = $response['errors'];
    }
    
    echo json_encode($jsonResponse);
} else {
    // Handle non-AJAX request (seharusnya tidak terjadi)
    if (isset($response['success']) && $response['success'] && isset($response['redirect'])) {
        header('Location: ' . $response['redirect']);
    } else {
        $_SESSION['error_message'] = $response['message'] ?? 'Terjadi kesalahan yang tidak diketahui. Silakan coba lagi nanti.';
        header('Location: error.php');
    }
}

exit;

