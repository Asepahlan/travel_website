<?php
$page_title = 'Dashboard'; // Set page title for header
// Database connection will be handled by getDbConnection()
require_once __DIR__ . '/../templates/partials/admin_header.php';

// Inisialisasi variabel untuk menyimpan statistik
$stats = [
    'reservasi_belum_bayar' => 0,
    'reservasi_sudah_bayar' => 0,
    'reservasi_dibatalkan' => 0,
    'jadwal_aktif' => 0,
    'total_kota' => 0,
    'total_kecamatan' => 0,
    'total_layout' => 0,
    'kota_asal' => '',
    'kota_tujuan' => ''
];

function getDbConnection() {
    static $conn = null;
    if ($conn === null) {
        // Include file konfigurasi database
        require_once __DIR__ . '/../config/database.php';
        
        // Gunakan konstanta yang sudah didefinisikan di database.php
        $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
        
        if ($conn->connect_error) {
            die("Koneksi database gagal: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}

try {
    $conn = getDbConnection();
    // 1. Reservasi Stats
    try {
        // Check if the reservasi table exists and has the status_pembayaran column
        $table_check = $conn->query("SHOW TABLES LIKE 'reservasi'");
        if ($table_check && $table_check->num_rows > 0) {
            $check_column = $conn->query("SHOW COLUMNS FROM reservasi LIKE 'status_pembayaran'");
            if ($check_column && $check_column->num_rows > 0) {
                // Use status_pembayaran column if it exists
                $sql_reservasi = "SELECT status_pembayaran, COUNT(*) as count FROM reservasi GROUP BY status_pembayaran";
                $result_reservasi = $conn->query($sql_reservasi);
                if ($result_reservasi) {
                    while ($row = $result_reservasi->fetch_assoc()) {
                        $status = $row['status_pembayaran'];
                        if (isset($stats['reservasi_' . $status])) {
                            $stats['reservasi_' . $status] = (int)$row['count'];
                        }
                    }
                }
            } else {
                // If status_pembayaran doesn't exist, just count all reservations as belum_bayar
                $sql_count = "SELECT COUNT(*) as count FROM reservasi";
                $result_count = $conn->query($sql_count);
                if ($result_count && $row = $result_count->fetch_assoc()) {
                    $stats['reservasi_belum_bayar'] = (int)$row['count'];
                }
            }
        }
    } catch (Exception $e) {
        error_log('Error loading reservation stats: ' . $e->getMessage());
        $_SESSION['error_message'] = 'Gagal memuat statistik: ' . $e->getMessage();
    }

    // 2. Jadwal Aktif Stats (Tersedia/Sedang Jalan & Belum Lewat)
    $today = date('Y-m-d');
    
    try {
        // Check if the jadwal table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'jadwal'");
        if ($table_check && $table_check->num_rows > 0) {
            // Check if the status_driver column exists
            $check_driver_status = $conn->query("SHOW COLUMNS FROM jadwal LIKE 'status_driver'");
            
            if ($check_driver_status && $check_driver_status->num_rows > 0) {
                // Use status_driver column if it exists
                $sql_jadwal = "SELECT COUNT(*) as count FROM jadwal WHERE status_driver IN ('tersedia', 'sedang_jalan') AND tanggal_berangkat >= ?";
            } else {
                // If status_driver doesn't exist, just count all future schedules
                $sql_jadwal = "SELECT COUNT(*) as count FROM jadwal WHERE tanggal_berangkat >= ?";
            }
            
            $stmt_jadwal = $conn->prepare($sql_jadwal);
            if ($stmt_jadwal) {
                $stmt_jadwal->bind_param("s", $today);
                $stmt_jadwal->execute();
                $result_jadwal = $stmt_jadwal->get_result();
                if ($row = $result_jadwal->fetch_assoc()) {
                    $stats['jadwal_aktif'] = (int)$row['count'];
                }
                $stmt_jadwal->close();
            }
        }
    } catch (Exception $e) {
        error_log('Error loading schedule stats: ' . $e->getMessage());
        // Don't show error to user for stats
    }

    // 3. Kota, Kecamatan, Layout Stats
    try {
        // Count cities (kota)
        $table_check = $conn->query("SHOW TABLES LIKE 'kota'");
        if ($table_check && $table_check->num_rows > 0) {
            $result = $conn->query("SELECT COUNT(*) as count FROM kota");
            if ($result && $row = $result->fetch_assoc()) {
                $stats['total_kota'] = (int)$row['count'];
            }
        }
    } catch (Exception $e) {
        error_log('Error counting cities: ' . $e->getMessage());
    }

    try {
        // Count districts (kecamatan)
        $table_check = $conn->query("SHOW TABLES LIKE 'kecamatan'");
        if ($table_check && $table_check->num_rows > 0) {
            $result = $conn->query("SELECT COUNT(*) as count FROM kecamatan");
            if ($result && $row = $result->fetch_assoc()) {
                $stats['total_kecamatan'] = (int)$row['count'];
            }
        }
    } catch (Exception $e) {
        error_log('Error counting districts: ' . $e->getMessage());
    }

    try {
        // Count layouts
        $table_check = $conn->query("SHOW TABLES LIKE 'layout_kursi'");
        if ($table_check && $table_check->num_rows > 0) {
            $result = $conn->query("SELECT COUNT(*) as count FROM layout_kursi");
            if ($result && $row = $result->fetch_assoc()) {
                $stats['total_layout'] = (int)$row['count'];
            }
        }
    } catch (Exception $e) {
        error_log('Error counting layouts: ' . $e->getMessage());
    }

} catch (Exception $e) {
    // Log error or display a message
    $dashboard_error = "Gagal memuat statistik: " . $e->getMessage();
} finally {
    // Jangan tutup koneksi disini, biarkan fungsi getDbConnection() mengelolanya
}

?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-semibold text-gray-700">Dashboard Admin</h2>
    <div class="text-sm text-gray-500">
        <?php echo date('l, d F Y'); ?>
    </div>
</div>

<?php if (isset($dashboard_error)): ?>
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
    <span class="block sm:inline"><?php echo htmlspecialchars($dashboard_error); ?></span>
</div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 mb-8">
    <!-- Reservasi Belum Bayar -->
    <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-yellow-400 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center gap-2 text-sm font-medium text-gray-500 mb-2">Reservasi Belum Bayar</div>
        <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo $stats['reservasi_belum_bayar']; ?></p>
    </div>
    <!-- Reservasi Sudah Bayar -->
    <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-green-400 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center gap-2 text-sm font-medium text-gray-500 mb-2">Reservasi Sudah Bayar</div>
        <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo $stats['reservasi_sudah_bayar']; ?></p>
    </div>
    <!-- Reservasi Dibatalkan -->
     <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-red-400 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center gap-2 text-sm font-medium text-gray-500 mb-2">Reservasi Dibatalkan</div>
        <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo $stats['reservasi_dibatalkan']; ?></p>
    </div>
    <!-- Jadwal Aktif -->
    <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-blue-400 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center gap-2 text-sm font-medium text-gray-500 mb-2">Jadwal Aktif</div>
        <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo $stats['jadwal_aktif']; ?></p>
    </div>
     <!-- Total Kota -->
    <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-indigo-400 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center gap-2 text-sm font-medium text-gray-500 mb-2">Total Kota</div>
        <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo $stats['total_kota']; ?></p>
    </div>
     <!-- Total Kecamatan -->
    <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-purple-400 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center gap-2 text-sm font-medium text-gray-500 mb-2">Total Kecamatan</div>
        <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo $stats['total_kecamatan']; ?></p>
    </div>
     <!-- Total Layout -->
    <div class="bg-white p-5 rounded-xl shadow-sm border-l-4 border-pink-400 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center gap-2 text-sm font-medium text-gray-500 mb-2">Total Layout Kursi</div>
        <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo $stats['total_layout']; ?></p>
    </div>
</div>

<!-- Quick Links/Actions -->
<div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
    <h3 class="text-lg font-semibold text-gray-700 mb-5">Akses Cepat</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <a href="manage_reservasi.php" class="flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200 text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
            </svg>
            Reservasi
        </a>
        <a href="manage_jadwal.php" class="flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200 text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
            </svg>
            Jadwal
        </a>
        <a href="manage_layout.php" class="flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200 text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path d="M5 3a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" />
            </svg>
            Layout Kursi
        </a>
        <a href="manage_kota.php" class="flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200 text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
            </svg>
            Kota & Kecamatan
        </a>
    </div>
</div>

<!-- Recent Activity -->
<div class="mt-8 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
    <h3 class="text-lg font-semibold text-gray-700 mb-4">Aktivitas Terkini</h3>
    <div class="space-y-4">
        <?php
        // Query sederhana untuk mengambil data reservasi terbaru
$recent_query = "SELECT r.*, 
                j.tanggal_berangkat, j.waktu_berangkat,
                ko.nama_kota as kota_asal,
                kt.nama_kota as kota_tujuan
                FROM reservasi r
                LEFT JOIN jadwal j ON r.id_jadwal = j.id_jadwal
                LEFT JOIN kota ko ON j.id_kota_asal = ko.id_kota
                LEFT JOIN kota kt ON j.id_kota_tujuan = kt.id_kota
                ORDER BY r.created_at DESC 
                LIMIT 5";

$recent_result = $conn->query($recent_query);
        
        if ($recent_result && $recent_result->num_rows > 0):
            while ($row = $recent_result->fetch_assoc()):
                $status_class = [
                    'lunas' => 'bg-green-100 text-green-800',
                    'dibayar' => 'bg-blue-100 text-blue-800',
                    'pending' => 'bg-yellow-100 text-yellow-800',
                    'batal' => 'bg-gray-100 text-gray-800',
                    'expired' => 'bg-gray-50 text-gray-500',
                    'refund' => 'bg-purple-100 text-purple-800'
                ][$row['status'] ?? 'pending'] ?? 'bg-gray-100 text-gray-800';
                
                $status_text = [
                    'lunas' => 'Lunas',
                    'dibayar' => 'Dibayar',
                    'pending' => 'Menunggu Pembayaran',
                    'batal' => 'Dibatalkan',
                    'expired' => 'Kadaluarsa',
                    'refund' => 'Dikembalikan'
                ][$row['status'] ?? 'pending'] ?? ucfirst($row['status'] ?? 'Pending');
                ?>
                <div class="flex items-start pb-4 border-b border-gray-100 last:border-0 last:pb-0">
                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['nama_pemesan']); ?></h4>
                            <span class="text-xs text-gray-500"><?php echo date('d M H:i', strtotime($row['created_at'])); ?></span>
                        </div>
                        <p class="text-sm text-gray-600">
                            Reservasi #<?php echo htmlspecialchars($row['kode_booking']); ?>
                            <span class="block text-xs text-gray-500 mt-1">
                                <?php echo htmlspecialchars($row['kota_asal'] ?? 'N/A'); ?> â†’ 
                                <?php echo htmlspecialchars($row['kota_tujuan'] ?? 'N/A'); ?>
                            </span>
                        </p>
                        <span class="mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                            <?php echo $status_text; ?>
                        </span>
                    </div>
                </div>
                <?php
            endwhile;
        else:
            echo '<p class="text-sm text-gray-500">Tidak ada aktivitas terbaru.</p>';
        endif;
        ?>
    </div>
    <div class="mt-4 text-right">
        <a href="manage_reservasi.php" class="text-sm font-medium text-blue-600 hover:text-blue-500">Lihat Semua Reservasi â†’</a>
    </div>
</div>

<!-- Stats Overview -->
<div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Jadwal Mendatang -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Jadwal Mendatang</h3>
        <div class="space-y-4">
            <?php
            $upcoming_query = "SELECT j.*, 
                (SELECT COUNT(*) FROM reservasi r WHERE r.id_jadwal = j.id_jadwal) as total_pemesan,
                ko.nama_kota as kota_asal,
                kt.nama_kota as kota_tujuan
                FROM jadwal j 
                LEFT JOIN kota ko ON j.id_kota_asal = ko.id_kota
                LEFT JOIN kota kt ON j.id_kota_tujuan = kt.id_kota
                WHERE j.tanggal_berangkat >= CURDATE() 
                ORDER BY j.tanggal_berangkat ASC, j.waktu_berangkat ASC 
                LIMIT 3";

$upcoming_result = $conn->query($upcoming_query);
            
            if ($upcoming_result && $upcoming_result->num_rows > 0):
                while ($row = $upcoming_result->fetch_assoc()):
                    $tanggal = date('d M Y', strtotime($row['tanggal_berangkat']));
                    $waktu = date('H:i', strtotime($row['waktu_berangkat']));
                    ?>
                    <div class="border-l-2 border-blue-500 pl-4 py-1">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-medium text-gray-900"><?php echo $tanggal; ?> â€¢ <?php echo $waktu; ?></h4>
                                <p class="text-sm text-gray-600">
                                    <?php echo htmlspecialchars($row['kota_asal'] ?? 'N/A'); ?> â†’ 
                                    <?php echo htmlspecialchars($row['kota_tujuan'] ?? 'N/A'); ?>
                                </p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <?php echo (int)$row['total_pemesan']; ?> Penumpang
                            </span>
                        </div>
                    </div>
                    <?php
                endwhile;
            else:
                echo '<p class="text-sm text-gray-500">Tidak ada jadwal mendatang.</p>';
            endif;
            ?>
        </div>
        <div class="mt-4 text-right">
            <a href="manage_jadwal.php" class="text-sm font-medium text-blue-600 hover:text-blue-500">Lihat Semua Jadwal â†’</a>
        </div>
    </div>
    
    <!-- Ringkasan Bulan Ini -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Ringkasan Bulan Ini</h3>
        <div class="space-y-4">
            <?php
            $current_month = date('Y-m');
            $summary_query = "SELECT 
                COUNT(CASE WHEN status = 'lunas' THEN 1 END) as total_lunas,
                COUNT(CASE WHEN status = 'dibayar' THEN 1 END) as total_dibayar,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as total_pending,
                COALESCE(SUM(CASE WHEN status IN ('lunas', 'dibayar') THEN total_harga ELSE 0 END), 0) as total_pendapatan
                FROM reservasi 
                WHERE DATE_FORMAT(created_at, '%Y-%m') = ?";
                
            $stmt = $conn->prepare($summary_query);
            if ($stmt) {
                $stmt->bind_param('s', $current_month);
                $stmt->execute();
                $result = $stmt->get_result();
                $summary = $result ? $result->fetch_assoc() : [];
                $stmt->close();
            } else {
                $summary = [
                    'total_lunas' => 0,
                    'total_dibayar' => 0,
                    'total_pending' => 0,
                    'total_pendapatan' => 0
                ];
            }
            ?>
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 bg-green-50 rounded-lg">
                    <p class="text-sm font-medium text-green-800">Lunas</p>
                    <p class="text-2xl font-bold text-green-600"><?php echo (int)$summary['total_lunas']; ?></p>
                </div>
                <div class="p-4 bg-blue-50 rounded-lg">
                    <p class="text-sm font-medium text-blue-800">Dibayar</p>
                    <p class="text-2xl font-bold text-blue-600"><?php echo (int)$summary['total_dibayar']; ?></p>
                </div>
                <div class="p-4 bg-yellow-50 rounded-lg">
                    <p class="text-sm font-medium text-yellow-800">Menunggu</p>
                    <p class="text-2xl font-bold text-yellow-600"><?php echo (int)$summary['total_pending']; ?></p>
                </div>
                <div class="p-4 bg-indigo-50 rounded-lg">
                    <p class="text-sm font-medium text-indigo-800">Pendapatan</p>
                    <p class="text-xl font-bold text-indigo-600">Rp <?php echo number_format($summary['total_pendapatan'] ?? 0, 0, ',', '.'); ?></p>
                </div>
            </div>
            <div class="pt-4 border-t border-gray-100">
                <p class="text-sm text-gray-600">Total <?php echo (int)($summary['total_lunas'] + $summary['total_dibayar'] + $summary['total_pending']); ?> transaksi di bulan <?php echo date('F Y'); ?></p>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../templates/partials/admin_footer.php'; 
?>

