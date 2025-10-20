<?php
require_once __DIR__ . '/./includes/database.php';
session_start();

// Set page title
$page_title = 'Konfirmasi Pemesanan';

// Include header
require_once __DIR__ . '/../templates/partials/header.php';

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Get data from session (set in select_seat.php)
$jadwal_id = $_SESSION['booking_data']['jadwal_id'] ?? null;
$selected_seats = $_SESSION['booking_data']['selected_seats'] ?? [];

// Redirect if essential data is missing
if (empty($jadwal_id) || empty($selected_seats)) {
    $_SESSION['error_message'] = 'Data pemesanan tidak lengkap. Silakan ulangi dari awal.';
    header('Location: /');
    exit;
}

// Fetch Jadwal Details
$jadwal_details = null;
$total_harga = 0;
try {
    $sql = "SELECT 
                j.id_jadwal, j.tanggal_berangkat, j.waktu_berangkat,
                ka.nama_kota AS kota_asal, kca.nama_kecamatan AS kec_asal,
                kt.nama_kota AS kota_tujuan, kct.nama_kecamatan AS kec_tujuan
            FROM jadwal j
            JOIN kota ka ON j.id_kota_asal = ka.id_kota
            JOIN kecamatan kca ON j.id_kecamatan_asal = kca.id_kecamatan
            JOIN kota kt ON j.id_kota_tujuan = kt.id_kota
            JOIN kecamatan kct ON j.id_kecamatan_tujuan = kct.id_kecamatan
            WHERE j.id_jadwal = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) throw new Exception('Gagal menyiapkan statement jadwal: ' . $conn->error);
    $stmt->bind_param("i", $jadwal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $jadwal_details = $result->fetch_assoc();
    $stmt->close();

    if (!$jadwal_details) {
        throw new Exception('Jadwal tidak ditemukan.');
    }

    // Add hardcoded price to jadwal details
    $harga_per_kursi = 75000;
    $jadwal_details['harga_per_kursi'] = $harga_per_kursi;
    
    // Calculate total price
    $total_harga = count($selected_seats) * $harga_per_kursi;

} catch (Exception $e) {
    $_SESSION['error_message'] = 'Terjadi kesalahan saat mengambil detail jadwal: ' . $e->getMessage();
    header('Location: /'); // Redirect to home on error
    exit;
}

// Fetch selected seat numbers
$seat_numbers = [];
if (!empty($selected_seats)) {
    $placeholders = implode(',', array_fill(0, count($selected_seats), '?'));
    $types = str_repeat('i', count($selected_seats));
    $sql_seats = "SELECT nomor_kursi FROM kursi WHERE id_kursi IN ($placeholders) ORDER BY nomor_kursi ASC";
    $stmt_seats = $conn->prepare($sql_seats);
    if ($stmt_seats) {
        $stmt_seats->bind_param($types, ...$selected_seats);
        $stmt_seats->execute();
        $result_seats = $stmt_seats->get_result();
        while ($row = $result_seats->fetch_assoc()) {
            $seat_numbers[] = $row['nomor_kursi'];
        }
        $stmt_seats->close();
    }
}

$conn->close();

$page_title = "Konfirmasi Pemesanan";

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - TravelKita</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6', // Blue-500
                        secondary: '#8b5cf6', // Purple-600
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 font-sans">

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8 md:py-12">
        <div class="bg-white rounded-lg shadow-xl p-6 md:p-8 max-w-3xl mx-auto">
            <h1 class="text-2xl md:text-3xl font-semibold text-gray-800 mb-6 text-center">Konfirmasi Pemesanan Anda</h1>

            <!-- Display Messages -->
            <?php 
            $form_error_message = $_SESSION['form_error_message'] ?? null;
            unset($_SESSION['form_error_message']);
            if ($form_error_message): 
            ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($form_error_message); ?></span>
            </div>
            <?php endif; ?>

            <!-- Order Summary -->
            <div class="border border-gray-200 rounded-lg p-4 md:p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-700 mb-4">Ringkasan Pesanan</h2>
                <div class="space-y-2 text-sm">
                    <p><strong>Jadwal:</strong> <?php echo date('d M Y, H:i', strtotime($jadwal_details['tanggal_berangkat'] . ' ' . $jadwal_details['waktu_berangkat'])); ?></p>
                    <p><strong>Rute:</strong> <?php echo htmlspecialchars($jadwal_details['kota_asal'] . ' (' . $jadwal_details['kec_asal'] . ') â†’ ' . $jadwal_details['kota_tujuan'] . ' (' . $jadwal_details['kec_tujuan'] . ')'); ?></p>
                    <p><strong>Kursi Dipilih:</strong> <?php echo htmlspecialchars(implode(', ', $seat_numbers)); ?> (<?php echo count($selected_seats); ?> kursi)</p>
                    <p><strong>Harga per Kursi:</strong> Rp <?php echo number_format($jadwal_details['harga_per_kursi'], 0, ',', '.'); ?></p>
                    <p class="text-base font-semibold"><strong>Total Harga:</strong> Rp <?php echo number_format($total_harga, 0, ',', '.'); ?></p>
                </div>
            </div>

            <!-- Booker Information Form -->
            <form action="./process/booking.php" method="POST" class="space-y-4" id="bookingForm" onsubmit="return validateForm()">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <?php 
                // Tambahkan input hidden untuk form_submission_id
                $form_submission_id = uniqid('form_', true);
                echo '<input type="hidden" name="form_submission_id" id="form_submission_id" value="' . $form_submission_id . '">';
                
                // Pastikan semua data booking ada dalam form
                if (isset($jadwal_id)) {
                    echo '<input type="hidden" name="jadwal_id" value="' . htmlspecialchars($jadwal_id) . '">';
                }
                if (isset($selected_seats)) {
                    foreach ($selected_seats as $kursi) {
                        echo '<input type="hidden" name="kursi[]" value="' . htmlspecialchars($kursi) . '">';
                    }
                }
                ?>
                
                <h2 class="text-lg font-semibold text-gray-700 mb-4">Data Pemesan</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label for="nama_pemesan" class="block text-sm font-medium text-gray-700">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input type="text" id="nama_pemesan" name="nama_pemesan" required 
                               value="<?php echo htmlspecialchars($_SESSION['form_data']['nama_pemesan'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm"
                               oninvalid="this.setCustomValidity('Mohon isi nama pemesan')"
                               oninput="this.setCustomValidity('')">
                    </div>
                    
                    <div class="space-y-1">
                        <label for="nomor_telepon_pemesan" class="block text-sm font-medium text-gray-700">No. WhatsApp <span class="text-red-500">*</span></label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 text-sm">+62</span>
                            </div>
                            <input type="tel" id="nomor_telepon_pemesan" name="nomor_telepon_pemesan" required 
                                   value="<?php echo htmlspecialchars(ltrim($_SESSION['form_data']['nomor_telepon_pemesan'] ?? '', '0')); ?>"
                                   class="pl-10 w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary text-sm" 
                                   placeholder="8123456789"
                                   pattern="[0-9]{9,13}"
                                   title="Masukkan nomor WhatsApp yang valid (contoh: 8123456789)"
                                   oninvalid="this.setCustomValidity('Mohon isi nomor WhatsApp yang valid (contoh: 8123456789)')"
                                   oninput="this.setCustomValidity('')">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Pastikan nomor aktif untuk konfirmasi pemesanan</p>
                    </div>
                </div>
                
                <div class="space-y-1">
                    <label for="email_pemesan" class="block text-sm font-medium text-gray-700">Alamat Email <span class="text-red-500">*</span></label>
                    <input type="email" id="email_pemesan" name="email_pemesan" required 
                           value="<?php echo htmlspecialchars($_SESSION['form_data']['email_pemesan'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm"
                           placeholder="contoh@email.com"
                           oninvalid="this.setCustomValidity('Mohon isi alamat email yang valid')"
                           oninput="this.setCustomValidity('')">
                    <p class="text-xs text-gray-500 mt-1">Tiket elektronik akan dikirim ke alamat email ini</p>
                </div>
                
                <div class="space-y-1">
                    <label for="alamat_jemput" class="block text-sm font-medium text-gray-700">Alamat Penjemputan <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <textarea id="alamat_jemput" name="alamat_jemput" required rows="3"
                                  class="pl-10 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm"
                                  placeholder="Contoh: Jl. Contoh No. 123, Kec. <?php echo htmlspecialchars($jadwal_details['kec_asal'] ?? ''); ?>, Kota <?php echo htmlspecialchars($jadwal_details['kota_asal'] ?? ''); ?>"
                                  oninvalid="this.setCustomValidity('Mohon isi alamat penjemputan')"
                                  oninput="this.setCustomValidity('')"><?php echo htmlspecialchars($_SESSION['form_data']['alamat_jemput'] ?? ''); ?></textarea>
                    </div>
                    <p class="text-xs text-gray-500">Pastikan alamat penjemputan lengkap dan jelas</p>
                </div>
                
                <div class="space-y-1">
                    <label for="catatan_pemesan" class="block text-sm font-medium text-gray-700">Catatan Tambahan <span class="text-gray-400 font-normal">(Tidak Wajib)</span></label>
                    <textarea id="catatan_pemesan" name="catatan_pemesan" rows="2" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm"
                              placeholder="Contoh: Mohon jemput di depan gerbang utama"><?php echo htmlspecialchars($_SESSION['form_data']['catatan_pemesan'] ?? ''); ?></textarea>
                </div>

                <!-- Submit Button -->
                <div class="mt-6">
                    <button type="submit" id="submitButton" class="w-full bg-blue-600 text-white py-3 px-6 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <span id="buttonText">Lanjutkan Pembayaran</span>
                        <span id="buttonLoader" class="hidden">Menyiapkan pesanan...</span>
                    </button>
                </div>
                
                <script>
                // Validasi form sebelum submit
                function validateForm() {
                    const form = document.getElementById('bookingForm');
                    const nama = document.getElementById('nama_pemesan').value.trim();
                    const telepon = document.getElementById('nomor_telepon_pemesan').value.trim();
                    const email = document.getElementById('email_pemesan').value.trim();
                    const alamat = document.getElementById('alamat_jemput').value.trim();
                    
                    // Reset pesan error
                    document.querySelectorAll('.error-message').forEach(el => el.remove());
                    document.querySelectorAll('input, textarea').forEach(el => el.classList.remove('border-red-500'));
                    
                    let isValid = true;
                    
                    // Validasi nama
                    if (nama === '' || nama.toLowerCase() === 'pelanggan') {
                        showError('nama_pemesan', 'Mohon isi nama pemesan dengan benar');
                        isValid = false;
                    }
                    
                    // Validasi nomor telepon
                    if (!/^[0-9]{9,13}$/.test(telepon)) {
                        showError('nomor_telepon_pemesan', 'Mohon isi nomor WhatsApp yang valid (9-13 digit)');
                        isValid = false;
                    }
                    
                    // Validasi email
                    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        showError('email_pemesan', 'Mohon isi alamat email yang valid');
                        isValid = false;
                    }
                    
                    // Validasi alamat
                    if (alamat === '') {
                        showError('alamat_jemput', 'Mohon isi alamat penjemputan');
                        isValid = false;
                    }
                    
                    if (isValid) {
                        // Tampilkan loading dan nonaktifkan tombol
                        const submitBtn = document.getElementById('submitButton');
                        const buttonText = document.getElementById('buttonText');
                        const buttonLoader = document.getElementById('buttonLoader');
                        
                        submitBtn.disabled = true;
                        buttonText.textContent = 'Memproses...';
                        buttonLoader.classList.remove('hidden');
                        
                        // Submit form
                        form.submit();
                    }
                    
                    return false;
                }
                
                function showError(fieldId, message) {
                    const field = document.getElementById(fieldId);
                    field.classList.add('border-red-500');
                    
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'text-red-500 text-xs mt-1 error-message';
                    errorDiv.textContent = message;
                    
                    field.parentNode.insertBefore(errorDiv, field.nextSibling);
                }
                
                document.addEventListener('DOMContentLoaded', function() {
                    const form = document.getElementById('bookingForm');
                    const submitBtn = document.getElementById('submitButton');
                    const buttonText = document.getElementById('buttonText');
                    const buttonLoader = document.getElementById('buttonLoader');
                    let isSubmitting = false;

                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        
                        if (isSubmitting) return false;

                        const nama = document.getElementById('nama_pemesan').value.trim();
                        const telp = document.getElementById('nomor_telepon_pemesan').value.trim();
                        const email = document.getElementById('email_pemesan').value.trim();
                        const alamat = document.getElementById('alamat_jemput').value.trim();
                        
                        // Reset error states
                        document.querySelectorAll('.error-message').forEach(el => el.remove());
                        document.querySelectorAll('.border-red-500').forEach(el => 
                            el.classList.remove('border-red-500'));
                        
                        let isValid = true;
                        
                        // Validasi nama
                        if (nama === '' || nama.toLowerCase() === 'pelanggan') {
                            showError('nama_pemesan', 'Mohon isi nama pemesan dengan benar');
                            isValid = false;
                        }
                        
                        // Validasi nomor telepon
                        const telpClean = telp.replace(/[^0-9]/g, '');
                        if (telpClean === '') {
                            showError('nomor_telepon_pemesan', 'Mohon isi nomor WhatsApp');
                            isValid = false;
                        } else if (telpClean.length < 10 || telpClean.length > 15) {
                            showError('nomor_telepon_pemesan', 'Nomor telepon harus 10-15 digit');
                            isValid = false;
                        }
                        
                        // Validasi email
                        if (email === '') {
                            showError('email_pemesan', 'Mohon isi alamat email');
                            isValid = false;
                        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                            showError('email_pemesan', 'Format email tidak valid');
                            isValid = false;
                        }
                        
                        // Validasi alamat
                        if (alamat === '') {
                            showError('alamat_jemput', 'Mohon isi alamat penjemputan');
                            isValid = false;
                        }
                        
                        if (!isValid) {
                            window.scrollTo({
                                top: document.querySelector('.error-message')?.offsetTop - 100 || 0,
                                behavior: 'smooth'
                            });
                            return false;
                        }
                        
                        // Disable form and show loading
                        isSubmitting = true;
                        submitBtn.disabled = true;
                        buttonText.classList.add('hidden');
                        buttonLoader.classList.remove('hidden');
                        
                        // Prepare form data
                        const formData = new FormData(form);
                        
                        // Send AJAX request
                        fetch(form.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success && data.redirect) {
                                window.location.href = data.redirect;
                            } else if (data.message) {
                                showFormError(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showFormError('Terjadi kesalahan saat memproses pesanan. Silakan coba lagi.');
                        })
                        .finally(() => {
                            isSubmitting = false;
                            submitBtn.disabled = false;
                            buttonText.classList.remove('hidden');
                            buttonLoader.classList.add('hidden');
                        });
                        
                        return false;
                    });
                    
                    function showError(fieldId, message) {
                        const field = document.getElementById(fieldId);
                        if (!field) return;
                        
                        field.classList.add('border-red-500');
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'text-red-500 text-xs mt-1 error-message';
                        errorDiv.textContent = message;
                        
                        const parent = field.closest('div.space-y-1') || field.parentElement;
                        parent.appendChild(errorDiv);
                    }
                    
                    function showFormError(message) {
                        // Show error message at the top of the form
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4';
                        errorDiv.textContent = message;
                        
                        const form = document.getElementById('bookingForm');
                        form.insertBefore(errorDiv, form.firstChild);
                        
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    }
                });
                </script>        Dengan mengklik tombol di atas, Anda menyetujui
                        <a href="#" class="text-primary hover:text-blue-800 font-medium">Syarat & Ketentuan</a> dan
                        <a href="#" class="text-primary hover:text-blue-800 font-medium">Kebijakan Privasi</a> kami.
                    </p>
                </div>
            </form>
            <?php unset($_SESSION['form_data']); // Clear form data after displaying ?>
        </div>
    </main>

<?php require_once __DIR__ . '/../templates/partials/footer.php'; ?>

