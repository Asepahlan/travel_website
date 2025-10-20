<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Pastikan admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $_SESSION['error_message'] = 'Anda harus login untuk mengakses halaman ini.';
    header('Location: login.php');
    exit();
}

$adminId = $_SESSION['admin_id'];
$error = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['error_message']);

$success = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
unset($_SESSION['success_message']);



// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validasi input
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Semua field harus diisi';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Password baru dan konfirmasi password tidak cocok';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password minimal 6 karakter';
    } else {
        // Verifikasi password saat ini
        $stmt = $conn->prepare("SELECT password FROM admin WHERE id = ?");
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();

        if (password_verify($currentPassword, $admin['password'])) {
            // Update password baru
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
            $updateStmt->bind_param("si", $hashedPassword, $adminId);
            
            if ($updateStmt->execute()) {
                $success = 'Password berhasil diubah';
            } else {
                $error = 'Terjadi kesalahan. Silakan coba lagi.';
            }
        } else {
            $error = 'Password saat ini salah';
        }
    }
}

// Dapatkan data admin
$stmt = $conn->prepare("SELECT id, username, email, nama, created_at FROM admin WHERE id = ?");
$stmt->bind_param("i", $adminId);
$stmt->execute();
$adminData = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Set page title for header
$page_title = 'Profil';
require_once __DIR__ . '/../templates/partials/admin_header.php';
?>
    
    <div class="min-h-screen bg-gray-50 p-4 sm:ml-64">
        <div class="p-4 mt-14">
            <div class="max-w-5xl mx-auto">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Profil Admin</h1>
                    <p class="text-gray-600 mt-1">Kelola informasi akun dan keamanan Anda</p>
                </div>
                
                <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg">
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-red-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm text-red-700"><?php echo htmlspecialchars($error); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-lg">
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm text-green-700"><?php echo htmlspecialchars($success); ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Header Profil -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                    <div class="flex flex-col sm:flex-row items-center gap-6">
                        <div class="flex-shrink-0">
                            <div class="w-20 h-20 rounded-full bg-blue-50 flex items-center justify-center">
                                <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 text-center sm:text-left">
                            <h2 class="text-xl font-semibold text-gray-900 mb-1"><?php echo htmlspecialchars($adminData['nama'] ?? $adminData['username']); ?></h2>
                            <p class="text-gray-600 mb-2">@<?php echo htmlspecialchars($adminData['username']); ?></p>
                            <p class="text-sm text-gray-500 mb-1"><?php echo htmlspecialchars($adminData['email']); ?></p>
                            <p class="text-xs text-gray-400">Bergabung pada <?php echo date('d F Y', strtotime($adminData['created_at'])); ?></p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Informasi Akun -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center mb-4">
                            <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900">Informasi Akun</h3>
                        </div>
                        <form id="usernameForm" action="./process/profile.php?action=update_username" method="POST" class="space-y-4">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                                <input type="text" 
                                       id="username" 
                                       name="username" 
                                       required
                                       minlength="3"
                                       maxlength="30"
                                       value="<?php echo htmlspecialchars($adminData['username']); ?>"
                                       class="w-full px-3 py-2 border-b-2 border-gray-200 focus:border-blue-500 focus:outline-none bg-transparent transition-colors"
                                       placeholder="Masukkan username baru">
                                <p class="mt-1 text-xs text-gray-500">Minimal 3 karakter, tanpa spasi</p>
                            </div>
                            <div class="pt-4">
                                <button type="submit" 
                                        class="w-full px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Ubah Password -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center mb-4">
                            <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900">Ubah Password</h3>
                        </div>
                        <form id="passwordForm" action="./process/profile.php?action=update_password" method="POST" class="space-y-5">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <!-- Password Saat Ini -->
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Password Saat Ini</label>
                                <div class="relative">
                                    <input type="password" 
                                           id="current_password" 
                                           name="current_password" 
                                           required
                                           minlength="6"
                                           class="w-full px-3 py-2 pr-10 border-b-2 border-gray-200 focus:border-blue-500 focus:outline-none bg-transparent transition-colors"
                                           placeholder="Masukkan password saat ini">
                                    <button type="button" 
                                            class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-blue-600 focus:outline-none toggle-password">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Masukkan password Anda saat ini untuk verifikasi</p>
                            </div>
                            
                            <!-- Password Baru -->
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">Password Baru</label>
                                <div class="relative">
                                    <input type="password" 
                                           id="new_password" 
                                           name="new_password" 
                                           required 
                                           minlength="6"
                                           class="w-full px-3 py-2 pr-10 border-b-2 border-gray-200 focus:border-blue-500 focus:outline-none bg-transparent transition-colors"
                                           placeholder="Minimal 6 karakter">
                                    <button type="button" 
                                            class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-blue-600 focus:outline-none toggle-password">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="mt-2">
                                    <div class="grid grid-cols-4 gap-1 mb-1">
                                        <div class="h-1.5 rounded-full bg-gray-200 password-strength" data-strength="0"></div>
                                        <div class="h-1.5 rounded-full bg-gray-200 password-strength" data-strength="1"></div>
                                        <div class="h-1.5 rounded-full bg-gray-200 password-strength" data-strength="2"></div>
                                        <div class="h-1.5 rounded-full bg-gray-200 password-strength" data-strength="3"></div>
                                    </div>
                                    <p class="text-xs text-gray-500" id="password-strength-text">Kekuatan password</p>
                                </div>
                            </div>
                            
                            <!-- Konfirmasi Password Baru -->
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password Baru</label>
                                <div class="relative">
                                    <input type="password" 
                                           id="confirm_password" 
                                           name="confirm_password" 
                                           required 
                                           minlength="6"
                                           class="w-full px-3 py-2 pr-10 border-b-2 border-gray-200 focus:border-blue-500 focus:outline-none bg-transparent transition-colors"
                                           placeholder="Ketik ulang password baru">
                                    <button type="button" 
                                            class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-blue-600 focus:outline-none toggle-password">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Pastikan password baru sama dengan yang Anda ketik di atas</p>
                            </div>
                            
                            <!-- Tombol Simpan -->
                            <div class="pt-4">
                                <button type="submit" 
                                        class="w-full px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                    Simpan Password Baru
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            const icon = this.querySelector('svg');
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            
            // Toggle eye icon
            if (type === 'text') {
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>';
            } else {
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
            }
        });
    });

    // Password strength indicator
    const passwordInput = document.getElementById('new_password');
    const strengthBars = document.querySelectorAll('.password-strength');
    const strengthText = document.getElementById('password-strength-text');

    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Check password strength
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]+/)) strength++;
            if (password.match(/[A-Z]+/)) strength++;
            if (password.match(/[0-9]+/)) strength++;
            if (password.match(/[!@#$%^&*(),.?\":{}|<>]+/)) strength++;
            
            // Cap at 4 for the 4 bars
            strength = Math.min(4, Math.ceil(strength / 1.5));
            
            // Update UI
            strengthBars.forEach((bar, index) => {
                if (index < strength) {
                    bar.classList.remove('bg-gray-200');
                    if (strength <= 2) {
                        bar.classList.add('bg-red-500');
                        bar.classList.remove('bg-yellow-500', 'bg-green-500');
                    } else if (strength === 3) {
                        bar.classList.add('bg-yellow-500');
                        bar.classList.remove('bg-red-500', 'bg-green-500');
                    } else {
                        bar.classList.add('bg-green-500');
                        bar.classList.remove('bg-red-500', 'bg-yellow-500');
                    }
                } else {
                    bar.classList.add('bg-gray-200');
                    bar.classList.remove('bg-red-500', 'bg-yellow-500', 'bg-green-500');
                }
            });
            
            // Update text
            const strengthMessages = [
                'Sangat lemah',
                'Lemah',
                'Cukup',
                'Kuat',
                'Sangat kuat'
            ];
            strengthText.textContent = `Kekuatan password: ${strengthMessages[strength]}`;
            
            // Update text color based on strength
            if (strength <= 1) {
                strengthText.className = 'text-xs text-red-600';
            } else if (strength <= 2) {
                strengthText.className = 'text-xs text-yellow-600';
            } else {
                strengthText.className = 'text-xs text-green-600';
            }
        });
    }

    // Form validation with SweetAlert2
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', async function(e) {
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            
            if (form.action.includes('update_password')) {
                const currentPassword = document.getElementById('current_password').value;
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if (!currentPassword || !newPassword || !confirmPassword) {
                    e.preventDefault();
                    await Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Semua field harus diisi',
                        showConfirmButton: false,
                        timer: 3000
                    });
                    return;
                }
                
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    await Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Password baru dan konfirmasi password tidak cocok',
                        showConfirmButton: false,
                        timer: 3000
                    });
                    return;
                }
                
                if (newPassword.length < 6) {
                    e.preventDefault();
                    await Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Password minimal 6 karakter',
                        showConfirmButton: false,
                        timer: 3000
                    });
                    return;
                }
                
                // Add loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Menyimpan...`;
            }
            
            if (form.action.includes('update_username')) {
                const username = document.getElementById('username').value.trim();
                if (!username || username.length < 3) {
                    e.preventDefault();
                    await Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Username minimal 3 karakter',
                        showConfirmButton: false,
                        timer: 3000
                    });
                    return;
                }
                
                // Add loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Menyimpan...`;
            }
        });
    });
    
    // Handle form submission success/error messages from PHP
    <?php if (isset($_SESSION['success_message'])): ?>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: '<?php echo addslashes($_SESSION['success_message']); ?>',
        showConfirmButton: false,
        timer: 2000
    });
    <?php unset($_SESSION['success_message']); endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
    Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: '<?php echo addslashes($_SESSION['error_message']); ?>',
        showConfirmButton: false,
        timer: 3000
    });
    <?php unset($_SESSION['error_message']); endif; ?>
    </script>
</body>
</html>

