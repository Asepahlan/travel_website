<?php
require_once __DIR__ . '/../config/database.php';
session_start();

// Ensure admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $_SESSION['error_message'] = 'Anda harus login untuk mengakses halaman ini.';
    header('Location: login.php');
    exit;
}

// --- CSRF Protection Check ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'Sesi tidak valid atau permintaan tidak sah. Silakan coba lagi.';
        header('Location: manage_jadwal.php'); // Redirect back
        exit;
    }
} else {
    // Redirect if not POST
    header('Location: manage_jadwal.php');
    exit;
}
// --- End CSRF Protection Check ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $id_jadwal = isset($_POST['id_jadwal']) ? (int)$_POST['id_jadwal'] : null;
    $id_kota_asal = (int)$_POST['id_kota_asal'];
    $id_kecamatan_asal = (int)$_POST['id_kecamatan_asal'];
    $id_kota_tujuan = (int)$_POST['id_kota_tujuan'];
    $id_kecamatan_tujuan = (int)$_POST['id_kecamatan_tujuan'];
    $tanggal_berangkat = $_POST['tanggal_berangkat'];
    $waktu_berangkat = $_POST['waktu_berangkat'];
    $harga = (int)$_POST['harga'];
    $estimasi_jam = (int)$_POST['estimasi_jam'];
    $keterangan = $_POST['keterangan'] ?? '';
    $id_layout_kursi = (int)$_POST['id_layout_kursi'];
    $kursi_tersedia = 0; // Default value, can be updated later

    // Validate layout exists
    $check_layout = $conn->query("SELECT id_layout FROM layout_kursi WHERE id_layout = $id_layout_kursi");
    if ($check_layout->num_rows === 0) {
        $_SESSION['error_message'] = 'Layout kursi yang dipilih tidak valid.';
        header('Location: manage_jadwal.php');
        exit;
    }

    // Basic Validation
    if (empty($id_kota_asal) || empty($id_kecamatan_asal) || empty($id_kota_tujuan) || empty($id_kecamatan_tujuan) || 
        empty($tanggal_berangkat) || empty($waktu_berangkat) || $harga <= 0 || $estimasi_jam <= 0) {
        $_SESSION['error_message'] = 'Semua field wajib diisi dengan benar.';
        header('Location: manage_jadwal.php');
        exit;
    }

    try {
        if ($action === 'add') {
            // Insert new jadwal
            $sql = "INSERT INTO jadwal (id_kota_asal, id_kecamatan_asal, id_kota_tujuan, id_kecamatan_tujuan, 
                    tanggal_berangkat, waktu_berangkat, harga, estimasi_jam, keterangan, kursi_tersedia, id_layout_kursi) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("iiiissiisii", $id_kota_asal, $id_kecamatan_asal, $id_kota_tujuan, $id_kecamatan_tujuan, 
                $tanggal_berangkat, $waktu_berangkat, $harga, $estimasi_jam, $keterangan, $kursi_tersedia, $id_layout_kursi);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Jadwal baru berhasil ditambahkan.';
            } else {
                throw new Exception('Gagal menambahkan jadwal: ' . $stmt->error);
            }
            $stmt->close();
        } elseif ($action === 'edit' && $id_jadwal) {
            // Update existing jadwal
            $sql = "UPDATE jadwal SET 
                        id_kota_asal = ?, id_kecamatan_asal = ?, id_kota_tujuan = ?, id_kecamatan_tujuan = ?, 
                        tanggal_berangkat = ?, waktu_berangkat = ?, harga = ?, estimasi_jam = ?, 
                        keterangan = ?, id_layout_kursi = ?
                    WHERE id_jadwal = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("iiiissiiisi", $id_kota_asal, $id_kecamatan_asal, $id_kota_tujuan, $id_kecamatan_tujuan, 
                $tanggal_berangkat, $waktu_berangkat, $harga, $estimasi_jam, $keterangan, $id_layout_kursi, $id_jadwal);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $_SESSION['success_message'] = 'Jadwal berhasil diperbarui.';
                } else {
                    $_SESSION['warning_message'] = 'Tidak ada perubahan data jadwal.';
                }
            } else {
                throw new Exception('Gagal memperbarui jadwal: ' . $stmt->error);
            }
            $stmt->close();
        } else {
            throw new Exception('Aksi tidak valid atau ID jadwal tidak ditemukan.');
        }
    } catch (Exception $e) {
        // Check for duplicate entry error (if there's a unique constraint on route+datetime)
        // if ($conn->errno === 1062) { ... }
        $_SESSION['error_message'] = 'Terjadi kesalahan: ' . $e->getMessage();
    }

    $conn->close();
    header('Location: manage_jadwal.php');
    exit;

}
// No need for the final else block as the CSRF check already handles non-POST requests
?>

