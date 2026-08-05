<?php
/**
 * Get User Profile API
 * GET: /api/community/get-profile.php?user_id=X
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

    $profile_user_id = $_GET['user_id'] ?? $_SESSION['community_user_id'];
    $current_user_id = $_SESSION['community_user_id'];

    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Get user profile
    $stmt = $conn->prepare("
        SELECT id, email, username, first_name, last_name, bio, profile_picture, 
               created_at, status
        FROM community_users
        WHERE id = ? AND role = 'user'
    ");
    $stmt->execute([$profile_user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }

    // Get user posts
    $stmt = $conn->prepare("
        SELECT id, content, created_at, likes_count, comments_count
        FROM community_posts
        WHERE user_id = ? AND privacy IN ('public', 'friends_only')
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$profile_user_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if current user is following
    $stmt = $conn->prepare("
        SELECT status FROM community_friends
        WHERE (requester_id = ? AND receiver_id = ?) OR (requester_id = ? AND receiver_id = ?)
    ");
    $stmt->execute([$current_user_id, $profile_user_id, $profile_user_id, $current_user_id]);
    $friendship = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get friend count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count FROM community_friends
        WHERE (requester_id = ? OR receiver_id = ?) AND status = 'accepted'
    ");
    $stmt->execute([$profile_user_id, $profile_user_id]);
    $friend_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    echo json_encode([
        'success' => true,
        'user' => array_merge($user, ['friend_count' => $friend_count]),
        'posts' => $posts,
        'friendship' => $friendship
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
