<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session and include database connection
session_start();

// Include database connection
require_once __DIR__ . '/./includes/database.php';

// Check if booking code is provided
$kode_booking = $_GET['code'] ?? '';
if (empty($kode_booking)) {
    die('Kode booking tidak valid');
}

// Query to get booking details
$sql = "SELECT 
            r.*, 
            GROUP_CONCAT(DISTINCT k.nomor_kursi ORDER BY k.nomor_kursi SEPARATOR ', ') as nomor_kursi,
            COUNT(DISTINCT drk.id_kursi) as jumlah_kursi,
            j.tanggal_berangkat,
            j.waktu_berangkat,
            kt_asal.nama_kota as kota_asal,
            kt_tujuan.nama_kota as kota_tujuan,
            kc_asal.nama_kecamatan as kecamatan_asal,
            kc_tujuan.nama_kecamatan as kecamatan_tujuan,
            'TravelKita' as nama_bus,
            '' as plat_bus,
            30 as kapasitas_bus
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
$stmt->bind_param('s', $kode_booking);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Data tiket tidak ditemukan');
}

$tiket = $result->fetch_assoc();

// Format dates and times
$tanggal_berangkat = date('d F Y', strtotime($tiket['tanggal_berangkat']));
$waktu_berangkat = date('H:i', strtotime($tiket['waktu_berangkat']));
$tanggal_cetak = date('d F Y H:i:s');

// Generate barcode (simple version using text)
$barcode = strtoupper(substr($kode_booking, 0, 4) . '-' . substr($kode_booking, -4));

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Tiket - <?= htmlspecialchars($kode_booking) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @page {
            size: A5;
            margin: 0;
        }
        @media print {
            body {
                width: 148mm;
                height: 210mm;
                margin: 0;
                padding: 5mm;
            }
            .no-print {
                display: none !important;
            }
        }
        .ticket {
            border: 2px dashed #4a5568;
            border-radius: 10px;
            position: relative;
            overflow: hidden;
        }
        .ticket:before {
            content: '';
            position: absolute;
            top: 0;
            left: 15%;
            right: 15%;
            height: 20px;
            border-bottom: 2px dashed #4a5568;
            background: #fff;
            z-index: 1;
        }
        .barcode {
            font-family: 'Libre Barcode 128', cursive;
            font-size: 2.5rem;
            letter-spacing: 5px;
            transform: rotate(-90deg);
            transform-origin: left top;
            white-space: nowrap;
            position: absolute;
            left: 20px;
            top: 100%;
            margin-top: -20px;
            color: #4a5568;
        }
    </style>
</head>
<body class="bg-gray-100 p-4 md:p-8">
    <div class="max-w-md mx-auto">
        <!-- Ticket -->
        <div class="ticket bg-white p-6 relative">
            <!-- Header -->
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">TIKET PERJALANAN</h1>
                <p class="text-sm text-gray-600">TravelKita - Selamat Jalan!</p>
            </div>

            <!-- Barcode -->
            <div class="absolute right-4 top-4 text-right">
                <div class="text-xs text-gray-500 mb-1">Kode Booking</div>
                <div class="font-mono font-bold text-lg"><?= htmlspecialchars($kode_booking) ?></div>
                <div class="text-xs mt-2 text-gray-500"><?= $tanggal_cetak ?></div>
            </div>

            <!-- Passenger Info -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-800 border-b pb-1 mb-2">Informasi Penumpang</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Nama</p>
                        <p class="font-medium"><?= htmlspecialchars($tiket['nama_pemesan']) ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">No. Telepon</p>
                        <p class="font-mono"><?= htmlspecialchars($tiket['no_hp']) ?></p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-sm text-gray-600">Email</p>
                        <p class="text-sm"><?= htmlspecialchars($tiket['email']) ?></p>
                    </div>
                </div>
            </div>

            <!-- Trip Info -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-800 border-b pb-1 mb-2">Rincian Perjalanan</h2>
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div class="col-span-2 text-center">
                        <div class="text-2xl font-bold"><?= htmlspecialchars($tiket['kota_asal']) ?></div>
                        <div class="text-sm text-gray-600"><?= htmlspecialchars($tiket['kecamatan_asal']) ?></div>
                    </div>
                    <div class="flex items-center justify-center">
                        <div class="text-center">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-1">
                                <i class="fas fa-arrow-right text-blue-600"></i>
                            </div>
                            <div class="text-xs text-gray-600"><?= $tiket['jumlah_jam'] ?? '4' ?> Jam</div>
                        </div>
                    </div>
                    <div class="col-span-2 text-center">
                        <div class="text-2xl font-bold"><?= htmlspecialchars($tiket['kota_tujuan']) ?></div>
                        <div class="text-sm text-gray-600"><?= htmlspecialchars($tiket['kecamatan_tujuan']) ?></div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mt-4">
                    <div>
                        <p class="text-sm text-gray-600">Tanggal Berangkat</p>
                        <p class="font-medium"><?= $tanggal_berangkat ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Jam Berangkat</p>
                        <p class="font-medium"><?= $waktu_berangkat ?> WIB</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Nomor Kursi</p>
                        <p class="font-mono font-bold"><?= htmlspecialchars($tiket['nomor_kursi'] ?? '-') ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Tipe Bus</p>
                        <p class="font-medium"><?= htmlspecialchars($tiket['nama_bus'] ?? 'Eksekutif') ?></p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-sm text-gray-600">Titik Penjemputan</p>
                        <p class="text-sm"><?= nl2br(htmlspecialchars($tiket['alamat_jemput'] ?? '-')) ?></p>
                    </div>
                </div>
            </div>

            <!-- Barcode -->
            <div class="mt-6 pt-4 border-t border-dashed border-gray-300 text-center">
                <div class="inline-block bg-gray-100 px-4 py-2 rounded">
                    <div class="font-mono text-2xl"><?= $barcode ?></div>
                    <div class="text-xs text-gray-500 mt-1">Scan barcode saat naik bus</div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-6 text-center text-xs text-gray-500">
                <p>Harap tiba di lokasi penjemputan minimal 30 menit sebelum keberangkatan</p>
                <p class="mt-1">Tiket ini berlaku sebagai bukti pembayaran yang sah</p>
                <div class="mt-4 pt-2 border-t border-gray-200">
                    <p>Terima kasih telah memilih TravelKita</p>
                    <p class="mt-1">Customer Service: 0812-3456-7890</p>
                </div>
            </div>

            <div class="barcode"><?= $kode_booking ?></div>
        </div>

        <!-- Print Button -->
        <div class="mt-6 text-center no-print">
            <button onclick="window.print()" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <i class="fas fa-print mr-2"></i> Cetak Tiket
            </button>
            <a href="booking_success.php?code=<?= urlencode($kode_booking) ?>" class="ml-3 px-6 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">
                Kembali
            </a>
        </div>
    </div>

    <script>
        // Auto print when page loads (optional)
        window.onload = function() {
            // Uncomment the line below to enable auto-print
            // window.print();
        };
    </script>
</body>
</html>

