<?php
/**
 * Create Community Post API
 * POST: /api/community/create-post.php
 */
session_start();
header('Content-Type: application/json');

require_once dirname(__FILE__) . '/../../config.php';

try {
    // Check authentication
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
    $content = trim($_POST['content'] ?? '');
    $privacy = $_POST['privacy'] ?? 'public';
    
    if (empty($content) || strlen($content) < 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Post content is required']);
        exit();
    }

    if (strlen($content) > 5000) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Post is too long (max 5000 characters)']);
        exit();
    }

    // Validate privacy setting
    if (!in_array($privacy, ['public', 'friends_only', 'private'])) {
        $privacy = 'public';
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Create post
    $stmt = $conn->prepare("
        INSERT INTO community_posts (user_id, content, privacy, created_at, likes_count, comments_count)
        VALUES (?, ?, ?, NOW(), 0, 0)
    ");
    $stmt->execute([$user_id, $content, $privacy]);
    $post_id = $conn->lastInsertId();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Post created successfully',
        'post_id' => $post_id
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error creating post: ' . $e->getMessage()
    ]);
}
?>
