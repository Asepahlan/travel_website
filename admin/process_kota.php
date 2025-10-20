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
        header('Location: manage_kota.php'); // Redirect back
        exit;
    }
} else {
    // Redirect if not POST
    header('Location: manage_kota.php');
    exit;
}
// --- End CSRF Protection Check ---


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $nama_kota = isset($_POST['nama_kota']) ? trim(htmlspecialchars($_POST['nama_kota'])) : null;
    $id_kota = isset($_POST['id_kota']) ? (int)$_POST['id_kota'] : null;

    if (empty($nama_kota)) {
        $_SESSION['error_message'] = 'Nama kota tidak boleh kosong.';
        header('Location: manage_kota.php');
        exit;
    }

    try {
        if ($action === 'add') {
            // Add new city
            $sql = "INSERT INTO kota (nama_kota) VALUES (?)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) throw new Exception('Gagal menyiapkan statement: ' . $conn->error);
            $stmt->bind_param("s", $nama_kota);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Kota baru berhasil ditambahkan.';
            } else {
                throw new Exception('Gagal menambahkan kota: ' . $stmt->error);
            }
            $stmt->close();
        } elseif ($action === 'edit' && !empty($id_kota)) {
            // Edit existing city
            $sql = "UPDATE kota SET nama_kota = ? WHERE id_kota = ?";
            $stmt = $conn->prepare($sql);
             if ($stmt === false) throw new Exception('Gagal menyiapkan statement: ' . $conn->error);
            $stmt->bind_param("si", $nama_kota, $id_kota);
             if ($stmt->execute()) {
                 if ($stmt->affected_rows > 0) {
                    $_SESSION['success_message'] = 'Data kota berhasil diperbarui.';
                } else {
                    $_SESSION['success_message'] = 'Tidak ada perubahan pada data kota.'; // Or handle as needed
                }
            } else {
                throw new Exception('Gagal memperbarui kota: ' . $stmt->error);
            }
            $stmt->close();
        } else {
            throw new Exception('Aksi tidak valid atau ID kota tidak ditemukan.');
        }
    } catch (Exception $e) {
        // Check for duplicate entry error (MySQL error code 1062)
        if ($conn->errno === 1062) {
             $_SESSION['error_message'] = 'Nama kota sudah ada. Silakan gunakan nama lain.';
        } else {
            $_SESSION['error_message'] = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }

    $conn->close();
    header('Location: manage_kota.php');
    exit;

} 
// No need for the final else block as the CSRF check already handles non-POST requests
?>

