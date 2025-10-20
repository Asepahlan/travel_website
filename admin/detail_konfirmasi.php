<?php
session_start();
require_once __DIR__ . '/./includes/database.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Ambil ID konfirmasi dari URL
$id = $_GET['id'] ?? 0;

// Query untuk mendapatkan detail konfirmasi pembayaran
$query = "SELECT 
            kp.*, 
            r.nama_pemesan, 
            r.no_hp, 
            r.email, 
            r.total_harga, 
            r.alamat_jemput as titik_jemput,
            r.status_pembayaran,
            r.kode_booking,
            j.tanggal_berangkat,
            j.waktu_berangkat as jam_berangkat,
            kota_asal.nama_kota as kota_asal,
            kota_tujuan.nama_kota as kota_tujuan,
            (SELECT GROUP_CONCAT(k.nomor_kursi) 
              FROM detail_reservasi_kursi drk 
              JOIN kursi k ON drk.id_kursi = k.id_kursi 
              WHERE drk.id_reservasi = r.id_reservasi) as kursi_terpilih
          FROM konfirmasi_pembayaran kp
          JOIN reservasi r ON kp.kode_booking = r.kode_booking
          JOIN jadwal j ON r.id_jadwal = j.id_jadwal
          JOIN kota kota_asal ON j.id_kota_asal = kota_asal.id_kota
          JOIN kota kota_tujuan ON j.id_kota_tujuan = kota_tujuan.id_kota
          WHERE kp.id = ?";
          
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$konfirmasi = $stmt->get_result()->fetch_assoc();

if (!$konfirmasi) {
    $_SESSION['error_message'] = 'Data konfirmasi tidak ditemukan';
    header('Location: konfirmasi_pembayaran.php');
    exit;
}

// Set page title
$page_title = 'Detail Konfirmasi Pembayaran';

// Kode debugging telah dihapus
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
<nav class="bg-white border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 py-3">
        <div class="flex justify-between items-center">
            <a href="index.php" class="text-lg font-medium text-gray-900">Admin</a>
            <a href="konfirmasi_pembayaran.php" class="text-blue-600 hover:text-blue-800 text-sm">â† Kembali</a>
        </div>
    </div>
</nav>

<div class="max-w-4xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Detail Konfirmasi</h1>
        <p class="text-sm text-gray-500 mt-1">#<?php echo htmlspecialchars($konfirmasi['kode_booking']); ?></p>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
            <span class="block sm:inline"><?php echo $_SESSION['success_message']; ?></span>
            <?php unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-6">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h3 class="text-base font-medium text-gray-700">Informasi Pembayaran</h3>
        </div>
        <div class="px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-sm font-medium text-gray-500">Status Pembayaran</h4>
                    <?php
                    $status_class = [
                        'menunggu' => 'bg-yellow-100 text-yellow-800',
                        'diverifikasi' => 'bg-green-100 text-green-800',
                        'ditolak' => 'bg-red-100 text-red-800',
                        'dibatalkan' => 'bg-gray-100 text-gray-800'
                    ][$konfirmasi['status']] ?? 'bg-gray-100 text-gray-800';
                    ?>
                    <p class="mt-1">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                            <?php echo ucfirst($konfirmasi['status']); ?>
                        </span>
                    </p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-500">Bank Tujuan</h4>
                    <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($konfirmasi['bank_tujuan']); ?></p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-500">Nama Pengirim</h4>
                    <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($konfirmasi['nama_pengirim']); ?></p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-500">Jumlah Dibayar</h4>
                    <p class="mt-1 text-sm text-gray-900">Rp <?php echo number_format($konfirmasi['jumlah_dibayar'], 0, ',', '.'); ?></p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-500">Tanggal Transfer</h4>
                    <p class="mt-1 text-sm text-gray-900"><?php echo date('d M Y', strtotime($konfirmasi['tanggal_transfer'])); ?></p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-500">Waktu Konfirmasi</h4>
                    <p class="mt-1 text-sm text-gray-900"><?php echo date('d M Y H:i', strtotime($konfirmasi['created_at'])); ?></p>
                </div>
                <?php 
                $bukti_path = '';
                if (!empty($konfirmasi['bukti_transfer'])) {
                    $filename = basename($konfirmasi['bukti_transfer']);
                    
                    // Define possible upload directories
                    $upload_dirs = [
                        __DIR__ . '/../public/uploads/payments/',
                        __DIR__ . '/../uploads/bukti_transfer/',
                        __DIR__ . '/../uploads/',
                        $_SERVER['DOCUMENT_ROOT'] . '/travel_website/public/uploads/payments/',
                        $_SERVER['DOCUMENT_ROOT'] . '/travel_website/uploads/bukti_transfer/'
                    ];
                    
                    // Check in all possible directories
                    foreach ($upload_dirs as $upload_dir) {
                        $full_path = $upload_dir . $filename;
                        if (file_exists($full_path)) {
                            $bukti_path = $full_path;
                            break;
                        }
                    }
                    
                    // If not found, try with relative paths
                    if (empty($bukti_path)) {
                        $possible_paths = [
                            $konfirmasi['bukti_transfer'],
                            '../' . $konfirmasi['bukti_transfer'],
                            '../public/uploads/payments/' . $filename,
                            '../uploads/bukti_transfer/' . $filename,
                            'uploads/' . $filename,
                            'uploads/bukti_transfer/' . $filename,
                            'public/uploads/payments/' . $filename
                        ];
                        
                        foreach ($possible_paths as $path) {
                            $full_path = str_replace('//', '/', $path);
                            if (file_exists($full_path)) {
                                $bukti_path = $full_path;
                                break;
                            }
                        }
                    }
                }
                ?>
                <div class="md:col-span-2">
                    <h4 class="text-sm font-medium text-gray-500 mb-2">Bukti Transfer</h4>
                    <?php 
                    $image_found = false;
                    if (!empty($bukti_path) && file_exists($bukti_path)) {
                        // Get just the filename
                        $filename = basename($bukti_path);
                        
                        // Create the correct URL path
                        $image_url = '/travel_website/public/uploads/payments/' . $filename;
                        
                        // Check if the URL is accessible
                        $full_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $image_url;
                        $headers = @get_headers($full_url);
                        
                        if ($headers && strpos($headers[0], '200') !== false) {
                            $image_found = true;
                        } else {
                            // If direct URL doesn't work, try to serve the file directly
                            $image_url = '/travel_website/admin/view_image.php?filename=' . urlencode($filename);
                            $image_found = true;
                        }
                    }
                    ?>
                    <?php if ($image_found): ?>
                        <a href="<?php echo htmlspecialchars($image_url); ?>" target="_blank" class="inline-block">
                            <img src="<?php echo htmlspecialchars($image_url); ?>" 
                                 alt="Bukti Transfer" 
                                 class="max-h-96 w-auto rounded-md shadow-sm border border-gray-200">
                        </a>
                        <p class="mt-1 text-xs text-gray-500">Klik gambar untuk memperbesar</p>
                    <?php else: ?>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p class="mt-1 text-sm text-gray-500">Gambar bukti transfer tidak ditemukan</p>
                            <?php if (!empty($konfirmasi['bukti_transfer'])): ?>
                                <p class="text-xs text-gray-400 mt-1">Path yang dicari: <?php echo htmlspecialchars($konfirmasi['bukti_transfer']); ?></p>
                                <p class="text-xs text-gray-400 mt-1">Direktori upload: <?php echo htmlspecialchars(realpath('../uploads/bukti_transfer/')); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mt-6">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h3 class="text-base font-medium text-gray-700">Detail Pemesanan</h3>
        </div>
        <div class="px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-sm font-medium text-gray-500">Nama Pemesan</h4>
                    <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($konfirmasi['nama_pemesan']); ?></p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-500">No. Telepon</h4>
                    <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($konfirmasi['no_hp']); ?></p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-500">Email</h4>
                    <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($konfirmasi['email']); ?></p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-500">Rute</h4>
                    <p class="mt-1 text-sm text-gray-900">
                        <?php 
                        $rute = [];
                        if (!empty($konfirmasi['kota_asal'])) $rute[] = htmlspecialchars($konfirmasi['kota_asal']);
                        if (!empty($konfirmasi['kota_tujuan'])) $rute[] = htmlspecialchars($konfirmasi['kota_tujuan']);
                        echo !empty($rute) ? implode(' â†’ ', $rute) : '-';
                        ?>
                    </p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-500">Tanggal Berangkat</h4>
                    <p class="mt-1 text-sm text-gray-900">
                        <?php echo date('d M Y', strtotime($konfirmasi['tanggal_berangkat'])); ?>
                        <?php echo !empty($konfirmasi['jam_berangkat']) ? ' â€¢ ' . $konfirmasi['jam_berangkat'] : ''; ?>
                    </p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-500">Kursi</h4>
                    <p class="mt-1 text-sm text-gray-900">
                        <?php 
                        if (!empty($konfirmasi['kursi_terpilih'])) {
                            // Format kursi: A1, A2, B1, B2, dst
                            $kursi = explode(',', $konfirmasi['kursi_terpilih']);
                            $kursi = array_map('trim', $kursi);
                            $kursi = array_filter($kursi, function($k) {
                                // Terima format huruf+angka (contoh: A1, B2, C3)
                                return !empty($k) && preg_match('/^[A-Za-z]\d+$/', $k);
                            });
                            
                            if (!empty($kursi)) {
                                echo htmlspecialchars(implode(', ', $kursi));
                            } else {
                                // Jika format tidak sesuai, tetap tampilkan aslinya
                                echo htmlspecialchars($konfirmasi['kursi_terpilih']);
                            }
                        } else {
                            echo '-';
                        }
                        ?>
                    </p>
                </div>
                <div class="md:col-span-2">
                    <h4 class="text-sm font-medium text-gray-500">Titik Jemput</h4>
                    <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($konfirmasi['titik_jemput']); ?></p>
                </div>
                <div class="md:col-span-2">
                    <h4 class="text-sm font-medium text-gray-500">Titik Antar</h4>
                    <p class="mt-1 text-sm text-gray-900">-</p>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-100">
        <?php if ($konfirmasi['status'] === 'menunggu'): ?>
            <button onclick="updateStatus('ditolak')" class="px-4 py-2 text-sm font-medium text-red-600 hover:text-red-800 border border-red-200 rounded hover:bg-red-50">
                Tolak
            </button>
            <button onclick="updateStatus('diverifikasi')" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700">
                Verifikasi
            </button>
        <?php else: ?>
            <button onclick="updateStatus('dibatalkan')" class="px-4 py-2 text-sm font-medium text-yellow-600 hover:text-yellow-800 border border-yellow-200 rounded hover:bg-yellow-50">
                Batalkan Konfirmasi
            </button>
        <?php endif; ?>
    </div>

    <script>
    // Confirmation Modal
    const confirmationModal = `
        <div id="confirmationModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-blue-100 rounded-full mb-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-center text-gray-900 mb-2">Konfirmasi</h3>
                    <p class="text-sm text-gray-500 text-center mb-6" id="modalMessage"></p>
                    <div class="flex justify-center space-x-3">
                        <button type="button" id="cancelButton" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Batal
                        </button>
                        <button type="button" id="confirmButton" class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Ya, Lanjutkan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Show notification function
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-md shadow-lg ${
            type === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'
        }`;
        
        notification.innerHTML = `
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 ${type === 'success' ? 'text-green-400' : 'text-red-400'}" 
                         xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="${type === 'success' ? 'M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z' : 'M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z'}" 
                              clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium ${type === 'success' ? 'text-green-800' : 'text-red-800'}">${message}</p>
                </div>
                <div class="ml-4">
                    <button type="button" class="inline-flex text-gray-400 hover:text-gray-500 focus:outline-none" onclick="this.parentElement.parentElement.parentElement.remove()">
                        <span class="sr-only">Tutup</span>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove notification after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    async function updateStatus(status) {
        // Show modal
        document.body.insertAdjacentHTML('beforeend', confirmationModal);
        const modal = document.getElementById('confirmationModal');
        let message = '';
        if (status === 'diverifikasi') {
            message = 'Apakah Anda yakin ingin memverifikasi pembayaran ini?';
        } else if (status === 'ditolak') {
            message = 'Apakah Anda yakin ingin menolak pembayaran ini?';
        } else if (status === 'dibatalkan') {
            message = 'Apakah Anda yakin ingin membatalkan konfirmasi pembayaran ini? Status akan dikembalikan ke menunggu verifikasi.';
        }
        
        document.getElementById('modalMessage').textContent = message;
        
        // Set button text and color based on action
        const confirmBtn = document.getElementById('confirmButton');
        if (status === 'ditolak') {
            confirmBtn.textContent = 'Ya, Tolak';
            confirmBtn.className = 'px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500';
        } else {
            confirmBtn.textContent = 'Ya, Verifikasi';
            confirmBtn.className = 'px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500';
        }
        
        // Handle confirm button click
        confirmBtn.onclick = async function() {
            // Show loading state
            const originalText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Memproses...
            `;
            
            try {
                const formData = new FormData();
                formData.append('id', '<?php echo $id; ?>');
                formData.append('status', status);
                
                const response = await fetch('update_status_pembayaran.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const successMessage = status === 'diverifikasi' 
                        ? 'Pembayaran berhasil diverifikasi' 
                        : 'Pembayaran berhasil ditolak';
                    
                    showNotification(successMessage, 'success');
                    
                    // Close modal and reload page after delay
                    modal.remove();
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    throw new Error(data.message || 'Terjadi kesalahan');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification(error.message || 'Terjadi kesalahan. Silakan coba lagi.', 'error');
                this.disabled = false;
                this.innerHTML = originalText;
            }
        };
        
        // Handle cancel button click
        document.getElementById('cancelButton').onclick = function() {
            modal.remove();
        };
        
        // Close modal when clicking outside
        modal.onclick = function(e) {
            if (e.target === modal) {
                modal.remove();
            }
        };
        
        // Close modal with Escape key
        const handleKeyDown = function(e) {
            if (e.key === 'Escape') {
                modal.remove();
                document.removeEventListener('keydown', handleKeyDown);
            }
        };
        document.addEventListener('keydown', handleKeyDown);
    }

    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-md shadow-lg ${
            type === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'
        }`;
        
        notification.innerHTML = `
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 ${type === 'success' ? 'text-green-400' : 'text-red-400'}" 
                         xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="${type === 'success' ? 'M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z' : 'M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z'}" 
                              clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium ${type === 'success' ? 'text-green-800' : 'text-red-800'}">${message}</p>
                </div>
                <div class="ml-4">
                    <button type="button" class="inline-flex text-gray-400 hover:text-gray-500 focus:outline-none" onclick="this.parentElement.parentElement.parentElement.remove()">
                        <span class="sr-only">Tutup</span>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove notification after 5 seconds
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
    </script>
</div>

</body>
</html>

