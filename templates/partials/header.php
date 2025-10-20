<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/travel_website/public/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/travel_website/public/favicon.ico" type="image/x-icon">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>TravelKita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .primary-bg {
            background-color: #00BFFF;
        }
        .primary-text {
            color: #00BFFF;
        }
        .primary-border {
            border-color: #00BFFF;
        }
        .hover\:primary-bg:hover {
            background-color: #0099cc;
        }
        /* Animasi untuk notifikasi */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.3s ease-out forwards;
        }
        
        /* Transisi untuk tombol */
        .transition-all {
            transition: all 0.2s ease-in-out;
        }
        
        /* Efek hover untuk tombol WhatsApp */
        .hover\:bg-green-600:hover {
            background-color: #059669;
        }
        
        /* Efek hover untuk tombol kirim */
        .hover\:bg-blue-700:hover {
            background-color: #1d4ed8;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <!-- Logo -->
                <div class="flex-shrink-0">
                    <a href="/travel_website/" class="flex items-center">
                        <span class="text-2xl font-bold primary-text">TravelKita</span>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <nav class="hidden md:flex space-x-8">
                    <a href="/travel_website/" class="text-gray-700 hover:text-blue-600 font-medium">Beranda</a>
                    <a href="/travel_website/jadwal.php" class="text-gray-700 hover:text-blue-600 font-medium">Rute</a>
                    <a href="/travel_website/tentang-kami.php" class="text-gray-700 hover:text-blue-600 font-medium">Tentang Kami</a>
                    <a href="/travel_website/kontak.php" class="text-gray-700 hover:text-blue-600 font-medium">Kontak</a>
                </nav>

                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button type="button" class="text-gray-500 hover:text-gray-600 focus:outline-none" id="mobile-menu-button">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile menu -->
            <div class="md:hidden hidden pt-4" id="mobile-menu">
                <div class="px-2 pt-2 pb-3 space-y-1">
                    <a href="/travel_website/" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">Beranda</a>
                    <a href="/travel_website/jadwal.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">Rute</a>
                    <a href="/travel_website/tentang-kami.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">Tentang Kami</a>
                    <a href="/travel_website/kontak.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">Kontak</a>
                    <a href="/travel_website/admin/login.php" class="block px-3 py-2 rounded-md text-base font-medium text-white bg-blue-600 hover:bg-blue-700">Admin Login</a>
                </div>
            </div>
        </div>
    </header>

    <main>

