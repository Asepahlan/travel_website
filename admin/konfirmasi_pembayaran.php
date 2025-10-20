<?php
// Set page title
$page_title = 'Konfirmasi Pembayaran';

// Include config dan header
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../templates/partials/admin_header.php';

// Query untuk mendapatkan daftar konfirmasi pembayaran
$query = "SELECT kp.*, r.nama_pemesan, r.no_hp, r.total_harga 
          FROM konfirmasi_pembayaran kp
          JOIN reservasi r ON kp.kode_booking = r.kode_booking
          ORDER BY kp.created_at DESC";
$result = $conn->query($query);

// Inisialisasi array untuk menyimpan data
$konfirmasi = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $konfirmasi[] = $row;
    }
}
?>

<div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Daftar Konfirmasi Pembayaran</h1>
            <p class="mt-1 text-sm text-gray-600">Kelola konfirmasi pembayaran dari pelanggan</p>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700">
                        <?php echo $_SESSION['success_message']; ?>
                    </p>
                </div>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode Booking</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bank</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tgl. Transfer</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Aksi</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($konfirmasi) > 0): ?>
                        <?php foreach ($konfirmasi as $item): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <div class="font-mono font-semibold"><?= htmlspecialchars($item['kode_booking']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($item['nama_pemesan']) ?></div>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($item['no_hp']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($item['bank_tujuan']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    Rp <?= number_format($item['jumlah_dibayar'], 0, ',', '.') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('d M Y H:i', strtotime($item['tanggal_transfer'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $status_class = [
                                        'menunggu' => 'bg-yellow-100 text-yellow-800',
                                        'diverifikasi' => 'bg-green-100 text-green-800',
                                        'ditolak' => 'bg-red-100 text-red-800'
                                    ][$item['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2.5 py-0.5 inline-flex text-xs font-medium rounded-full <?= $status_class ?>">
                                        <?= ucfirst($item['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="detail_konfirmasi.php?id=<?= $item['id'] ?>" class="text-blue-600 hover:text-blue-900">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data</h3>
                                <p class="mt-1 text-sm text-gray-500">Belum ada konfirmasi pembayaran yang masuk.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/partials/admin_footer.php'; ?>

