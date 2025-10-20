<?php
require_once __DIR__ . '/../config/database.php';
session_start();

$page_title = 'Hasil Pencarian';

// Set base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$script_name = dirname($_SERVER['SCRIPT_NAME']);
$base_url = rtrim("$protocol://$host$script_name", '/');

// Set default timezone
date_default_timezone_set('Asia/Jakarta');

// Fungsi untuk membuat URL pencarian dengan parameter saat ini
function getSearchUrl($params = []) {
    global $kota_asal_id, $kecamatan_asal_id, $kota_tujuan_id, $kecamatan_tujuan_id, $tanggal_berangkat, $alamat_jemput, $base_url;
    
    // Format tanggal ke Y-m-d jika perlu
    $formatted_date = '';
    if (!empty($tanggal_berangkat)) {
        $date_obj = DateTime::createFromFormat('d M Y', $tanggal_berangkat);
        $formatted_date = $date_obj ? $date_obj->format('Y-m-d') : $tanggal_berangkat;
    }
    
    $current_params = [
        'kota_asal' => $kota_asal_id ?? null,
        'kecamatan_asal' => $kecamatan_asal_id ?? null,
        'kota_tujuan' => $kota_tujuan_id ?? null,
        'kecamatan_tujuan' => $kecamatan_tujuan_id ?? null,
        'tanggal_berangkat' => $formatted_date ?: null,
        'alamat_jemput' => $alamat_jemput ?? null
    ];
    
    // Update with any new parameters
    $params = array_merge($current_params, $params);
    
    // Remove null values and empty strings
    $params = array_filter($params, function($value) {
        return $value !== null && $value !== '';
    });
    
    // Build the query string
    $query = http_build_query($params);
    
    // Return the full URL
    return $base_url . '/index.php' . ($query ? '?' . $query : '');
}

// Fungsi untuk memeriksa dan membersihkan parameter
function cleanParameter($param, $type = 'string') {
    if (!isset($_GET[$param])) {
        return null;
    }
    
    $value = trim($_GET[$param]);
    
    if ($type === 'int') {
        return is_numeric($value) ? (int)$value : null;
    }
    
    return $value !== '' ? htmlspecialchars($value) : null;
}

// Ambil dan validasi parameter pencarian
$kota_asal_id = cleanParameter('kota_asal', 'int');
$kecamatan_asal_id = cleanParameter('kecamatan_asal', 'int');
$alamat_jemput = cleanParameter('alamat_jemput');
$kota_tujuan_id = cleanParameter('kota_tujuan', 'int');
$kecamatan_tujuan_id = cleanParameter('kecamatan_tujuan', 'int');
$tanggal_berangkat = cleanParameter('tanggal_berangkat');

// Daftar parameter yang diperlukan dengan label yang ramah pengguna
$required_params = [
    'kota_asal' => ['value' => $kota_asal_id, 'label' => 'Kota Asal'],
    'kecamatan_asal' => ['value' => $kecamatan_asal_id, 'label' => 'Kecamatan Asal'],
    'kota_tujuan' => ['value' => $kota_tujuan_id, 'label' => 'Kota Tujuan'],
    'kecamatan_tujuan' => ['value' => $kecamatan_tujuan_id, 'label' => 'Kecamatan Tujuan'],
    'tanggal_berangkat' => ['value' => $tanggal_berangkat, 'label' => 'Tanggal Berangkat']
    // Alamat jemput tidak lagi diperlukan di sini
];

// Tetap simpan alamat jemput jika ada, tapi tidak wajib
$alamat_jemput = $alamat_jemput ?: 'Alamat penjemputan akan dikonfirmasi melalui WhatsApp';

// Periksa parameter yang hilang atau tidak valid
$missing_params = [];
$invalid_params = [];

foreach ($required_params as $param => $data) {
    $value = $data['value'];
    $label = $data['label'];
    
    if ($value === null) {
        $missing_params[$param] = "$label tidak boleh kosong";
    } elseif (empty($value) && $value !== '0' && $value !== 0) {
        $invalid_params[$param] = "$label tidak valid";
    }
}

// Gabungkan pesan error
$error_messages = array_merge($missing_params, $invalid_params);

// Jika ada error, redirect kembali dengan pesan error
if (!empty($error_messages)) {
    $error_msg = 'Data yang dimasukkan tidak valid: ' . implode(', ', array_values($error_messages));
    
    // Simpan input pengguna untuk ditampilkan kembali di form
    $input_values = [
        'kota_asal' => $kota_asal_id,
        'kecamatan_asal' => $kecamatan_asal_id,
        'kota_tujuan' => $kota_tujuan_id,
        'kecamatan_tujuan' => $kecamatan_tujuan_id,
        'tanggal_berangkat' => $tanggal_berangkat,
        'jumlah_penumpang' => 1 // Default value
    ];
    
    // Redirect dengan parameter error dan input yang ada
    $redirect_url = 'index.php?error=validation_error';
    $redirect_url .= '&message=' . urlencode($error_msg);
    $redirect_url .= '&' . http_build_query(array_filter($input_values));
    
    header('Location: ' . $redirect_url);
    exit;
}

// Query untuk mencari jadwal
// Perlu join dengan tabel kota dan kecamatan untuk mendapatkan nama
$sql = "SELECT 
            j.id_jadwal, 
            j.tanggal_berangkat, 
            j.waktu_berangkat, 
            j.estimasi_jam,
            ADDTIME(j.waktu_berangkat, CONCAT(j.estimasi_jam, ':00:00')) as perkiraan_waktu_tiba,
            j.harga,
            j.keterangan,
            j.id_layout_kursi,
            ka.nama_kota AS nama_kota_asal, 
            kca.nama_kecamatan AS nama_kecamatan_asal, 
            kt.nama_kota AS nama_kota_tujuan, 
            kct.nama_kecamatan AS nama_kecamatan_tujuan,
            lk.nama_layout,
            lk.jumlah_baris,
            lk.jumlah_kolom,
            (SELECT COUNT(*) FROM kursi k WHERE k.id_layout = lk.id_layout) as total_kursi_aktual,
            (lk.jumlah_baris * lk.jumlah_kolom) as total_kursi_desain,
            CASE 
                WHEN j.harga IS NOT NULL AND j.harga > 0 THEN j.harga
                ELSE 75000 
            END AS harga_per_kursi
        FROM jadwal j
        JOIN kota ka ON j.id_kota_asal = ka.id_kota
        JOIN kecamatan kca ON j.id_kecamatan_asal = kca.id_kecamatan
        JOIN kota kt ON j.id_kota_tujuan = kt.id_kota
        JOIN kecamatan kct ON j.id_kecamatan_tujuan = kct.id_kecamatan
        LEFT JOIN layout_kursi lk ON j.id_layout_kursi = lk.id_layout
        WHERE j.id_kota_asal = ? 
          AND j.id_kecamatan_asal = ?
          AND j.id_kota_tujuan = ?
          AND j.id_kecamatan_tujuan = ?
          AND j.tanggal_berangkat = ?
        ORDER BY j.waktu_berangkat ASC";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    // Handle error persiapan statement
    die(
'Error preparing statement: ' . $conn->error);
}

$stmt->bind_param(
"iiiis", $kota_asal_id, $kecamatan_asal_id, $kota_tujuan_id, $kecamatan_tujuan_id, $tanggal_berangkat);
$stmt->execute();
$result = $stmt->get_result();

$jadwal_list = [];

while ($row = $result->fetch_assoc()) {
    // Dapatkan total kursi dari data aktual, fallback ke desain jika tidak ada
    $total_kursi = isset($row['total_kursi_aktual']) && $row['total_kursi_aktual'] > 0 
        ? (int)$row['total_kursi_aktual'] 
        : (isset($row['total_kursi_desain']) ? (int)$row['total_kursi_desain'] : 2);
    $row['total_kursi'] = $total_kursi; // Simpan kembali ke array row
    // Hitung kursi yang sudah dipesan (hitung dari detail_reservasi_kursi)
    $jadwal_id = $row['id_jadwal'];
    $kursi_terisi = 0;
    
    // Hitung kursi yang sudah dibayar atau dikonfirmasi
    $kursi_terisi_sql = "SELECT COUNT(*) as terisi 
                        FROM detail_reservasi_kursi drk
                        JOIN reservasi r ON drk.id_reservasi = r.id_reservasi
                        WHERE r.id_jadwal = ? AND (r.status = 'dibayar' OR r.status = 'dikonfirmasi')";
    $stmt_terisi = $conn->prepare($kursi_terisi_sql);
    if ($stmt_terisi) {
        $stmt_terisi->bind_param("i", $jadwal_id);
        $stmt_terisi->execute();
        $kursi_terisi_result = $stmt_terisi->get_result()->fetch_assoc();
        $kursi_terisi = $kursi_terisi_result ? (int)$kursi_terisi_result['terisi'] : 0;
        $stmt_terisi->close();
    }
    
    // Hitung kursi pending yang masih dalam batas waktu pembayaran (15 menit)
    $batas_waktu = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    $kursi_pending_sql = "SELECT COUNT(DISTINCT drk.id_kursi) as pending 
                         FROM detail_reservasi_kursi drk
                         JOIN reservasi r ON drk.id_reservasi = r.id_reservasi
                         WHERE r.id_jadwal = ? 
                         AND r.status = 'pending' 
                         AND r.created_at >= ?
                         AND NOT EXISTS (
                             SELECT 1 
                             FROM reservasi r2 
                             JOIN detail_reservasi_kursi drk2 ON r2.id_reservasi = drk2.id_reservasi
                             WHERE r2.id_jadwal = r.id_jadwal 
                             AND (r2.status = 'dibayar' OR r2.status = 'dikonfirmasi')
                             AND drk2.id_kursi = drk.id_kursi
                         )";
    $stmt_pending = $conn->prepare($kursi_pending_sql);
    if ($stmt_pending) {
        $stmt_pending->bind_param("is", $jadwal_id, $batas_waktu);
        $stmt_pending->execute();
        $kursi_pending_result = $stmt_pending->get_result()->fetch_assoc();
        $kursi_pending = $kursi_pending_result ? (int)$kursi_pending_result['pending'] : 0;
        $kursi_terisi = max($kursi_terisi, $kursi_pending); // Gunakan yang lebih besar antara kursi terisi atau pending
        $stmt_pending->close();
    }
    
    // Pastikan tidak ada perhitungan yang melebihi total kursi
    $kursi_terisi = min($kursi_terisi, $total_kursi);
    $row['kursi_tersedia'] = max(0, $total_kursi - $kursi_terisi);
    $row['is_available'] = $row['kursi_tersedia'] > 0;

    // Hitung kursi tersedia
    $row['kursi_tersedia'] = max(0, $total_kursi - $kursi_terisi);
    $jadwal_list[] = $row;
}

$stmt->close();
$conn->close();

// Format tanggal untuk tampilan
$tanggal_tampil = date("d M Y", strtotime($tanggal_berangkat));

// Ambil nama kota/kecamatan dari ID untuk ditampilkan (asumsi sudah ada di $jadwal_list jika ada hasil)
$nama_kota_asal_tampil = !empty($jadwal_list) ? $jadwal_list[0]['nama_kota_asal'] : '-';
$nama_kecamatan_asal_tampil = !empty($jadwal_list) ? $jadwal_list[0]['nama_kecamatan_asal'] : '-';
$nama_kota_tujuan_tampil = !empty($jadwal_list) ? $jadwal_list[0]['nama_kota_tujuan'] : '-';
$nama_kecamatan_tujuan_tampil = !empty($jadwal_list) ? $jadwal_list[0]['nama_kecamatan_tujuan'] : '-';
?>

<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/travel_website/public/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/travel_website/public/favicon.ico" type="image/x-icon">
    <title>Hasil Pencarian Jadwal Travel</title>
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
        main {
            flex: 1 0 auto;
            min-height: calc(100vh - 300px);
        }
        footer {
            flex-shrink: 0;
        }
        .schedule-card {
            transition: all 0.2s ease-in-out;
        }
        .schedule-card:hover {
            transform: translateY(-2px);
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6',
                        secondary: '#8b5cf6',
                        success: '#10b981',
                        danger: '#ef4444',
                        warning: '#f59e0b',
                        info: '#3b82f6'
                    },
                    boxShadow: {
                        'card': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)'
                    }
                }
            },
            variants: {
                extend: {
                    opacity: ['disabled'],
                    cursor: ['disabled'],
                }
            }
        };
    </script>
</head>
<body class="bg-gray-50 font-sans flex flex-col min-h-screen">

<!-- Main Content -->
<main class="flex-grow">
    <!-- Search Summary -->
    <div class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900">Hasil Pencarian</h1>
                    <div class="mt-2 flex flex-col sm:flex-row sm:flex-wrap sm:mt-0 sm:space-x-6">
                        <div class="mt-2 flex items-center text-sm text-gray-500">
                            <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                            </svg>
                            <?php echo htmlspecialchars($nama_kota_asal_tampil . ' (' . $nama_kecamatan_asal_tampil . ')'); ?>
                            <span class="mx-2 text-gray-300">â†’</span>
                            <?php echo htmlspecialchars($nama_kota_tujuan_tampil . ' (' . $nama_kecamatan_tujuan_tampil . ')'); ?>
                        </div>
                        <div class="mt-2 flex items-center text-sm text-gray-500">
                            <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                            </svg>
                            <?php echo htmlspecialchars($tanggal_tampil); ?>
                        </div>
                        <?php if (!empty($alamat_jemput)): ?>
                        <div class="mt-2 flex items-center text-sm text-gray-500">
                            <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                            </svg>
                            <?php echo htmlspecialchars($alamat_jemput); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mt-4 flex-shrink-0 md:mt-0">
                    <?php
                    // Build the URL manually to ensure proper encoding
                    $search_params = [];
                    if (!empty($kota_asal_id)) $search_params['kota_asal'] = $kota_asal_id;
                    if (!empty($kecamatan_asal_id)) $search_params['kecamatan_asal'] = $kecamatan_asal_id;
                    if (!empty($kota_tujuan_id)) $search_params['kota_tujuan'] = $kota_tujuan_id;
                    if (!empty($kecamatan_tujuan_id)) $search_params['kecamatan_tujuan'] = $kecamatan_tujuan_id;
                    if (!empty($tanggal_berangkat)) {
                        $date_obj = DateTime::createFromFormat('d M Y', $tanggal_berangkat);
                        $search_params['tanggal_berangkat'] = $date_obj ? $date_obj->format('Y-m-d') : $tanggal_berangkat;
                    }
                    if (!empty($alamat_jemput)) $search_params['alamat_jemput'] = $alamat_jemput;
                    
                    $search_url = $base_url . '/index.php';
                    if (!empty($search_params)) {
                        $search_url .= '?' . http_build_query($search_params);
                    }
                    ?>
                    <a href="<?php echo htmlspecialchars($search_url); ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors duration-200">
                        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        Ubah Pencarian
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule List -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <?php if (!empty($jadwal_list)): ?>
                <div class="space-y-4">
                    <?php foreach ($jadwal_list as $jadwal): 
                        $is_available = $jadwal['kursi_tersedia'] > 0;
                        $departure_time = date("H:i", strtotime($jadwal['waktu_berangkat']));
                        $arrival_time = $jadwal['perkiraan_waktu_tiba'] ? date("H:i", strtotime($jadwal['perkiraan_waktu_tiba'])) : '-';
                        $price = isset($jadwal['harga_per_kursi']) ? number_format($jadwal['harga_per_kursi'], 0, ',', '.') : '0';
                    ?>
                    <div class="schedule-card bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200 border border-gray-100 overflow-hidden" 
                         data-departure-time="<?= date('Y-m-d H:i:s', strtotime($jadwal['waktu_berangkat'])) ?>"
                         data-arrival-time="<?= date('Y-m-d H:i:s', strtotime($jadwal['perkiraan_waktu_tiba'])) ?>"
                         data-price="<?= $jadwal['harga_per_kursi'] ?>"
                         data-duration="<?= $jadwal['estimasi_jam'] ?>">
                        <div class="p-5">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <!-- Time & Route -->
                                <div class="flex-1">
                                    <div class="flex items-start gap-4">
                                        <!-- Departure Time -->
                                        <div class="text-center">
                                            <div class="text-2xl font-bold text-gray-900"><?= $departure_time ?></div>
                                            <div class="text-xs text-gray-500 mt-1">Berangkat</div>
                                        </div>
                                        
                                        <!-- Arrow -->
                                        <div class="flex-1 flex items-center justify-center pt-2">
                                            <div class="relative w-full">
                                                <div class="border-t-2 border-dashed border-gray-300 absolute top-1/2 w-full"></div>
                                                <div class="absolute -right-1 top-1/2 transform -translate-y-1/2 w-0 h-0 border-t-4 border-t-transparent border-b-4 border-b-transparent border-l-4 border-l-gray-400"></div>
                                            </div>
                                        </div>
                                        
                                        <!-- Arrival Time -->
                                        <div class="text-center">
                                            <div class="text-xl font-semibold text-gray-800"><?= $arrival_time ?></div>
                                            <div class="text-xs text-gray-500">Tiba</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Route Details -->
                                    <div class="mt-4 pl-2">
                                        <div class="font-medium text-gray-900">
                                            <?= htmlspecialchars($jadwal['nama_kota_asal']) ?> â†’ <?= htmlspecialchars($jadwal['nama_kota_tujuan']) ?>
                                        </div>
                                        <div class="text-sm text-gray-500 mt-1">
                                            <?= htmlspecialchars($jadwal['nama_kecamatan_asal']) ?> - <?= htmlspecialchars($jadwal['nama_kecamatan_tujuan']) ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Price & Availability -->
                                <div class="flex flex-col items-end gap-2">
                                    <div class="text-right">
                                        <div class="text-2xl font-bold text-primary">Rp<?= number_format($jadwal['harga_per_kursi'], 0, ',', '.') ?></div>
                                        <div class="flex items-center justify-end gap-1 text-sm <?= $is_available ? 'text-green-600' : 'text-red-600' ?>">
                                            <?php if ($is_available): ?>
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                            <?php else: ?>
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                            <?php endif; ?>
                                            <span><?= $is_available ? 'Tersedia' : 'Habis' ?> (<?= $jadwal['kursi_tersedia'] ?>/<?= $jadwal['total_kursi'] ?>)</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Action Button -->
                                    <div class="w-full sm:w-40">
                                        <?php if ($is_available): ?>
                                            <a href="/travel_website/select_seat.php?jadwal_id=<?= $jadwal['id_jadwal'] ?>&alamat_jemput=<?= urlencode($alamat_jemput) ?>" 
                                               class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-primary hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                                                Pilih
                                                <svg class="ml-1.5 -mr-0.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </a>
                                        <?php else: ?>
                                            <button disabled class="w-full px-4 py-2.5 border border-transparent rounded-lg text-sm font-medium text-white bg-gray-400 cursor-not-allowed">
                                                Penuh
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Additional Info -->
                            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                                <div class="flex items-center">
                                    <svg class="h-4 w-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Estimasi: <?= $jadwal['estimasi_jam'] ?> jam</span>
                                </div>
                                <?php if (!empty($jadwal['keterangan'])): ?>
                                    <div class="text-right text-xs text-gray-500 italic">
                                        <?= htmlspecialchars($jadwal['keterangan']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-2 text-lg font-medium text-gray-900">Tidak ada jadwal yang tersedia</h3>
                    <p class="mt-1 text-sm text-gray-500">Silakan coba dengan kriteria pencarian yang berbeda.</p>
                    <div class="mt-6">
                        <a href="<?php echo $search_url; ?>" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Kembali ke Pencarian
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../templates/partials/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle - Only run if elements exist
    const menuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (menuButton && mobileMenu) {
        menuButton.addEventListener('click', function(e) {
            e.preventDefault();
            mobileMenu.classList.toggle('hidden');
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('#mobile-menu') && !event.target.closest('#mobile-menu-button')) {
                mobileMenu.classList.add('hidden');
            }
        });
    }

    // Search and Sort functionality - Only initialize if elements exist
    const searchInput = document.getElementById('searchInput');
    const sortSelect = document.getElementById('sortSelect');
    const scheduleContainer = document.querySelector('.schedule-container');
    
    // Only proceed with search/sort functionality if all required elements exist
    if (searchInput && sortSelect && scheduleContainer) {
        let scheduleItems = Array.from(document.querySelectorAll('.schedule-item'));
        
        // Create no results message element
        const noResultsMessage = document.createElement('div');
        noResultsMessage.className = 'col-span-full text-center py-8 text-gray-600';
        noResultsMessage.textContent = 'Tidak ada jadwal yang ditemukan';

        // Filter schedules based on search input
        function filterSchedules() {
            const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
            let hasVisibleItems = false;

            scheduleItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                const isVisible = searchTerm === '' || text.includes(searchTerm);
                item.style.display = isVisible ? '' : 'none';
                if (isVisible) hasVisibleItems = true;
            });

            // Show/hide no results message
            const existingMessage = document.querySelector('.no-results-message');
            if (!hasVisibleItems) {
                if (!existingMessage) {
                    const message = noResultsMessage.cloneNode(true);
                    message.classList.add('no-results-message');
                    scheduleContainer.appendChild(message);
                }
            } else if (existingMessage) {
                existingMessage.remove();
            }
        }

        // Sort schedules based on selected option
        function sortSchedules() {
            if (!sortSelect) return;
            
            const sortBy = sortSelect.value;
            const container = scheduleContainer || document.querySelector('main > .container');
            
            // Get visible items only for sorting
            const visibleItems = scheduleItems.filter(item => 
                !item.style.display || item.style.display !== 'none'
            );

            visibleItems.sort((a, b) => {
                switch(sortBy) {
                    case 'waktu_berangkat_asc':
                        return new Date(a.dataset.departureTime) - new Date(b.dataset.departureTime);
                    case 'waktu_berangkat_desc':
                        return new Date(b.dataset.departureTime) - new Date(a.dataset.departureTime);
                    case 'harga_asc':
                        return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
                    case 'harga_desc':
                        return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
                    case 'durasi_asc':
                        return parseInt(a.dataset.duration || 0) - parseInt(b.dataset.duration || 0);
                    case 'durasi_desc':
                        return parseInt(b.dataset.duration || 0) - parseInt(a.dataset.duration || 0);
                    default:
                        return 0;
                }
            });

            // Re-append items in new order
            if (container) {
                // Remove all items
                const items = Array.from(container.children);
                items.forEach(item => {
                    if (item !== noResultsMessage && !item.classList.contains('no-results-message')) {
                        item.remove();
                    }
                });
                
                // Add sorted items
                visibleItems.forEach(item => container.appendChild(item));
                
                // Add no results message if needed
                if (visibleItems.length === 0 && !document.querySelector('.no-results-message')) {
                    const message = noResultsMessage.cloneNode(true);
                    message.classList.add('no-results-message');
                    container.appendChild(message);
                }
            }
        }

        // Initialize event listeners
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                filterSchedules();
                sortSchedules(); // Re-sort after filtering
            });
        }
        
        if (sortSelect) {
            sortSelect.addEventListener('change', sortSchedules);
        }

        // Initialize with default sort if sort select exists
        if (sortSelect) {
            sortSchedules();
        }
    } // End of if (searchInput && sortSelect && scheduleContainer)
});
</script>
</body>
</html>

