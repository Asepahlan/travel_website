<?php
$page_title = 'Kelola Kursi';
require_once __DIR__ . '/../templates/partials/admin_header.php';
require_once __DIR__ . '/../config/database.php';

$layout_id = isset($_GET['layout_id']) ? (int)$_GET['layout_id'] : null;
if (!$layout_id) {
    $_SESSION['error_message'] = 'ID Layout tidak valid.';
    header('Location: manage_layout.php');
    exit;
}

// Ambil Detail Layout
$sql_layout = "SELECT id_layout, nama_layout, gambar_layout FROM layout_kursi WHERE id_layout = ? LIMIT 1";
$stmt_layout = $conn->prepare($sql_layout);
$stmt_layout->bind_param("i", $layout_id);
$stmt_layout->execute();
$result_layout = $stmt_layout->get_result();
$layout_details = $result_layout->fetch_assoc();
$stmt_layout->close();

if (!$layout_details) {
    $_SESSION['error_message'] = 'Layout tidak ditemukan.';
    header('Location: manage_layout.php');
    exit;
}

// Ambil Data Kursi lengkap dengan posisi_x & posisi_y
$kursi_list = [];
$stmt_kursi = $conn->prepare("SELECT id_kursi, nomor_kursi, posisi_x, posisi_y FROM kursi WHERE id_layout = ? ORDER BY nomor_kursi ASC");
$stmt_kursi->bind_param("i", $layout_id);
$stmt_kursi->execute();
$result_kursi = $stmt_kursi->get_result();
while ($row = $result_kursi->fetch_assoc()) {
    $kursi_list[] = $row;
}
$stmt_kursi->close();
$conn->close();

$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-semibold text-gray-700">Kelola Kursi: <?= htmlspecialchars($layout_details['nama_layout']); ?></h2>
    <a href="manage_layout.php" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md transition duration-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
        </svg>
        Kembali ke Daftar Layout
    </a>
</div>

<?php if ($success_message): ?>
<div class="bg-green-100 border text-green-700 px-4 py-3 rounded mb-4"><?= htmlspecialchars($success_message); ?></div>
<?php endif; ?>
<?php if ($error_message): ?>
<div class="bg-red-100 border text-red-700 px-4 py-3 rounded mb-4"><?= htmlspecialchars($error_message); ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Visual Layout -->
    <div class="lg:col-span-2 bg-white p-4 rounded shadow relative">
        <h3 class="text-lg font-semibold text-gray-700 mb-3">Visualisasi Layout Kursi</h3>
        <?php if (!empty($layout_details['gambar_layout'])): 
            $image_name = htmlspecialchars($layout_details['gambar_layout']);
            $relative_path = '/travel_website/public/uploads/layouts/' . $image_name;
            $full_path = $_SERVER['DOCUMENT_ROOT'] . $relative_path;
            
            // Debug: Tampilkan path yang digunakan
            error_log("Mencoba memuat gambar dari: " . $full_path);
            
            if (file_exists($full_path)): ?>
            <div id="layout-container" class="relative border rounded overflow-hidden" style="max-width:100%;">
                <img id="layout-image" 
                     src="<?= $relative_path; ?>" 
                     class="block w-full h-auto" 
                     alt="Layout" 
                     onerror="console.error('Gagal memuat gambar:', this.src); this.onerror=null; this.src='/travel_website/public/img/no-image.jpg';">
                <?php foreach ($kursi_list as $kursi): ?>
                    <div class="absolute w-6 h-6 bg-blue-500 border-2 border-white rounded-full flex items-center justify-center text-white text-xs font-bold cursor-pointer hover:bg-blue-700 transform -translate-x-1/2 -translate-y-1/2"
                         style="left: <?= $kursi['posisi_x'] ?? 10; ?>%; top: <?= $kursi['posisi_y'] ?? 10; ?>%;"
                         title="<?= htmlspecialchars($kursi['nomor_kursi']); ?>"
                         onclick='selectKursi(<?= json_encode($kursi); ?>)'>
                        <?= htmlspecialchars($kursi['nomor_kursi']); ?>
                    </div>
                <?php endforeach; ?>
                <div id="click-marker" class="absolute w-3 h-3 bg-red-500 rounded-full hidden transform -translate-x-1/2 -translate-y-1/2"></div>
            </div>
            <p class="text-xs text-gray-500 mt-2">Klik pada gambar untuk memilih posisi kursi baru.</p>
        <?php else: ?>
            <p>Gambar layout tidak ditemukan.</p>
        <?php endif; endif; ?>
    </div>

    <!-- Form & Daftar Kursi -->
    <div class="space-y-6">
        <!-- Form Kursi -->
        <div class="bg-white p-6 rounded shadow">
        <h3 id="form-kursi-title" class="text-lg font-semibold mb-4">Tambah Kursi Baru</h3>
        <form id="form-kursi" action="./process/kursi.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token; ?>">
            <input type="hidden" name="id_layout" value="<?= $layout_id; ?>">
            <input type="hidden" id="kursi_id" name="id_kursi">
            <input type="hidden" id="kursi_action" name="action" value="add">

            <div class="mb-4">
                <label for="nomor_kursi" class="block text-sm mb-1">Nomor Kursi</label>
                <input type="text" id="nomor_kursi" name="nomor_kursi" required class="w-full p-2 border rounded" placeholder="Contoh: A1, B2">
            </div>

            <div class="mb-4">
                <label class="block text-sm mb-1">Posisi X / Y (%)</label>
                <div class="flex space-x-2">
                    <input type="text" id="posisi_x" name="posisi_x" readonly class="w-1/2 p-2 border rounded bg-gray-100" placeholder="X">
                    <input type="text" id="posisi_y" name="posisi_y" readonly class="w-1/2 p-2 border rounded bg-gray-100" placeholder="Y">
                </div>
                <p class="text-xs text-gray-500 mt-1">Posisi ditentukan dengan klik pada gambar layout.</p>
            </div>

            <div class="flex flex-col space-y-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white py-2 rounded">Simpan Kursi</button>
                <button type="button" id="delete-button" onclick="deleteKursi()" class="hidden bg-red-600 hover:bg-red-700 text-white py-2 rounded">Hapus Kursi Ini</button>
                <button type="button" onclick="resetForm()" class="bg-gray-300 hover:bg-gray-400 py-2 rounded">Reset / Tambah Baru</button>
            </div>
        </form>
        </div>
        
        <!-- Daftar Kursi -->
        <div class="bg-white p-6 rounded shadow">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Daftar Kursi</h3>
            <?php if (!empty($kursi_list)): ?>
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2">
                    <?php foreach ($kursi_list as $kursi): ?>
                        <button type="button" 
                                onclick='selectKursi(<?= json_encode($kursi); ?>)'
                                class="p-2 border rounded text-center hover:bg-blue-50 hover:border-blue-300 transition-colors"
                                title="Klik untuk edit">
                            <?= htmlspecialchars($kursi['nomor_kursi']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-gray-500 mt-2">Total: <?= count($kursi_list) ?> kursi</p>
            <?php else: ?>
                <p class="text-gray-500 text-center py-4">Belum ada kursi yang ditambahkan</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const layoutContainer = document.getElementById('layout-container');
const layoutImage = document.getElementById('layout-image');
const clickMarker = document.getElementById('click-marker');
const posXInput = document.getElementById('posisi_x');
const posYInput = document.getElementById('posisi_y');
const kursiIdInput = document.getElementById('kursi_id');
const nomorKursiInput = document.getElementById('nomor_kursi');
const actionInput = document.getElementById('kursi_action');
const formTitle = document.getElementById('form-kursi-title');
const deleteButton = document.getElementById('delete-button');

if (layoutContainer && layoutImage) {
    layoutContainer.addEventListener('click', function(event) {
        const rect = layoutImage.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;
        const posXPercent = ((x / rect.width) * 100).toFixed(2);
        const posYPercent = ((y / rect.height) * 100).toFixed(2);

        posXInput.value = posXPercent;
        posYInput.value = posYPercent;

        clickMarker.style.left = `${posXPercent}%`;
        clickMarker.style.top = `${posYPercent}%`;
        clickMarker.classList.remove('hidden');
    });
}

function selectKursi(kursi) {
    formTitle.textContent = 'Edit Kursi';
    actionInput.value = 'edit';
    kursiIdInput.value = kursi.id_kursi;
    nomorKursiInput.value = kursi.nomor_kursi;
    posXInput.value = parseFloat(kursi.posisi_x).toFixed(2);
    posYInput.value = parseFloat(kursi.posisi_y).toFixed(2);

    clickMarker.style.left = `${kursi.posisi_x}%`;
    clickMarker.style.top = `${kursi.posisi_y}%`;
    clickMarker.classList.remove('hidden');
    deleteButton.classList.remove('hidden');
}

function resetForm() {
    // Reset form fields
    document.getElementById('form-kursi').reset();
    
    // Reset title and action
    formTitle.textContent = 'Tambah Kursi Baru';
    actionInput.value = 'add';
    
    // Clear ID and position
    kursiIdInput.value = '';
    posXInput.value = '';
    posYInput.value = '';
    
    // Hide marker and delete button
    if (clickMarker) {
        clickMarker.classList.add('hidden');
    }
    deleteButton.classList.add('hidden');
    
    // Clear any selected kursi
    const selectedKursi = document.querySelector('.bg-blue-700');
    if (selectedKursi) {
        selectedKursi.classList.remove('bg-blue-700');
        selectedKursi.classList.add('bg-blue-500');
    }
}

// Function to handle delete kursi
function deleteKursi() {
    const id = document.getElementById('kursi_id').value;
    if (!id) {
        Swal.fire('Error', 'Tidak ada kursi yang dipilih', 'error');
        return;
    }
    
    Swal.fire({
        title: 'Hapus Kursi',
        text: 'Apakah Anda yakin ingin menghapus kursi ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `./process/delete.php?type=kursi&id=${id}&layout_id=<?= $layout_id; ?>&csrf_token=<?= $csrf_token; ?>`;
        }
    });
}

// Add event listener for delete button
document.addEventListener('DOMContentLoaded', function() {
    const deleteBtn = document.getElementById('delete-button');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', deleteKursi);
    }
});
</script>

<?php require_once __DIR__ . '/../templates/partials/admin_footer.php'; ?>

