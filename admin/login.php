<?php
session_start();

// Jika admin sudah login, redirect ke dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['error_message']); // Clear error message after displaying

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - TravelKita</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2563eb',
                        secondary: '#4f46e5',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 font-sans bg-gradient-to-br from-blue-50 to-indigo-50">
    <div class="w-full max-w-md bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl overflow-hidden transition-all duration-300 hover:shadow-2xl hover:-translate-y-1">
        <!-- Header -->
        <div class="px-10 pt-10 pb-6 text-center">
            <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Admin Panel</h1>
            <p class="text-gray-500">Silakan masuk untuk melanjutkan</p>
        </div>

        <?php if ($error_message): ?>
        <div class="mx-6 mb-6 bg-red-50 border-l-4 border-red-500 rounded-lg p-4">
            <div class="flex items-start">
                <svg class="h-5 w-5 text-red-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <div class="text-sm text-red-700">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <form action="./process_login.php" method="POST" class="px-8 pb-8">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="mb-6">
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1.5">Username</label>
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <input type="text" id="username" name="username" required 
                           class="block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-lg bg-gray-50 focus:ring-2 focus:ring-blue-100 focus:border-blue-500 focus:bg-white transition duration-150 ease-in-out"
                           placeholder="Masukkan username">
                </div>
            </div>

            <div class="mb-8">
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                </div>
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <input type="password" id="password" name="password" required 
                           class="block w-full pl-10 pr-10 py-3 border border-gray-200 rounded-lg bg-gray-50 focus:ring-2 focus:ring-blue-100 focus:border-blue-500 focus:bg-white transition duration-150 ease-in-out"
                           placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢">
                    <button type="button" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-blue-600 focus:outline-none"
                            id="togglePassword"
                            aria-label="Toggle password visibility">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                Masuk ke Dashboard
            </button>
        </form>

        <div class="px-8 pb-8 text-center">
            <a href="../index.php" class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800 group transition duration-150 ease-in-out">
                <svg class="w-4 h-4 mr-1.5 transition-transform duration-200 group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Kembali ke Halaman Utama
            </a>
        </div>
    </div>

    <script>
    // Toggle password visibility
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');
    const icon = togglePassword.querySelector('svg');
    
    togglePassword.addEventListener('click', function() {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        
        // Toggle eye icon
        if (type === 'text') {
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>';
        } else {
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
        }
    });
    </script>
</body>
</html>

