<?php
/**
 * ASCOT Community - Photos & Albums API
 * Handle photo uploads, albums management, and gallery operations
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
    $action = $_GET['action'] ?? 'list_albums';

    switch ($action) {
        // ─── CREATE ALBUM ───
        case 'create_album':
            if ($method !== 'POST') throw new Exception('Method not allowed');

            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $privacy = in_array($_POST['privacy'] ?? 'public', ['public', 'friends', 'private']) ? $_POST['privacy'] : 'public';

            if (empty($title)) throw new Exception('Album title required');

            $stmt = $conn->prepare("
                INSERT INTO community_photo_albums (user_id, title, description, privacy)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $title, $description, $privacy]);
            $album_id = $conn->lastInsertId();

            echo json_encode([
                'success' => true,
                'message' => 'Album created',
                'album_id' => $album_id
            ]);
            break;

        // ─── LIST ALBUMS ───
        case 'list_albums':
            $target_user_id = intval($_GET['user_id'] ?? $user_id);

            if ($target_user_id == $user_id) {
                // Show all own albums
                $stmt = $conn->prepare("
                    SELECT * FROM community_photo_albums
                    WHERE user_id = ?
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$user_id]);
            } else {
                // Show only public/visible albums
                $stmt = $conn->prepare("
                    SELECT * FROM community_photo_albums
                    WHERE user_id = ? AND privacy = 'public'
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$target_user_id]);
            }

            $albums = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'albums' => $albums
            ]);
            break;

        // ─── UPLOAD PHOTO ───
        case 'upload':
            if ($method !== 'POST') throw new Exception('Method not allowed');
            if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Photo upload failed');
            }

            $album_id = intval($_POST['album_id'] ?? 0);
            if ($album_id > 0) {
                // Verify album ownership
                $stmt = $conn->prepare("SELECT user_id FROM community_photo_albums WHERE id = ?");
                $stmt->execute([$album_id]);
                $album = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$album || $album['user_id'] != $user_id) {
                    throw new Exception('Album not found or unauthorized');
                }
            }

            $caption = trim($_POST['caption'] ?? '');
            $privacy = in_array($_POST['privacy'] ?? 'public', ['public', 'friends', 'private']) ? $_POST['privacy'] : 'public';

            // Save photo
            $upload_dir = dirname(__FILE__) . '/../../img/photos/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = 'photo_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $ext;
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $filename)) {
                throw new Exception('Failed to save photo');
            }

            $image_url = 'img/photos/' . $filename;

            $stmt = $conn->prepare("
                INSERT INTO community_photos (album_id, user_id, image_url, caption, privacy)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$album_id ?: null, $user_id, $image_url, $caption, $privacy]);
            $photo_id = $conn->lastInsertId();

            // Update album cover and count
            if ($album_id > 0) {
                $conn->exec("
                    UPDATE community_photo_albums 
                    SET cover_image = '$image_url',
                        photos_count = (SELECT COUNT(*) FROM community_photos WHERE album_id = $album_id)
                    WHERE id = $album_id
                ");
            }

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Photo uploaded',
                'photo_id' => $photo_id,
                'image_url' => $image_url
            ]);
            break;

        // ─── GET ALBUM PHOTOS ───
        case 'get_album':
            $album_id = intval($_GET['album_id'] ?? 0);

            $stmt = $conn->prepare("
                SELECT p.*, u.first_name, u.last_name
                FROM community_photos p
                JOIN community_users u ON p.user_id = u.id
                WHERE p.album_id = ?
                ORDER BY p.created_at DESC
            ");
            $stmt->execute([$album_id]);
            $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'photos' => $photos
            ]);
            break;

        // ─── DELETE PHOTO ───
        case 'delete':
            if ($method !== 'POST') throw new Exception('Method not allowed');

            $photo_id = intval($_POST['photo_id'] ?? 0);

            // Verify ownership
            $stmt = $conn->prepare("SELECT user_id, album_id FROM community_photos WHERE id = ?");
            $stmt->execute([$photo_id]);
            $photo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$photo || $photo['user_id'] != $user_id) {
                throw new Exception('Cannot delete this photo');
            }

            $stmt = $conn->prepare("DELETE FROM community_photos WHERE id = ?");
            $stmt->execute([$photo_id]);

            // Update album count
            if ($photo['album_id']) {
                $conn->exec("
                    UPDATE community_photo_albums 
                    SET photos_count = (SELECT COUNT(*) FROM community_photos WHERE album_id = {$photo['album_id']})
                    WHERE id = {$photo['album_id']}
                ");
            }

            echo json_encode(['success' => true, 'message' => 'Photo deleted']);
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
