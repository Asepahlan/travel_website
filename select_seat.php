<?php
require_once __DIR__ . '/config/database.php';
session_start();

require_once __DIR__ . '/templates/partials/header.php';

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get jadwal_id and alamat_jemput from URL
$jadwal_id = (int)($_GET['jadwal_id'] ?? null);
$alamat_jemput = htmlspecialchars($_GET['alamat_jemput'] ?? '', ENT_QUOTES);

if (empty($jadwal_id) || empty($alamat_jemput)) {
    header('Location: index.php?error=missing_params');
    exit;
}

$page_title = 'Pilih Kursi';

// Fetch schedule details
$stmt_jadwal = $conn->prepare("SELECT 
        j.id_jadwal, 
        j.tanggal_berangkat, 
        j.waktu_berangkat, 
        j.harga,
        j.estimasi_jam,
        ka.nama_kota AS nama_kota_asal, 
        kca.nama_kecamatan AS nama_kecamatan_asal, 
        kt.nama_kota AS nama_kota_tujuan, 
        kct.nama_kecamatan AS nama_kecamatan_tujuan,
        CASE 
            WHEN j.harga IS NOT NULL AND j.harga > 0 THEN j.harga
            ELSE 75000 
        END AS harga_per_kursi
    FROM jadwal j 
    JOIN kota ka ON j.id_kota_asal = ka.id_kota 
    JOIN kecamatan kca ON j.id_kecamatan_asal = kca.id_kecamatan 
    JOIN kota kt ON j.id_kota_tujuan = kt.id_kota 
    JOIN kecamatan kct ON j.id_kecamatan_tujuan = kct.id_kecamatan 
    WHERE j.id_jadwal = ?");
$stmt_jadwal->bind_param("i", $jadwal_id);
$stmt_jadwal->execute();
$result_jadwal = $stmt_jadwal->get_result();
$jadwal = $result_jadwal->fetch_assoc();
$stmt_jadwal->close();

if (!$jadwal) {
    header('Location: index.php?error=jadwal_not_found');
    exit;
}

// Get layout for this schedule
$stmt_layout = $conn->prepare("SELECT lk.* FROM layout_kursi lk 
    JOIN jadwal j ON j.id_layout_kursi = lk.id_layout 
    WHERE j.id_jadwal = ?");
$stmt_layout->bind_param("i", $jadwal_id);
$stmt_layout->execute();
$layout_result = $stmt_layout->get_result();
$layout = $layout_result->fetch_assoc();
$stmt_layout->close();

if (!$layout) {
    // Fallback to first available layout if none assigned
    $layout_query = "SELECT * FROM layout_kursi ORDER BY id_layout ASC LIMIT 1";
    $layout_result = $conn->query($layout_query);
    $layout = $layout_result->fetch_assoc();
}

$layout_id = $layout ? $layout['id_layout'] : 1;

// Fetch all seats for the layout
$stmt_kursi = $conn->prepare("SELECT id_kursi, nomor_kursi, posisi_x, posisi_y, status 
    FROM kursi 
    WHERE id_layout = ? 
    ORDER BY 
        SUBSTRING_INDEX(nomor_kursi, ' ', -1) + 0,  -- Sort by row number
        SUBSTRING_INDEX(nomor_kursi, ' ', 1)        -- Then by column letter
    ");

if (!$stmt_kursi) {
    die('Error preparing statement: ' . $conn->error);
}

$stmt_kursi->bind_param("i", $layout_id);
$stmt_kursi->execute();
$result_kursi = $stmt_kursi->get_result();
$all_seats = [];
while ($row = $result_kursi->fetch_assoc()) {
    $all_seats[$row['id_kursi']] = $row;
}
$stmt_kursi->close();

// Fetch booked seats for this schedule
$stmt_booked = $conn->prepare("SELECT drk.id_kursi 
    FROM detail_reservasi_kursi drk 
    JOIN reservasi r ON drk.id_reservasi = r.id_reservasi 
    WHERE r.id_jadwal = ? 
    AND r.status IN ('dibayar', 'pending', 'dikonfirmasi')");

if ($stmt_booked === false) {
    die('Error preparing statement: ' . $conn->error);
}

$stmt_booked->bind_param("i", $jadwal_id);
$stmt_booked->execute();
$result_booked = $stmt_booked->get_result();
$booked_seat_ids = [];

if ($result_booked) {
    while ($row = $result_booked->fetch_assoc()) {
        $booked_seat_ids[] = $row['id_kursi'];
    }
}

$stmt_booked->close();
$stmt_booked = $conn->prepare('SELECT drk.id_kursi FROM detail_reservasi_kursi drk JOIN reservasi r ON drk.id_reservasi = r.id_reservasi WHERE r.id_jadwal = ? AND r.status IN ("dibayar", "pending", "dikonfirmasi")');
$stmt_booked->bind_param("i", $jadwal_id);
$stmt_booked->execute();
$result_booked = $stmt_booked->get_result();
$booked_seat_ids = [];

if ($result_booked) {
    while ($row = $result_booked->fetch_assoc()) {
        $booked_seat_ids[] = $row['id_kursi'];
    }
}

$stmt_booked->close();
$conn->close();

// Format data for display
$tanggal_tampil = date("d M Y", strtotime($jadwal['tanggal_berangkat']));
$waktu_tampil = date("H:i", strtotime($jadwal['waktu_berangkat']));
$harga_per_kursi = $jadwal['harga_per_kursi'];

// Generate a unique seat session ID
if (!isset($_SESSION['seat_selection'])) {
    $_SESSION['seat_selection'] = [];
}

$seat_session_id = 'sess_' . bin2hex(random_bytes(8));
$_SESSION['seat_selection'][$seat_session_id] = [
    'jadwal_id' => $jadwal_id,
    'expires' => time() + 1800, // 30 minutes expiration
];
?>

<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - TravelKita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#00BFFF',
                        secondary: '#0099cc',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .seat {
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        .seat::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.1);
            opacity: 0;
            transition: opacity 0.2s;
        }
        .seat:hover::after {
            opacity: 1;
        }
        .seat-selected {
            background-color: #2563eb !important;
            color: white !important;
            border-color: #1d4ed8 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .seat-selected::before {
            content: '✓';
            position: absolute;
            top: 2px;
            right: 2px;
            font-size: 10px;
            color: white;
        }
        .seat-legend {
            width: 20px;
            height: 20px;
            display: inline-block;
            margin-right: 8px;
            vertical-align: middle;
        }
        @media (max-width: 768px) {
            .seat-grid {
                grid-template-columns: repeat(4, 1fr) !important;
            }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans min-h-screen">
    <!-- Header -->
    <!-- <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <a href="index.php" class="text-2xl font-bold text-primary">TravelKita</a>
                </div>
                <nav class="hidden md:flex space-x-8">
                    <a href="index.php" class="text-gray-600 hover:text-primary transition-colors">Beranda</a>
                    <a href="jadwal.php" class="text-gray-600 hover:text-primary transition-colors">Jadwal</a>
                    <a href="kontak.php" class="text-gray-600 hover:text-primary transition-colors">Kontak</a>
                </nav>
                <div class="flex items-center space-x-4">
                    <a href="admin/login.php" class="text-gray-600 hover:text-primary transition-colors">Admin</a>
                </div>
            </div>
        </div>
    </header> -->

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Pilih Kursi Anda</h1>
                <p class="text-gray-600">Silakan pilih kursi yang tersedia untuk perjalanan Anda</p>
            </div>
            
            <!-- Travel Details Card -->
            <div class="bg-blue-50 rounded-xl p-6 mb-8 border border-blue-100">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Detail Perjalanan
                </h2>
                <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm font-medium rounded-full">
                    <?php echo htmlspecialchars($jadwal['nama_kota_asal'] . ' â†’ ' . $jadwal['nama_kota_tujuan']); ?>
                </span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="flex items-center text-gray-600 mb-1">
                        <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span class="font-medium">Tanggal</span>
                    </div>
                    <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($tanggal_tampil); ?></p>
                </div>
                
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="flex items-center text-gray-600 mb-1">
                        <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-medium">Berangkat</span>
                    </div>
                    <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($waktu_tampil); ?> WIB</p>
                </div>
                
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="flex items-center text-gray-600 mb-1">
                        <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span class="font-medium">Harga/Kursi</span>
                    </div>
                    <p class="text-lg font-bold text-blue-600">Rp <?php echo number_format($harga_per_kursi, 0, ',', '.'); ?></p>
                </div>
            </div>
        </section>

        <!-- Seat Selection -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Seat Map -->
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-md border border-gray-100">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z M14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z M4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z M14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                        Pilih Kursi
                    </h2>
                    <!-- Legend -->
                    <div class="flex flex-wrap gap-6 mb-8 p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <span class="w-5 h-5 bg-green-400 rounded-md mr-2 border border-green-500"></span>
                            <span class="text-gray-700 font-medium">Tersedia</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-5 h-5 bg-blue-600 rounded-md mr-2 border border-blue-700"></span>
                            <span class="text-gray-700 font-medium">Dipilih</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-5 h-5 bg-gray-300 rounded-md mr-2 border border-gray-400"></span>
                            <span class="text-gray-500 font-medium">Terisi</span>
                        </div>
                    </div>
                </div>

                <?php if ($layout && !empty($layout['gambar_layout'])): 
                    $image_path = '/travel_website/public/uploads/layouts/' . basename($layout['gambar_layout']);
                ?>
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <img src="<?php echo htmlspecialchars($image_path); ?>" alt="Layout Kursi" class="w-full max-w-md mx-auto rounded">
                    </div>
                <?php endif; ?>
                
                <?php if ($layout && !empty($all_seats)): ?>
                    
                    <div class="seat-grid grid grid-cols-5 md:grid-cols-7 lg:grid-cols-10 gap-2 p-4 bg-gray-50 rounded-lg">
                        <?php foreach ($all_seats as $seat): 
                            $is_booked = in_array($seat['id_kursi'], $booked_seat_ids);
                            $seat_class = 'seat w-10 h-10 flex items-center justify-center rounded-md font-medium text-sm transition-all duration-200 ';
                            
                            if ($is_booked) {
                                $seat_class .= 'bg-gray-300 cursor-not-allowed';
                            } else {
                                $seat_class .= 'bg-white border-2 border-green-500 hover:border-blue-600 hover:bg-blue-50 cursor-pointer';
                            }
                        ?>
                            <button type="button" 
                                    class="<?php echo $seat_class; ?>" 
                                    data-seat-id="<?php echo $seat['id_kursi']; ?>"
                                    data-seat-number="<?php echo htmlspecialchars($seat['nomor_kursi']); ?>"
                                    <?php echo $is_booked ? 'disabled' : ''; ?>>
                                <?php echo htmlspecialchars(preg_replace('/^[A-Za-z]+\s*/', '', $seat['nomor_kursi'])); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 bg-gray-50 rounded-lg">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada kursi tersedia</h3>
                        <p class="mt-1 text-sm text-gray-500">Silakan hubungi admin untuk informasi lebih lanjut.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Ringkasan Pemesanan -->
            <div class="sticky top-6">
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-gray-800 mb-6 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            Ringkasan Pemesanan
                        </h2>
                        
                        <div class="space-y-5">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-gray-600">Rute</span>
                                    <span class="font-medium text-gray-800"><?php echo htmlspecialchars($jadwal['nama_kota_asal'] . ' â†’ ' . $jadwal['nama_kota_tujuan']); ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Tanggal & Waktu</span>
                                    <div class="text-right">
                                        <div class="font-medium text-gray-800"><?php echo htmlspecialchars($tanggal_tampil); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($waktu_tampil); ?> WIB</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="border-t border-gray-100 pt-4">
                                <h3 class="font-medium text-gray-700 mb-3 flex items-center text-lg">
                                    <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                    Kursi Dipilih
                                </h3>
                                <div id="selected-seats-display" class="bg-gray-50 p-4 rounded-lg border border-gray-200 min-h-16 flex items-center justify-center">
                                    <p class="text-gray-400 text-center">Belum ada kursi dipilih</p>
                                </div>
                            </div>
                    
                    <!-- Form Data Pemesan -->
                    <div id="booking-form-container" class="hidden mt-6">
                        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                            <h3 class="text-xl font-semibold text-gray-800 mb-6 pb-3 border-b border-gray-100 flex items-center">
                                <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Data Pemesan
                                <span class="ml-auto text-sm font-normal text-gray-500">Semua field wajib diisi</span>
                            </h3>
                            
                            <div class="space-y-4">
                                <!-- Nama Lengkap -->
                                <div>
                                    <label for="nama_pemesan" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                                    <div class="relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                        </div>
                                        <input type="text" id="nama_pemesan" name="nama_pemesan" required
                                            class="form-input block w-full pl-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Nama sesuai KTP/identitas"
                                            minlength="3" maxlength="100"
                                            oninvalid="this.setCustomValidity('Harap isi nama lengkap dengan benar')"
                                            oninput="this.setCustomValidity('')">
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">Minimal 3 karakter, maksimal 100 karakter</p>
                                </div>

                                <!-- Nomor Telepon dan Email -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="nomor_telepon_pemesan" class="block text-sm font-medium text-gray-700 mb-1">Nomor Telepon <span class="text-red-500">*</span></label>
                                        <div class="relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500 sm:text-sm">+62</span>
                                            </div>
                                            <input type="tel" id="nomor_telepon_pemesan" name="nomor_telepon_pemesan" required
                                                class="form-input block w-full pl-12 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="85798347675"
                                                minlength="10"
                                                maxlength="13"
                                                pattern="[0-9]{10,13}"
                                                oninvalid="this.setCustomValidity('Nomor telepon harus 10-13 digit angka')"
                                                oninput="this.value = this.value.replace(/[^0-9]/g, ''); this.setCustomValidity('')">
                                            <div id="nomor_telepon_pemesan-error" class="mt-1 text-sm text-red-600 hidden"></div>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">Contoh: 85798347675</p>
                                    </div>

                                    <div>
                                        <label for="email_pemesan" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                                        <div class="relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                            <input type="email" id="email_pemesan" name="email_pemesan" required
                                                class="form-input block w-full pl-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="email@contoh.com"
                                                oninvalid="this.setCustomValidity('Harap masukkan alamat email yang valid')"
                                                oninput="this.setCustomValidity('')">
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">Email aktif untuk konfirmasi booking</p>
                                    </div>
                                </div>

                                <!-- Alamat Penjemputan -->
                                <div>
                                    <label for="alamat_jemput" class="block text-sm font-medium text-gray-700 mb-1">Alamat Penjemputan <span class="text-red-500">*</span></label>
                                    <div class="relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                        </div>
                                        <input type="text" id="alamat_jemput" name="alamat_jemput" required
                                            class="form-input block w-full pl-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Contoh: Jl. Contoh No. 123, Kec. Contoh, Kota"
                                            oninvalid="this.setCustomValidity('Harap isi alamat penjemputan')"
                                            oninput="this.setCustomValidity('')">
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">Mohon isi alamat lengkap untuk penjemputan</p>
                                </div>

                                <!-- Catatan Tambahan -->
                                <div>
                                    <label for="catatan" class="block text-sm font-medium text-gray-700 mb-1">Catatan Tambahan</label>
                                    <textarea id="catatan" name="catatan" rows="2"
                                        class="form-textarea block w-full py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Contoh: Titik penjemputan khusus, kebutuhan khusus, dll." maxlength="500"></textarea>
                                    <p class="mt-1 text-xs text-gray-500">Maksimal 500 karakter</p>
                                </div>

                                <!-- Syarat dan Ketentuan -->
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="terms" name="terms" type="checkbox" required
                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                            oninvalid="this.setCustomValidity('Anda harus menyetujui syarat dan ketentuan')"
                                            oninput="this.setCustomValidity('')">
                                    </div>
                                    <div class="ml-3">
                                        <label for="terms" class="font-medium text-gray-700">Saya setuju dengan <a href="syarat-ketentuan.php" target="_blank" class="text-blue-600 hover:text-blue-800">Syarat dan Ketentuan</a> yang berlaku</label>
                                        <p class="text-xs text-gray-500 mt-1">Dengan mencentang ini, Anda menyetujui semua persyaratan yang berlaku.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Harga -->
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-6 rounded-xl text-white shadow-lg mb-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="text-sm font-medium text-blue-100">Total Pembayaran</div>
                                <div class="text-xs text-blue-200">Harga sudah termasuk PPN</div>
                            </div>
                            <div id="total-price" class="text-3xl font-bold">Rp 0</div>
                        </div>
                    </div>
                    
                    <!-- Form Pemesanan -->
                    <form id="seat-form" method="POST" class="space-y-4" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                        <input type="hidden" name="form_submission_id" value="<?php echo uniqid('booking_', true); ?>">
                        <input type="hidden" name="jadwal_id" value="<?php echo htmlspecialchars($jadwal_id); ?>">
                        <input type="hidden" id="form-nama" name="nama_pemesan" value="">
                        <input type="hidden" id="form-telepon" name="nomor_telepon_pemesan" value="">
                        <input type="hidden" id="form-email" name="email_pemesan" value="">
                        <input type="hidden" id="form_alamat_jemput" name="alamat_jemput" value="<?php echo htmlspecialchars($alamat_jemput); ?>">
                        <input type="hidden" id="form_total_harga" name="total_harga" value="0">
                        <input type="hidden" id="form_selected_seats" name="selected_seats" value="">
                        <div class="grid grid-cols-2 gap-3">
                            <a href="jadwal.php" class="px-4 py-3.5 bg-white border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center text-sm sm:text-base">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1 sm:mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                <span class="truncate">Kembali</span>
                            </a>
                            <button type="button" id="continue-btn" class="px-4 py-3.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-medium rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all transform hover:-translate-y-0.5 shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none disabled:shadow-none flex items-center justify-center text-sm sm:text-base" disabled>
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1 sm:mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="truncate">Lanjutkan</span>
                                <span id="loading-spinner" class="hidden ml-1 sm:ml-2">
                                    <svg class="animate-spin h-4 w-4 sm:h-5 sm:w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                        
                        <!-- Pesan Peringatan -->
                        <div class="mt-4 bg-amber-50 border-l-4 border-amber-400 p-4 rounded-r-lg">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-amber-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-amber-700">
                                        Pastikan untuk memeriksa kembali pilihan kursi dan data pemesan sebelum melanjutkan ke pembayaran.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Confirmation Modal -->
    <div id="confirm-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-6 text-white text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-white/20 mb-4">
                    <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold mb-2">Konfirmasi Pemesanan</h3>
                <p class="text-blue-100">Pastikan data yang Anda masukkan sudah benar</p>
            </div>
            
            <!-- Booking Details -->
            <div class="p-6">
                <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
                    <h4 class="font-medium text-gray-800 text-lg mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        Detail Pemesanan
                    </h4>
                    
                    <div class="space-y-4 mt-4">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Rute</span>
                            <span class="font-medium text-gray-800" id="confirm-route"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tanggal & Waktu</span>
                            <div class="text-right">
                                <div class="font-medium text-gray-800" id="confirm-date"></div>
                                <div class="text-sm text-gray-500" id="confirm-time"></div>
                            </div>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Kursi</span>
                            <span class="font-medium text-gray-800" id="confirm-seats"></span>
                        </div>
                        <div class="border-t border-gray-200 my-3"></div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 font-medium">Total Pembayaran</span>
                            <span class="text-xl font-bold text-blue-600" id="confirm-total"></span>
                        </div>
                    </div>
                    
                    <div class="mt-6 bg-amber-50 border-l-4 border-amber-400 p-4 rounded-r">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-amber-700">
                                    Pastikan untuk memeriksa kembali data pemesanan Anda sebelum melanjutkan.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="mt-6 flex flex-col sm:flex-row gap-3">
                    <button type="button" onclick="closeModal()" class="px-6 py-3 bg-white border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors flex-1">
                        Kembali
                    </button>
                    <button type="button" onclick="document.getElementById('seat-form').submit()" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-medium rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-md hover:shadow-lg flex-1 flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Konfirmasi Pemesanan
                    </button>
                </div>
            </div>
        </div>
    </main>

    <style>
        .seat {
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        .seat::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.1);
            opacity: 0;
            transition: opacity 0.2s;
        }
        .seat:hover::after {
            opacity: 1;
        }
        .seat-selected {
            background-color: #2563eb !important;
            color: white !important;
            border-color: #1d4ed8 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .seat-selected::before {
            content: 'âœ“';
            position: absolute;
            top: 2px;
            right: 2px;
            font-size: 10px;
            color: white;
        }
        .seat-legend {
            width: 20px;
            height: 20px;
            display: inline-block;
            margin-right: 8px;
            vertical-align: middle;
        }
        @media (max-width: 768px) {
            .seat-grid {
                grid-template-columns: repeat(4, 1fr) !important;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            const bookingFormContainer = document.getElementById('booking-form-container');
            
            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', () => {
                    mobileMenu.classList.toggle('hidden');
                });
            }

            // Seat selection
            const seatButtons = document.querySelectorAll('.seat');
            const selectedSeatsInput = document.getElementById('form_selected_seats');
            const selectedSeatsDisplay = document.getElementById('selected-seats-display');
            const totalPriceDisplay = document.getElementById('total-price');
            const pricePerSeat = <?php echo $harga_per_kursi; ?>;
            const seatForm = document.getElementById('seat-form');
            let selectedSeats = [];
            
            // Inisialisasi form
            updateSelection();
            updateBookingForm();
            
            // Initialize seat buttons
            seatButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const seatId = this.dataset.seatId;
                    const seatNumber = this.textContent.trim();
                    
                    // Check if seat is already selected
                    const seatIndex = selectedSeats.findIndex(seat => seat.id === seatId);
                    
                    if (seatIndex === -1) {
                        // Check if this seat number is already in the selection
                        const isSeatNumberExists = selectedSeats.some(seat => seat.number === seatNumber);
                        
                        if (isSeatNumberExists) {
                            // If seat number already exists, show error and don't add it
                            alert('Kursi ' + seatNumber + ' sudah dipilih. Silakan pilih kursi lain.');
                            return;
                        }
                        
                        // Add to selection if not already selected
                        selectedSeats.push({ id: seatId, number: seatNumber });
                        this.classList.remove('bg-white', 'border-green-400', 'hover:border-blue-500', 'hover:bg-blue-50');
                        this.classList.add('seat-selected', 'bg-blue-600');
                    } else {
                        // Remove from selection if already selected
                        selectedSeats.splice(seatIndex, 1);
                        this.classList.remove('seat-selected', 'bg-blue-600');
                        this.classList.add('bg-white', 'border-green-400', 'hover:border-blue-500', 'hover:bg-blue-50');
                    }
                    
                    // Update UI
                    updateSelection();
                });
            });
            
            function updateSelection() {
                const continueBtn = document.getElementById('continue-btn');
                if (!continueBtn) return;
                
                const totalPrice = selectedSeats.length * pricePerSeat;
                
                // Update selected seats display
                if (selectedSeats.length > 0) {
                    if (selectedSeatsDisplay) {
                        selectedSeatsDisplay.textContent = selectedSeats.map(seat => seat.number || seat.id).join(', ');
                    }
                    if (totalPriceDisplay) {
                        totalPriceDisplay.textContent = 'Rp ' + totalPrice.toLocaleString('id-ID');
                    }
                    
                    continueBtn.disabled = false;
                    const buttonText = continueBtn.querySelector('span:not(.hidden)') || continueBtn;
                    buttonText.textContent = 'Lanjutkan Pembayaran';
                } else {
                    if (selectedSeatsDisplay) {
                        selectedSeatsDisplay.textContent = '-';
                    }
                    if (totalPriceDisplay) {
                        totalPriceDisplay.textContent = 'Rp 0';
                    }
                    continueBtn.disabled = true;
                    const buttonText = continueBtn.querySelector('span:not(.hidden)') || continueBtn;
                    buttonText.textContent = 'Pilih Kursi Terlebih Dahulu';
                }
                
                // Update hidden input with seat data in the correct format
                if (selectedSeatsInput) {
                    const seatData = selectedSeats.map(seat => ({
                        id_kursi: seat.id,
                        nomor_kursi: seat.number || seat.id
                    }));
                    selectedSeatsInput.value = JSON.stringify(seatData);
                }
                
                // Update booking form visibility
                updateBookingForm();
            }
            
            // Show booking form when seats are selected
            function updateBookingForm() {
                const continueBtn = document.getElementById('continue-btn');
                if (!continueBtn) return;
                
                if (selectedSeats.length > 0) {
                    bookingFormContainer.classList.remove('hidden');
                    continueBtn.disabled = false;
                    const buttonText = continueBtn.querySelector('span:not(.hidden)') || continueBtn;
                    buttonText.textContent = 'Lanjutkan Pembayaran';
                } else {
                    bookingFormContainer.classList.add('hidden');
                    continueBtn.disabled = true;
                    const buttonText = continueBtn.querySelector('span:not(.hidden)') || continueBtn;
                    buttonText.textContent = 'Pilih Kursi Terlebih Dahulu';
                }
            }
            
            // Panggil updateBookingForm saat selectedSeats berubah
            const originalUpdateSelectedSeats = window.updateSelectedSeats || function() {};
            window.updateSelectedSeats = function() {
                originalUpdateSelectedSeats();
                updateSelection();
                updateBookingForm();
            };
            
            // Inisialisasi form
            updateSelection();
            updateBookingForm();
            
            // Handle form submission
            if (seatForm) {
                seatForm.addEventListener('submit', async function(e) {
                    e.preventDefault();

                    // Validasi form
                    if (!validateForm()) {
                        console.log('Validasi form gagal');
                        return false;
                    }

                    console.log('Validasi form berhasil');

                    // Update hidden inputs
                    const nama = document.getElementById('nama_pemesan').value.trim();
                    const telepon = document.getElementById('nomor_telepon_pemesan').value.trim();
                    const email = document.getElementById('email_pemesan').value.trim();
                    const alamatJemput = document.getElementById('alamat_jemput').value.trim();
                    const totalHarga = selectedSeats.length * <?php echo $harga_per_kursi; ?>;

                    // Format selected seats data to match backend expectation
                    const formattedSelectedSeats = selectedSeats.map(seat => ({
                        id_kursi: seat.id,
                        nomor_kursi: seat.number || seat.id
                    }));

                    const selectedSeatsData = JSON.stringify(formattedSelectedSeats);

                    // Set form values
                    document.getElementById('form-nama').value = nama;
                    document.getElementById('form-telepon').value = telepon;
                    document.getElementById('form-email').value = email;
                    document.getElementById('form_alamat_jemput').value = alamatJemput;
                    document.getElementById('form_total_harga').value = totalHarga;
                    document.getElementById('form_selected_seats').value = selectedSeatsData;

                    // Show loading state
                    const submitButton = seatForm.querySelector('button[type="submit"], #continue-btn');
                    if (!submitButton) {
                        console.error('Submit button not found');
                        return;
                    }

                    const originalButtonText = submitButton.innerHTML;
                    const originalButtonState = submitButton.disabled;

                    submitButton.disabled = true;
                    if (submitButton.querySelector('i.fa-spinner') === null) {
                        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Memproses...';
                    }

                    try {
                        // Submit form via AJAX
                        const formData = new FormData(seatForm);

                        // Log form data
                        console.log('Form data being sent:');
                        for (let [key, value] of formData.entries()) {
                            console.log(key, value);
                        }

                        const response = await fetch(window.location.origin + '/travel_website/process/booking.php', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        const result = await response.json();

                        if (result.success) {
                            // Redirect to booking success page with booking code
                            window.location.href = 'booking_success.php?code=' + encodeURIComponent(result.booking_code);
                        } else {
                            // Show error message
                            alert(result.message || 'Terjadi kesalahan. Silakan coba lagi.');
                            if (submitButton) {
                                submitButton.disabled = originalButtonState;
                                submitButton.innerHTML = originalButtonText;
                            }
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat mengirim data. Silakan coba lagi.');
                        if (submitButton) {
                            submitButton.disabled = originalButtonState;
                            submitButton.innerHTML = originalButtonText;
                        }
                    }
                });
            }
            
            // Fungsi untuk validasi form
            function validateForm() {
                const form = document.getElementById('seat-form');
                const phoneInput = document.getElementById('nomor_telepon_pemesan');
                const phoneError = document.getElementById('nomor_telepon_pemesan-error');
                const namaInput = document.getElementById('nama_pemesan');
                const emailInput = document.getElementById('email_pemesan');
                const alamatInput = document.getElementById('alamat_jemput');
                
                // Reset error state
                phoneInput.classList.remove('border-red-500');
                phoneError.classList.add('hidden');
                
                // Validasi nama
                if (!namaInput.value.trim()) {
                    alert('Nama pemesan tidak boleh kosong');
                    namaInput.focus();
                    return false;
                }
                
                // Validate phone number
                const phoneRegex = /^[0-9]{10,13}$/;
                if (!phoneRegex.test(phoneInput.value.trim())) {
                    phoneInput.classList.add('border-red-500');
                    phoneError.textContent = 'Nomor telepon harus 10-13 digit angka';
                    phoneError.classList.remove('hidden');
                    phoneInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    phoneInput.focus();
                    return false;
                }
                
                // Validasi email
                if (!emailInput.value.trim()) {
                    alert('Email tidak boleh kosong');
                    emailInput.focus();
                    return false;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim())) {
                    alert('Format email tidak valid');
                    emailInput.focus();
                    return false;
                }
                
                // Validasi alamat
                if (!alamatInput.value.trim()) {
                    alert('Alamat penjemputan tidak boleh kosong');
                    alamatInput.focus();
                    return false;
                }
                
                // Validasi kursi dipilih
                if (selectedSeats.length === 0) {
                    alert('Silakan pilih minimal 1 kursi');
                    return false;
                }
                
                return true;
            }
            
            // Close modal when clicking outside
            const confirmModal = document.getElementById('confirm-modal');
            if (confirmModal) {
                confirmModal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeModal();
                    }
                });
            }
            
            // Add event listener for the continue button
            const continueButton = document.getElementById('continue-btn');
            if (continueButton) {
                continueButton.addEventListener('click', async function(e) {
                    e.preventDefault();
                    console.log('Continue button clicked');

                    // Validasi form
                    if (!validateForm()) {
                        console.log('Form validation failed');
                        return false;
                    }

                    console.log('Form validation passed, preparing data...');

                    // Update hidden inputs dengan nilai dari form
                    const nama = document.getElementById('nama_pemesan').value.trim();
                    const telepon = document.getElementById('nomor_telepon_pemesan').value.trim();
                    const email = document.getElementById('email_pemesan').value.trim();
                    const alamatJemput = document.getElementById('alamat_jemput').value.trim();
                    const totalHarga = selectedSeats.length * <?php echo $harga_per_kursi; ?>;

                    console.log('Selected seats:', selectedSeats);
                    console.log('Form data:', {
                        nama: nama,
                        telepon: telepon,
                        email: email,
                        alamat: alamatJemput,
                        totalHarga: totalHarga
                    });

                    // Format selected seats data
                    const formattedSelectedSeats = selectedSeats.map(seat => ({
                        id_kursi: seat.id,
                        nomor_kursi: seat.number || seat.id
                    }));

                    const selectedSeatsData = JSON.stringify(formattedSelectedSeats);

                    // Set form values
                    document.getElementById('form-nama').value = nama;
                    document.getElementById('form-telepon').value = telepon;
                    document.getElementById('form-email').value = email;
                    document.getElementById('form_alamat_jemput').value = alamatJemput;
                    document.getElementById('form_total_harga').value = totalHarga;
                    document.getElementById('form_selected_seats').value = selectedSeatsData;

                    // Show loading state
                    const submitButton = this;
                    const originalButtonText = submitButton.innerHTML;

                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Memproses...';

                    try {
                        // Submit form via AJAX
                        const form = document.getElementById('seat-form');
                        const formData = new FormData(form);

                        console.log('Form data being sent:');
                        for (let [key, value] of formData.entries()) {
                            console.log(key, value);
                        }

                        const response = await fetch(window.location.origin + '/travel_website/process/booking.php', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            credentials: 'same-origin'
                        });

                        console.log('Response status:', response.status);

                        // Cek jika response bukan JSON
                        const contentType = response.headers.get('content-type');
                        if (!contentType || !contentType.includes('application/json')) {
                            const text = await response.text();
                            console.error('Response is not JSON:', text);
                            throw new Error('Respon server tidak valid. Silakan coba lagi.');
                        }

                        const result = await response.json();
                        console.log('Server response:', result);

                        if (!response.ok) {
                            throw new Error(result.message || 'Terjadi kesalahan pada server');
                        }

                        if (result.success && result.booking_code) {
                            console.log('Redirecting to booking success page with code:', result.booking_code);
                            window.location.href = 'booking_success.php?code=' + encodeURIComponent(result.booking_code);
                        } else {
                            throw new Error(result.message || 'Pemesanan gagal diproses');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat mengirim data. Silakan coba lagi.');
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonText;
                    }
                });
            }
        });
    </script>
<?php require_once __DIR__ . '/templates/partials/footer.php'; ?>
</body>
</html>
