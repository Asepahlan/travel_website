<?php
// Konfigurasi Database
define('DB_HOST', 'localhost'); // Ganti jika host database berbeda
define('DB_USERNAME', 'root'); // Ganti dengan username database Anda
define('DB_PASSWORD', ''); // Ganti dengan password database Anda
define('DB_NAME', 'db_travel'); // Database with all required tables

// Membuat koneksi
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Cek koneksi
if ($conn->connect_error) {
    // Sebaiknya jangan tampilkan error detail di production
    // die("Koneksi Gagal: " . $conn->connect_error);
    die("Tidak dapat terhubung ke database. Silakan coba lagi nanti.");
}

// Set character set
$conn->set_charset("utf8mb4");

// Anda bisa membuat fungsi untuk menutup koneksi jika diperlukan
// function close_connection($conn) {
//     $conn->close();
// }
?>
