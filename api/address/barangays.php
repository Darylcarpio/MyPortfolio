<?php
/**
 * Philippine Barangays API
 */
header('Content-Type: application/json');
require_once '../../config.php';

try {
    if (empty($_GET['municipality_id'])) {
        throw new Exception('Municipality ID is required');
    }

    $municipality_id = intval($_GET['municipality_id']);
    
    $stmt = $conn->prepare("SELECT id, name FROM ph_barangays WHERE municipality_id = ? ORDER BY name ASC");
    $stmt->bind_param("i", $municipality_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $barangays = [];
    while ($row = $result->fetch_assoc()) {
        $barangays[] = [
            'id' => $row['id'],
            'name' => $row['name']
        ];
    }

    echo json_encode($barangays);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
