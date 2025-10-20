<?php
$page_title = 'Kota & Kecamatan'; // Set page title for header
require_once __DIR__ . '/../templates/partials/admin_header.php'; // Includes CSRF token generation
require_once __DIR__ . '/../config/database.php';

// --- Fetch Kota Data ---
$sql_kota = "SELECT id_kota, nama_kota FROM kota ORDER BY nama_kota ASC";
$result_kota = $conn->query($sql_kota);
$kota_list = [];
if ($result_kota && $result_kota->num_rows > 0) {
    while ($row = $result_kota->fetch_assoc()) {
        $kota_list[] = $row;
    }
}

// --- Fetch Kecamatan Data ---
$sql_kecamatan = "SELECT k.id_kecamatan, k.nama_kecamatan, k.id_kota, ko.nama_kota 
                  FROM kecamatan k 
                  JOIN kota ko ON k.id_kota = ko.id_kota 
                  ORDER BY ko.nama_kota ASC, k.nama_kecamatan ASC";
$result_kecamatan = $conn->query($sql_kecamatan);
$kecamatan_list = [];
if ($result_kecamatan && $result_kecamatan->num_rows > 0) {
    while ($row = $result_kecamatan->fetch_assoc()) {
        $kecamatan_list[] = $row;
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

<!-- Tabs -->
<div class="mb-4 border-b border-gray-200">
    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="myTab" data-tabs-toggle="#myTabContent" role="tablist">
        <li class="mr-2" role="presentation">
            <button class="inline-block p-4 border-b-2 rounded-t-lg" id="kota-tab" data-tabs-target="#kota" type="button" role="tab" aria-controls="kota" aria-selected="true">Daftar Kota</button>
        </li>
        <li class="mr-2" role="presentation">
            <button class="inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300" id="kecamatan-tab" data-tabs-target="#kecamatan" type="button" role="tab" aria-controls="kecamatan" aria-selected="false">Daftar Kecamatan</button>
        </li>
    </ul>
</div>

<!-- Tab Content -->
<div id="myTabContent">
    <!-- Kota Tab -->
    <div class="hidden p-4 rounded-lg bg-white shadow-md" id="kota" role="tabpanel" aria-labelledby="kota-tab">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-700">Daftar Kota</h2>
            <button onclick="openModal('modal-kota', null)" class="bg-primary hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm transition duration-300">
                + Tambah Kota
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Kota</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($kota_list)): ?>
                        <?php foreach ($kota_list as $kota): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $kota['id_kota']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($kota['nama_kota']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button onclick='openModal("modal-kota", <?php echo json_encode($kota); ?>)' class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                    <button onclick='showDeleteConfirmation("kota", <?php echo $kota['id_kota']; ?>, "<?php echo htmlspecialchars($kota['nama_kota']); ?>")' class="text-red-600 hover:text-red-900">Hapus</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada data kota.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Kecamatan Tab -->
    <div class="hidden p-4 rounded-lg bg-white shadow-md" id="kecamatan" role="tabpanel" aria-labelledby="kecamatan-tab">
         <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-700">Daftar Kecamatan</h2>
            <button onclick="openModal('modal-kecamatan', null)" class="bg-primary hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm transition duration-300">
                + Tambah Kecamatan
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Kecamatan</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kota Induk</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                     <?php if (!empty($kecamatan_list)): ?>
                        <?php foreach ($kecamatan_list as $kecamatan): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $kecamatan['id_kecamatan']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($kecamatan['nama_kecamatan']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($kecamatan['nama_kota']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button onclick='openModal("modal-kecamatan", <?php echo json_encode($kecamatan); ?>)' class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                    <button onclick='showDeleteConfirmation("kecamatan", <?php echo $kecamatan['id_kecamatan']; ?>, "<?php echo htmlspecialchars($kecamatan['nama_kecamatan']); ?>")' class="text-red-600 hover:text-red-900">Hapus</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada data kecamatan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Kota -->
<div id="modal-kota" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center hidden z-50">
    <div class="relative mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center border-b pb-3 mb-4">
            <h3 id="modal-kota-title" class="text-lg leading-6 font-medium text-gray-900">Tambah Kota Baru</h3>
            <button onclick="closeModal('modal-kota')" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
            </button>
        </div>
        <form id="form-kota" action="./process_kota.php" method="POST">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" id="kota_id" name="id_kota">
            <input type="hidden" name="action" id="kota_action" value="add">
            <div class="mb-4">
                <label for="nama_kota" class="block text-sm font-medium text-gray-700 mb-1">Nama Kota</label>
                <input type="text" id="nama_kota" name="nama_kota" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm">
            </div>
            <div class="flex justify-end mt-6">
                <button type="button" onclick="closeModal('modal-kota')" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded mr-2 text-sm transition duration-300">Batal</button>
                <button type="submit" class="bg-primary hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm transition duration-300">Simpan Kota</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Kecamatan -->
<div id="modal-kecamatan" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center hidden z-50">
    <div class="relative mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
         <div class="flex justify-between items-center border-b pb-3 mb-4">
            <h3 id="modal-kecamatan-title" class="text-lg leading-6 font-medium text-gray-900">Tambah Kecamatan Baru</h3>
            <button onclick="closeModal('modal-kecamatan')" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
            </button>
        </div>
        <form id="form-kecamatan" action="./process_kecamatan.php" method="POST">
             <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" id="kecamatan_id" name="id_kecamatan">
            <input type="hidden" name="action" id="kecamatan_action" value="add">
            <div class="mb-4">
                <label for="nama_kecamatan" class="block text-sm font-medium text-gray-700 mb-1">Nama Kecamatan</label>
                <input type="text" id="nama_kecamatan" name="nama_kecamatan" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm">
            </div>
             <div class="mb-4">
                <label for="kecamatan_id_kota" class="block text-sm font-medium text-gray-700 mb-1">Kota Induk</label>
                <select id="kecamatan_id_kota" name="id_kota" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary bg-white text-sm">
                    <option value="" disabled selected>-- Pilih Kota --</option>
                    <?php foreach ($kota_list as $kota): ?>
                        <option value="<?php echo $kota['id_kota']; ?>"><?php echo htmlspecialchars($kota['nama_kota']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex justify-end mt-6">
                <button type="button" onclick="closeModal('modal-kecamatan')" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded mr-2 text-sm transition duration-300">Batal</button>
                <button type="submit" class="bg-primary hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm transition duration-300">Simpan Kecamatan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div id="modal-delete-confirm" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-delete-title" role="dialog" aria-modal="true">
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
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-delete-title">Konfirmasi Hapus</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Apakah Anda yakin ingin menghapus <span id="delete-item-name" class="font-medium"></span>?
                                <span id="delete-warning"></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <form id="form-delete" method="POST" action="./process_delete.php" class="inline-flex w-full sm:ml-3 sm:w-auto">
                    <input type="hidden" name="type" id="delete-type">
                    <input type="hidden" name="id" id="delete-id">
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

<script>
    // Function to open modals and populate data for editing
    function openModal(modalId, data) {
        const modal = document.getElementById(modalId);
        const form = modal.querySelector('form');
        form.reset(); // Reset form fields

        if (modalId === 'modal-kota') {
            const title = modal.querySelector('#modal-kota-title');
            const actionInput = modal.querySelector('#kota_action');
            const idInput = modal.querySelector('#kota_id');
            const nameInput = modal.querySelector('#nama_kota');
            if (data) { // Edit mode
                title.textContent = 'Edit Kota';
                actionInput.value = 'edit';
                idInput.value = data.id_kota;
                nameInput.value = data.nama_kota;
            } else { // Add mode
                title.textContent = 'Tambah Kota Baru';
                actionInput.value = 'add';
                idInput.value = '';
            }
        } else if (modalId === 'modal-kecamatan') {
             const title = modal.querySelector('#modal-kecamatan-title');
            const actionInput = modal.querySelector('#kecamatan_action');
            const idInput = modal.querySelector('#kecamatan_id');
            const nameInput = modal.querySelector('#nama_kecamatan');
            const kotaSelect = modal.querySelector('#kecamatan_id_kota');
             if (data) { // Edit mode
                title.textContent = 'Edit Kecamatan';
                actionInput.value = 'edit';
                idInput.value = data.id_kecamatan;
                nameInput.value = data.nama_kecamatan;
                kotaSelect.value = data.id_kota; // Select the correct city
            } else { // Add mode
                title.textContent = 'Tambah Kecamatan Baru';
                actionInput.value = 'add';
                idInput.value = '';
                kotaSelect.value = ''; // Reset city selection
            }
        }

        modal.classList.remove('hidden');
    }

    // Function to close modals
    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    // Function to show delete confirmation
    function showDeleteConfirmation(type, id, name) {
        document.getElementById('delete-type').value = type;
        document.getElementById('delete-id').value = id;
        document.getElementById('delete-item-name').textContent = name;
        
        let warning = '';
        if (type === 'kota') {
            warning = ' Menghapus kota akan menghapus kecamatan dan data terkait lainnya.';
        } else if (type === 'kecamatan') {
            warning = ' Menghapus kecamatan akan menghapus data terkait.';
        }
        document.getElementById('delete-warning').textContent = warning;
        
        document.getElementById('modal-delete-confirm').classList.remove('hidden');
    }

    // Tab switching logic (simple example)
    document.addEventListener('DOMContentLoaded', function () {
        const tabs = document.querySelectorAll('[data-tabs-target]');
        const tabContents = document.querySelectorAll('[role="tabpanel"]');
        const tabButtons = document.querySelectorAll('[role="tab"]');

        // Function to activate tab
        function activateTab(targetId) {
             tabContents.forEach(content => {
                content.classList.add('hidden');
            });
            tabButtons.forEach(button => {
                button.setAttribute('aria-selected', 'false');
                button.classList.remove('border-primary', 'text-primary');
                 button.classList.add('hover:text-gray-600', 'hover:border-gray-300');
            });

            const targetContent = document.querySelector(targetId);
            const targetButton = document.querySelector(`[data-tabs-target="${targetId}"]`);

            if (targetContent) targetContent.classList.remove('hidden');
            if (targetButton) {
                targetButton.setAttribute('aria-selected', 'true');
                targetButton.classList.add('border-primary', 'text-primary');
                targetButton.classList.remove('hover:text-gray-600', 'hover:border-gray-300');
            }
             // Store active tab in localStorage
            localStorage.setItem('activeAdminTab', targetId);
        }

        // Check localStorage for active tab on page load
        const activeTabId = localStorage.getItem('activeAdminTab');
        if (activeTabId && document.querySelector(activeTabId)) {
            activateTab(activeTabId);
        } else {
            // Default to the first tab if none stored or invalid
            activateTab('#kota'); 
        }

        // Add click listeners to tabs
        tabs.forEach(tab => {
            tab.addEventListener('click', function () {
                const targetId = this.getAttribute('data-tabs-target');
                activateTab(targetId);
            });
        });
    });

</script>

<?php
require_once __DIR__ . '/../templates/partials/admin_footer.php'; 
?>

