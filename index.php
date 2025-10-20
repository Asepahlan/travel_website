<?php
$page_title = 'Pesan Tiket Travel Online';
require_once __DIR__ . '/templates/partials/header.php';

// Fetch kota list
require_once __DIR__ . '/config/database.php';
$sql_kota = "SELECT id_kota, nama_kota FROM kota ORDER BY nama_kota ASC";
$result_kota = $conn->query($sql_kota);
$kota_list = [];
if ($result_kota && $result_kota->num_rows > 0) {
    while ($row = $result_kota->fetch_assoc()) {
        $kota_list[$row['id_kota']] = $row['nama_kota'];
    }
}

// Fetch kecamatan list
$sql_kecamatan = "SELECT id_kecamatan, nama_kecamatan, id_kota FROM kecamatan ORDER BY nama_kecamatan ASC";
$result_kecamatan = $conn->query($sql_kecamatan);
$kecamatan_list_grouped = [];
if ($result_kecamatan && $result_kecamatan->num_rows > 0) {
    while ($row = $result_kecamatan->fetch_assoc()) {
        $kecamatan_list_grouped[$row['id_kota']][] = $row;
    }
}
$conn->close();
?>

    <!-- Hero Section -->

    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-[#00BFFF] to-[#0099cc] text-white py-16 md:py-24">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-3xl md:text-5xl font-bold mb-4">Perjalanan Nyaman, Harga Terjangkau</h1>
            <p class="text-lg md:text-xl mb-8">Pesan tiket travel antar kota dengan mudah dan cepat.</p>
            <!-- Search Form moved below -->
        </div>
    </section>

    <!-- Search Form Section -->
    <section class="py-12 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="max-w-6xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="p-8">
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2 text-center">Cari Jadwal Perjalanan</h2>
                    <p class="text-gray-600 text-center mb-8">Temukan perjalanan yang sesuai dengan kebutuhan Anda</p>
                    
                    <?php if (isset($_GET['error'])): ?>
                        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium">
                                        <?php 
                                            if (isset($_GET['message'])) {
                                                echo htmlspecialchars(urldecode($_GET['message']));
                                            } else {
                                                echo 'Terjadi kesalahan. Silakan periksa kembali data yang Anda masukkan.';
                                            }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form id="searchForm" action="search_results.php" method="GET" class="space-y-6">
                        <!-- Origin Section -->
                        <div class="bg-blue-50 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold text-[#0077aa] mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                </svg>
                                Lokasi Keberangkatan
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Kota Asal -->
                                <div>
                                    <label for="kota_asal" class="block text-sm font-medium text-gray-700 mb-1">Kota Asal <span class="text-red-500">*</span></label>
                                    <select id="kota_asal" name="kota_asal" required 
                                            class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition duration-150 ease-in-out">
                                        <option value="" disabled selected>Pilih Kota Asal</option>
                                        <?php foreach ($kota_list as $id => $nama): ?>
                                            <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($nama); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div id="kota_asal_error" class="mt-1 text-sm text-red-600"></div>
                                </div>
                                
                                <!-- Kecamatan Asal -->
                                <div>
                                    <label for="kecamatan_asal" class="block text-sm font-medium text-gray-700 mb-1">Kecamatan Asal <span class="text-red-500">*</span></label>
                                    <select id="kecamatan_asal" name="kecamatan_asal" required 
                                            class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition duration-150 ease-in-out"
                                            disabled>
                                        <option value="" disabled selected>Pilih Kota Terlebih Dahulu</option>
                                    </select>
                                    <div id="kecamatan_asal_error" class="mt-1 text-sm text-red-600"></div>
                                </div>
                                
                                <!-- Alamat Jemput akan diisi di halaman konfirmasi -->
                                <input type="hidden" id="alamat_jemput" name="alamat_jemput" value="">
                            </div>
                        </div>
                        
                        <!-- Destination Section -->
                        <div class="bg-blue-50 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold text-[#0077aa] mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                </svg>
                                Lokasi Tujuan
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Kota Tujuan -->
                                <div>
                                    <label for="kota_tujuan" class="block text-sm font-medium text-gray-700 mb-1">Kota Tujuan <span class="text-red-500">*</span></label>
                                    <select id="kota_tujuan" name="kota_tujuan" required 
                                            class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm transition duration-150 ease-in-out">
                                        <option value="" disabled selected>Pilih Kota Tujuan</option>
                                        <?php foreach ($kota_list as $id => $nama): ?>
                                            <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($nama); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div id="kota_tujuan_error" class="mt-1 text-sm text-red-600"></div>
                                </div>
                                
                                <!-- Kecamatan Tujuan -->
                                <div>
                                    <label for="kecamatan_tujuan" class="block text-sm font-medium text-gray-700 mb-1">Kecamatan Tujuan <span class="text-red-500">*</span></label>
                                    <select id="kecamatan_tujuan" name="kecamatan_tujuan" required 
                                            class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm transition duration-150 ease-in-out"
                                            disabled>
                                        <option value="" disabled selected>Pilih Kota Terlebih Dahulu</option>
                                    </select>
                                    <div id="kecamatan_tujuan_error" class="mt-1 text-sm text-red-600"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Travel Date Section -->
                        <div class="bg-blue-50 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold text-[#0077aa] mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 11-2 0 1 1 0 012 0zM7 4a1 1 0 011-1h12a1 1 0 110 2H8a1 1 0 01-1-1z" clip-rule="evenodd" />
                                </svg>
                                Waktu Keberangkatan
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Tanggal Keberangkatan -->
                                <div>
                                    <label for="tanggal_berangkat" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Keberangkatan <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <input type="date" id="tanggal_berangkat" name="tanggal_berangkat" required 
                                               class="pl-10 w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm"
                                               min="<?php echo date('Y-m-d'); ?>" 
                                               placeholder="Pilih tanggal">
                                    </div>
                                    <div id="tanggal_berangkat_error" class="mt-1 text-sm text-red-600"></div>
                                </div>
                                
                                <!-- Jumlah penumpang dihitung dari jumlah kursi yang dipilih -->
                                <input type="hidden" id="jumlah_penumpang" name="jumlah_penumpang" value="1">
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="flex justify-center mt-8">
                            <button type="submit" 
                                    class="inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-gradient-to-r from-[#00BFFF] to-[#0099cc] hover:from-[#0099cc] hover:to-[#0088bb] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#00BFFF] transition duration-150 ease-in-out transform hover:scale-105">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                                Cari Jadwal Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Other Sections (Features, Testimonials, etc. - Optional) -->
    <!-- ... -->

    <?php require_once __DIR__ . '/templates/partials/footer.php'; ?>

    <!-- jQuery and Select2 JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- jQuery UI -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Bootstrap JS (for tooltip) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Base URL for API
            const kecamatanData = <?php echo json_encode($kecamatan_list_grouped); ?>;

            // Format location for display in dropdown
            function formatLocation(location) {
                if (!location.id) {
                    return location.text;
                }
                return $('<span>').text(location.text);
            }

            // Format selected location
            function formatLocationSelection(location) {
                if (!location.id) {
                    return location.text;
                }
                return location.text;
            }

            // Function to load kota data
            function loadKota(selectId, selectedId = '') {
                $.ajax({
                    url: `${baseUrl}/api/get_locations.php?type=kota`,
                    dataType: 'json',
                    success: function(data) {
                        if (data.success && data.results && data.results.length > 0) {
                            const $select = $(`#${selectId}`);
                            $select.empty().append('<option value="">-- Pilih Kota --</option>');
                            
                            data.results.forEach(function(kota) {
                                $select.append(new Option(kota.text, kota.id, false, kota.id == selectedId));
                            });
                            
                            // Enable dependent dropdown if this is a kota selection
                            if (selectId.includes('kota')) {
                                const dependentId = selectId.replace('kota', 'kecamatan');
                                $(`#${dependentId}`).prop('disabled', false);
                                
                                // Trigger change to load kecamatan if kota is pre-selected
                                if (selectedId) {
                                    $select.trigger('change');
                                }
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading kota:', error);
                        $(`#${selectId}_error`).text('Gagal memuat data kota');
                    }
                });
            }
            
            // Function to load kecamatan data
            function loadKecamatan(kotaId, kecamatanSelectId, selectedId = '') {
                if (!kotaId) {
                    $(`#${kecamatanSelectId}`).html('<option value="">-- Pilih Kecamatan --</option>').prop('disabled', true);
                    return;
                }
                
                $.ajax({
                    url: `${baseUrl}/api/get_locations.php?type=kecamatan&kota_id=${kotaId}`,
                    dataType: 'json',
                    success: function(data) {
                        const $select = $(`#${kecamatanSelectId}`);
                        $select.empty().append('<option value="">-- Pilih Kecamatan --</option>');
                        
                        if (data.success && data.results && data.results.length > 0) {
                            data.results.forEach(function(kecamatan) {
                                $select.append(new Option(kecamatan.text, kecamatan.id, false, kecamatan.id == selectedId));
                            });
                            $select.prop('disabled', false);
                        } else {
                            $select.append('<option value="">Tidak ada kecamatan tersedia</option>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading kecamatan:', error);
                        $(`#${kecamatanSelectId}_error`).text('Gagal memuat data kecamatan');
                    }
                });
            }
            
            // Real-time form validation
            function validateForm() {
                let isValid = true;
                
                // Reset error messages
                $('[id$="_error"]').text('');
                $('select, input').removeClass('border-red-500').addClass('border-gray-300');
                
                // Validate required fields
                $('select[required], input[required]').each(function() {
                    if (!$(this).val()) {
                        const fieldName = $(this).attr('name');
                        const label = $(`label[for="${fieldName}"]`).text().replace('*', '').trim();
                        $(`#${fieldName}_error`).text(`${label} harus diisi`);
                        $(this).addClass('border-red-500').removeClass('border-gray-300');
                        isValid = false;
                    }
                });
                
                // Validate date is not in the past
                const selectedDate = new Date($('#tanggal_berangkat').val());
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if ($('#tanggal_berangkat').val() && selectedDate < today) {
                    $('#tanggal_berangkat_error').text('Tanggal tidak boleh di masa lalu');
                    $('#tanggal_berangkat').addClass('border-red-500').removeClass('border-gray-300');
                    isValid = false;
                }
                
                // Validate origin and destination are different
                if ($('#kota_asal').val() && $('#kota_tujuan').val() && 
                    $('#kota_asal').val() === $('#kota_tujuan').val() &&
                    $('#kecamatan_asal').val() && $('#kecamatan_tujuan').val() &&
                    $('#kecamatan_asal').val() === $('#kecamatan_tujuan').val()) {
                    
                    $('#kota_tujuan_error').text('Lokasi asal dan tujuan tidak boleh sama');
                    $('#kota_asal, #kota_tujuan, #kecamatan_asal, #kecamatan_tujuan')
                        .addClass('border-red-500').removeClass('border-gray-300');
                    isValid = false;
                }
                
                return isValid;
            }
            
            // Add real-time validation on field changes
            $('select, input').on('change', function() {
                validateForm();
            });
            
            // Form submission
            $('#searchForm').on('submit', function(e) {
                e.preventDefault();
                
                if (validateForm()) {
                    // Show loading state
                    const submitBtn = $(this).find('button[type="submit"]');
                    const originalText = submitBtn.html();
                    submitBtn.prop('disabled', true).html(`
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Memproses...
                    `);
                    
                    // Get form data and build URL
                    const formData = $(this).serialize();
                    const targetUrl = 'search_results.php' + (formData ? '?' + formData : '');
                    
                    // Redirect to search results with form data
                    window.location.href = targetUrl;
                } else {
                    // Scroll to first error
                    $('html, body').animate({
                        scrollTop: $('.border-red-500').first().offset().top - 100
                    }, 500);
                }
            });
            
            // Pre-fill form if URL parameters exist
            function prefillFormFromUrl() {
                const urlParams = new URLSearchParams(window.location.search);
                
                if (urlParams.has('kota_asal') && urlParams.has('kecamatan_asal') && 
                    urlParams.has('kota_tujuan') && urlParams.has('kecamatan_tujuan') && 
                    urlParams.has('tanggal_berangkat')) {
                    
                    const kotaAsalId = urlParams.get('kota_asal');
                    const kotaTujuanId = urlParams.get('kota_tujuan');
                    const kecamatanAsalId = urlParams.get('kecamatan_asal');
                    const kecamatanTujuanId = urlParams.get('kecamatan_tujuan');
                    
                    // Set the date first
                    $('#tanggal_berangkat').val(urlParams.get('tanggal_berangkat'));
                    
                    // Function to set kota and kecamatan
                    // Cache untuk menyimpan hasil request
                    const locationCache = {};
                    
                    // Fungsi debounce untuk membatasi frekuensi request
                    function debounce(func, wait) {
                        let timeout;
                        return function executedFunction(...args) {
                            const later = () => {
                                clearTimeout(timeout);
                                func(...args);
                            };
                            clearTimeout(timeout);
                            timeout = setTimeout(later, wait);
                        };
                    }
                    
                    // Fungsi untuk mengambil data lokasi dengan caching
                    function fetchLocationData(url, callback) {
                        // Cek cache dulu
                        if (locationCache[url]) {
                            callback(locationCache[url]);
                            return;
                        }
                        
                        $.ajax({
                            url: url,
                            dataType: 'json',
                            success: function(data) {
                                // Simpan ke cache
                                locationCache[url] = data;
                                callback(data);
                            },
                            error: function(xhr, status, error) {
                                console.error('Error fetching location data:', error);
                            }
                        });
                    }
                    
                    const setKotaAndKecamatan = (kotaId, kotaSelectId, kecamatanId, kecamatanSelectId, isAsal) => {
                        debounce(() => {
                            if (kotaId) {
                                const kotaUrl = `${baseUrl}/api/get_locations.php?type=kota&term=${encodeURIComponent(kotaId)}`;
                                
                                fetchLocationData(kotaUrl, function(data) {
                                    if (data.success && data.results && data.results.length > 0) {
                                        const kota = data.results[0];
                                        const option = new Option(kota.text, kota.id, true, true);
                                        $(`#${kotaSelectId}`).append(option).trigger('change');
                                        
                                        // Fetch kecamatan jika ada
                                        if (kecamatanId) {
                                            const kecamatanUrl = `${baseUrl}/api/get_locations.php?type=kecamatan&kota_id=${kotaId}&term=${encodeURIComponent(kecamatanId)}`;
                                            fetchLocationData(kecamatanUrl, function(kecData) {
                                                if (kecData.success && kecData.results && kecData.results.length > 0) {
                                                    const kecamatan = kecData.results[0];
                                                    const kecOption = new Option(kecamatan.text, kecamatan.id, true, true);
                                                    $(`#${kecamatanSelectId}`).append(kecOption).trigger('change');
                                                }
                                            });
                                        }
                                    }
                                });
                            }
                        }, 300)(); // Debounce 300ms
                    };
                    
                    // Set origin and destination
                    setKotaAndKecamatan(kotaAsalId, 'kota_asal', kecamatanAsalId, 'kecamatan_asal', true);
                    setKotaAndKecamatan(kotaTujuanId, 'kota_tujuan', kecamatanTujuanId, 'kecamatan_tujuan', false);
                }
            }
            
            // Run prefill after a short delay to ensure Select2 is initialized
            setTimeout(prefillFormFromUrl, 500);
            
            // Function to load kota data
            function loadKota(selectId, selectedId = '') {
                $.ajax({
                    url: `${baseUrl}/api/get_locations.php?type=kota`,
                    dataType: 'json',
                    success: function(data) {
                        if (data.success && data.results && data.results.length > 0) {
                            const $select = $(`#${selectId}`);
                            $select.empty().append('<option value="">-- Pilih Kota --</option>');
                            
                            data.results.forEach(function(kota) {
                                $select.append(new Option(kota.text, kota.id, false, kota.id == selectedId));
                            });
                            
                            // Enable dependent dropdown if this is a kota selection
                            if (selectId.includes('kota')) {
                                const dependentId = selectId.replace('kota', 'kecamatan');
                                $(`#${dependentId}`).prop('disabled', false);
                                
                                // Trigger change to load kecamatan if kota is pre-selected
                                if (selectedId) {
                                    $select.trigger('change');
                                }
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading kota:', error);
                        $(`#${selectId}_error`).text('Gagal memuat data kota');
                    }
                });
            }
            
            // Function to load kecamatan data
            function loadKecamatan(kotaId, kecamatanSelectId, selectedId = '') {
                if (!kotaId) {
                    $(`#${kecamatanSelectId}`).html('<option value="">-- Pilih Kecamatan --</option>').prop('disabled', true);
                    return;
                }
                
                $.ajax({
                    url: `${baseUrl}/api/get_locations.php?type=kecamatan&kota_id=${kotaId}`,
                    dataType: 'json',
                    success: function(data) {
                        const $select = $(`#${kecamatanSelectId}`);
                        $select.empty().append('<option value="">-- Pilih Kecamatan --</option>');
                        
                        if (data.success && data.results && data.results.length > 0) {
                            data.results.forEach(function(kecamatan) {
                                $select.append(new Option(kecamatan.text, kecamatan.id, false, kecamatan.id == selectedId));
                            });
                            $select.prop('disabled', false);
                        } else {
                            $select.append('<option value="">Tidak ada kecamatan tersedia</option>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading kecamatan:', error);
                        $(`#${kecamatanSelectId}_error`).text('Gagal memuat data kecamatan');
                    }
                });
            }
            
            // Load initial data for kota dropdowns (already populated with PHP)
            // loadKota('kota_asal');
            // loadKota('kota_tujuan');
            
            // Handle kota change events for kecamatan
            $('#kota_asal').on('change', function() {
                const kotaId = $(this).val();
                const kecamatanSelect = $('#kecamatan_asal');
                kecamatanSelect.empty().append('<option value="" disabled selected>Pilih Kecamatan Asal</option>');
                kecamatanSelect.prop('disabled', true);
                
                if (kotaId && kecamatanData[kotaId] && kecamatanData[kotaId].length > 0) {
                    kecamatanSelect.prop('disabled', false);
                    kecamatanData[kotaId].forEach(function(kecamatan) {
                        kecamatanSelect.append(new Option(kecamatan.nama_kecamatan, kecamatan.id_kecamatan));
                    });
                } else {
                    kecamatanSelect.append('<option value="">Tidak ada kecamatan tersedia</option>');
                }
                validateForm();
            });
            
            $('#kota_tujuan').on('change', function() {
                const kotaId = $(this).val();
                const kecamatanSelect = $('#kecamatan_tujuan');
                kecamatanSelect.empty().append('<option value="" disabled selected>Pilih Kecamatan Tujuan</option>');
                kecamatanSelect.prop('disabled', true);
                
                if (kotaId && kecamatanData[kotaId] && kecamatanData[kotaId].length > 0) {
                    kecamatanSelect.prop('disabled', false);
                    kecamatanData[kotaId].forEach(function(kecamatan) {
                        kecamatanSelect.append(new Option(kecamatan.nama_kecamatan, kecamatan.id_kecamatan));
                    });
                } else {
                    kecamatanSelect.append('<option value="">Tidak ada kecamatan tersedia</option>');
                }
                validateForm();
            });
            
            // Form validation
            $('#searchForm').on('submit', function(e) {
                let isValid = true;
                const $form = $(this);
                
                // Validate required fields
                $form.find('select[required]').each(function() {
                    if (!$(this).val()) {
                        isValid = false;
                        $(this).addClass('border-red-500');
                        $(`#${this.id}_error`).text('Field ini wajib diisi');
                    } else {
                        $(this).removeClass('border-red-500');
                        $(`#${this.id}_error`).text('');
                    }
                });
                
                // Prevent form submission if validation fails
                if (!isValid) {
                    e.preventDefault();
                    // Scroll to first error
                    $('html, body').animate({
                        scrollTop: $('.border-red-500').first().offset().top - 100
                    }, 500);
                }
            });
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    trigger: 'hover',
                    placement: 'top'
                });
            });
            
            // Prefill form from URL parameters if any
            function getUrlParameter(name) {
                name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
                const regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
                const results = regex.exec(location.search);
                return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
            }
            
            // Check for URL parameters and prefill form
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('kota_asal')) {
                const kotaId = getUrlParameter('kota_asal');
                const kecamatanId = getUrlParameter('kecamatan_asal');
                $('#kota_asal').val(kotaId);
                $('#kota_asal').trigger('change');
                
                if (kecamatanId) {
                    setTimeout(() => {
                        const kecamatanSelect = $('#kecamatan_asal');
                        if (kecamatanData[kotaId]) {
                            kecamatanData[kotaId].forEach(function(kecamatan) {
                                kecamatanSelect.append(new Option(kecamatan.nama_kecamatan, kecamatan.id_kecamatan, false, kecamatan.id_kecamatan == kecamatanId));
                            });
                            kecamatanSelect.prop('disabled', false);
                        }
                    }, 500);
                }
            }
            
            if (urlParams.has('kota_tujuan')) {
                const kotaId = getUrlParameter('kota_tujuan');
                const kecamatanId = getUrlParameter('kecamatan_tujuan');
                $('#kota_tujuan').val(kotaId);
                $('#kota_tujuan').trigger('change');
                
                if (kecamatanId) {
                    setTimeout(() => {
                        const kecamatanSelect = $('#kecamatan_tujuan');
                        if (kecamatanData[kotaId]) {
                            kecamatanData[kotaId].forEach(function(kecamatan) {
                                kecamatanSelect.append(new Option(kecamatan.nama_kecamatan, kecamatan.id_kecamatan, false, kecamatan.id_kecamatan == kecamatanId));
                            });
                            kecamatanSelect.prop('disabled', false);
                        }
                    }, 500);
                }
            }
        });
    </script>

<?php require_once __DIR__ . '/templates/partials/footer.php'; ?>