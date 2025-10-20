<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if filename is provided
if (!isset($_GET['filename']) || empty($_GET['filename'])) {
    header('HTTP/1.0 400 Bad Request');
    die('Filename is required');
}

// Sanitize the filename to prevent directory traversal
$filename = basename($_GET['filename']);

// Define allowed directories where images can be stored
$allowed_directories = [
    __DIR__ . '/../public/uploads/payments/',
    __DIR__ . '/../uploads/bukti_transfer/'
];

$file_found = false;
$file_path = '';

// Check each allowed directory for the file
foreach ($allowed_directories as $dir) {
    $potential_path = $dir . $filename;
    if (file_exists($potential_path) && is_file($potential_path)) {
        $file_found = true;
        $file_path = $potential_path;
        break;
    }
}

// If file not found in any allowed directory
if (!$file_found) {
    header('HTTP/1.0 404 Not Found');
    die('File not found');
}

// Get the file's MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file_path);
finfo_close($finfo);

// Set appropriate headers
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: public, max-age=31536000'); // Cache for 1 year
header('Pragma: cache');

// Output the file
readfile($file_path);

