<?php
session_start();
require_once __DIR__ . '/./includes/database.php';

// Pastikan admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $_SESSION['error_message'] = 'Anda harus login untuk mengakses halaman ini.';
    header('Location: login.php');
    exit();
}

// Validasi CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = 'Token keamanan tidak valid. Silakan coba lagi.';
    header('Location: profile.php');
    exit();
}

$adminId = $_SESSION['admin_id'];
$action = $_GET['action'] ?? '';

try {
    if ($action === 'update_username') {
        // Handle update username
        $newUsername = trim($_POST['username'] ?? '');
        
        // Validasi username
        if (empty($newUsername) || strlen($newUsername) < 3) {
            throw new Exception('Username minimal 3 karakter');
        }
        
        // Cek apakah username sudah digunakan
        $stmt = $conn->prepare("SELECT id FROM admin WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $newUsername, $adminId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('Username sudah digunakan');
        }
        $stmt->close();
        
        // Update username
        $updateStmt = $conn->prepare("UPDATE admin SET username = ? WHERE id = ?");
        $updateStmt->bind_param("si", $newUsername, $adminId);
        
        if ($updateStmt->execute()) {
            $_SESSION['admin_username'] = $newUsername;
            $_SESSION['success_message'] = 'Username berhasil diubah';
        } else {
            throw new Exception('Gagal memperbarui username');
        }
        
        $updateStmt->close();
        
    } elseif ($action === 'update_password') {
        // Handle update password
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validasi input
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            throw new Exception('Semua field harus diisi');
        }

        if ($newPassword !== $confirmPassword) {
            throw new Exception('Password baru dan konfirmasi password tidak cocok');
        }

        if (strlen($newPassword) < 6) {
            throw new Exception('Password minimal 6 karakter');
        }

        // Verifikasi password saat ini
        $stmt = $conn->prepare("SELECT password FROM admin WHERE id = ?");
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        $stmt->close();

        if (!$admin || !password_verify($currentPassword, $admin['password'])) {
            throw new Exception('Password saat ini salah');
        }

        // Update password baru
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
        $updateStmt->bind_param("si", $hashedPassword, $adminId);
        
        if ($updateStmt->execute()) {
            $_SESSION['success_message'] = 'Password berhasil diubah';
        } else {
            throw new Exception('Gagal memperbarui password');
        }
        
        $updateStmt->close();
    } else {
        throw new Exception('Aksi tidak valid');
    }
    
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
}

header('Location: profile.php');
exit();
?>

