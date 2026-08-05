<?php
/**
 * Add Comment to Post API
 * POST: /api/community/add-comment.php
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
    $content = trim($_POST['content'] ?? '');

    if (!$post_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Post ID required']);
        exit();
    }

    if (empty($content)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Comment content required']);
        exit();
    }

    if (strlen($content) > 1000) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Comment is too long']);
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

    // Create comment
    $stmt = $conn->prepare("
        INSERT INTO community_comments (post_id, user_id, content, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$post_id, $user_id, $content]);
    $comment_id = $conn->lastInsertId();

    // Update comment count
    $stmt = $conn->prepare("UPDATE community_posts SET comments_count = comments_count + 1 WHERE id = ?");
    $stmt->execute([$post_id]);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Comment added successfully',
        'comment_id' => $comment_id
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
