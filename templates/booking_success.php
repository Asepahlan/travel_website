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
        <div class="flex flex-wrap gap-4 mt-8">
            <!-- Tombol Kembali ke Beranda -->
            <a href="index.php" class="flex-1 sm:flex-none text-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Kembali ke Beranda
            </a>
            
            <!-- Tombol Cetak Tiket (selalu tampil) -->
            <a href="cetak_tiket.php?code=<?= htmlspecialchars($booking_code) ?>" 
               target="_blank"
               class="flex-1 sm:flex-none text-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                <i class="fas fa-print mr-2"></i> Cetak Tiket
            </a>
            
            <?php if (($reservasi['status_pembayaran'] ?? '') !== 'Dibayar'): ?>
                <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
                    <button type="button" 
                            onclick="showUploadForm()" 
                            class="flex-1 sm:flex-none text-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-upload mr-2"></i> Upload Bukti Pembayaran
                    </button>
                <?php
                $whatsapp_message = "Halo Admin TravelKita,%0A%0A" .
                    "Saya ingin mengonfirmasi telah melakukan pembayaran dengan rincian sebagai berikut:%0A%0A" .
                    "*Kode Booking*: " . urlencode($booking_code) . "%0A" .
                    "*Nama Pemesan*: " . urlencode($reservasi['nama_pemesan'] ?? '') . "%0A" .
                    "*Rute*: " . urlencode(($reservasi['kota_asal'] ?? '') . ' â†’ ' . ($reservasi['kota_tujuan'] ?? '')) . "%0A" .
                    "*Tanggal Berangkat*: " . urlencode(date('d F Y', strtotime($reservasi['tanggal_berangkat'] ?? ''))) . "%0A" .
                    "*Jam Berangkat*: " . urlencode(date('H:i', strtotime($reservasi['waktu_berangkat'] ?? ''))) . "%0A" .
                    "*Jumlah Kursi*: " . urlencode($reservasi['jumlah_kursi'] ?? '') . "%0A" .
                    "*Nomor Kursi*: " . urlencode($reservasi['nomor_kursi'] ?? '') . "%0A" .
                    "*Total Pembayaran*: " . urlencode('Rp ' . number_format($reservasi['total_harga'] ?? 0, 0, ',', '.')) . "%0A%0A" .
                    "Saya telah mengunggah bukti pembayaran melalui website. Mohon verifikasi dan konfirmasi pembayaran saya. Terima kasih.";
                ?>
                <a href="https://wa.me/6285798347675?text=<?= $whatsapp_message ?>" 
                   target="_blank" 
                   class="text-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                    <i class="fab fa-whatsapp mr-2"></i> Konfirmasi via WhatsApp
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php
// Include footer
require_once __DIR__ . '/../templates/partials/footer.php';
?>

<!-- Modal Upload Bukti Pembayaran -->
<div id="uploadModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center pb-3">
            <h3 class="text-xl font-semibold text-gray-900">Upload Bukti Pembayaran</h3>
            <button onclick="closeUploadModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <form id="paymentForm" action="./process/payment.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="kode_booking" value="<?= htmlspecialchars($booking_code) ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="bank_tujuan" class="block text-sm font-medium text-gray-700 mb-1">Bank Tujuan</label>
                    <select id="bank_tujuan" name="bank_tujuan" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Pilih Bank</option>
                        <option value="BCA">BCA</option>
                        <option value="BRI">BRI</option>
                        <option value="BNI">BNI</option>
                        <option value="Mandiri">Mandiri</option>
                        <option value="BSI">BSI</option>
                    </select>
                </div>
                
                <div>
                    <label for="nama_pengirim" class="block text-sm font-medium text-gray-700 mb-1">Nama Pengirim</label>
                    <input type="text" id="nama_pengirim" name="nama_pengirim" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Nama sesuai rekening pengirim">
                </div>
                
                <div>
                    <label for="jumlah_dibayar" class="block text-sm font-medium text-gray-700 mb-1">
                        Jumlah Dibayar
                        <span id="harga_seharusnya" class="text-xs text-gray-500 ml-1">
                            (Total: Rp <?= number_format($reservasi['total_harga'] ?? 0, 0, ',', '.') ?>)
                        </span>
                    </label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">Rp </span>
                        </div>
                        <input type="text" 
                               id="jumlah_dibayar" 
                               name="jumlah_dibayar" 
                               required
                               value="<?= number_format($reservasi['total_harga'] ?? 0, 0, ',', '.'); ?>"
                               class="pl-10 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Jumlah yang dibayarkan"
                               oninput="formatCurrency(this)">
                    </div>
                </div>
                
                <div>
                    <label for="tanggal_transfer" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Transfer</label>
                    <input type="datetime-local" id="tanggal_transfer" name="tanggal_transfer" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            
            <div>
                <label for="bukti_transfer" class="block text-sm font-medium text-gray-700 mb-1">Bukti Transfer</label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                    <div class="space-y-1 text-center w-full">
                        <div id="preview-container" class="mb-4 hidden">
                            <img id="image-preview" src="#" alt="Pratinjau gambar" class="mx-auto max-h-48 rounded-md">
                        </div>
                        <div id="upload-ui">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600 justify-center">
                                <label for="bukti_transfer" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                    <span>Upload file</span>
                                    <input id="bukti_transfer" name="bukti_transfer" type="file" class="sr-only" accept="image/*" required>
                                </label>
                                <p class="pl-1">atau drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500">
                                PNG, JPG (maks. 2MB)
                            </p>
                        </div>
                        <div id="file-name" class="text-sm text-gray-900 mt-2"></div>
                    </div>
                </div>
            </div>
            
            <div>
                <label for="catatan" class="block text-sm font-medium text-gray-700 mb-1">Catatan (Opsional)</label>
                <textarea id="catatan" name="catatan" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                          placeholder="Tambahkan catatan jika diperlukan"></textarea>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeUploadModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Batal
                </button>
                <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Kirim Bukti Pembayaran
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Format currency input
function formatNumber(n) {
    return n.toString().replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

// Format currency on input
function formatCurrency(input) {
    // Get value and remove non-numeric characters
    let value = input.value.replace(/[^\d]/g, '');
    
    // Format with thousand separators
    if (value) {
        value = parseInt(value, 10);
        input.value = value.toLocaleString('id-ID');
    } else {
        input.value = '0';
    }
}

// Tampilkan modal upload
function showUploadForm() {
    document.getElementById('uploadModal').classList.remove('hidden');
    // Set tanggal transfer default ke hari ini
    const now = new Date();
    const localDateTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
    document.getElementById('tanggal_transfer').value = localDateTime;
    
    // Set focus to bank selection
    setTimeout(() => {
        document.getElementById('bank_tujuan').focus();
    }, 100);
}

// Tutup modal upload
function closeUploadModal() {
    document.getElementById('uploadModal').classList.add('hidden');
}

// Tampilkan pratinjau gambar yang diupload
const fileInput = document.getElementById('bukti_transfer');
const previewContainer = document.getElementById('preview-container');
const imagePreview = document.getElementById('image-preview');
const uploadUI = document.getElementById('upload-ui');
const fileNameDisplay = document.getElementById('file-name');

if (fileInput && previewContainer && imagePreview && uploadUI && fileNameDisplay) {
    fileInput.addEventListener('change', function(e) {
        const file = this.files[0];
        if (!file) return;
        
        const fileName = file.name || 'Tidak ada file dipilih';
        fileNameDisplay.textContent = `File: ${fileName}`;
        
        // Cek apakah file adalah gambar
        if (file.type.match('image.*')) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                previewContainer.classList.remove('hidden');
                uploadUI.classList.add('hidden');
            }
            
            reader.readAsDataURL(file);
        } else {
            previewContainer.classList.add('hidden');
            uploadUI.classList.remove('hidden');
        }
    });
}

// Reset preview saat modal ditutup
document.getElementById('uploadModal').addEventListener('hidden.bs.modal', function () {
    if (previewContainer && uploadUI) {
        previewContainer.classList.add('hidden');
        uploadUI.classList.remove('hidden');
    }
});

// Handle form submission
const paymentForm = document.getElementById('paymentForm');
if (paymentForm) {
    paymentForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        
        // Format jumlah_dibayar to remove thousand separators before submitting
        const amountInput = document.getElementById('jumlah_dibayar');
        if (amountInput) {
            formData.set('jumlah_dibayar', amountInput.value.replace(/\./g, ''));
        }
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Mengunggah...';
        
        try {
            // Send data to server
            const response = await fetch('./process/payment.php', {
                method: 'POST',
                body: formData
            });
            
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                throw new Error(`Format respons tidak valid: ${text.substring(0, 100)}...`);
            }
            
            const data = await response.json();
            
            // Handle specific status codes
            if (data.code === 'PAYMENT_ALREADY_CONFIRMED' || data.code === 'PAYMENT_REJECTED' || data.status === 'menunggu') {
                const isRejected = data.code === 'PAYMENT_REJECTED';
                const isWaiting = data.status === 'menunggu';
                const title = isRejected ? 'Pembayaran Ditolak' : (isWaiting ? 'Pembayaran Diterima' : 'Status Pembayaran');
                const icon = isRejected ? 'warning' : (isWaiting ? 'success' : 'info');
                const buttonColor = isRejected ? '#d33' : (isWaiting ? '#28a745' : '#3085d6');
                
                // Show status message
                return Swal.fire({
                    icon: icon,
                    title: title,
                    html: `
                        <div class="text-left">
                            <p class="mb-3">${data.message || 'Terima kasih telah melakukan pembayaran.'}</p>
                            <div class="bg-blue-50 p-3 rounded-md">
                                <p class="text-sm text-gray-700">
                                    <i class="fas ${isRejected ? 'fa-exclamation-triangle' : (isWaiting ? 'fa-clock' : 'fa-info-circle')} mr-2"></i>
                                    <span class="font-medium">Status Pembayaran:</span> ${data.status_display || data.status || 'Menunggu Verifikasi'}
                                </p>
                                ${isWaiting ? `
                                <p class="text-xs text-gray-500 mt-2">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Tim kami akan memverifikasi pembayaran Anda segera.
                                </p>` : ''}
                            </div>
                        </div>
                    `,
                    confirmButtonText: 'Mengerti',
                    confirmButtonColor: buttonColor,
                    allowOutsideClick: !isRejected,
                    allowEscapeKey: true,
                    allowEnterKey: true
                }).then(() => {
                    // Hanya tutup modal tanpa reload halaman
                    closeUploadModal();
                    // Perbarui status pembayaran di halaman jika diperlukan
                    updatePaymentStatus(data.status || 'menunggu', data.status_display || 'Menunggu Verifikasi');
                });
            }
            
            if (!response.ok) {
                throw new Error(data.message || 'Terjadi kesalahan pada server');
            }
            
            if (data.success) {
                // Show success message
                return Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Bukti pembayaran berhasil diupload. Tim kami akan memverifikasi pembayaran Anda.',
                    confirmButtonText: 'OK',
                    allowOutsideClick: false
                }).then(() => {
                    window.location.reload();
                });
            } else {
                throw new Error(data.message || 'Gagal mengupload bukti pembayaran');
            }
        } catch (error) {
            console.error('Error:', error);
            
            // Show error message
            return Swal.fire({
                icon: 'error',
                title: 'Terjadi Kesalahan',
                html: `
                    <div class="text-left">
                        <p class="mb-2">${error.message || 'Terjadi kesalahan saat mengirim data. Silakan coba lagi.'}</p>
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-info-circle mr-1"></i> 
                            Jika masalah berlanjut, silakan hubungi tim dukungan kami.
                        </p>
                    </div>
                `,
                confirmButtonText: 'Mengerti',
                confirmButtonColor: '#d33'
            });
        } finally {
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    });
}

// Fungsi untuk memperbarui status pembayaran di halaman
function updatePaymentStatus(status, statusText) {
    // Update tombol upload bukti pembayaran
    const uploadBtn = document.getElementById('uploadBtn');
    const paymentStatus = document.getElementById('paymentStatus');
    
    if (uploadBtn) {
        uploadBtn.disabled = status === 'menunggu' || status === 'diverifikasi';
        uploadBtn.innerHTML = status === 'menunggu' ? 
            '<i class="fas fa-clock mr-2"></i> Menunggu Verifikasi' :
            (status === 'diverifikasi' ? 
                '<i class="fas fa-check-circle mr-2"></i> Sudah Diverifikasi' :
                '<i class="fas fa-upload mr-2"></i> Unggah Bukti Pembayaran');
        
        // Update class warna tombol
        uploadBtn.className = ''; // Reset class
        if (status === 'menunggu') {
            uploadBtn.classList.add('bg-yellow-500', 'hover:bg-yellow-600');
        } else if (status === 'diverifikasi') {
            uploadBtn.classList.add('bg-green-500', 'hover:bg-green-600');
        } else {
            uploadBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
        }
        uploadBtn.classList.add('text-white', 'font-medium', 'py-2', 'px-4', 'rounded-md', 'transition', 'duration-200');
    }
    
    // Update teks status pembayaran
    if (paymentStatus) {
        paymentStatus.textContent = statusText || 'Menunggu Pembayaran';
        // Update class warna status
        paymentStatus.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium';
        if (status === 'menunggu') {
            paymentStatus.classList.add('bg-yellow-100', 'text-yellow-800');
        } else if (status === 'diverifikasi') {
            paymentStatus.classList.add('bg-green-100', 'text-green-800');
        } else if (status === 'ditolak') {
            paymentStatus.classList.add('bg-red-100', 'text-red-800');
        } else {
            paymentStatus.classList.add('bg-gray-100', 'text-gray-800');
        }
    }
}

// Tutup modal saat mengklik di luar konten
window.onclick = function(event) {
    const modal = document.getElementById('uploadModal');
    if (event.target === modal) {
        closeUploadModal();
    }
}
</script>

