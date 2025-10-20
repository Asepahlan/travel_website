<?php
$page_title = 'Jadwal'; // Set page title for header
require_once __DIR__ . '/../templates/partials/admin_header.php'; // Includes CSRF token generation
require_once __DIR__ . '/../config/database.php';

// --- Fetch Data for Form Dropdowns ---

// Fetch Kota
$sql_kota = "SELECT id_kota, nama_kota FROM kota ORDER BY nama_kota ASC";
$result_kota = $conn->query($sql_kota);
$kota_list = [];
if ($result_kota && $result_kota->num_rows > 0) {
    while ($row = $result_kota->fetch_assoc()) {
        $kota_list[$row['id_kota']] = $row['nama_kota'];
    }
}

// Fetch Kecamatan (Grouped by Kota for JS)
$sql_kecamatan = "SELECT id_kecamatan, nama_kecamatan, id_kota FROM kecamatan ORDER BY nama_kecamatan ASC";
$result_kecamatan = $conn->query($sql_kecamatan);
$kecamatan_list_grouped = [];
if ($result_kecamatan && $result_kecamatan->num_rows > 0) {
    while ($row = $result_kecamatan->fetch_assoc()) {
        $kecamatan_list_grouped[$row['id_kota']][] = ['id' => $row['id_kecamatan'], 'nama' => $row['nama_kecamatan']];
    }
}

// Fetch Layout Kursi
$sql_layout = "SELECT id_layout, nama_layout FROM layout_kursi ORDER BY nama_layout ASC";
$result_layout = $conn->query($sql_layout);
$layout_list = [];
if ($result_layout && $result_layout->num_rows > 0) {
    while ($row = $result_layout->fetch_assoc()) {
        $layout_list[$row['id_layout']] = $row['nama_layout'];
    }
}

// Check column existence
$check_columns = $conn->query("SHOW COLUMNS FROM jadwal");
$columns = [];
if ($check_columns) {
    while ($col = $check_columns->fetch_assoc()) {
        $columns[] = $col['Field'];
    }
}

// Build the SELECT part of the query
$select_columns = [
    'j.id_jadwal',
    'j.tanggal_berangkat',
    'j.waktu_berangkat',
    '75000 AS harga_per_kursi',  // Default price
    'ka.nama_kota AS kota_asal',
    'kca.nama_kecamatan AS kec_asal',
    'kt.nama_kota AS kota_tujuan',
    'kct.nama_kecamatan AS kec_tujuan',
];

// Cek apakah tabel reservasi ada
$check_table = $conn->query("SHOW TABLES LIKE 'reservasi'");
$reservasi_table_exists = $check_table && $check_table->num_rows > 0;

// Dapatkan total kursi dari layout
$sql_total_kursi = "SELECT IFNULL(SUM(jumlah_baris * jumlah_kolom), 40) as total FROM layout_kursi LIMIT 1";
$result_total_kursi = $conn->query($sql_total_kursi);
$total_kursi_default = 40; // Default jika tidak ada data
if ($result_total_kursi && $result_total_kursi->num_rows > 0) {
    $total_kursi_default = $result_total_kursi->fetch_assoc()['total'];
}

// Debug: Cek kolom di tabel jadwal
$result = $conn->query("SHOW COLUMNS FROM jadwal");
$columns = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
}
// Debug: Tampilkan kolom yang ada
error_log("Kolom di tabel jadwal: " . implode(', ', $columns));

// Dapatkan jumlah kursi dari tabel kursi untuk setiap layout
$sql_layout = "SELECT l.id_layout, l.nama_layout, COUNT(k.id_kursi) as total_kursi 
              FROM layout_kursi l 
              LEFT JOIN kursi k ON l.id_layout = k.id_layout 
              GROUP BY l.id_layout 
              ORDER BY l.id_layout ASC 
              LIMIT 1";
$result_layout = $conn->query($sql_layout);
$default_layout = $result_layout ? $result_layout->fetch_assoc() : null;
$total_kursi_default = $default_layout ? (int)$default_layout['total_kursi'] : 2; // Default 2 kursi jika tidak ada data

error_log("SQL Layout: " . $sql_layout);
error_log("Data Layout: " . print_r($default_layout, true));
error_log("Total kursi default: " . $total_kursi_default);

// Simpan ke session untuk digunakan di halaman lain jika diperlukan
$_SESSION['total_kursi_default'] = $total_kursi_default;

// Dapatkan dulu data jadwal
// Dapatkan data layout kursi dengan jumlah kursi aktual
$layout_kursi = [];
$sql_layout = "SELECT 
    l.id_layout, 
    l.nama_layout, 
    l.jumlah_baris, 
    l.jumlah_kolom, 
    COUNT(k.id_kursi) as total_kursi_aktual,
    (l.jumlah_baris * l.jumlah_kolom) as total_kursi_desain 
    FROM layout_kursi l 
    LEFT JOIN kursi k ON l.id_layout = k.id_layout 
    GROUP BY l.id_layout";

$result_layout = $conn->query($sql_layout);
if ($result_layout && $result_layout->num_rows > 0) {
    while ($row = $result_layout->fetch_assoc()) {
        $layout_kursi[$row['id_layout']] = $row;
    }
}

// Dapatkan data jadwal
$sql_jadwal = "SELECT 
    j.id_jadwal, 
    j.tanggal_berangkat, 
    j.waktu_berangkat,
    j.harga,
    j.estimasi_jam,
    j.keterangan,
    j.id_kota_asal,
    j.id_kecamatan_asal,
    j.id_kota_tujuan,
    j.id_kecamatan_tujuan,
    j.id_layout_kursi,
    ka.nama_kota AS kota_asal,
    kca.nama_kecamatan AS kec_asal,
    kt.nama_kota AS kota_tujuan,
    kct.nama_kecamatan AS kec_tujuan,
    lk.nama_layout as nama_layout
    FROM jadwal j
    LEFT JOIN kota ka ON j.id_kota_asal = ka.id_kota
    LEFT JOIN kecamatan kca ON j.id_kecamatan_asal = kca.id_kecamatan
    LEFT JOIN kota kt ON j.id_kota_tujuan = kt.id_kota
    LEFT JOIN kecamatan kct ON j.id_kecamatan_tujuan = kct.id_kecamatan
    LEFT JOIN layout_kursi lk ON j.id_layout_kursi = lk.id_layout
    ORDER BY j.tanggal_berangkat DESC, j.waktu_berangkat ASC";

// Debug: Tampilkan query SQL
error_log("SQL Query: " . $sql_jadwal);

// Log the query for debugging
error_log("Simplified SQL Query: " . $sql_jadwal);
$result_jadwal = $conn->query($sql_jadwal);
$jadwal_list = [];

if ($result_jadwal && $result_jadwal->num_rows > 0) {
    while ($row = $result_jadwal->fetch_assoc()) {
        // Inisialisasi variabel
        $id_layout = $row['id_layout_kursi'];
        $total_kursi = 0;
        $nama_layout = 'Tidak Ada Layout';
        
        // Ambil data layout jika ada
        if ($id_layout && isset($layout_kursi[$id_layout])) {
            $total_kursi = (int)$layout_kursi[$id_layout]['total_kursi_aktual'];
            $nama_layout = htmlspecialchars($layout_kursi[$id_layout]['nama_layout']);
            
            // Log untuk debugging
            error_log("Layout ID $id_layout - Total Aktual: " . $layout_kursi[$id_layout]['total_kursi_aktual'] . 
                    ", Desain: " . $layout_kursi[$id_layout]['total_kursi_desain']);
        }
        
        $kursi_terpakai = 0;
        $row['total_kursi'] = $total_kursi;
        $row['nama_layout'] = $nama_layout;
        
        // Hitung kursi yang sudah dipesan (jika tabel reservasi ada)
        if ($reservasi_table_exists) {
            $sql_reservasi = "SELECT COUNT(*) as total 
                            FROM detail_reservasi_kursi drk
                            JOIN reservasi r ON drk.id_reservasi = r.id_reservasi
                            WHERE r.id_jadwal = ? AND (r.status = 'dibayar' OR r.status = 'dipesan')";
            $stmt_reservasi = $conn->prepare($sql_reservasi);
            if ($stmt_reservasi) {
                $stmt_reservasi->bind_param("i", $row['id_jadwal']);
                $stmt_reservasi->execute();
                $result_reservasi = $stmt_reservasi->get_result();
                if ($result_reservasi && $result_reservasi->num_rows > 0) {
                    $kursi_terpakai = (int)$result_reservasi->fetch_assoc()['total'];
                }
                $stmt_reservasi->close();
            }
        }
        
        // Debug log
        error_log("Jadwal ID " . $row['id_jadwal'] . ": " . 
                "Total Aktual=$total_kursi, " . 
                "Terpakai=$kursi_terpakai, " . 
                "Layout ID=" . ($row['id_layout_kursi'] ?? 'null') . 
                ", Nama Layout=$nama_layout");

        // Hitung kursi tersedia
        $kursi_tersedia = max(0, $total_kursi - $kursi_terpakai);
        
        // Tambahkan data ke array hasil
        $row['total_kursi'] = $total_kursi;
        $row['kursi_terpakai'] = $kursi_terpakai;
        $row['kursi_tersedia'] = $kursi_tersedia;
        $jadwal_list[] = $row;
    }
}

$conn->close();

// Handle messages from session
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

?>

<!-- Success/Error Messages -->
<?php if ($success_message): ?>
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
    <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
</div>
<?php endif; ?>
<?php if ($error_message): ?>
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
    <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
</div>
<?php endif; ?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Daftar Jadwal Keberangkatan</h1>
            <p class="mt-1 text-sm text-gray-500">Kelola jadwal keberangkatan armada travel</p>
        </div>
        <button onclick="openModal('modal-jadwal', null)" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow-sm transition-all duration-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Tambah Jadwal
        </button>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($success_message): ?>
    <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4 rounded">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-green-700"><?php echo htmlspecialchars($success_message); ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
    <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-700"><?php echo htmlspecialchars($error_message); ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="bg-white shadow overflow-hidden rounded-lg border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider whitespace-nowrap">Tanggal</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider whitespace-nowrap">Waktu</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Asal</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Tujuan</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider whitespace-nowrap">Harga</th>
                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider whitespace-nowrap">Kursi Tersedia</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider whitespace-nowrap">Estimasi</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider whitespace-nowrap">Layout</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (!empty($jadwal_list)): ?>
                <?php foreach ($jadwal_list as $jadwal): ?>
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900 font-medium whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-8 w-8 flex items-center justify-center bg-blue-50 rounded-full text-blue-600 mr-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo date('d M Y', strtotime($jadwal['tanggal_berangkat'])); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 font-medium whitespace-nowrap">
                            <?php echo date('H:i', strtotime($jadwal['waktu_berangkat'])); ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($jadwal['kota_asal']); ?></div>
                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($jadwal['kec_asal']); ?></div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($jadwal['kota_tujuan']); ?></div>
                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($jadwal['kec_tujuan']); ?></div>
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 whitespace-nowrap">
                            Rp <?php echo number_format($jadwal['harga'], 0, ',', '.'); ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php 
                            // Ambil total kursi dari data jadwal yang sudah di-query
                            $total_kursi = (int)($jadwal['total_kursi'] ?? 0);
                            $kursi_terpakai = (int)($jadwal['kursi_terpakai'] ?? 0);
                            $kursi_tersedia = max(0, $total_kursi - $kursi_terpakai);
                            
                            // Hitung persentase untuk warna
                            $percentage = $total_kursi > 0 ? ($kursi_tersedia / $total_kursi) * 100 : 0;
                            $bgColor = $total_kursi <= 0 ? 'bg-gray-100 text-gray-800' : 
                                     ($percentage < 20 ? 'bg-red-100 text-red-800' : 
                                     ($percentage < 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'));
                            
                            // Tampilkan status khusus jika tidak ada kursi
                            if ($total_kursi <= 0) {
                                echo '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $bgColor . '" title="Belum ada konfigurasi kursi">Tidak tersedia</span>';
                            } else {
                                echo '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $bgColor . '" title="Total kursi: ' . $total_kursi . ', Terpakai: ' . $kursi_terpakai . '">' . 
                                     htmlspecialchars($kursi_tersedia . '/' . $total_kursi) . '</span>';
                            }
                            ?>
                            <!-- Debug: <?php echo "Total: $total_kursi, Terpakai: $kursi_terpakai"; ?> -->
                        </td>
                        <td class="px-4 py-3 text-center text-sm text-gray-700 whitespace-nowrap">
                            <?php echo htmlspecialchars(($jadwal['estimasi_jam'] ?? '0') . ' jam'); ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">
                                <?php echo !empty($jadwal['nama_layout']) ? htmlspecialchars($jadwal['nama_layout']) : 'Default'; ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-medium whitespace-nowrap">
                            <div class="flex items-center justify-end space-x-2">
                                <button onclick='openModal("modal-jadwal", <?php echo json_encode($jadwal); ?>)' class="inline-flex items-center justify-center p-1.5 text-indigo-600 hover:text-indigo-900 hover:bg-indigo-50 rounded-md transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                    </svg>
                                </button>
                                <?php 
                                    $detail = $jadwal['kota_asal'] . ' ke ' . $jadwal['kota_tujuan'] . ' - ' . 
                                              date('d M Y', strtotime($jadwal['tanggal_berangkat'])) . ' ' . 
                                              substr($jadwal['waktu_berangkat'], 0, 5);
                                    $detail = htmlspecialchars($detail, ENT_QUOTES, 'UTF-8');
                                ?>
                                <button type="button" 
                                        onclick="showDeleteConfirmation(<?php echo $jadwal['id_jadwal']; ?>, '<?php echo $detail; ?>')" 
                                        class="inline-flex items-center justify-center p-1.5 text-red-600 hover:text-red-900 hover:bg-red-50 rounded-md transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada data jadwal.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Konfirmasi Hapus -->
<div id="modal-delete-confirm" class="fixed z-60 inset-0 overflow-y-auto hidden" aria-labelledby="modal-delete-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('modal-delete-confirm')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-delete-title">Konfirmasi Hapus Jadwal</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                <span id="delete-jadwal-detail"></span> 
                                Menghapus jadwal ini juga akan menghapus semua reservasi terkait. Tindakan ini tidak dapat dibatalkan.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <form id="form-delete" method="POST" action="./process_delete.php" class="inline-flex w-full sm:ml-3 sm:w-auto">
                    <input type="hidden" name="type" value="jadwal">
                    <input type="hidden" name="id" id="delete-jadwal-id">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Ya, Hapus
                    </button>
                </form>
                <button type="button" onclick="closeModal('modal-delete-confirm')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Batal
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit Jadwal -->
<div id="modal-jadwal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('modal-jadwal')"></div>
        
        <!-- This element is to trick the browser into centering the modal contents. -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full">
            <!-- Modal Header -->
            <div class="bg-white px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 id="modal-jadwal-title" class="text-lg font-medium text-gray-900">Tambah Jadwal Baru</h3>
                    <button type="button" onclick="closeModal('modal-jadwal')" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                        <span class="sr-only">Tutup</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="px-6 py-4">
                <form id="form-jadwal" action="./process_jadwal.php" method="POST" class="space-y-4">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" id="jadwal_id" name="id_jadwal">
                    <input type="hidden" name="action" id="jadwal_action" value="add">
                    
                    <!-- Asal -->
                    <div class="space-y-4">
                        <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Asal</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="id_kota_asal" class="block text-sm font-medium text-gray-700 mb-1">Kota Asal</label>
                                <select id="id_kota_asal" name="id_kota_asal" required 
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md">
                                    <option value="" disabled selected>Pilih Kota Asal</option>
                                    <?php foreach ($kota_list as $id => $nama): ?>
                                        <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($nama); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="id_kecamatan_asal" class="block text-sm font-medium text-gray-700 mb-1">Kecamatan Asal</label>
                                <select id="id_kecamatan_asal" name="id_kecamatan_asal" required 
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md bg-gray-100" 
                                    disabled>
                                    <option value="" disabled selected>Pilih Kota Terlebih Dahulu</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Tujuan -->
                    <div class="space-y-4">
                        <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Tujuan</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="id_kota_tujuan" class="block text-sm font-medium text-gray-700 mb-1">Kota Tujuan</label>
                                <select id="id_kota_tujuan" name="id_kota_tujuan" required 
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md">
                                    <option value="" disabled selected>Pilih Kota Tujuan</option>
                                    <?php foreach ($kota_list as $id => $nama): ?>
                                        <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($nama); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="id_kecamatan_tujuan" class="block text-sm font-medium text-gray-700 mb-1">Kecamatan Tujuan</label>
                                <select id="id_kecamatan_tujuan" name="id_kecamatan_tujuan" required 
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md bg-gray-100" 
                                    disabled>
                                    <option value="" disabled selected>Pilih Kota Terlebih Dahulu</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Waktu -->
                    <div class="space-y-4">
                        <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Waktu Keberangkatan</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="tanggal_berangkat" class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                                <input type="date" id="tanggal_berangkat" name="tanggal_berangkat" required 
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md">
                            </div>
                            <div>
                                <label for="waktu_berangkat" class="block text-sm font-medium text-gray-700 mb-1">Jam</label>
                                <input type="time" id="waktu_berangkat" name="waktu_berangkat" required 
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md">
                            </div>
                            <div>
                                <label for="estimasi_jam" class="block text-sm font-medium text-gray-700 mb-1">Durasi (Jam)</label>
                                <input type="number" id="estimasi_jam" name="estimasi_jam" required min="1"
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md"
                                    placeholder="Contoh: 2">
                            </div>
                        </div>
                    </div>

                    <!-- Layout Kursi -->
                    <div class="space-y-4">
                        <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Layout Kursi</h4>
                        <div class="grid grid-cols-1">
                            <div>
                                <label for="id_layout_kursi" class="block text-sm font-medium text-gray-700 mb-1">Pilih Layout Kursi</label>
                                <select id="id_layout_kursi" name="id_layout_kursi" required 
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md">
                                    <option value="" disabled selected>Pilih Layout Kursi</option>
                                    <?php foreach ($layout_list as $id => $nama): ?>
                                        <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($nama); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Harga -->
                    <div class="space-y-4">
                        <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Harga</h4>
                        <div class="grid grid-cols-1">
                            <div>
                                <label for="harga" class="block text-sm font-medium text-gray-700 mb-1">Harga (Rp)</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">Rp</span>
                                    </div>
                                    <input type="number" id="harga" name="harga" required 
                                        class="focus:ring-primary focus:border-primary block w-full pl-12 pr-12 sm:text-sm border border-gray-300 rounded-md py-2"
                                        placeholder="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Keterangan -->
                    <div class="space-y-2">
                        <label for="keterangan" class="block text-sm font-medium text-gray-700">Keterangan (Opsional)</label>
                        <textarea id="keterangan" name="keterangan" rows="2"
                            class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border border-gray-300 rounded-md p-2"
                            placeholder="Tulis keterangan tambahan jika perlu"></textarea>
                    </div>

                    <!-- Footer -->
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" 
                            onclick="closeModal('modal-jadwal')" 
                            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            Batal
                        </button>
                        <button type="submit" 
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            Simpan Jadwal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle form submission
        const form = document.getElementById('form-jadwal');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Client-side validation
                const kotaAsal = document.getElementById('id_kota_asal').value;
                const kecAsal = document.getElementById('id_kecamatan_asal').value;
                const kotaTujuan = document.getElementById('id_kota_tujuan').value;
                const kecTujuan = document.getElementById('id_kecamatan_tujuan').value;
                const tanggal = document.getElementById('tanggal_berangkat').value;
                const waktu = document.getElementById('waktu_berangkat').value;
                const harga = document.getElementById('harga').value;
                const estimasi = document.getElementById('estimasi_jam').value;

                if (!kotaAsal || !kecAsal || !kotaTujuan || !kecTujuan || !tanggal || !waktu || !harga || !estimasi) {
                    e.preventDefault();
                    alert('Semua field wajib diisi kecuali keterangan.');
                    return false;
                }

                if (kotaAsal === kotaTujuan && kecAsal === kecTujuan) {
                    e.preventDefault();
                    alert('Kota asal dan tujuan tidak boleh sama.');
                    return false;
                }

                // If all validations pass, the form will submit normally
            });
        }
    });

    const kecamatanData = <?php echo json_encode($kecamatan_list_grouped); ?>;

    function populateKecamatan(kotaSelectId, kecamatanSelectId, selectedKecamatanId = null) {
        const kotaSelect = document.getElementById(kotaSelectId);
        const kecamatanSelect = document.getElementById(kecamatanSelectId);
        const selectedKotaId = kotaSelect.value;

        // Clear existing options
        kecamatanSelect.innerHTML = '<option value="" disabled selected>-- Pilih Kecamatan --</option>';
        kecamatanSelect.disabled = true;

        if (selectedKotaId && kecamatanData[selectedKotaId]) {
            kecamatanSelect.disabled = false;
            kecamatanData[selectedKotaId].forEach(kecamatan => {
                const option = document.createElement('option');
                option.value = kecamatan.id;
                option.textContent = kecamatan.nama;
                if (selectedKecamatanId && kecamatan.id == selectedKecamatanId) {
                    option.selected = true;
                }
                kecamatanSelect.appendChild(option);
            });
        } else if (selectedKotaId) {
             kecamatanSelect.innerHTML = '<option value="" disabled selected>-- Tidak ada kecamatan --</option>';
             kecamatanSelect.disabled = true;
        } else {
             kecamatanSelect.innerHTML = '<option value="" disabled selected>-- Pilih Kota Dulu --</option>';
             kecamatanSelect.disabled = true;
        }
    }

    document.getElementById('id_kota_asal').addEventListener('change', () => {
        populateKecamatan('id_kota_asal', 'id_kecamatan_asal');
    });
    document.getElementById('id_kota_tujuan').addEventListener('change', () => {
        populateKecamatan('id_kota_tujuan', 'id_kecamatan_tujuan');
    });

    function openModal(modalId, data) {
        const modal = document.getElementById(modalId);
        const form = modal.querySelector('form');
        form.reset(); // Reset form fields

        const title = modal.querySelector('#modal-jadwal-title');
        const actionInput = modal.querySelector('#jadwal_action');
        const idInput = modal.querySelector('#jadwal_id');
        
        // Reset kecamatan dropdowns
        document.getElementById('id_kecamatan_asal').innerHTML = '<option value="" disabled selected>-- Pilih Kota Dulu --</option>';
        document.getElementById('id_kecamatan_asal').disabled = true;
        document.getElementById('id_kecamatan_tujuan').innerHTML = '<option value="" disabled selected>-- Pilih Kota Dulu --</option>';
        document.getElementById('id_kecamatan_tujuan').disabled = true;

        if (data) { // Edit mode
            title.textContent = 'Edit Jadwal';
            actionInput.value = 'edit';
            idInput.value = data.id_jadwal;
            document.getElementById('id_kota_asal').value = data.id_kota_asal;
            document.getElementById('id_kota_tujuan').value = data.id_kota_tujuan;
            document.getElementById('tanggal_berangkat').value = data.tanggal_berangkat;
            document.getElementById('waktu_berangkat').value = data.waktu_berangkat.substring(0, 5); // Format HH:MM
            document.getElementById('harga').value = data.harga;
            document.getElementById('estimasi_jam').value = data.estimasi_jam || 1;
            document.getElementById('keterangan').value = data.keterangan || '';
            
            // Set the selected layout
            if (data.id_layout_kursi) {
                document.getElementById('id_layout_kursi').value = data.id_layout_kursi;
            }

            // Populate and select kecamatan after setting kota
            setTimeout(() => {
                populateKecamatan('id_kota_asal', 'id_kecamatan_asal', data.id_kecamatan_asal);
                populateKecamatan('id_kota_tujuan', 'id_kecamatan_tujuan', data.id_kecamatan_tujuan);
            }, 100);

        } else { // Add mode
            title.textContent = 'Tambah Jadwal Baru';
            actionInput.value = 'add';
            idInput.value = '';
            // Set default date to tomorrow
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            document.getElementById('tanggal_berangkat').valueAsDate = tomorrow;
            // Set default time to 08:00
            document.getElementById('waktu_berangkat').value = '08:00';
        }

        // Show the modal
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    // Fungsi untuk menampilkan konfirmasi hapus
    function showDeleteConfirmation(id, detail) {
        document.getElementById('delete-jadwal-id').value = id;
        document.getElementById('delete-jadwal-detail').textContent = 'Anda yakin ingin menghapus jadwal ' + detail + '?';
        document.getElementById('modal-delete-confirm').style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    // Close modal when clicking on the overlay
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            closeModal('modal-jadwal');
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal('modal-jadwal');
        }
    });

</script>

<?php
require_once __DIR__ . '/../templates/partials/admin_footer.php'; 
?>

