<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$term = isset($_GET['term']) ? trim($_GET['term']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'kota'; // 'kota' or 'kecamatan'
$kota_id = isset($_GET['kota_id']) ? (int)$_GET['kota_id'] : null;

$results = [];

try {
    if ($type === 'kota') {
        $sql = "SELECT id_kota as id, nama_kota as text FROM kota WHERE nama_kota LIKE ? ORDER BY nama_kota ASC LIMIT 10";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) throw new Exception('Prepare failed: ' . $conn->error);
        $like_term = "%$term%";
        $stmt->bind_param("s", $like_term);
    } elseif ($type === 'kecamatan' && !empty($kota_id)) {
        $sql = "SELECT id_kecamatan as id, nama_kecamatan as text FROM kecamatan WHERE id_kota = ? AND nama_kecamatan LIKE ? ORDER BY nama_kecamatan ASC LIMIT 10";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) throw new Exception('Prepare failed: ' . $conn->error);
        $like_term = "%$term%";
        $stmt->bind_param("is", $kota_id, $like_term);
    } else {
        // Return empty if type is kecamatan but no kota_id provided
        echo json_encode(['results' => []]);
        $conn->close();
        exit;
    }

    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    $stmt->close();

} catch (Exception $e) {
    // Log error in production
    error_log("API Error: " . $e->getMessage());
    // Return an empty array or an error structure in JSON
    echo json_encode(['error' => 'Failed to fetch data']);
    $conn->close();
    exit;
}

$conn->close();
echo json_encode(['results' => $results]);
?>

