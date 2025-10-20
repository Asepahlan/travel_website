<?php
$page_title = 'Layout Kursi'; // Set page title for header
require_once __DIR__ . '/../templates/partials/admin_header.php'; // Includes CSRF token generation
require_once __DIR__ . '/../config/database.php';

// --- Fetch Layout Data with Actual Seat Count ---
$sql_layout = "SELECT l.id_layout, l.nama_layout, l.gambar_layout, 
                COUNT(k.id_kursi) as jumlah_kursi_aktif,
                (l.jumlah_baris * l.jumlah_kolom) as jumlah_kursi_desain 
                FROM layout_kursi l 
                LEFT JOIN kursi k ON l.id_layout = k.id_layout
                GROUP BY l.id_layout, l.nama_layout, l.gambar_layout, l.jumlah_baris, l.jumlah_kolom
                ORDER BY l.nama_layout ASC";
$result_layout = $conn->query($sql_layout);
$layout_list = [];
if ($result_layout && $result_layout->num_rows > 0) {
    while ($row = $result_layout->fetch_assoc()) {
        $layout_list[] = $row;
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

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Daftar Layout Kursi</h2>
                <p class="text-sm text-gray-500 mt-1">Kelola konfigurasi layout kursi armada</p>
            </div>
            <button onclick="openModal('modal-layout', null)" 
                    class="inline-flex items-center px-4 py-2 bg-primary hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow-sm transition-colors duration-150 ease-in-out">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Tambah Layout
            </button>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Layout</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gambar</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah Kursi</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (!empty($layout_list)): ?>
                <?php foreach ($layout_list as $layout): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $layout['id_layout']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($layout['nama_layout']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php if (!empty($layout['gambar_layout'])): ?>
                                <?php 
                                    $imageFile = htmlspecialchars($layout['gambar_layout']);
                                    $imagePath = $_SERVER['DOCUMENT_ROOT'] . '/travel_website/public/uploads/layouts/' . $imageFile;
                                    $imageUrl = '/travel_website/public/uploads/layouts/' . $imageFile;
                                    if (file_exists($imagePath)): ?>
                                        <img src="<?php echo $imageUrl; ?>" alt="Layout <?php echo htmlspecialchars($layout['nama_layout']); ?>" class="h-10 w-auto object-contain">
                                    <?php else: ?>
                                        <span class="text-red-500">Gambar tidak ditemukan (<?php echo $imageFile; ?>)</span>
                                    <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-blue-100 text-blue-800 font-medium">
                                    <?php echo (int)$layout['jumlah_kursi_aktif']; ?>
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                            <div class="flex items-center justify-end space-x-2">
                                <a href="manage_kursi.php?layout_id=<?php echo $layout['id_layout']; ?>" 
                                   class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                    </svg>
                                    Kelola
                                </a>
                                <button onclick='openModal("modal-layout", <?php echo json_encode($layout); ?>)' 
                                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit
                                </button>
                                <button type="button" 
                                        onclick="showDeleteConfirmation(<?php echo $layout['id_layout']; ?>, '<?php echo addslashes(htmlspecialchars($layout['nama_layout'])); ?>')"
                                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada data layout kursi.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Layout -->
<!-- Modal Layout -->
<div id="modal-layout" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('modal-layout')"></div>
        
        <!-- Modal panel -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <!-- Modal header -->
            <div class="bg-white px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 id="modal-layout-title" class="text-lg font-medium text-gray-900">Tambah Layout Baru</h3>
                    <button type="button" onclick="closeModal('modal-layout')" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                        <span class="sr-only">Tutup</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Modal body -->
            <form id="form-layout" action="./process_layout.php" method="POST" enctype="multipart/form-data" class="px-6 py-4">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" id="layout_id" name="id_layout">
                <input type="hidden" name="action" id="layout_action" value="add">
                <input type="hidden" id="existing_image" name="existing_image">
                
                <div class="space-y-4">
                    <div>
                        <label for="nama_layout" class="block text-sm font-medium text-gray-700 mb-1">Nama Layout</label>
                        <input type="text" id="nama_layout" name="nama_layout" required 
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"
                               placeholder="Contoh: HiAce 10 Kursi">
                    </div>
                    
                    <div>
                        <label for="gambar_layout" class="block text-sm font-medium text-gray-700 mb-1">Gambar Layout</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600">
                                    <label for="gambar_layout" class="relative cursor-pointer bg-white rounded-md font-medium text-primary hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary">
                                        <span>Unggah file</span>
                                        <input id="gambar_layout" name="gambar_layout" type="file" class="sr-only" accept="image/png, image/jpeg, image/webp, image/gif">
                                    </label>
                                    <p class="pl-1">atau drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">PNG, JPG, WEBP, GIF hingga 5MB</p>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Kosongkan jika tidak ingin mengubah gambar yang sudah ada.</p>
                        <div id="current-image-preview" class="mt-2"></div>
                    </div>
                </div>
                
                <!-- Modal footer -->
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('modal-layout')" 
                            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        Batal
                    </button>
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        <span id="submit-text">Simpan Layout</span>
                        <span id="submit-loading" class="hidden ml-2">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div id="delete-confirm-modal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Konfirmasi Hapus</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">Apakah Anda yakin ingin menghapus layout <span id="layout-name" class="font-medium"></span>?</p>
                            <p class="text-sm text-red-600 mt-2">Perhatian: Menghapus layout akan menghapus semua kursi dan detail reservasi yang terkait.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" id="confirm-delete-btn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Ya, Hapus
                </button>
                <button type="button" onclick="closeModal('delete-confirm-modal')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Batal
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function openModal(modalId, data) {
        const modal = document.getElementById(modalId);
        
        // Only handle form reset and field population for the layout modal
        if (modalId === 'modal-layout') {
            const form = modal.querySelector('form');
            if (form) form.reset();
            
            const imagePreview = document.getElementById('current-image-preview');
            const imageInput = document.getElementById('gambar_layout');
            
            if (imagePreview) imagePreview.innerHTML = '';
            if (imageInput) imageInput.value = '';

            const title = modal.querySelector('#modal-layout-title');
            const actionInput = modal.querySelector('#layout_action');
            const idInput = modal.querySelector('#layout_id');
            const nameInput = modal.querySelector('#nama_layout');
            const existingImageInput = modal.querySelector('#existing_image');

            if (data) { // Edit mode
                title.textContent = 'Edit Layout';
                actionInput.value = 'edit';
                idInput.value = data.id_layout;
                nameInput.value = data.nama_layout;
                existingImageInput.value = data.gambar_layout || '';
                if (data.gambar_layout) {
                    const imageUrl = '/travel_website/public/uploads/layouts/' + data.gambar_layout;
                    imagePreview.innerHTML = `
                        <div class="mt-2">
                            <p class="text-xs font-medium text-gray-700 mb-1">Gambar saat ini:</p>
                            <div class="mt-1 flex items-center">
                                <img src="${imageUrl}" 
                                     alt="Current Layout" 
                                     class="h-24 w-auto object-contain border rounded-md p-1 bg-white"
                                     onerror="this.onerror=null; this.src='/travel_website/public/img/no-image.jpg';">
                            </div>
                        </div>
                    `;
                }
            } else { // Add mode
                title.textContent = 'Tambah Layout Baru';
                actionInput.value = 'add';
                idInput.value = '';
                existingImageInput.value = '';
            }
        }

        // Show modal with animation
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
    
    function showDeleteConfirmation(id, name) {
        document.getElementById('layout-name').textContent = '"' + name + '"';
        
        // Store the ID in a data attribute of the confirm button
        const confirmBtn = document.getElementById('confirm-delete-btn');
        confirmBtn.setAttribute('data-layout-id', id);
        
        openModal('delete-confirm-modal');
    }

    // Handle form submission with loading state
    document.getElementById('form-layout')?.addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        const submitText = submitBtn.querySelector('#submit-text');
        const submitLoading = submitBtn.querySelector('#submit-loading');
        
        // Show loading state
        submitText.textContent = 'Menyimpan...';
        submitLoading.classList.remove('hidden');
        submitBtn.disabled = true;
        
        // Re-enable after 5 seconds in case of timeout
        setTimeout(() => {
            submitText.textContent = 'Simpan Layout';
            submitLoading.classList.add('hidden');
            submitBtn.disabled = false;
        }, 5000);
    });
    
    // Handle delete confirmation button click
    document.getElementById('confirm-delete-btn')?.addEventListener('click', function() {
        const layoutId = this.getAttribute('data-layout-id');
        if (!layoutId) return;
        
        // Create a form and submit it with POST
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = './process_delete.php';
        
        const typeInput = document.createElement('input');
        typeInput.type = 'hidden';
        typeInput.name = 'type';
        typeInput.value = 'layout';
        form.appendChild(typeInput);
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = layoutId;
        form.appendChild(idInput);
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?php echo $csrf_token; ?>';
        form.appendChild(csrfInput);
        
        document.body.appendChild(form);
        form.submit();
    });
    
    // Preview image before upload
    document.getElementById('gambar_layout')?.addEventListener('change', function(e) {
        const file = this.files[0];
        const preview = document.getElementById('current-image-preview');
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `
                    <div class="mt-2">
                        <p class="text-xs font-medium text-gray-700 mb-1">Pratinjau Gambar:</p>
                        <div class="mt-1">
                            <img src="${e.target.result}" 
                                 alt="Preview" 
                                 class="h-24 w-auto object-contain border rounded-md p-1 bg-white">
                        </div>
                    </div>
                `;
            };
            reader.readAsDataURL(file);
        } else {
            preview.innerHTML = '';
        }
    });
</script>

<?php
require_once __DIR__ . '/../templates/partials/admin_footer.php'; 
?>

