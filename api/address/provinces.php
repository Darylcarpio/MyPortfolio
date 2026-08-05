<?php
/**
 * Philippine Provinces API
 */
header('Content-Type: application/json');
require_once '../../config.php';

try {
    $result = $conn->query("SELECT id, name FROM ph_provinces ORDER BY name ASC");
    
    if (!$result) {
        throw new Exception('Database query failed');
    }

    $provinces = [];
    while ($row = $result->fetch_assoc()) {
        $provinces[] = [
            'id' => $row['id'],
            'name' => $row['name']
        ];
    }

    echo json_encode($provinces);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
