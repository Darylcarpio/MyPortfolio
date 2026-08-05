<?php
/**
 * Community Messages API
 * Direct messaging endpoint
 */
header('Content-Type: application/json');
session_start();

require_once dirname(__FILE__) . '/../../config.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Check if user is logged in
if (!isset($_SESSION['community_user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$current_user_id = $_SESSION['community_user_id'];
$action = $_GET['action'] ?? '';

try {
    if ($action === 'send') {
        // Send a message
        $receiver_id = $_POST['receiver_id'] ?? null;
        $message = $_POST['message'] ?? '';

        if (!$receiver_id || empty($message)) {
            throw new Exception('Missing required fields');
        }

        // Verify receiver exists
        $stmt = $conn->prepare("SELECT id FROM community_users WHERE id = ?");
        $stmt->execute([$receiver_id]);
        if (!$stmt->fetch()) {
            throw new Exception('Receiver not found');
        }

        // Insert message
        $stmt = $conn->prepare("
            INSERT INTO community_messages (sender_id, receiver_id, message_text, sent_at, is_read)
            VALUES (?, ?, ?, NOW(), 0)
        ");
        $stmt->execute([$current_user_id, $receiver_id, $message]);

        echo json_encode([
            'success' => true,
            'message' => 'Message sent',
            'message_id' => $conn->lastInsertId()
        ]);

    } elseif ($action === 'get') {
        // Get conversation with a specific user
        $user_id = $_GET['user_id'] ?? null;

        if (!$user_id) {
            throw new Exception('Missing user_id');
        }

        // Get user info
        $stmt = $conn->prepare("
            SELECT id, first_name, last_name, profile_picture, username
            FROM community_users
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception('User not found');
        }

        // Get all messages between the two users
        $stmt = $conn->prepare("
            SELECT id, sender_id, receiver_id, message_text, sent_at, is_read
            FROM community_messages
            WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
            ORDER BY sent_at ASC
            LIMIT 100
        ");
        $stmt->execute([$current_user_id, $user_id, $user_id, $current_user_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Mark messages from this user as read
        $stmt = $conn->prepare("
            UPDATE community_messages
            SET is_read = 1
            WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
        ");
        $stmt->execute([$user_id, $current_user_id]);

        echo json_encode([
            'success' => true,
            'data' => $messages,
            'user' => $user
        ]);

    } else {
        throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
