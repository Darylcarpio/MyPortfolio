<?php
/**
 * Philippine Municipalities API
 */
header('Content-Type: application/json');
require_once '../../config.php';

try {
    if (empty($_GET['province_id'])) {
        throw new Exception('Province ID is required');
    }

    $province_id = intval($_GET['province_id']);
    
    $stmt = $conn->prepare("SELECT id, name FROM ph_municipalities WHERE province_id = ? ORDER BY name ASC");
    $stmt->bind_param("i", $province_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $municipalities = [];
    while ($row = $result->fetch_assoc()) {
        $municipalities[] = [
            'id' => $row['id'],
            'name' => $row['name']
        ];
    }

    echo json_encode($municipalities);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
