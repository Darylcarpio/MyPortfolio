<?php
/**
 * ASCOT Community - Stories API
 * Handle creating, viewing, and managing 24-hour temporary posts
 */

header('Content-Type: application/json');
session_start();

require_once dirname(__FILE__) . '/../../config.php';

try {
    // Check if user logged in
    if (!isset($_SESSION['community_user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();
    $user_id = $_SESSION['community_user_id'];
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? 'view';

    switch ($action) {
        // ─── CREATE STORY ───
        case 'create':
            if ($method !== 'POST') throw new Exception('Method not allowed');

            $content = trim($_POST['content'] ?? '');
            $privacy = in_array($_POST['privacy'] ?? 'public', ['public', 'friends', 'private']) ? $_POST['privacy'] : 'public';
            
            // Handle image upload
            $image_url = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = dirname(__FILE__) . '/../../img/stories/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = 'story_' . $user_id . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename);
                $image_url = 'img/stories/' . $filename;
            }

            if (empty($content) && !$image_url) {
                throw new Exception('Story must have content or image');
            }

            // Stories expire in 24 hours
            $expires_at = date('Y-m-d H:i:s', time() + 86400);

            $stmt = $conn->prepare("
                INSERT INTO community_stories (user_id, content, image_url, privacy, expires_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $content, $image_url, $privacy, $expires_at]);
            $story_id = $conn->lastInsertId();

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Story created successfully',
                'story_id' => $story_id
            ]);
            break;

        // ─── VIEW STORIES ───
        case 'view':
            $filter = $_GET['filter'] ?? 'all'; // all, friends, own
            
            // Clean up expired stories
            $conn->exec("UPDATE community_stories SET is_expired = TRUE WHERE expires_at < NOW()");
            
            if ($filter === 'own') {
                // Get current user's stories
                $stmt = $conn->prepare("
                    SELECT s.*, 
                        u.first_name, u.last_name, u.profile_picture,
                        COUNT(DISTINCT r.id) as reactions_count
                    FROM community_stories s
                    JOIN community_users u ON s.user_id = u.id
                    LEFT JOIN community_reactions r ON r.reactable_type = 'story' AND r.reactable_id = s.id
                    WHERE s.user_id = ? AND s.is_expired = FALSE
                    GROUP BY s.id
                    ORDER BY s.created_at DESC
                    LIMIT 50
                ");
                $stmt->execute([$user_id]);
            } else {
                // Get stories from friends and public
                $stmt = $conn->prepare("
                    SELECT s.*, 
                        u.first_name, u.last_name, u.profile_picture,
                        COUNT(DISTINCT r.id) as reactions_count
                    FROM community_stories s
                    JOIN community_users u ON s.user_id = u.id
                    LEFT JOIN community_reactions r ON r.reactable_type = 'story' AND r.reactable_id = s.id
                    WHERE s.is_expired = FALSE
                    AND (s.privacy = 'public' OR (s.privacy = 'friends' AND s.user_id IN (
                        SELECT receiver_id FROM community_friends WHERE requester_id = ? AND status = 'accepted'
                        UNION
                        SELECT requester_id FROM community_friends WHERE receiver_id = ? AND status = 'accepted'
                    )))
                    GROUP BY s.id
                    ORDER BY s.created_at DESC
                    LIMIT 50
                ");
                $stmt->execute([$user_id, $user_id]);
            }

            $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'stories' => $stories
            ]);
            break;

        // ─── DELETE STORY ───
        case 'delete':
            if ($method !== 'POST') throw new Exception('Method not allowed');

            $story_id = intval($_POST['story_id'] ?? 0);
            
            // Verify ownership
            $stmt = $conn->prepare("SELECT user_id FROM community_stories WHERE id = ?");
            $stmt->execute([$story_id]);
            $story = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$story || $story['user_id'] != $user_id) {
                throw new Exception('Cannot delete this story');
            }

            $stmt = $conn->prepare("DELETE FROM community_stories WHERE id = ?");
            $stmt->execute([$story_id]);

            echo json_encode(['success' => true, 'message' => 'Story deleted']);
            break;

        default:
            throw new Exception('Unknown action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
