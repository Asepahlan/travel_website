<?php
require_once __DIR__ . '/config/database.php';
session_start();

$page_title = 'Jadwal Keberangkatan';
require_once __DIR__ . '/templates/partials/header.php';
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 to-blue-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header Section -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-extrabold text-gray-900 sm:text-5xl mb-4 bg-clip-text text-transparent bg-gradient-to-r from-[#00BFFF] to-[#0099cc]">
                Jadwal Keberangkatan
            </h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Temukan jadwal perjalanan terbaik untuk perjalanan Anda yang nyaman dan aman
            </p>
            <div class="mt-6">
                <div class="inline-flex items-center px-4 py-2 bg-white rounded-full shadow-sm text-sm font-medium text-gray-700">
                    <svg class="-ml-1 mr-2 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                    </svg>
                    Update Terakhir: <?php echo date('d M Y H:i'); ?>
                </div>
            </div>
        </div>

        <!-- Schedule Card -->
        <div class="bg-white shadow-xl rounded-xl overflow-hidden mb-8">
            <div class="px-6 py-5 bg-gradient-to-r from-[#00BFFF] to-[#0099cc] sm:px-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-white">
                            Daftar Jadwal Tersedia
                        </h3>
                        <p class="mt-1 text-sm text-blue-100">
                            Pilih jadwal yang sesuai dengan kebutuhan perjalanan Anda
                        </p>
                    </div>
                    <div class="mt-3 sm:mt-0 w-full sm:w-auto">
                        <div class="flex flex-col sm:flex-row gap-2">
                            <!-- Search Input -->
                            <div class="relative flex-grow max-w-full">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <input type="text" id="searchInput" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-[#00BFFF] focus:border-[#00BFFF] sm:text-sm" placeholder="Cari rute...">
                            </div>
                            
                            <!-- Sort Dropdown -->
                            <div class="relative">
                                <button type="button" id="sortButton" class="inline-flex justify-center w-full sm:w-auto px-4 py-2 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-1 focus:ring-[#00BFFF] focus:border-[#00BFFF]">
                                    <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 100 2h7a1 1 0 100-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3z" />
                                    </svg>
                                    Urutkan
                                    <svg class="ml-2 -mr-1 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <!-- Dropdown menu -->
                                <div id="sortDropdown" class="hidden origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-10">
                                    <div class="py-1" role="none">
                                        <a href="#" class="text-gray-700 block px-4 py-2 text-sm hover:bg-gray-100" data-sort="harga-asc">Harga: Rendah ke Tinggi</a>
                                        <a href="#" class="text-gray-700 block px-4 py-2 text-sm hover:bg-gray-100" data-sort="harga-desc">Harga: Tinggi ke Rendah</a>
                                        <a href="#" class="text-gray-700 block px-4 py-2 text-sm hover:bg-gray-100" data-sort="waktu-asc">Waktu: Awal ke Akhir</a>
                                        <a href="#" class="text-gray-700 block px-4 py-2 text-sm hover:bg-gray-100" data-sort="waktu-desc">Waktu: Akhir ke Awal</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Loading Indicator -->
                <div id="loadingIndicator" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white p-6 rounded-lg shadow-xl">
                        <div class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-lg font-medium text-gray-700">Memuat data...</span>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <div class="md:hidden space-y-4" id="mobileScheduleView">
                        <!-- Mobile cards will be inserted here by JavaScript -->
                    </div>
                    <table class="min-w-full divide-y divide-gray-200 hidden md:table" id="desktopScheduleView">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rute Perjalanan</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jadwal</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Kursi</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">Aksi</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="scheduleTable">
                        <?php
                        // Query to get available schedules with actual seat count
                        $sql = "SELECT 
                                    j.*, 
                                    ka.nama_kota as kota_asal, 
                                    kca.nama_kecamatan as kec_asal,
                                    kt.nama_kota as kota_tujuan, 
                                    kct.nama_kecamatan as kec_tujuan,
                                    lk.nama_layout, 
                                    lk.jumlah_baris, 
                                    lk.jumlah_kolom,
                                    (SELECT COUNT(*) FROM kursi k WHERE k.id_layout = lk.id_layout) as total_kursi_aktual,
                                    (lk.jumlah_baris * lk.jumlah_kolom) as total_kursi_desain
                                FROM jadwal j
                                JOIN kota ka ON j.id_kota_asal = ka.id_kota
                                JOIN kecamatan kca ON j.id_kecamatan_asal = kca.id_kecamatan
                                JOIN kota kt ON j.id_kota_tujuan = kt.id_kota
                                JOIN kecamatan kct ON j.id_kecamatan_tujuan = kct.id_kecamatan
                                LEFT JOIN layout_kursi lk ON j.id_layout_kursi = lk.id_layout
                                WHERE j.tanggal_berangkat >= CURDATE()
                                ORDER BY j.tanggal_berangkat ASC, j.waktu_berangkat ASC";
                        $result = $conn->query($sql);

                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                // Format date and time
                                $tanggal = date('d M Y', strtotime($row['tanggal_berangkat']));
                                $waktu = date('H:i', strtotime($row['waktu_berangkat']));
                                
                                // Get total seats from actual seat count, fallback to design count if not available
                                $total_seats = isset($row['total_kursi_aktual']) && $row['total_kursi_aktual'] > 0 
                                    ? (int)$row['total_kursi_aktual'] 
                                    : (isset($row['total_kursi_desain']) ? (int)$row['total_kursi_desain'] : 2); // Default 2 kursi jika tidak ada data
                                
                                // Get booked seats (count all seat reservations for this schedule with status 'dibayar' atau 'pending')
                                $booked_seats_sql = "SELECT COUNT(*) as booked 
                                                  FROM detail_reservasi_kursi drk 
                                                  JOIN reservasi r ON drk.id_reservasi = r.id_reservasi
                                                  WHERE r.id_jadwal = ? AND (r.status = 'dibayar' OR r.status = 'pending')";
                                $stmt = $conn->prepare($booked_seats_sql);
                                $stmt->bind_param("i", $row['id_jadwal']);
                                $stmt->execute();
                                $booked_seats = (int)$stmt->get_result()->fetch_assoc()['booked'];
                                $stmt->close();
                                
                                // Calculate available seats
                                $available_seats = max(0, $total_seats - $booked_seats);
                                $is_available = $available_seats > 0;
                                
                                // Get price from jadwal or use default 75000
                                $harga_per_kursi = isset($row['harga']) && $row['harga'] > 0 ? (int)$row['harga'] : 75000;
                                
                                echo "<tr class='hover:bg-gray-50 transition-colors duration-150'>";
                                echo "<td class='px-6 py-4 whitespace-nowrap'>";
                                echo "<div class='flex items-center'>";
                                echo "<div class='flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center'>
                                        <svg class='h-6 w-6 text-blue-600' fill='none' viewBox='0 0 24 24' stroke='currentColor'>
                                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z' />
                                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 11a3 3 0 11-6 0 3 3 0 016 0z' />
                                        </svg>
                                    </div>";
                                echo "<div class='ml-4'>";
                                echo "<div class='text-sm font-medium text-gray-900'>{$row['kota_asal']} &rarr; {$row['kota_tujuan']}</div>";
                                echo "<div class='text-sm text-gray-500'>{$row['kec_asal']} ke {$row['kec_tujuan']}</div>";
                                echo "</div>";
                                echo "</div>";
                                echo "</td>";
                                
                                echo "<td class='px-6 py-4 whitespace-nowrap'>";
                                echo "<div class='text-sm text-gray-900 font-medium'>{$tanggal}</div>";
                                echo "<div class='text-sm text-gray-500 flex items-center'>";
                                echo "<svg class='h-4 w-4 mr-1 text-blue-500' fill='none' viewBox='0 0 24 24' stroke='currentColor'>
                                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' />
                                    </svg>
                                    {$waktu} WIB";
                                echo "</div>";
                                echo "</td>";
                                
                                echo "<td class='px-6 py-4 whitespace-nowrap text-center'>";
                                $layout_name = !empty($row['nama_layout']) ? htmlspecialchars($row['nama_layout']) : 'Standard';
                                $layout_info = '';
                                
                                // Tampilkan peringatan jika ada perbedaan antara jumlah kursi aktual dan desain
                                if (isset($row['total_kursi_aktual']) && isset($row['total_kursi_desain']) && 
                                    $row['total_kursi_aktual'] != $row['total_kursi_desain']) {
                                    $layout_info = " <span class='text-yellow-600' title='Perbedaan jumlah kursi aktual dan desain'>âš </span>";
                                }
                                
                                if ($is_available) {
                                    echo "<div class='flex flex-col items-center'>";
                                    echo "<span class='px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 mb-1'>";
                                    echo "{$available_seats}/{$total_seats} kursi";
                                    echo "</span>";
                                    if (!empty($layout_name)) {
                                        echo "<span class='text-xs text-gray-500 mt-1'>{$layout_name}{$layout_info}</span>";
                                    }
                                    echo "</div>";
                                } else {
                                    echo "<div class='flex flex-col items-center'>";
                                    echo "<span class='px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 mb-1'>";
                                    echo "Habis";
                                    echo "</span>";
                                    if (!empty($layout_name)) {
                                        echo "<span class='text-xs text-gray-500 mt-1'>{$layout_name}{$layout_info}</span>";
                                    }
                                    echo "</div>";
                                }
                                echo "</td>";
                                
                                echo "<td class='px-6 py-4 whitespace-nowrap text-right'>";
                                echo "<div class='text-sm font-bold text-blue-600'>Rp " . number_format($harga_per_kursi, 0, ',', '.') . "</div>";
                                echo "<div class='text-xs text-gray-500'>per kursi</div>";
                                echo "</td>";
                                
                                echo "<td class='px-6 py-4 whitespace-nowrap text-right text-sm font-medium'>";
                                if ($is_available) {
                                    echo "<a href='search_results.php?kota_asal={$row['id_kota_asal']}&kecamatan_asal={$row['id_kecamatan_asal']}&kota_tujuan={$row['id_kota_tujuan']}&kecamatan_tujuan={$row['id_kecamatan_tujuan']}&tanggal_berangkat={$row['tanggal_berangkat']}&alamat_jemput=' class='inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-[#00BFFF] hover:bg-[#0099cc] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#00BFFF] transition-colors duration-200'>";
                                    echo "Pesan Sekarang";
                                    echo "<svg class='ml-2 -mr-1 h-4 w-4' fill='currentColor' viewBox='0 0 20 20'>
                                            <path fill-rule='evenodd' d='M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z' clip-rule='evenodd' />
                                        </svg>";
                                    echo "</a>";
                                } else {
                                    echo "<button disabled class='inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-100 cursor-not-allowed'>";
                                    echo "Tidak Tersedia";
                                    echo "</button>";
                                }
                                echo "</td>";
                                
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='px-6 py-12 text-center text-gray-500'>";
                            echo "<svg class='mx-auto h-12 w-12 text-gray-400' fill='none' viewBox='0 0 24 24' stroke='currentColor'>
                                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' />
                                </svg>";
                            echo "<h3 class='mt-2 text-sm font-medium text-gray-900'>Tidak ada jadwal tersedia</h3>";
                            echo "<p class='mt-1 text-sm text-gray-500'>Silakan coba tanggal atau rute lain.</p>";
                            echo "<div class='mt-6'>";
                            echo "<a href='index.php' class='inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-[#00BFFF] hover:bg-[#0099cc] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#00BFFF]'>";
                            echo "<svg class='-ml-1 mr-2 h-5 w-5' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='currentColor'>
                                    <path fill-rule='evenodd' d='M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z' clip-rule='evenodd' />
                                </svg>";
                            echo "Kembali ke Beranda";
                            echo "</a>";
                            echo "</div>";
                            echo "</td></tr>";
                        }
                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Menampilkan
                            <span class="font-medium">1</span>
                            sampai
                            <span class="font-medium">10</span>
                            dari
                            <span class="font-medium">20</span>
                            hasil
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-1 focus:ring-[#00BFFF] focus:border-[#00BFFF]">
                                <span class="sr-only">Sebelumnya</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </a>
                            <a href="#" aria-current="page" class="z-10 bg-[#00BFFF] border-[#00BFFF] text-white relative inline-flex items-center px-4 py-2 border text-sm font-medium"> 1 </a>
                            <a href="#" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium"> 2 </a>
                            <a href="#" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium"> 3 </a>
                            <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-1 focus:ring-[#00BFFF] focus:border-[#00BFFF]">
                                <span class="sr-only">Berikutnya</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </a>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Info Section -->
        <div class="mt-12 bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Butuh bantuan?
                        </h3>
                        <div class="mt-2 text-sm text-gray-500">
                            <p>Hubungi layanan pelanggan kami di <a href="tel:+628123456789" class="font-medium text-blue-600 hover:text-blue-500">+62 857-9834-7675</a> atau <a href="mailto:info@travelkita.com" class="font-medium text-blue-600 hover:text-blue-500">info@travelkita.com</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add search and sort functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show loading indicator
    const loadingIndicator = document.getElementById('loadingIndicator');
    const mobileView = document.getElementById('mobileScheduleView');
    const desktopView = document.getElementById('desktopScheduleView');
    
    // Function to check screen size and toggle view
    function updateView() {
        if (window.innerWidth < 768) { // Mobile view
            if (desktopView) desktopView.classList.add('hidden');
            if (mobileView) mobileView.classList.remove('hidden');
            convertTableToCards();
        } else { // Desktop view
            if (desktopView) desktopView.classList.remove('hidden');
            if (mobileView) mobileView.classList.add('hidden');
        }
    }
    
    // Initial check
    updateView();
    
    // Update on window resize
    window.addEventListener('resize', updateView);
    
    // Function to convert table rows to mobile cards
    function convertTableToCards() {
        if (!mobileView) return;
        
        const rows = document.querySelectorAll('#scheduleTable tr');
        let cardsHtml = '';
        
        rows.forEach(row => {
            if (row.cells.length < 4) return;
            
            const route = row.cells[0]?.querySelector('.text-gray-900')?.textContent || '';
            const detail = row.cells[0]?.querySelector('.text-gray-500')?.textContent || '';
            const date = row.cells[1]?.querySelector('.text-gray-900')?.textContent || '';
            const time = row.cells[1]?.querySelector('.text-blue-500')?.parentElement?.textContent.trim() || '';
            const seats = row.cells[2]?.textContent.trim() || '';
            const price = row.cells[3]?.textContent.trim() || '';
            const button = row.cells[4]?.querySelector('a')?.outerHTML || '';
            
            cardsHtml += `
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="font-medium text-gray-900">${route}</div>
                            <div class="text-sm text-gray-500">${detail}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-medium">${price}</div>
                            <div class="text-sm text-gray-500">${seats}</div>
                        </div>
                    </div>
                    <div class="mt-2 text-sm text-gray-700">
                        <div>${date}</div>
                        <div class="flex items-center">
                            <svg class="h-4 w-4 mr-1 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            ${time}
                        </div>
                    </div>
                    <div class="mt-3 text-center">
                        ${button}
                    </div>
                </div>
            `;
        });
        
        mobileView.innerHTML = cardsHtml || '<p class="text-center text-gray-500 py-4">Tidak ada jadwal tersedia</p>';
    }
    
    // Show loading state during search/sort
    const searchInput = document.getElementById('searchInput');
    const sortLinks = document.querySelectorAll('#sortDropdown a');
    
    function showLoading() {
        if (loadingIndicator) loadingIndicator.classList.remove('hidden');
    }
    
    function hideLoading() {
        if (loadingIndicator) loadingIndicator.classList.add('hidden');
    }
    
    // Add loading state to search
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            showLoading();
            // Simulate loading delay
            setTimeout(hideLoading, 300);
        });
    }
    
    // Add loading state to sort
    sortLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            showLoading();
            
            // Get sort type
            const sortType = this.getAttribute('data-sort');
            
            // Simulate loading delay
            setTimeout(() => {
                sortSchedules(sortType);
                hideLoading();
                
                // Update active sort
                sortLinks.forEach(l => l.classList.remove('bg-blue-50', 'text-blue-700'));
                this.classList.add('bg-blue-50', 'text-blue-700');
                
                // Update URL for sharing
                const url = new URL(window.location);
                url.searchParams.set('sort', sortType);
                window.history.pushState({}, '', url);
            }, 300);
        });
    });
    
    // Function to sort schedules
    function sortSchedules(sortType) {
        const scheduleTable = document.getElementById('scheduleTable');
        const rows = Array.from(scheduleTable.querySelectorAll('tr')).filter(row => row.cells.length > 1);
        
        rows.sort((a, b) => {
            if (sortType === 'harga-asc') {
                return getPrice(a) - getPrice(b);
            } else if (sortType === 'harga-desc') {
                return getPrice(b) - getPrice(a);
            } else if (sortType === 'waktu-asc') {
                return getTimeValue(a) - getTimeValue(b);
            } else if (sortType === 'waktu-desc') {
                return getTimeValue(b) - getTimeValue(a);
            }
            return 0;
        });
        
        // Reorder rows
        rows.forEach(row => scheduleTable.appendChild(row));
        
        // Update mobile view if needed
        if (window.innerWidth < 768) {
            convertTableToCards();
        }
    }
    
    // Helper function to get price from a row
    function getPrice(row) {
        const priceText = row.cells[3]?.textContent || '0';
        return parseInt(priceText.replace(/\D/g, ''), 10) || 0;
    }
    
    // Helper function to get time value from a row
    function getTimeValue(row) {
        const timeText = row.cells[1]?.querySelector('.text-blue-500')?.parentElement?.textContent.trim() || '';
        const timeMatch = timeText.match(/(\d+):(\d+)/);
        if (timeMatch) {
            return parseInt(timeMatch[1], 10) * 60 + parseInt(timeMatch[2], 10);
        }
        return 0;
    }
    
    // Handle search functionality
    function handleSearch() {
        const searchTerm = searchInput.value.toLowerCase();
        const rows = document.querySelectorAll('#scheduleTable tr');
        
        rows.forEach(row => {
            if (row.cells.length < 4) return;
            
            const route = row.cells[0]?.textContent.toLowerCase() || '';
            const time = row.cells[1]?.textContent.toLowerCase() || '';
            const seats = row.cells[2]?.textContent.toLowerCase() || '';
            const price = row.cells[3]?.textContent.toLowerCase() || '';
            
            const isVisible = route.includes(searchTerm) || 
                             time.includes(searchTerm) || 
                             seats.includes(searchTerm) ||
                             price.includes(searchTerm);
            
            row.style.display = isVisible ? '' : 'none';
        });
        
        // Update mobile view if needed
        if (window.innerWidth < 768) {
            convertTableToCards();
        }
    }
    
    // Initialize elements
    const sortButton = document.getElementById('sortButton');
    const sortDropdown = document.getElementById('sortDropdown');
    const tableBody = document.getElementById('scheduleTable');
    let tableRows = Array.from(document.querySelectorAll('#scheduleTable tr'));
    
    // Toggle sort dropdown
    sortButton.addEventListener('click', function(e) {
        e.stopPropagation();
        sortDropdown.classList.toggle('hidden');
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function() {
        sortDropdown.classList.add('hidden');
    });
    
    // Handle sort selection
    sortDropdown.querySelectorAll('a').forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const sortType = this.getAttribute('data-sort');
            sortTable(sortType);
            sortDropdown.classList.add('hidden');
            // Update button text
            sortButton.querySelector('span:not(.sr-only)').textContent = this.textContent.trim();
        });
    });
    
    // Search functionality
    searchInput.addEventListener('input', function() {
        filterTable();
    });
    
    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        
        tableRows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            row.style.display = rowText.includes(searchTerm) ? '' : 'none';
        });
    }
    
    function sortTable(sortType) {
        const [field, order] = sortType.split('-');
        
        tableRows.sort((a, b) => {
            let aValue, bValue;
            
            if (field === 'harga') {
                aValue = parseInt(a.cells[3].textContent.replace(/[^0-9]/g, ''));
                bValue = parseInt(b.cells[3].textContent.replace(/[^0-9]/g, ''));
            } else if (field === 'waktu') {
                const aTime = a.cells[1].querySelector('div:first-child').textContent.trim();
                const bTime = b.cells[1].querySelector('div:first-child').textContent.trim();
                aValue = new Date(aTime);
                bValue = new Date(bTime);
            }
            
            if (order === 'asc') {
                return aValue > bValue ? 1 : -1;
            } else {
                return aValue < bValue ? 1 : -1;
            }
        });
        
        // Re-append rows in new order
        tableRows.forEach(row => tableBody.appendChild(row));
    }
    
    // Initial sort by time (earliest first)
    sortTable('waktu-asc');
});
</script>

<?php require_once __DIR__ . '/templates/partials/footer.php'; ?>

