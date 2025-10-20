<?php
require_once __DIR__ . 
'/./includes/database.php';
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
        // Redirect back to the specific layout's kursi management page
        $redirect_layout_id = isset($_POST['id_layout']) ? (int)$_POST['id_layout'] : null;
        $redirect_url = $redirect_layout_id ? "manage_kursi.php?layout_id=$redirect_layout_id" : 'manage_layout.php';
        header("Location: $redirect_url");
        exit;
    }
} else {
    // Redirect if not POST
    $redirect_layout_id = isset($_GET['layout_id']) ? (int)$_GET['layout_id'] : null;
    $redirect_url = $redirect_layout_id ? "manage_kursi.php?layout_id=$redirect_layout_id" : 'manage_layout.php';
    header("Location: $redirect_url");
    exit;
}
// --- End CSRF Protection Check ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $id_layout = isset($_POST['id_layout']) ? (int)$_POST['id_layout'] : null;
    $id_kursi = isset($_POST['id_kursi']) ? (int)$_POST['id_kursi'] : null;
    $nomor_kursi = isset($_POST['nomor_kursi']) ? trim(htmlspecialchars($_POST['nomor_kursi'])) : null;
    $posisi_x = isset($_POST['posisi_x']) && $_POST['posisi_x'] !== '' ? (float)$_POST['posisi_x'] : null;
    $posisi_y = isset($_POST['posisi_y']) && $_POST['posisi_y'] !== '' ? (float)$_POST['posisi_y'] : null;

    // Basic Validation
    if (empty($id_layout) || empty($nomor_kursi)) {
        $_SESSION['error_message'] = 'ID Layout dan Nomor Kursi tidak boleh kosong.';
        header("Location: manage_kursi.php?layout_id=$id_layout");
        exit;
    }
    // Validate position if provided
    if (($posisi_x !== null && ($posisi_x < 0 || $posisi_x > 100)) || 
        ($posisi_y !== null && ($posisi_y < 0 || $posisi_y > 100))) {
        $_SESSION['error_message'] = 'Posisi X dan Y harus antara 0 dan 100.';
         header("Location: manage_kursi.php?layout_id=$id_layout");
        exit;
    }

    try {
        // Cek apakah nomor kursi sudah ada di layout yang sama
        $check_sql = "SELECT id_kursi FROM kursi WHERE id_layout = ? AND nomor_kursi = ?";
        $check_stmt = $conn->prepare($check_sql);
        if ($check_stmt === false) throw new Exception('Gagal memeriksa nomor kursi: ' . $conn->error);
        
        // Bind parameters untuk pengecekan
        $check_stmt->bind_param("is", $id_layout, $nomor_kursi);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $existing_kursi = $check_result->fetch_assoc();
        $check_stmt->close();
        
        // Jika nomor kursi sudah ada dan bukan operasi update ke nomor yang sama
        if ($existing_kursi && ($action !== 'edit' || $existing_kursi['id_kursi'] != $id_kursi)) {
            throw new Exception('Nomor kursi "' . htmlspecialchars($nomor_kursi) . '" sudah ada di layout ini. Silakan gunakan nomor lain.');
        }
        
        if ($action === 'add') {
            // Add new kursi
            $sql = "INSERT INTO kursi (id_layout, nomor_kursi, posisi_x, posisi_y) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) throw new Exception('Gagal menyiapkan statement: ' . $conn->error);
            $stmt->bind_param("isdd", $id_layout, $nomor_kursi, $posisi_x, $posisi_y);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Kursi baru berhasil ditambahkan.';
            } else {
                throw new Exception('Gagal menambahkan kursi: ' . $stmt->error);
            }
            $stmt->close();
        } elseif ($action === 'edit' && !empty($id_kursi)) {
            // Edit existing kursi
            $sql = "UPDATE kursi SET nomor_kursi = ?, posisi_x = ?, posisi_y = ? WHERE id_kursi = ? AND id_layout = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) throw new Exception('Gagal menyiapkan statement: ' . $conn->error);
            $stmt->bind_param("sddii", $nomor_kursi, $posisi_x, $posisi_y, $id_kursi, $id_layout);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $_SESSION['success_message'] = 'Data kursi berhasil diperbarui.';
                } else {
                    $_SESSION['success_message'] = 'Tidak ada perubahan pada data kursi.';
                }
            } else {
                throw new Exception('Gagal memperbarui kursi: ' . $stmt->error);
            }
            $stmt->close();
        } else {
            throw new Exception('Aksi tidak valid atau ID kursi tidak ditemukan.');
        }
    } catch (Exception $e) {
        // Set pesan error yang lebih informatif
        $_SESSION['error_message'] = 'Terjadi kesalahan: ' . $e->getMessage();
        // Simpan data yang diinput untuk ditampilkan kembali
        $_SESSION['form_data'] = [
            'nomor_kursi' => $nomor_kursi,
            'posisi_x' => $posisi_x,
            'posisi_y' => $posisi_y
        ];
    }

    $conn->close();
    header("Location: manage_kursi.php?layout_id=$id_layout");
    exit;

}
// No need for the final else block as the CSRF check already handles non-POST requests
?>

