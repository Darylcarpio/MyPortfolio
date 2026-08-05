<?php
/**
 * Search Users API
 * GET: /api/community/search-users.php?q=search+term
 */
session_start();
header('Content-Type: application/json');

require_once dirname(__FILE__) . '/../../config.php';

try {
    if (!isset($_SESSION['community_user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit();
    }

    $query = trim($_GET['q'] ?? '');
    
    if (strlen($query) < 2) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Search query too short']);
        exit();
    }

    if (strlen($query) > 100) {
        $query = substr($query, 0, 100);
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();

    $search_term = '%' . $query . '%';
    
    $stmt = $conn->prepare("
        SELECT id, first_name, last_name, username, profile_picture, status
        FROM community_users
        WHERE role = 'user' AND status = 'active' AND 
        (first_name LIKE ? OR last_name LIKE ? OR username LIKE ?)
        LIMIT 20
    ");
    $stmt->execute([$search_term, $search_term, $search_term]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'results' => $users
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
