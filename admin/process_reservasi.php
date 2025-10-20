<?php
require_once __DIR__ . '/../config/database.php';

// Set default error reporting
ini_set('display_errors', 0);
error_reporting(0);

// Start session
session_start();

// Function to send JSON response
function sendJsonResponse($success, $message = '', $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    $response = ['success' => $success];
    if ($message) $response['message'] = $message;
    if ($data !== null) $response['data'] = $data;
    echo json_encode($response);
    exit;
}

// Check if request is AJAX
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Ensure admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    if ($isAjax) {
        sendJsonResponse(false, 'Session expired. Please login again.', null, 401);
    } else {
        $_SESSION['error_message'] = 'Anda harus login untuk mengakses halaman ini.';
        header('Location: login.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $id_reservasi = isset($_POST['id_reservasi']) ? (int)$_POST['id_reservasi'] : null;
    $status = isset($_POST['status']) ? $_POST['status'] : null;
    $alamat_jemput = isset($_POST['alamat_jemput']) ? trim($_POST['alamat_jemput']) : null;

    // Basic Validation
    if (empty($id_reservasi) || ($action !== 'update_status' && $action !== 'update_alamat' && $action !== 'delete')) {
        if ($isAjax) {
            sendJsonResponse(false, 'Data tidak valid atau aksi tidak dikenali.', null, 400);
        } else {
            $_SESSION['error_message'] = 'Data tidak valid atau aksi tidak dikenali.';
            header('Location: manage_reservasi.php');
            exit;
        }
    }
    
    // Handle delete action
    if ($action === 'delete') {
        try {
            $conn->begin_transaction();
            
            // Delete reservation seats first
            $sql_delete_seats = "DELETE FROM detail_reservasi_kursi WHERE id_reservasi = ?";
            $stmt = $conn->prepare($sql_delete_seats);
            if ($stmt === false) throw new Exception('Gagal menyiapkan statement hapus kursi: ' . $conn->error);
            $stmt->bind_param("i", $id_reservasi);
            if (!$stmt->execute()) {
                throw new Exception('Gagal menghapus data kursi: ' . $stmt->error);
            }
            $stmt->close();
            
            // Delete the reservation
            $sql_delete_reservasi = "DELETE FROM reservasi WHERE id_reservasi = ?";
            $stmt = $conn->prepare($sql_delete_reservasi);
            if ($stmt === false) throw new Exception('Gagal menyiapkan statement hapus reservasi: ' . $conn->error);
            $stmt->bind_param("i", $id_reservasi);
            
            $result = $stmt->execute();
            $stmt->close();
            
            if ($result) {
                $conn->commit();
                if ($isAjax) {
                    sendJsonResponse(true, 'Reservasi berhasil dihapus');
                } else {
                    $_SESSION['success_message'] = 'Reservasi berhasil dihapus';
                    header('Location: manage_reservasi.php');
                    exit;
                }
            } else {
                throw new Exception('Gagal menghapus data reservasi: ' . $conn->error);
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            if ($isAjax) {
                http_response_code(500);
                sendJsonResponse(false, 'Gagal menghapus reservasi: ' . $e->getMessage());
            } else {
                $_SESSION['error_message'] = 'Gagal menghapus reservasi: ' . $e->getMessage();
                header('Location: manage_reservasi.php');
                exit;
            }
        }
    }
    
    // Validasi untuk update alamat
    if ($action === 'update_alamat') {
        if (empty($alamat_jemput)) {
            if ($isAjax) {
                http_response_code(400);
                sendJsonResponse(false, 'Alamat jemput tidak boleh kosong.');
            } else {
                $_SESSION['error_message'] = 'Alamat jemput tidak boleh kosong.';
                header('Location: manage_reservasi.php');
                exit;
            }
        }
    }
    
    // Validasi untuk update status
    if ($action === 'update_status') {
        if (empty($status)) {
            if ($isAjax) {
                http_response_code(400);
                sendJsonResponse(false, 'Status tidak boleh kosong.');
            } else {
                $_SESSION['error_message'] = 'Status tidak boleh kosong.';
                header('Location: manage_reservasi.php');
                exit;
            }
        }

        // Validate status value
        $allowed_status = ['pending', 'dibayar', 'dibatalkan'];
        if (!in_array($status, $allowed_status)) {
            if ($isAjax) {
                http_response_code(400);
                sendJsonResponse(false, 'Status pembayaran tidak valid.');
            } else {
                $_SESSION['error_message'] = 'Status pembayaran tidak valid.';
                header('Location: manage_reservasi.php');
                exit;
            }
        }
    }

    try {
        // Mulai transaksi
        $conn->begin_transaction();
        
        // Dapatkan data jadwal dari reservasi
        $sql_get_jadwal = "SELECT id_jadwal, status FROM reservasi WHERE id_reservasi = ?";
        $stmt = $conn->prepare($sql_get_jadwal);
        if ($stmt === false) throw new Exception('Gagal menyiapkan statement: ' . $conn->error);
        $stmt->bind_param("i", $id_reservasi);
        $stmt->execute();
        $result = $stmt->get_result();
        $reservasi = $result->fetch_assoc();
        $stmt->close();
        
        if (!$reservasi) {
            throw new Exception('Data reservasi tidak ditemukan.');
        }
        
        $id_jadwal = $reservasi['id_jadwal'];
        $current_status = $reservasi['status'];
        
        // Update data reservasi berdasarkan aksi
        if ($action === 'update_status') {
            // Update status reservasi
            $sql = "UPDATE reservasi SET status = ? WHERE id_reservasi = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) throw new Exception('Gagal menyiapkan statement: ' . $conn->error);
            $stmt->bind_param("si", $status, $id_reservasi);
            $success_message = 'Status reservasi berhasil diperbarui' . ($status === 'dibatalkan' ? ' dan kursi telah dibebaskan' : '') . '.';
        } else if ($action === 'update_alamat') {
            // Update alamat jemput
            $sql = "UPDATE reservasi SET alamat_jemput = ? WHERE id_reservasi = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) throw new Exception('Gagal menyiapkan statement: ' . $conn->error);
            $stmt->bind_param("si", $alamat_jemput, $id_reservasi);
            $success_message = 'Alamat jemput berhasil diperbarui.';
        } else {
            throw new Exception('Aksi tidak valid');
        }
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                // Jika status diubah menjadi 'dibatalkan', hapus detail reservasi kursi
                if ($action === 'update_status' && $status === 'dibatalkan' && $current_status !== 'dibatalkan') {
                    $sql_delete = "DELETE FROM detail_reservasi_kursi WHERE id_reservasi = ?";
                    $stmt_del = $conn->prepare($sql_delete);
                    if ($stmt_del === false) throw new Exception('Gagal menyiapkan statement hapus kursi: ' . $conn->error);
                    $stmt_del->bind_param("i", $id_reservasi);
                    if (!$stmt_del->execute()) {
                        throw new Exception('Gagal menghapus data kursi: ' . $stmt_del->error);
                    }
                    $stmt_del->close();
                }
                
                $conn->commit();
                
                if ($isAjax) {
                    $response = [
                        'success' => true,
                        'message' => $success_message,
                        'data' => [
                            'id_reservasi' => $id_reservasi,
                            'status' => $action === 'update_status' ? $status : $current_status,
                            'alamat_jemput' => $action === 'update_alamat' ? $alamat_jemput : null,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    ];
                    sendJsonResponse(true, $success_message, $response['data']);
                } else {
                    $_SESSION['success_message'] = $success_message;
                }
            } else {
                $message = 'Tidak ada perubahan pada data reservasi.';
                if ($isAjax) {
                    sendJsonResponse(true, $message);
                } else {
                    $_SESSION['info_message'] = $message;
                }
            }
        } else {
            throw new Exception('Gagal memperbarui data reservasi: ' . $stmt->error);
        }
        $stmt->close();

    } catch (Exception $e) {
        $conn->rollback();
        if ($isAjax) {
            http_response_code(500);
            sendJsonResponse(false, 'Terjadi kesalahan: ' . $e->getMessage());
        } else {
            $_SESSION['error_message'] = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }

    $conn->close();
    if (!$isAjax) {
        header('Location: manage_reservasi.php');
        exit;
    }

} else {
    // Redirect if not POST
    header('Location: manage_reservasi.php');
    exit;
}
?>

