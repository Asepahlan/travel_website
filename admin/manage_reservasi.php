<?php
// Handle export to CSV first, before any output
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    require_once __DIR__ . '/../config/database.php';
    
    // Initialize filter variables
    $filter_status = $_GET['status'] ?? 'all';
    $filter_date_start = $_GET['date_start'] ?? null;
    $filter_date_end = $_GET['date_end'] ?? null;
    $cari_alamat = $_GET['cari_alamat'] ?? null;
    
    $where_clauses = [];
    $params = [];
    $types = '';
    
    if ($filter_status !== 'all') {
        $where_clauses[] = "r.status = ?";
        $params[] = $filter_status;
        $types .= 's';
    }
    
    if (!empty($filter_date_start)) {
        $where_clauses[] = "DATE(r.created_at) >= ?";
        $params[] = $filter_date_start;
        $types .= 's';
    }
    
    if (!empty($filter_date_end)) {
        $where_clauses[] = "DATE(r.created_at) <= ?";
        $params[] = $filter_date_end;
        $types .= 's';
    }
    
    if (!empty($cari_alamat)) {
        $where_clauses[] = "r.alamat_jemput LIKE ?";
        $params[] = "%$cari_alamat%";
        $types .= 's';
    }
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=reservasi_' . date('Y-m-d') . '.csv');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel compatibility
    fputs($output, "\xEF\xBB\xBF");
    
    // Add CSV headers
    $headers = [
        'Kode Booking',
        'Nama Pemesan',
        'No. HP',
        'Email',
        'Alamat Penjemputan',
        'Tanggal Keberangkatan',
        'Jam Keberangkatan',
        'Nomor Kursi',
        'Total Harga',
        'Status',
        'Tanggal Dibuat'
    ];
    fputcsv($output, $headers);
    
    // Get filtered data - simplified query to match existing tables
    $query = "SELECT 
                kode_booking,
                nama_pemesan,
                no_hp,
                email,
                alamat_jemput as alamat_penjemputan,
                tanggal_berangkat as tanggal_keberangkatan,
                jam_berangkat as jam_keberangkatan,
                nomor_kursi as nomor_kursi_list,
                total_harga,
                CASE 
                    WHEN status = 'pending' THEN 'Menunggu Pembayaran'
                    WHEN status = 'dibayar' THEN 'Sudah Dibayar'
                    WHEN status = 'dibatalkan' THEN 'Dibatalkan'
                    ELSE status 
                END as status,
                created_at
            FROM reservasi" . 
            (!empty($where_clauses) ? ' WHERE ' . implode(' AND ', $where_clauses) : '') . "
            ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Add data rows
    while ($row = $result->fetch_assoc()) {
        $data = [
            $row['kode_booking'],
            $row['nama_pemesan'],
            $row['no_hp'],
            $row['email'],
            $row['alamat_penjemputan'],
            $row['rute'],
            $row['tanggal_keberangkatan'],
            $row['jam_keberangkatan'],
            $row['nomor_kursi_list'],
            'Rp ' . number_format($row['total_harga'], 0, ',', '.'),
            $row['status'],
            $row['created_at']
        ];
        fputcsv($output, $data);
    }
    
    fclose($output);
    exit();
}

// Set page title for header
$page_title = 'Reservasi';
require_once __DIR__ . '/../templates/partials/admin_header.php';
require_once __DIR__ . '/../config/database.php';

// Initialize filter variables
$filter_status = $_GET['status'] ?? 'all';
$filter_date_start = $_GET['date_start'] ?? null;
$filter_date_end = $_GET['date_end'] ?? null;
$cari_alamat = $_GET['cari_alamat'] ?? null;

$where_clauses = [];
$params = [];
$types = '';

if ($filter_status !== 'all') {
    $where_clauses[] = "r.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if (!empty($filter_date_start)) {
    $where_clauses[] = "DATE(r.created_at) >= ?";
    $params[] = $filter_date_start;
    $types .= 's';
}

if (!empty($filter_date_end)) {
    $where_clauses[] = "DATE(r.created_at) <= ?";
    $params[] = $filter_date_end;
    $types .= 's';
}

if (!empty($cari_alamat)) {
    $where_clauses[] = "r.alamat_jemput LIKE ?";
    $params[] = "%$cari_alamat%";
    $types .= 's';
}

// Debug mode dinonaktifkan

// Query untuk mendapatkan data reservasi tanpa duplikat
$query = "SELECT
            r.id_reservasi,
            r.kode_booking,
            r.nama_pemesan,
            r.no_hp,
            r.email,
            r.total_harga,
            r.status,
            COALESCE(r.alamat_jemput, 'Alamat penjemputan akan dikonfirmasi melalui WhatsApp') as alamat_jemput,
            r.created_at,
            j.tanggal_berangkat,
            j.waktu_berangkat,
            j.harga,
            ka.nama_kota as kota_asal,
            kca.nama_kecamatan as kecamatan_asal,
            kt.nama_kota as kota_tujuan,
            kct.nama_kecamatan as kecamatan_tujuan,
            (
                SELECT GROUP_CONCAT(DISTINCT k.nomor_kursi ORDER BY k.nomor_kursi ASC SEPARATOR ', ')
                FROM detail_reservasi_kursi drk
                JOIN kursi k ON drk.id_kursi = k.id_kursi
                WHERE drk.id_reservasi = r.id_reservasi
                GROUP BY drk.id_reservasi
            ) AS nomor_kursi_list
          FROM reservasi r
          LEFT JOIN jadwal j ON r.id_jadwal = j.id_jadwal
          LEFT JOIN kota ka ON j.id_kota_asal = ka.id_kota
          LEFT JOIN kecamatan kca ON j.id_kecamatan_asal = kca.id_kecamatan
          LEFT JOIN kota kt ON j.id_kota_tujuan = kt.id_kota
          LEFT JOIN kecamatan kct ON j.id_kecamatan_tujuan = kct.id_kecamatan
          WHERE 1=1 " . (!empty($where_clauses) ? 'AND ' . implode(' AND ', $where_clauses) : '') . "
          GROUP BY r.id_reservasi
          ORDER BY r.created_at DESC";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$reservasi_list = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Query sudah dieksekusi di atas

// Success/Error messages
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $error_message ?? $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!-- Main Content - Full viewport height and width -->
<div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Page Header -->
    <div class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Manajemen Reservasi</h1>
                <p class="mt-1 text-sm text-gray-600">Kelola dan pantau semua reservasi pelanggan</p>
            </div>
            <div class="mt-4 md:mt-0 flex items-center space-x-3">
                <span class="text-sm text-gray-500">
                    Menampilkan <span class="font-medium"><?php echo count($reservasi_list); ?></span> data reservasi
                </span>
                <a href="?export=csv<?php
                    $query_params = $_GET;
                    unset($query_params['export']);
                    echo !empty($query_params) ? '&' . http_build_query($query_params) : '';
                ?>" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <svg class="-ml-0.5 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Ekspor CSV
                </a>
            </div>
        </div>
    </div>

    <!-- Filter Section - Compact and responsive -->
    <div class="bg-white border-b border-gray-200 px-6 py-4">
        <form method="GET" action="manage_reservasi.php" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                <!-- Status Filter -->
                <div class="sm:col-span-1">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="status" name="status" class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                        <option value="all" <?php echo ($filter_status === 'all') ? 'selected' : ''; ?>>Semua Status</option>
                        <option value="pending" <?php echo ($filter_status === 'pending') ? 'selected' : ''; ?>>Menunggu</option>
                        <option value="dibayar" <?php echo ($filter_status === 'dibayar') ? 'selected' : ''; ?>>Dibayar</option>
                        <option value="dibatalkan" <?php echo ($filter_status === 'dibatalkan') ? 'selected' : ''; ?>>Dibatalkan</option>
                    </select>
                </div>

                <!-- Search Address -->
                <div class="sm:col-span-2">
                    <label for="cari_alamat" class="block text-sm font-medium text-gray-700 mb-1">Cari Alamat</label>
                    <div class="relative">
                        <input type="text" id="cari_alamat" name="cari_alamat"
                               value="<?php echo htmlspecialchars($_GET['cari_alamat'] ?? ''); ?>"
                               class="block w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                               placeholder="Cari alamat jemput...">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Date Filters -->
                <div class="sm:col-span-1">
                    <label for="date_start" class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                    <input type="date" id="date_start" name="date_start"
                           value="<?php echo htmlspecialchars($filter_date_start ?? ''); ?>"
                           class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                </div>

                <div class="sm:col-span-1">
                    <label for="date_end" class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                    <input type="date" id="date_end" name="date_end"
                           value="<?php echo htmlspecialchars($filter_date_end ?? ''); ?>"
                           class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="-ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Terapkan Filter
                </button>
                <a href="manage_reservasi.php" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    Reset Filter
                </a>
            </div>
        </form>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 px-6 py-6">
        <!-- Table Container - Full height with horizontal scroll -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <!-- Table Header - Sticky -->
            <div class="sticky top-0 bg-gray-50 border-b border-gray-200 z-10">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 min-w-[120px]">Kode Booking</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[100px]">Pemesan</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[120px]">Kontak</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[120px]">Jadwal</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[200px]">Rute</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[100px]">Kursi</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[200px]">Alamat</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[100px]">Total</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[100px]">Status</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[100px]">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <!-- Table Body - Horizontal scroll -->
            <div class="overflow-x-auto" style="max-height: calc(100vh - 300px);">
                <table class="min-w-full divide-y divide-gray-200">
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($reservasi_list)): ?>
                            <?php foreach ($reservasi_list as $reservasi): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-mono text-gray-700 sticky left-0 bg-white min-w-[120px]"><?php echo htmlspecialchars($reservasi['kode_booking']); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 min-w-[100px]"><?php echo htmlspecialchars($reservasi['nama_pemesan']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-500 min-w-[120px]">
                                        <div class="space-y-1">
                                            <div>WA: <?php echo !empty($reservasi['no_hp']) ? htmlspecialchars($reservasi['no_hp']) : '-'; ?></div>
                                            <div class="text-xs">Email: <?php echo !empty($reservasi['email']) ? htmlspecialchars($reservasi['email']) : '-'; ?></div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap min-w-[120px]">
                                        <?php
                                        $tanggal = $reservasi['tanggal_berangkat'] ?? '';
                                        $jam = $reservasi['waktu_berangkat'] ?? '';
                                        if ($tanggal && $jam) {
                                            echo '<div class="text-sm text-gray-900">' . date('d M Y', strtotime($tanggal)) . '</div>';
                                            echo '<div class="text-sm text-gray-500">' . date('H:i', strtotime($jam)) . ' WIB</div>';
                                        } else {
                                            echo '<span class="text-gray-400">-</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="px-4 py-3 min-w-[200px]">
                                        <div class="flex items-center space-x-2">
                                            <div class="flex-shrink-0">
                                                <div class="w-8 h-8 bg-blue-50 rounded-full flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($reservasi['kota_asal'] ?? '-'); ?></div>
                                                <div class="text-sm text-gray-500 truncate"><?php echo htmlspecialchars($reservasi['kecamatan_asal'] ?? ''); ?></div>
                                            </div>
                                        </div>
                                        <div class="flex justify-center my-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <div class="flex-shrink-0">
                                                <div class="w-8 h-8 bg-green-50 rounded-full flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($reservasi['kota_tujuan'] ?? '-'); ?></div>
                                                <div class="text-sm text-gray-500 truncate"><?php echo htmlspecialchars($reservasi['kecamatan_tujuan'] ?? ''); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 min-w-[100px]">
                                        <div class="flex flex-wrap gap-1 max-w-[100px]">
                                            <?php
                                            $kursi_list = !empty($reservasi['nomor_kursi_list']) ? explode(',', $reservasi['nomor_kursi_list']) : [];
                                            foreach ($kursi_list as $kursi):
                                                $kursi = trim($kursi);
                                                if (!empty($kursi)): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                        <?php echo htmlspecialchars($kursi); ?>
                                                    </span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                            <?php if (empty($kursi_list) || (count($kursi_list) === 1 && empty(trim($kursi_list[0])))): ?>
                                                <span class="text-gray-400 text-sm">-</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 min-w-[200px]">
                                        <div class="text-sm text-gray-900 max-w-[200px]">
                                            <?php
                                            $alamat = !empty($reservasi['alamat_jemput'])
                                                ? $reservasi['alamat_jemput']
                                                : 'Alamat penjemputan akan dikonfirmasi melalui WhatsApp';
                                            if ($alamat === 'Alamat penjemputan akan dikonfirmasi melalui WhatsApp') {
                                                echo '<span class="text-gray-400 italic">' . htmlspecialchars($alamat) . '</span>';
                                            } else {
                                                echo '<div class="line-clamp-2" title="' . htmlspecialchars($alamat) . '">' . htmlspecialchars($alamat) . '</div>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium text-gray-900 min-w-[100px]">
                                        <?php
                                        $total_harga = isset($reservasi['total_harga']) ? (float)$reservasi['total_harga'] : 0;
                                        echo 'Rp ' . number_format($total_harga, 0, ',', '.');
                                        ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm min-w-[100px]">
                                        <?php
                                        $status = $reservasi['status'] ?? 'pending';
                                        $status_classes = [
                                            'lunas' => 'bg-green-100 text-green-800',
                                            'dibayar' => 'bg-green-100 text-green-800',
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'dibatalkan' => 'bg-red-100 text-red-800',
                                            'expired' => 'bg-gray-100 text-gray-800',
                                            'default' => 'bg-gray-100 text-gray-800'
                                        ];
                                        $status_text = [
                                            'lunas' => 'Lunas',
                                            'dibayar' => 'Dibayar',
                                            'pending' => 'Menunggu',
                                            'dibatalkan' => 'Dibatalkan',
                                            'expired' => 'Kadaluarsa'
                                        ];
                                        $class = $status_classes[$status] ?? $status_classes['default'];
                                        $text = $status_text[$status] ?? ucfirst($status);
                                        ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $class; ?>">
                                            <?php echo $text; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium min-w-[100px]">
                                        <button onclick='openModal("modal-reservasi", <?php echo htmlspecialchars(json_encode($reservasi), ENT_QUOTES, 'UTF-8'); ?>)'
                                                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            <svg class="-ml-0.5 mr-1.5 h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            Detail & Edit
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data</h3>
                                    <p class="mt-1 text-sm text-gray-500">Tidak ada data reservasi yang cocok dengan filter yang Anda pilih.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- Modal Reservasi Detail & Edit Status -->
<div id="modal-reservasi" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center hidden z-50">
    <div class="relative mx-auto p-5 border w-full max-w-xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center border-b pb-3 mb-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Detail Reservasi (<span id="modal-kode-booking"></span>)</h3>
            <button onclick="closeModal('modal-reservasi')" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
            </button>
        </div>
        <div class="space-y-3 text-sm mb-6">
            <p><strong>Pemesan:</strong> <span id="modal-nama-pemesan"></span></p>
            <p><strong>Kontak:</strong> WA: <span id="modal-nomor-telepon"></span> / Email: <span id="modal-email-pemesan"></span></p>
            <p><strong>Tanggal Pesan:</strong> <span id="modal-tanggal-pesan"></span></p>
            <p><strong>Jadwal:</strong> <span id="modal-jadwal-berangkat"></span></p>
            <p><strong>Rute:</strong> <span id="modal-rute"></span></p>
            <div class="mb-4">
                <div class="flex justify-between items-center mb-1">
                    <p class="font-medium text-gray-700">Alamat Jemput:</p>
                    <button type="button" onclick="toggleEditAlamat()" class="text-xs text-blue-600 hover:text-blue-800">Edit</button>
                </div>
                <div id="alamat-jemput-display" class="bg-gray-50 p-3 rounded border border-gray-200 text-sm text-gray-700 whitespace-pre-line"></div>
                <form id="form-edit-alamat" class="hidden" action="./process_reservasi.php" method="POST">
                    <input type="hidden" name="action" value="update_alamat">
                    <input type="hidden" name="id_reservasi" id="edit-alamat-reservasi-id">
                    <div class="mt-2">
                        <textarea name="alamat_jemput" id="edit-alamat-jemput" rows="3" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm" required></textarea>
                    </div>
                    <div class="mt-2 flex justify-end space-x-2">
                        <button type="button" onclick="toggleEditAlamat(false)" class="px-3 py-1 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Batal</button>
                        <button type="submit" class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">Simpan</button>
                    </div>
                </form>
            </div>
            <p><strong>Kursi:</strong> <span id="modal-nomor-kursi"></span></p>
            <p><strong>Total Harga:</strong> <span id="modal-total-harga"></span></p>
            <p><strong>Catatan:</strong> <span id="modal-catatan"></span></p>
        </div>
        <form id="form-reservasi-status" action="./process_reservasi.php" method="POST">
            <input type="hidden" id="reservasi_id" name="id_reservasi">
            <input type="hidden" name="action" value="update_status">
            <div class="mb-4">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Ubah Status Pembayaran</label>
                <select id="status" name="status" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary bg-white text-sm">
                    <option value="pending">Pending</option>
                    <option value="dibayar">Sudah Dibayar</option>
                    <option value="dibatalkan">Dibatalkan</option>
                </select>
            </div>
            <div class="flex justify-between items-center mt-6">
                <div>
                    <button type="button" onclick="showDeleteConfirmation(document.getElementById('reservasi_id').value)" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded text-sm transition duration-300 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 22H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Hapus Reservasi
                    </button>
                </div>
                <div class="flex space-x-2">
                    <button type="button" onclick="closeModal('modal-reservasi')" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded text-sm transition duration-300">Tutup</button>
                    <button type="submit" class="bg-primary hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm transition duration-300">Update Status</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(modalId, data) {
        const modal = document.getElementById(modalId);
        
        // Format contact information
        const phoneNumber = data.no_hp || data.nomor_telepon_pemesan || '-';
        const email = data.email || data.email_pemesan || '-';
        
        // Format route information
        const kotaAsal = data.kota_asal || 'Bandung';
        const kotaTujuan = data.kota_tujuan || 'Jakarta';
        const kecAsal = data.kec_asal || data.kecamatan_asal || '';
        const kecTujuan = data.kec_tujuan || data.kecamatan_tujuan || '';
        const ruteText = `${kotaAsal}${kecAsal ? ` (${kecAsal})` : ''} â†’ ${kotaTujuan}${kecTujuan ? ` (${kecTujuan})` : ''}`;
        
        // Populate detail fields with fallbacks
        document.getElementById('modal-kode-booking').textContent = data.kode_booking || '-';
        document.getElementById('modal-nama-pemesan').textContent = data.nama_pemesan || '-';
        document.getElementById('modal-nomor-telepon').textContent = phoneNumber;
        document.getElementById('modal-email-pemesan').textContent = email;
        document.getElementById('modal-tanggal-pesan').textContent = data.created_at 
            ? new Date(data.created_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
            : '-';
            
        const jadwalText = data.tanggal_berangkat && data.waktu_berangkat
            ? new Date(`${data.tanggal_berangkat} ${data.waktu_berangkat}`).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
            : 'Belum ditentukan';
            
        document.getElementById('modal-jadwal-berangkat').textContent = jadwalText;
        document.getElementById('modal-rute').textContent = ruteText;
        document.getElementById('alamat-jemput-display').textContent = data.alamat_jemput || '-';
        document.getElementById('edit-alamat-jemput').value = data.alamat_jemput || '';
        document.getElementById('edit-alamat-reservasi-id').value = data.id_reservasi;
        document.getElementById('modal-nomor-kursi').textContent = data.nomor_kursi_list || '-';
        document.getElementById('modal-total-harga').textContent = `Rp ${parseInt(data.total_harga).toLocaleString('id-ID')}`;
        document.getElementById('modal-catatan').textContent = data.catatan_pemesan || '-';
        
        // Sembunyikan form edit alamat saat pertama kali dibuka
        toggleEditAlamat(false);

        // Set form values
        document.getElementById('reservasi_id').value = data.id_reservasi || '';
        document.getElementById('status').value = data.status || 'pending';

        modal.classList.remove('hidden');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }
    
    function toggleEditAlamat(show = null) {
        const displayElement = document.getElementById('alamat-jemput-display');
        const formElement = document.getElementById('form-edit-alamat');
        
        if (show === null) {
            // Toggle current state
            show = displayElement.classList.contains('hidden');
        }
        
        if (show) {
            displayElement.classList.add('hidden');
            formElement.classList.remove('hidden');
        } else {
            displayElement.classList.remove('hidden');
            formElement.classList.add('hidden');
        }
    }
    
    // Function to show delete confirmation modal
    function showDeleteConfirmation(id) {
        // Create modal if it doesn't exist
        let modal = document.getElementById('delete-confirmation-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'delete-confirmation-modal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100] hidden';
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                    <div class="p-6">
                        <div class="flex items-center justify-center mb-4">
                            <div class="bg-red-100 p-3 rounded-full">
                                <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 text-center mb-2">Hapus Reservasi</h3>
                        <p class="text-gray-600 text-center mb-6">Apakah Anda yakin ingin menghapus reservasi ini? Tindakan ini tidak dapat dibatalkan.</p>
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="document.getElementById('delete-confirmation-modal').classList.add('hidden')" class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Batal
                            </button>
                            <button type="button" id="confirm-delete-btn" class="px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Ya, Hapus
                            </button>
                        </div>
                    </div>
                </div>`;
            document.body.appendChild(modal);
        }
        
        // Set up the confirm button with the correct ID
        const confirmBtn = document.getElementById('confirm-delete-btn');
        const oldConfirmHandler = confirmBtn.onclick;
        confirmBtn.onclick = null; // Remove old event listeners
        
        // Add new event listener
        confirmBtn.onclick = function() {
            deleteReservasi(id);
        };
        
        // Show the modal
        modal.classList.remove('hidden');
    }
    
    // Function to handle reservation deletion
    function deleteReservasi(id) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id_reservasi', id);
        
        // Show loading state
        const deleteBtn = document.getElementById('confirm-delete-btn');
        const originalText = deleteBtn.innerHTML;
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Menghapus...';
        
        fetch('./process_reservasi.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(async response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Unexpected response:', text);
                throw new Error('Respon tidak valid dari server');
            }
            return response.json();
        })
        .then(data => {
            if (data && data.success) {
                // Hide the delete confirmation modal
                const deleteModal = document.getElementById('delete-confirmation-modal');
                if (deleteModal) {
                    deleteModal.classList.add('hidden');
                }
                
                // Show success message
                const successToast = document.createElement('div');
                successToast.className = 'fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded shadow-lg z-50 flex items-center';
                successToast.innerHTML = `
                    <svg class="h-6 w-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>${data.message || 'Reservasi berhasil dihapus'}</span>`;
                document.body.appendChild(successToast);
                
                // Hide the detail modal
                closeModal('modal-reservasi');
                
                // Reload the page after a short delay
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
                
                // Remove the toast after 3 seconds
                setTimeout(() => {
                    successToast.remove();
                }, 3000);
            } else {
                throw new Error(data && data.message ? data.message : 'Terjadi kesalahan tidak diketahui');
            }
        })
        .catch(error => {
            // Reset button state
            if (deleteBtn) {
                deleteBtn.disabled = false;
                deleteBtn.innerHTML = originalText;
            }
            
            // Show error message
            const errorToast = document.createElement('div');
            errorToast.className = 'fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded shadow-lg z-50 flex items-center';
            errorToast.innerHTML = `
                <svg class="h-6 w-6 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>${error.message || 'Gagal menghapus reservasi'}</span>`;
            document.body.appendChild(errorToast);
            
            // Remove the toast after 5 seconds
            setTimeout(() => {
                errorToast.remove();
            }, 5000);
            
            console.error('Error:', error);
        });    
    }
    
    // Handle form submission for alamat jemput
    document.getElementById('form-edit-alamat').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(() => {
            // Reload the page to see changes
            window.location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat memperbarui alamat jemput');
        });
    });
</script>

<style>
/* Custom CSS untuk layout admin yang responsif */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-clamp: 2; /* Standard property */
}

/* Sticky table header */
.sticky {
    position: sticky;
    z-index: 10;
}

/* Scrollable table body */
.table-container {
    max-height: calc(100vh - 300px);
    overflow-y: auto;
}

/* Hover effects */
.hover\:bg-gray-50:hover {
    background-color: #f9fafb;
}

/* Custom scrollbar untuk webkit browsers */
.overflow-x-auto::-webkit-scrollbar,
.table-container::-webkit-scrollbar {
    height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track,
.table-container::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.overflow-x-auto::-webkit-scrollbar-thumb,
.table-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover,
.table-container::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Responsive table minimum widths */
@media (max-width: 768px) {
    .min-w-\[120px\] {
        min-width: 100px;
    }

    .min-w-\[200px\] {
        min-width: 150px;
    }
}

/* Ensure full viewport height */
.min-h-screen {
    min-height: 100vh;
}

/* Sticky left column */
.sticky.left-0 {
    position: sticky;
    left: 0;
    z-index: 5;
}
</style>

<?php
require_once __DIR__ . '/../templates/partials/admin_footer.php'; 
?>



