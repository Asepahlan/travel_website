<?php
require_once __DIR__ . '/../config/database.php';
session_start();

// Ensure admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $_SESSION['error_message'] = 'Anda harus login untuk mengakses halaman ini.';
    header('Location: login.php');
    exit;
}

// Check if required parameters are set
if (!isset($_POST['type']) || !isset($_POST['id']) || !isset($_POST['csrf_token'])) {
    $_SESSION['error_message'] = 'Parameter tidak valid.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

// CSRF Protection
if (!isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = 'Token keamanan tidak valid.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

$type = $_POST['type'];
$id = (int)$_POST['id'];
$layout_id = isset($_GET['layout_id']) ? (int)$_GET['layout_id'] : null;

if ($id <= 0) {
    $_SESSION['error_message'] = 'ID tidak valid.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

try {
    $conn->begin_transaction();
    
    switch ($type) {
        case 'kursi':
            // Delete kursi
            $stmt = $conn->prepare("DELETE FROM kursi WHERE id_kursi = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['success_message'] = 'Kursi berhasil dihapus.';
            $redirect = $layout_id ? "manage_kursi.php?layout_id=" . $layout_id : 'manage_layout.php';
            break;
            
        case 'layout':
            // First, delete all schedules associated with this layout
            $stmt = $conn->prepare("DELETE FROM jadwal WHERE id_layout_kursi = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            // Then, delete all seats associated with this layout
            $stmt = $conn->prepare("DELETE FROM kursi WHERE id_layout = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            // Then delete the layout itself
            $stmt = $conn->prepare("DELETE FROM layout_kursi WHERE id_layout = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['success_message'] = 'Layout berhasil dihapus.';
            $redirect = 'manage_layout.php';
            break;
            
        case 'kota':
            // First, delete all kecamatan associated with this kota
            $stmt = $conn->prepare("DELETE FROM kecamatan WHERE id_kota = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            // Then delete the kota itself
            $stmt = $conn->prepare("DELETE FROM kota WHERE id_kota = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['success_message'] = 'Kota berhasil dihapus.';
            $redirect = 'manage_kota.php';
            break;
            
        case 'kecamatan':
            // Delete the kecamatan itself
            $stmt = $conn->prepare("DELETE FROM kecamatan WHERE id_kecamatan = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['success_message'] = 'Kecamatan berhasil dihapus.';
            $redirect = 'manage_kota.php';
            break;
    }
    
    $conn->commit();
    
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = 'Terjadi kesalahan: ' . $e->getMessage();
    $redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';
}

// Redirect back
header('Location: ' . $redirect);
exit;
?>

