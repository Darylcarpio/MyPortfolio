<?php
/**
 * ASCOT Community - Reactions API
 * Handle emoji reactions on posts, comments, photos, stories
 */

header('Content-Type: application/json');
session_start();

require_once dirname(__FILE__) . '/../../config.php';

try {
    if (!isset($_SESSION['community_user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();
    $user_id = $_SESSION['community_user_id'];
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? 'add';

    // Valid reactions
    $valid_reactions = ['like', '❤️', '😂', '😮', '😢', '😡', '👍', '👏'];

    switch ($action) {
        // ─── ADD REACTION ───
        case 'add':
            if ($method !== 'POST') throw new Exception('Method not allowed');

            $reactable_type = $_POST['type'] ?? 'post'; // post, comment, photo, story
            $reactable_id = intval($_POST['id'] ?? 0);
            $reaction = $_POST['reaction'] ?? 'like';

            if (!in_array($reaction, $valid_reactions)) {
                throw new Exception('Invalid reaction type');
            }

            if ($reactable_id <= 0) throw new Exception('Invalid ID');

            // Check if reaction already exists
            $stmt = $conn->prepare("
                SELECT id FROM community_reactions
                WHERE user_id = ? AND reactable_type = ? AND reactable_id = ?
            ");
            $stmt->execute([$user_id, $reactable_type, $reactable_id]);

            if ($stmt->rowCount() > 0) {
                // Update existing reaction
                $stmt = $conn->prepare("
                    UPDATE community_reactions
                    SET reaction = ?
                    WHERE user_id = ? AND reactable_type = ? AND reactable_id = ?
                ");
                $stmt->execute([$reaction, $user_id, $reactable_type, $reactable_id]);
                $message = 'Reaction updated';
            } else {
                // Create new reaction
                $stmt = $conn->prepare("
                    INSERT INTO community_reactions (user_id, reactable_type, reactable_id, reaction)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$user_id, $reactable_type, $reactable_id, $reaction]);
                $message = 'Reaction added';
            }

            // Get updated count
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count FROM community_reactions
                WHERE reactable_type = ? AND reactable_id = ?
            ");
            $stmt->execute([$reactable_type, $reactable_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'message' => $message,
                'reaction_count' => $result['count']
            ]);
            break;

        // ─── REMOVE REACTION ───
        case 'remove':
            if ($method !== 'POST') throw new Exception('Method not allowed');

            $reactable_type = $_POST['type'] ?? 'post';
            $reactable_id = intval($_POST['id'] ?? 0);

            if ($reactable_id <= 0) throw new Exception('Invalid ID');

            $stmt = $conn->prepare("
                DELETE FROM community_reactions
                WHERE user_id = ? AND reactable_type = ? AND reactable_id = ?
            ");
            $stmt->execute([$user_id, $reactable_type, $reactable_id]);

            // Get updated count
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count FROM community_reactions
                WHERE reactable_type = ? AND reactable_id = ?
            ");
            $stmt->execute([$reactable_type, $reactable_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'message' => 'Reaction removed',
                'reaction_count' => $result['count']
            ]);
            break;

        // ─── GET REACTIONS ───
        case 'get':
            $reactable_type = $_GET['type'] ?? 'post';
            $reactable_id = intval($_GET['id'] ?? 0);

            if ($reactable_id <= 0) throw new Exception('Invalid ID');

            // Get reaction counts by type
            $stmt = $conn->prepare("
                SELECT reaction, COUNT(*) as count
                FROM community_reactions
                WHERE reactable_type = ? AND reactable_id = ?
                GROUP BY reaction
            ");
            $stmt->execute([$reactable_type, $reactable_id]);
            $reactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Check current user's reaction
            $stmt = $conn->prepare("
                SELECT reaction FROM community_reactions
                WHERE user_id = ? AND reactable_type = ? AND reactable_id = ?
            ");
            $stmt->execute([$user_id, $reactable_type, $reactable_id]);
            $user_reaction = $stmt->fetch(PDO::FETCH_ASSOC);

            $total_count = array_sum(array_column($reactions, 'count'));

            echo json_encode([
                'success' => true,
                'reactions' => $reactions,
                'user_reaction' => $user_reaction['reaction'] ?? null,
                'total_count' => $total_count
            ]);
            break;

        // ─── GET REACTIONS WHO REACTED ───
        case 'who_reacted':
            $reactable_type = $_GET['type'] ?? 'post';
            $reactable_id = intval($_GET['id'] ?? 0);
            $reaction_type = $_GET['reaction'] ?? null; // Optional filter

            if ($reactable_id <= 0) throw new Exception('Invalid ID');

            $query = "
                SELECT u.id, u.first_name, u.last_name, u.profile_picture, r.reaction
                FROM community_reactions r
                JOIN community_users u ON r.user_id = u.id
                WHERE r.reactable_type = ? AND r.reactable_id = ?
            ";
            $params = [$reactable_type, $reactable_id];

            if ($reaction_type) {
                $query .= " AND r.reaction = ?";
                $params[] = $reaction_type;
            }

            $query .= " ORDER BY r.created_at DESC LIMIT 50";

            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'users' => $users
            ]);
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
