<?php
/**
 * Send Private Message API
 * POST: /api/community/send-message.php
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

    $sender_id = $_SESSION['community_user_id'];
    $recipient_id = $_POST['recipient_id'] ?? null;
    $content = trim($_POST['content'] ?? '');

    if (!$recipient_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Recipient ID required']);
        exit();
    }

    if (empty($content)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message content required']);
        exit();
    }

    if (strlen($content) > 2000) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message too long']);
        exit();
    }

    if ($sender_id == $recipient_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot message yourself']);
        exit();
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Check if recipient exists
    $stmt = $conn->prepare("SELECT id FROM community_users WHERE id = ? AND role = 'user'");
    $stmt->execute([$recipient_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Recipient not found']);
        exit();
    }

    // Create message
    $stmt = $conn->prepare("
        INSERT INTO community_messages (sender_id, recipient_id, content, is_read, created_at)
        VALUES (?, ?, ?, FALSE, NOW())
    ");
    $stmt->execute([$sender_id, $recipient_id, $content]);
    $message_id = $conn->lastInsertId();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Message sent',
        'message_id' => $message_id
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
