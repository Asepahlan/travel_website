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
        header('Location: manage_kota.php#kecamatan'); // Redirect back to kecamatan tab
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
    $nama_kecamatan = isset($_POST['nama_kecamatan']) ? trim(htmlspecialchars($_POST['nama_kecamatan'])) : null;
    $id_kota = isset($_POST['id_kota']) ? (int)$_POST['id_kota'] : null;
    $id_kecamatan = isset($_POST['id_kecamatan']) ? (int)$_POST['id_kecamatan'] : null;

    // Basic Validation
    if (empty($nama_kecamatan) || empty($id_kota)) {
        $_SESSION['error_message'] = 'Nama kecamatan dan kota tidak boleh kosong.';
        header('Location: manage_kota.php#kecamatan'); // Redirect back to kecamatan tab
        exit;
    }

    try {
        if ($action === 'add') {
            // Add new kecamatan
            $sql = "INSERT INTO kecamatan (nama_kecamatan, id_kota) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) throw new Exception('Gagal menyiapkan statement: ' . $conn->error);
            $stmt->bind_param("si", $nama_kecamatan, $id_kota);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Kecamatan baru berhasil ditambahkan.';
            } else {
                throw new Exception('Gagal menambahkan kecamatan: ' . $stmt->error);
            }
            $stmt->close();
        } elseif ($action === 'edit' && !empty($id_kecamatan)) {
            // Edit existing kecamatan
            $sql = "UPDATE kecamatan SET nama_kecamatan = ?, id_kota = ? WHERE id_kecamatan = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) throw new Exception('Gagal menyiapkan statement: ' . $conn->error);
            $stmt->bind_param("sii", $nama_kecamatan, $id_kota, $id_kecamatan);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $_SESSION['success_message'] = 'Data kecamatan berhasil diperbarui.';
                } else {
                    $_SESSION['success_message'] = 'Tidak ada perubahan pada data kecamatan.';
                }
            } else {
                throw new Exception('Gagal memperbarui kecamatan: ' . $stmt->error);
            }
            $stmt->close();
        } else {
            throw new Exception('Aksi tidak valid atau ID kecamatan tidak ditemukan.');
        }
    } catch (Exception $e) {
         // Check for duplicate entry error (MySQL error code 1062) - often on (nama_kecamatan, id_kota)
        if ($conn->errno === 1062) {
             $_SESSION['error_message'] = 'Nama kecamatan sudah ada di kota yang dipilih. Silakan gunakan nama lain.';
        } else {
            $_SESSION['error_message'] = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }

    $conn->close();
    header('Location: manage_kota.php#kecamatan'); // Redirect back to kecamatan tab
    exit;

}
// No need for the final else block as the CSRF check already handles non-POST requests
?>

