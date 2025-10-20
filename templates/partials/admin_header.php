<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Daftar halaman yang boleh diakses tanpa login
$allowed_pages = ['login.php', './process/login.php'];
$current_script = basename($_SERVER['PHP_SELF']);

// Redirect ke halaman login jika belum login dan bukan halaman yang diizinkan
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    if (!in_array($current_script, $allowed_pages)) {
        $_SESSION['error_message'] = 'Anda harus login untuk mengakses halaman ini.';
        header('Location: login.php');
        exit;
    }
}

// Generate CSRF token jika belum ada
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$current_page_title = $page_title ?? 'Admin Panel';
$current_page = $current_script;

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/travel_website/public/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/travel_website/public/favicon.ico" type="image/x-icon">
    <title><?= htmlspecialchars($current_page_title) ?> - Admin TravelKita</title>
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6', // blue-500
                        'primary-hover': '#2563eb', // blue-600
                    }
                }
            }
        }
    </script>
    <style>
        .sidebar-link {
            display: block;
            padding: 0.75rem 1.5rem;
            color: #d1d5db;
            transition: background-color 0.2s, color 0.2s;
            border-left: 4px solid transparent;
        }
        .sidebar-link:hover {
            background-color: #374151;
            color: #ffffff;
        }
        .sidebar-link.active {
            background-color: #1f2937;
            color: #ffffff;
            border-left-color: #3b82f6;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans flex">

    <aside class="w-64 bg-gray-900 text-gray-300 min-h-screen shadow-lg fixed lg:relative lg:translate-x-0 transform -translate-x-full lg:static transition-transform duration-300 ease-in-out z-50" id="sidebar">
        <div class="p-6 border-b border-gray-700">
            <a href="index.php" class="text-2xl font-bold text-white">Admin TravelKita</a>
        </div>
        <nav class="mt-4">
            <a href="index.php" class="sidebar-link <?= $current_page === 'index.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                Dashboard
            </a>
            <a href="manage_reservasi.php" class="sidebar-link <?= $current_page === 'manage_reservasi.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                Reservasi
            </a>
            <a href="konfirmasi_pembayaran.php" class="sidebar-link <?= $current_page === 'konfirmasi_pembayaran.php' || $current_page === 'detail_konfirmasi.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Konfirmasi Pembayaran
            </a>
            <a href="manage_jadwal.php" class="sidebar-link <?= $current_page === 'manage_jadwal.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Jadwal
            </a>
            <a href="manage_layout.php" class="sidebar-link <?= $current_page === 'manage_layout.php' || $current_page === 'manage_kursi.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                </svg>
                Layout & Kursi
            </a>
            <a href="manage_kota.php" class="sidebar-link <?= $current_page === 'manage_kota.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Kota & Kecamatan
            </a>
            <a href="profile.php" class="sidebar-link <?= $current_page === 'profile.php' ? 'active' : '' ?>">
                <svg class="w-5 h-5 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                Profil
            </a>
            <a href="logout.php" class="sidebar-link mt-4 text-red-400 hover:text-red-300">
                <svg class="w-5 h-5 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                Logout
            </a>
        </nav>
    </aside>

    <div class="flex-1 flex flex-col">
        <header class="bg-white shadow-md lg:hidden sticky top-0 z-40">
            <div class="container mx-auto px-4 py-3 flex justify-between items-center">
                <a href="index.php" class="text-xl font-bold text-primary">Admin TravelKita</a>
                <button id="sidebar-toggle" class="text-gray-600 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                </button>
            </div>
        </header>
        <main class="flex-1 p-6 md:p-8">
            <!-- Content from specific admin pages will be loaded here -->

