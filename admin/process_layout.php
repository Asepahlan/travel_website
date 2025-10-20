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
        header('Location: manage_layout.php'); // Redirect back
        exit;
    }
} else {
    // Redirect if not POST
    header('Location: manage_layout.php');
    exit;
}
// --- End CSRF Protection Check ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $id_layout = isset($_POST['id_layout']) ? (int)$_POST['id_layout'] : null;
    $nama_layout = isset($_POST['nama_layout']) ? trim(htmlspecialchars($_POST['nama_layout'])) : null;
    $existing_image = isset($_POST['existing_image']) ? trim(htmlspecialchars($_POST['existing_image'])) : null;
    $gambar_layout_file = $_FILES['gambar_layout'] ?? null;

    // Basic Validation
    if (empty($nama_layout)) {
        $_SESSION['error_message'] = 'Nama layout tidak boleh kosong.';
        header('Location: manage_layout.php');
        exit;
    }

    $upload_dir = __DIR__ . '/../public/uploads/layouts/';
    $uploaded_filename = null;

    // Handle file upload
    if ($gambar_layout_file && $gambar_layout_file['error'] === UPLOAD_ERR_OK) {
        // Validate file type and size
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($gambar_layout_file['type'], $allowed_types)) {
            $_SESSION['error_message'] = 'Format gambar tidak valid. Hanya JPG, PNG, GIF, WebP yang diizinkan.';
            header('Location: manage_layout.php');
            exit;
        }
        if ($gambar_layout_file['size'] > $max_size) {
            $_SESSION['error_message'] = 'Ukuran gambar terlalu besar. Maksimal 2MB.';
            header('Location: manage_layout.php');
            exit;
        }

        // Create unique filename
        $file_extension = pathinfo($gambar_layout_file['name'], PATHINFO_EXTENSION);
        $uploaded_filename = uniqid('layout_', true) . '.' . $file_extension;
        $upload_path = $upload_dir . $uploaded_filename;

        // Ensure upload directory exists
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0775, true);
        }

        // Move uploaded file
        if (!move_uploaded_file($gambar_layout_file['tmp_name'], $upload_path)) {
            $_SESSION['error_message'] = 'Gagal mengunggah gambar.';
            header('Location: manage_layout.php');
            exit;
        }

        // Delete old image if editing and new image uploaded
        if ($action === 'edit' && !empty($existing_image) && file_exists($upload_dir . $existing_image)) {
            unlink($upload_dir . $existing_image);
        }

    } elseif ($action === 'edit') {
        // Keep existing image if no new file uploaded during edit
        $uploaded_filename = $existing_image;
    }

    try {
        if ($action === 'add') {
            // Add new layout
            $sql = "INSERT INTO layout_kursi (nama_layout, gambar_layout) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) throw new Exception('Gagal menyiapkan statement: ' . $conn->error);
            $stmt->bind_param("ss", $nama_layout, $uploaded_filename);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Layout baru berhasil ditambahkan.';
            } else {
                throw new Exception('Gagal menambahkan layout: ' . $stmt->error);
            }
            $stmt->close();
        } elseif ($action === 'edit' && !empty($id_layout)) {
            // Edit existing layout
            $sql = "UPDATE layout_kursi SET nama_layout = ?, gambar_layout = ? WHERE id_layout = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) throw new Exception('Gagal menyiapkan statement: ' . $conn->error);
            $stmt->bind_param("ssi", $nama_layout, $uploaded_filename, $id_layout);
            if ($stmt->execute()) {
                 if ($stmt->affected_rows > 0 || ($gambar_layout_file && $gambar_layout_file['error'] === UPLOAD_ERR_OK)) {
                    $_SESSION['success_message'] = 'Data layout berhasil diperbarui.';
                } else {
                    $_SESSION['success_message'] = 'Tidak ada perubahan pada data layout.';
                }
            } else {
                throw new Exception('Gagal memperbarui layout: ' . $stmt->error);
            }
            $stmt->close();
        } else {
            throw new Exception('Aksi tidak valid atau ID layout tidak ditemukan.');
        }
    } catch (Exception $e) {
        // Check for duplicate entry error (MySQL error code 1062)
        if ($conn->errno === 1062) {
             $_SESSION['error_message'] = 'Nama layout sudah ada. Silakan gunakan nama lain.';
        } else {
            $_SESSION['error_message'] = 'Terjadi kesalahan: ' . $e->getMessage();
        }
        // If error occurred after upload, delete the newly uploaded file
        if ($uploaded_filename && $gambar_layout_file && $gambar_layout_file['error'] === UPLOAD_ERR_OK && file_exists($upload_dir . $uploaded_filename)) {
            unlink($upload_dir . $uploaded_filename);
        }
    }

    $conn->close();
    header('Location: manage_layout.php');
    exit;

}
// No need for the final else block as the CSRF check already handles non-POST requests
?>

