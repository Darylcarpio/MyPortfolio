<?php
/**
 * api/community/browse.php - Get List of Community Users
 */
session_start();
header('Content-Type: application/json');

require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/CommunityAuth_class.php';

// Check authentication
if (!isset($_SESSION['community_logged_in']) || $_SESSION['community_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['community_user_id'] ?? null;

try {
    $db = new Database();
    
    // Get all active users except the current user
    $query = "SELECT 
        cu.id,
        cu.first_name,
        cu.last_name,
        cu.profile_image,
        cu.location,
        cu.department,
        ad.department_name,
        ad.id as department_id,
        cp.bio
    FROM community_users cu
    LEFT JOIN ascot_departments ad ON cu.department = ad.id
    LEFT JOIN community_profiles cp ON cu.id = cp.user_id
    WHERE cu.status = 'active' AND cu.id != :userId
    ORDER BY cu.created_at DESC
    LIMIT 100";
    
    $stmt = $db->prepare($query);
    $stmt->execute(['userId' => $userId]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'users' => $users,
        'count' => count($users)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
