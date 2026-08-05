<?php
/**
 * Friend Request Management API
 * POST: /api/community/friend-request.php
 * Actions: send, accept, decline, cancel, remove
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

    $current_user = $_SESSION['community_user_id'];
    $target_user = $_POST['user_id'] ?? null;
    $action = $_POST['action'] ?? 'send';

    if (!$target_user) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        exit();
    }

    if ($current_user == $target_user) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot add yourself']);
        exit();
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Check if target user exists
    $stmt = $conn->prepare("SELECT id FROM community_users WHERE id = ? AND role = 'user'");
    $stmt->execute([$target_user]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }

    switch ($action) {
        case 'send':
            // Check if already friends or request exists
            $stmt = $conn->prepare("
                SELECT id, status FROM community_friends
                WHERE (requester_id = ? AND receiver_id = ?) OR (requester_id = ? AND receiver_id = ?)
            ");
            $stmt->execute([$current_user, $target_user, $target_user, $current_user]);
            $existing = $stmt->fetch();

            if ($existing) {
                if ($existing['status'] === 'accepted') {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Already friends']);
                } elseif ($existing['status'] === 'pending') {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Request already sent']);
                }
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO community_friends (requester_id, receiver_id, status, created_at)
                    VALUES (?, ?, 'pending', NOW())
                ");
                $stmt->execute([$current_user, $target_user]);
                echo json_encode(['success' => true, 'message' => 'Friend request sent']);
            }
            break;

        case 'accept':
            $stmt = $conn->prepare("
                UPDATE community_friends 
                SET status = 'accepted'
                WHERE requester_id = ? AND receiver_id = ? AND status = 'pending'
            ");
            $stmt->execute([$target_user, $current_user]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Friend request accepted']);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Request not found']);
            }
            break;

        case 'decline':
            $stmt = $conn->prepare("
                DELETE FROM community_friends
                WHERE requester_id = ? AND receiver_id = ? AND status = 'pending'
            ");
            $stmt->execute([$target_user, $current_user]);
            echo json_encode(['success' => true, 'message' => 'Friend request declined']);
            break;

        case 'remove':
            $stmt = $conn->prepare("
                DELETE FROM community_friends
                WHERE (requester_id = ? AND receiver_id = ?) OR (requester_id = ? AND receiver_id = ?)
            ");
            $stmt->execute([$current_user, $target_user, $target_user, $current_user]);
            echo json_encode(['success' => true, 'message' => 'Friend removed']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
