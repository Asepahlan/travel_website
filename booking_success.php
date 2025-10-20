<?php
require_once __DIR__ . '/config/database.php';
session_start();

// Check if booking code is provided
$booking_code = $_GET['code'] ?? '';

if (empty($booking_code)) {
    header('Location: index.php?error=invalid_booking_code');
    exit;
}

$page_title = 'Pemesanan Berhasil';

// Check if this is a valid booking redirect from session
$is_valid_redirect = isset($_SESSION['booking_redirect']) && $_SESSION['booking_redirect'] === 'true';

// Get booking details
try {
    $stmt = $conn->prepare("
        SELECT r.*, j.tanggal_berangkat, j.waktu_berangkat, j.harga,
               ka.nama_kota AS nama_kota_asal,
               kt.nama_kota AS nama_kota_tujuan
        FROM reservasi r
        JOIN jadwal j ON r.id_jadwal = j.id_jadwal
        JOIN kota ka ON j.id_kota_asal = ka.id_kota
        JOIN kota kt ON j.id_kota_tujuan = kt.id_kota
        WHERE r.kode_booking = ? AND r.status = 'pending'
    ");

    $stmt->bind_param('s', $booking_code);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$booking) {
        header('Location: index.php?error=booking_not_found');
        exit;
    }

    // Get seat details
    $stmt_seats = $conn->prepare("
        SELECT k.nomor_kursi, k.posisi_x, k.posisi_y
        FROM detail_reservasi_kursi drk
        JOIN kursi k ON drk.id_kursi = k.id_kursi
        WHERE drk.id_reservasi = ?
        ORDER BY k.nomor_kursi
    ");

    $stmt_seats->bind_param('i', $booking['id_reservasi']);
    $stmt_seats->execute();
    $seats_result = $stmt_seats->get_result();
    $selected_seats = [];

    while ($seat = $seats_result->fetch_assoc()) {
        $selected_seats[] = $seat;
    }

    $stmt_seats->close();

    // Format data for display
    $tanggal_tampil = date("d M Y", strtotime($booking['tanggal_berangkat']));
    $waktu_tampil = date("H:i", strtotime($booking['waktu_berangkat']));

    // Clear the booking redirect flag after successful validation
    unset($_SESSION['booking_redirect']);

} catch (Exception $e) {
    error_log('Error fetching booking details: ' . $e->getMessage());
    header('Location: index.php?error=system_error');
    exit;
}

require_once __DIR__ . '/templates/partials/header.php';
?>

<div class="min-h-screen bg-gradient-to-br from-green-50 to-green-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Success Header -->
        <div class="text-center mb-12">
            <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 mb-6">
                <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-4xl font-extrabold text-gray-900 mb-4 bg-clip-text text-transparent bg-gradient-to-r from-green-600 to-green-700">
                Pemesanan Berhasil!
            </h1>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                Terima kasih atas pemesanan Anda. Berikut adalah detail pemesanan yang telah kami terima.
            </p>
        </div>

        <!-- Booking Details Card -->
        <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
            <div class="px-8 py-6 bg-gradient-to-r from-green-600 to-green-700 text-white">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-bold mb-2">Detail Pemesanan</h2>
                        <p class="text-green-100">Kode Booking: <span class="font-mono text-lg"><?php echo htmlspecialchars($booking_code); ?></span></p>
                    </div>
                    <div class="mt-4 sm:mt-0">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-500 text-white">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Menunggu Pembayaran
                        </span>
                    </div>
                </div>
            </div>

            <div class="p-8">
                <!-- Journey Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <div class="bg-gray-50 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Informasi Perjalanan
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Rute:</span>
                                <span class="font-medium text-gray-800">
                                    <?php echo htmlspecialchars($booking['nama_kota_asal'] . ' → ' . $booking['nama_kota_tujuan']); ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tanggal:</span>
                                <span class="font-medium text-gray-800"><?php echo htmlspecialchars($tanggal_tampil); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Waktu:</span>
                                <span class="font-medium text-gray-800"><?php echo htmlspecialchars($waktu_tampil); ?> WIB</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Informasi Penumpang
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Nama:</span>
                                <span class="font-medium text-gray-800"><?php echo htmlspecialchars($booking['nama_pemesan']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Telepon:</span>
                                <span class="font-medium text-gray-800"><?php echo htmlspecialchars($booking['no_hp']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Email:</span>
                                <span class="font-medium text-gray-800"><?php echo htmlspecialchars($booking['email']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Alamat Jemput:</span>
                                <span class="font-medium text-gray-800"><?php echo htmlspecialchars($booking['alamat_jemput']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seat Information -->
                <div class="bg-gray-50 rounded-xl p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z M14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z M4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z M14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                        Kursi yang Dipilih
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                        <?php foreach ($selected_seats as $seat): ?>
                            <div class="bg-white border-2 border-green-500 rounded-lg p-3 text-center">
                                <div class="text-sm font-medium text-gray-600">Kursi</div>
                                <div class="text-lg font-bold text-green-600"><?php echo htmlspecialchars($seat['nomor_kursi']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="bg-gradient-to-r from-green-50 to-green-100 rounded-xl p-6 border border-green-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Informasi Pembayaran
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="text-sm text-gray-600 mb-2">Total Pembayaran:</div>
                            <div class="text-3xl font-bold text-green-600">
                                Rp <?php echo number_format($booking['total_harga'], 0, ',', '.'); ?>
                            </div>
                            <div class="text-sm text-gray-500 mt-1">Harga sudah termasuk PPN</div>
                        </div>

                        <div>
                            <div class="text-sm text-gray-600 mb-2">Status Pembayaran:</div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                Menunggu Pembayaran
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-8 flex flex-col sm:flex-row gap-4">
                    <a href="index.php" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-6 rounded-lg text-center transition-colors">
                        Kembali ke Beranda
                    </a>
                    <a href="jadwal.php" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg text-center transition-colors">
                        Lihat Jadwal Lain
                    </a>
                    <a href="https://wa.me/6285798347675?text=Halo%20Admin%2C%20saya%20ingin%20melakukan%20pembayaran%20untuk%20kode%20booking%20<?php echo urlencode($booking_code); ?>%0A%0A%2ADetail%20Pemesanan%3A%2A%0ANama%3A%20<?php echo urlencode($booking['nama_pemesan']); ?>%0ATelepon%3A%20<?php echo urlencode($booking['no_hp']); ?>%0AEmail%3A%20<?php echo urlencode($booking['email']); ?>%0A%0A%2AInformasi%20Perjalanan%3A%2A%0ARute%3A%20<?php echo urlencode($booking['nama_kota_asal'] . ' → ' . $booking['nama_kota_tujuan']); ?>%0ATanggal%3A%20<?php echo urlencode($tanggal_tampil); ?>%0AWaktu%3A%20<?php echo urlencode($waktu_tampil); ?>%20WIB%0A%0A%2AKursi%20Dipilih%3A%2A%0A<?php echo urlencode(implode(', ', array_column($selected_seats, 'nomor_kursi'))); ?>%0A%0A%2AAlamat%20Jemput%3A%2A%0A<?php echo urlencode($booking['alamat_jemput']); ?>%0A%0A%2ATotal%20Pembayaran%3A%2A%0ARp%20<?php echo urlencode(number_format($booking['total_harga'], 0, ',', '.')); ?>%0A%0AMohon%20konfirmasi%20pembayaran.%20Terima%20kasih."
                       target="_blank"
                       class="flex-1 bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-6 rounded-lg text-center transition-colors flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.465 3.516"/>
                        </svg>
                        Bayar via WhatsApp
                    </a>
                </div>

                <!-- Important Notes -->
                <div class="mt-8 bg-amber-50 border-l-4 border-amber-400 p-4 rounded-r-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-amber-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-amber-800">Penting!</h4>
                            <div class="mt-2 text-sm text-amber-700">
                                <p>• Silakan lakukan pembayaran dalam waktu 30 menit untuk mengkonfirmasi pemesanan</p>
                                <p>• Admin akan menghubungi Anda melalui WhatsApp untuk konfirmasi pembayaran</p>
                                <p>• Simpan kode booking ini sebagai referensi</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/partials/footer.php'; ?>
</body>
</html>
