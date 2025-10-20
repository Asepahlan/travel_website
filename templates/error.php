<?php
// Start session to access error message
session_start();

// Get error message from session or use default
$error_message = $_SESSION['error_message'] ?? 'Terjadi kesalahan yang tidak diketahui. Silakan coba lagi nanti.';

// Clear the error message from session
unset($_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - TravelKita</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-md w-full text-center">
        <div class="text-red-500 text-6xl mb-4">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-4">Terjadi Kesalahan</h1>
        <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($error_message); ?></p>
        <div class="flex justify-center space-x-4">
            <a href="javascript:history.back()" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded-lg transition duration-200">
                Kembali
            </a>
            <a href="index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-6 rounded-lg transition duration-200">
                Ke Halaman Utama
            </a>
        </div>
    </div>
</body>
</html>

