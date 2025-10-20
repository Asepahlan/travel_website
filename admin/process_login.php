<?php
require_once __DIR__ . '/../config/database.php';
session_start();

// --- CSRF Protection Check ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        // Token mismatch - handle the error (e.g., redirect back with an error message)
        $_SESSION['error_message'] = 'Sesi tidak valid atau permintaan tidak sah. Silakan coba lagi.';
        header('Location: login.php');
        exit;
    }
} else {
    // If accessed directly via GET, redirect
    header('Location: login.php');
    exit;
}
// --- End CSRF Protection Check ---

$username = $_POST['username'] ?? null;
$password = $_POST['password'] ?? null;

if (empty($username) || empty($password)) {
    $_SESSION['error_message'] = 'Username dan password tidak boleh kosong.';
    header('Location: login.php');
    exit;
}

try {
    $sql = "SELECT id, username, password FROM admin WHERE username = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) throw new Exception('Gagal menyiapkan statement: ' . $conn->error);
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close();

    if ($admin && password_verify($password, $admin['password'])) {
        // Login successful
        session_regenerate_id(true); // Regenerate session ID for security
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        // Regenerate CSRF token after successful login
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        header('Location: index.php'); // Redirect to admin dashboard
        exit;
    } else {
        // Login failed
        $_SESSION['error_message'] = 'Username atau password salah.';
        header('Location: login.php');
        exit;
    }

} catch (Exception $e) {
    $_SESSION['error_message'] = 'Terjadi kesalahan: ' . $e->getMessage();
    header('Location: login.php');
    exit;
} finally {
    $conn->close();
}
?>

