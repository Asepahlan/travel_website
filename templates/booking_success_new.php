<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session and include database connection at the top
session_start();

// Include database connection
try {
    require_once __DIR__ . '/./includes/database.php';
    
    // Check database connection
    if (!$conn) {
        throw new Exception("Tidak dapat terhubung ke database");
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Normalize parameters (support both 'kode' and 'code')
$kode_param = isset($_GET['kode']) ? trim($_GET['kode']) : (isset($_GET['code']) ? trim($_GET['code']) : null);

// If no URL parameter, try to get from session
if (!$kode_param && isset($_SESSION['booking_code'])) {
    $kode_param = $_SESSION['booking_code'];
    $success_message = $_SESSION['success_message'] ?? '';
}

// Validate access
if (!$kode_param) {
    header('Location: index.php');
    exit;
}

// Set page title
$page_title = 'Pemesanan Berhasil';

// Initialize variables
$reservasi = [];
$error_message = '';
$success_message = $success_message ?? '';
$booking_code = $kode_param;

// Process database query
if ($kode_param) {
    try {
        // Query untuk mendapatkan data reservasi dan nomor kursi
        $sql = "SELECT 
                    r.*, 
                    GROUP_CONCAT(DISTINCT k.nomor_kursi ORDER BY k.nomor_kursi SEPARATOR ', ') as nomor_kursi,
                    COUNT(DISTINCT drk.id_kursi) as jumlah_kursi,
                    j.tanggal_berangkat,
                    j.waktu_berangkat,
                    kt_asal.nama_kota as kota_asal,
                    kt_tujuan.nama_kota as kota_tujuan,
                    kc_asal.nama_kecamatan as kecamatan_asal,
                    kc_tujuan.nama_kecamatan as kecamatan_tujuan
                FROM reservasi r 
                LEFT JOIN detail_reservasi_kursi drk ON r.id_reservasi = drk.id_reservasi
                LEFT JOIN kursi k ON drk.id_kursi = k.id_kursi
                LEFT JOIN jadwal j ON r.id_jadwal = j.id_jadwal
                LEFT JOIN kota kt_asal ON j.id_kota_asal = kt_asal.id_kota
                LEFT JOIN kota kt_tujuan ON j.id_kota_tujuan = kt_tujuan.id_kota
                LEFT JOIN kecamatan kc_asal ON j.id_kecamatan_asal = kc_asal.id_kecamatan
                LEFT JOIN kecamatan kc_tujuan ON j.id_kecamatan_tujuan = kc_tujuan.id_kecamatan
                WHERE r.kode_booking = ? 
                GROUP BY r.id_reservasi";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Gagal mempersiapkan statement: " . $conn->error);
        }
        
        $stmt->bind_param('s', $kode_param);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $reservasi = $result->fetch_assoc();
            
            // Set session data
            $_SESSION['booking_code'] = $reservasi['kode_booking'];
            $_SESSION['nama_pemesan'] = $reservasi['nama_pemesan'];
            
            // Format tanggal dan waktu
            $reservasi['tanggal_berangkat_formatted'] = date('d M Y', strtotime($reservasi['tanggal_berangkat']));
            $reservasi['waktu_berangkat_formatted'] = date('H:i', strtotime($reservasi['waktu_berangkat']));
            
            // Format harga
            $reservasi['total_harga_formatted'] = 'Rp ' . number_format($reservasi['total_harga'], 0, ',', '.');
            
        } else {
            $error_message = "Data pemesanan tidak ditemukan.";
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $error_message = "Terjadi kesalahan: " . $e->getMessage();
        error_log($e->getMessage());
    }
}

// Include header
require_once __DIR__ . '/../templates/partials/header.php';
?>

<main class="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Pemesanan Berhasil!</h1>
            <p class="text-gray-600">Terima kasih telah memesan tiket di TravelKita</p>
            
            <?php if (!empty($success_message)): ?>
                <div class="mt-4 p-3 bg-green-50 text-green-700 rounded-md">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="mt-4 p-3 bg-red-50 text-red-700 rounded-md">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($reservasi)): ?>
        <!-- Ringkasan Pemesanan -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-8">
            <!-- Header -->
            <div class="bg-blue-600 px-6 py-4">
                <h2 class="text-lg font-semibold text-white">Ringkasan Pemesanan</h2>
            </div>
            
            <!-- Content -->
            <div class="p-6">
                <!-- Rute -->
                <div class="flex items-start mb-6 pb-6 border-b border-gray-100">
                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">Rute</h3>
                        <p class="mt-1 text-lg font-semibold text-gray-900">
                            <?= htmlspecialchars($reservasi['kota_asal'] ?? 'Jakarta') ?> â†’ <?= htmlspecialchars($reservasi['kota_tujuan'] ?? 'Bandung') ?>
                        </p>
                        <p class="text-sm text-gray-500 mt-1">
                            <?= htmlspecialchars($reservasi['kecamatan_asal'] ?? '') ?> - <?= htmlspecialchars($reservasi['kecamatan_tujuan'] ?? '') ?>
                        </p>
                    </div>
                </div>

                <!-- Tanggal & Waktu -->
                <div class="flex items-start mb-6 pb-6 border-b border-gray-100">
                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">Tanggal & Waktu</h3>
                        <p class="mt-1 text-lg font-semibold text-gray-900">
                            <?= $reservasi['tanggal_berangkat_formatted'] ?? date('d M Y') ?>
                            <span class="mx-2 text-gray-400">â€¢</span>
                            <?= $reservasi['waktu_berangkat_formatted'] ?? '08:00' ?> WIB
                        </p>
                    </div>
                </div>

                <!-- Kursi Dipilih -->
                <div class="flex items-start mb-6">
                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">Kursi Dipilih</h3>
                        <p class="mt-1 text-lg font-semibold text-gray-900">
                            <?= $reservasi['jumlah_kursi'] ?? 1 ?> Kursi
                            <?php if (!empty($reservasi['nomor_kursi'])): ?>
                                <span class="text-gray-600 font-normal">(<?= htmlspecialchars($reservasi['nomor_kursi']) ?>)</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Total Pembayaran -->
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Pembayaran</p>
                        <p class="text-xs text-gray-500">Harga sudah termasuk PPN</p>
                    </div>
                    <span class="text-xl font-bold text-blue-600">
                        <?= $reservasi['total_harga_formatted'] ?? 'Rp 0' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Data Pemesan -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-8">
            <!-- Header -->
            <div class="bg-blue-600 px-6 py-4">
                <h2 class="text-lg font-semibold text-white">Data Pemesan</h2>
            </div>
            
            <!-- Content -->
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">Nama Lengkap</p>
                        <p class="text-gray-900"><?= htmlspecialchars($reservasi['nama_pemesan'] ?? '') ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">Nomor Telepon</p>
                        <p class="text-gray-900"><?= !empty($reservasi['no_hp']) ? htmlspecialchars($reservasi['no_hp']) : '-' ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">Email</p>
                        <p class="text-gray-900"><?= !empty($reservasi['email']) ? htmlspecialchars($reservasi['email']) : '-' ?></p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-sm font-medium text-gray-500 mb-1">Alamat Penjemputan</p>
                        <p class="text-gray-900">
                            <?= !empty($reservasi['alamat_jemput']) ? nl2br(htmlspecialchars($reservasi['alamat_jemput'])) : '-' ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <?php if (($reservasi['status_pembayaran'] ?? '') !== 'Dibayar'): ?>
        <!-- Instruksi Pembayaran -->
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Batas Waktu Pembayaran</h3>
                    <div class="mt-1 text-sm text-yellow-700">
                        <p>Harap selesaikan pembayaran sebelum:</p>
                        <p class="font-bold"><?= date('d M Y H:i', strtotime('+2 days')) ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tombol Aksi -->
        <div class="flex flex-col sm:flex-row gap-4 mt-8">
            <a href="index.php" class="text-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Kembali ke Beranda
            </a>
            <?php if (($reservasi['status_pembayaran'] ?? '') !== 'Dibayar'): ?>
            <button type="button" onclick="showUploadForm()" class="px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                Upload Bukti Pembayaran
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php
// Include footer
require_once __DIR__ . '/../templates/partials/footer.php';
?>

<script>
// Tampilkan form upload
function showUploadForm() {
    // Implement your upload form logic here
    alert('Fitur upload bukti pembayaran akan segera tersedia');
}
</script>

