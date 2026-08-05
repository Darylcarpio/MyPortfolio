<?php
/**
 * Like/Unlike Community Post API
 * POST: /api/community/like-post.php
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

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit();
    }

    $user_id = $_SESSION['community_user_id'];
    $post_id = $_POST['post_id'] ?? null;

    if (!$post_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Post ID required']);
        exit();
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Check if post exists
    $stmt = $conn->prepare("SELECT id FROM community_posts WHERE id = ?");
    $stmt->execute([$post_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Post not found']);
        exit();
    }

    // Check if already liked
    $stmt = $conn->prepare("SELECT id FROM community_likes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Unlike
        $stmt = $conn->prepare("DELETE FROM community_likes WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$user_id, $post_id]);
        
        // Decrease count
        $stmt = $conn->prepare("UPDATE community_posts SET likes_count = likes_count - 1 WHERE id = ?");
        $stmt->execute([$post_id]);

        echo json_encode(['success' => true, 'liked' => false, 'message' => 'Post unliked']);
    } else {
        // Like
        $stmt = $conn->prepare("INSERT INTO community_likes (user_id, post_id, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $post_id]);
        
        // Increase count
        $stmt = $conn->prepare("UPDATE community_posts SET likes_count = likes_count + 1 WHERE id = ?");
        $stmt->execute([$post_id]);

        echo json_encode(['success' => true, 'liked' => true, 'message' => 'Post liked']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
