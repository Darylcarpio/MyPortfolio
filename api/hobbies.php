<?php
// api/hobbies.php - Hobbies CRUD API with File Upload & Backup/Restore
header('Content-Type: application/json');
session_start();

// Check admin authorization
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../config.php';
$dal = new PortfolioDAL();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? $_GET['action'] ?? null;

// Handle GET requests for fetching data
if ($method === 'GET') {
    try {
        if ($action === 'categories') {
            // Return all hobby categories
            $categories = $dal->getHobbyCategories();
            echo json_encode(['success' => true, 'categories' => $categories]);
            exit();
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// Handle file upload - returns filename or empty string
function handleHobbyImageUpload() {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        // Check for PHP upload errors
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors = [
                UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
                UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit.',
                UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder on server.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION  => 'Upload blocked by a PHP extension.',
            ];
            $msg = $errors[$_FILES['image']['error']] ?? 'Unknown upload error';
            throw new Exception($msg);
        }
        return '';
    }
    
    $file = $_FILES['image'];
    $maxSize = 10 * 1024 * 1024; // 10MB
    
    if ($file['size'] > $maxSize) {
        throw new Exception('File size exceeds 10MB limit (' . round($file['size'] / 1024 / 1024, 1) . 'MB uploaded)');
    }
    
    // Accept all image types (no MIME restriction for admin flexibility)
    
    $upload_dir = __DIR__ . '/../img/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'hobby_' . time() . '_' . uniqid() . '.' . strtolower($ext);
    $upload_path = $upload_dir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        throw new Exception('Failed to save image file');
    }
    
    return $filename;
}

// Handle hobby audio file upload - returns filename or empty string
function handleHobbyAudioUpload() {
    if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
        // Check for PHP upload errors
        if (isset($_FILES['audio']) && $_FILES['audio']['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors = [
                UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
                UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit.',
                UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder on server.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION  => 'Upload blocked by a PHP extension.',
            ];
            $msg = $errors[$_FILES['audio']['error']] ?? 'Unknown upload error';
            throw new Exception($msg);
        }
        return '';
    }
    
    $file = $_FILES['audio'];
    $maxSize = 50 * 1024 * 1024; // 50MB for audio files
    
    if ($file['size'] > $maxSize) {
        throw new Exception('Audio file exceeds 50MB limit (' . round($file['size'] / 1024 / 1024, 1) . 'MB uploaded)');
    }
    
    // Validate audio file extensions
    $allowedExts = ['mp3', 'wav', 'm4a', 'aac', 'ogg', 'flac', 'wma'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowedExts)) {
        throw new Exception('Invalid audio format. Allowed: ' . implode(', ', $allowedExts));
    }
    
    $upload_dir = __DIR__ . '/../audio/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $filename = 'hobby_' . time() . '_' . uniqid() . '.' . $ext;
    $upload_path = $upload_dir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        throw new Exception('Failed to save audio file');
    }
    
    return $filename;
}

try {
    if ($method === 'POST') {
        if ($action === 'add_category') {
            // Add new hobby category
            $category_name = trim($_POST['category_name'] ?? '');
            $display_name = trim($_POST['display_name'] ?? '');
            $category_icon = trim($_POST['category_icon'] ?? '⭐');
            $sort_order = (int)($_POST['sort_order'] ?? 99);
            
            if (!$category_name || !$display_name) {
                throw new Exception('Category name and display name are required');
            }
            
            // Sanitize category name (lowercase, no special chars)
            $category_name = strtolower(preg_replace('/[^a-z0-9_-]/', '', str_replace(' ', '_', $category_name)));
            
            if (empty($category_name)) {
                throw new Exception('Category name is invalid');
            }
            
            $data = [
                'category_name' => $category_name,
                'display_name' => $display_name,
                'category_icon' => $category_icon,
                'sort_order' => $sort_order
            ];
            
            $id = $dal->addHobbyCategory($data);
            $dal->logActivity($_SESSION['admin_id'], 'category_add', 'hobby_categories', $id, "Added category: {$display_name}");
            echo json_encode(['success' => true, 'message' => 'Category added successfully', 'id' => $id]);
            
        } elseif ($action === 'update_category') {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('Category ID is required');
            
            $data = [
                'display_name' => trim($_POST['display_name'] ?? ''),
                'category_icon' => trim($_POST['category_icon'] ?? '⭐'),
                'sort_order' => (int)($_POST['sort_order'] ?? 0)
            ];
            
            if (empty($data['display_name'])) {
                throw new Exception('Display name is required');
            }
            
            $dal->updateHobbyCategory($id, $data);
            $dal->logActivity($_SESSION['admin_id'], 'category_update', 'hobby_categories', $id, "Updated: {$data['display_name']}");
            echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
            
        } elseif ($action === 'delete_category') {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('Category ID is required');
            
            // Check if category has items
            $items = $dal->db->fetchAll("SELECT COUNT(id) as count FROM hobby_items WHERE category_id = ?", [$id]);
            if (!empty($items) && $items[0]['count'] > 0) {
                throw new Exception('Cannot delete category with items. Delete items first.');
            }
            
            $dal->deleteHobbyCategory($id);
            $dal->logActivity($_SESSION['admin_id'], 'category_delete', 'hobby_categories', $id);
            echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
            
        } elseif ($action === 'add') {
            // Handle image upload
            $uploadedImage = handleHobbyImageUpload();
            
            // Handle audio upload (optional, for music categories)
            $uploadedAudio = handleHobbyAudioUpload();
            
            $data = [
                'category_id' => (int)($_POST['category_id'] ?? 0),
                'item_name' => $_POST['item_name'] ?? '',
                'image' => $uploadedImage ?: ($_POST['image'] ?? 'placeholder.jpg'),
                'audio_file' => $uploadedAudio,
                'sort_order' => (int)($_POST['sort_order'] ?? 0)
            ];
            
            if (!$data['category_id'] || empty($data['item_name'])) {
                throw new Exception('Category and Item Name are required');
            }
            
            $id = $dal->addHobbyItem($data);
            $dal->logActivity($_SESSION['admin_id'], 'hobby_add', 'hobbies', $id, "Added: {$data['item_name']}");
            echo json_encode(['success' => true, 'message' => 'Hobby added successfully', 'id' => $id]);
            
        } elseif ($action === 'update') {
            $id = $_POST['id'] ?? 0;
            if (!$id) throw new Exception('ID is required');
            
            // Handle audio upload (optional, for music categories)
            $uploadedAudio = handleHobbyAudioUpload();
            
            $data = [
                'category_id' => (int)($_POST['category_id'] ?? 0),
                'item_name' => $_POST['item_name'] ?? '',
                'image' => $_POST['image'] ?? 'placeholder.jpg',
                'sort_order' => (int)($_POST['sort_order'] ?? 0)
            ];
            
            // Only add audio_file if one was uploaded
            if ($uploadedAudio) {
                $data['audio_file'] = $uploadedAudio;
            }
            
            if (!$data['category_id'] || empty($data['item_name'])) {
                throw new Exception('Category and Item Name are required');
            }
            
            $dal->updateHobbyItem($id, $data);
            $dal->logActivity($_SESSION['admin_id'], 'hobby_update', 'hobbies', $id, "Updated: {$data['item_name']}");
            echo json_encode(['success' => true, 'message' => 'Hobby updated successfully']);
            
        } elseif ($action === 'update_image') {
            $id = $_POST['id'] ?? 0;
            if (!$id) throw new Exception('ID is required');
            
            $uploadedImage = handleHobbyImageUpload();
            if (empty($uploadedImage)) {
                throw new Exception('No image file provided or upload error');
            }
            
            $dal->updateHobbyItemImage($id, $uploadedImage);
            $dal->logActivity($_SESSION['admin_id'], 'hobby_image', 'hobbies', $id, "Image: $uploadedImage");
            
            echo json_encode(['success' => true, 'message' => 'Image updated successfully', 'filename' => $uploadedImage]);
            
        } elseif ($action === 'update_details') {
            // Update hobby name and optionally image and audio
            $id = $_POST['id'] ?? 0;
            if (!$id) throw new Exception('ID is required');
            
            $itemName = $_POST['item_name'] ?? '';
            $categoryId = (int)($_POST['category_id'] ?? 0);
            
            if (empty($itemName)) {
                throw new Exception('Item name is required');
            }
            
            // Try to upload new image if provided
            $uploadedImage = '';
            try {
                $uploadedImage = handleHobbyImageUpload();
            } catch (Exception $e) {
                // No image uploaded or error - that's okay, we'll keep the existing one
            }
            
            // Try to upload new audio if provided
            $uploadedAudio = '';
            try {
                $uploadedAudio = handleHobbyAudioUpload();
            } catch (Exception $e) {
                // No audio uploaded or error - that's okay, we'll keep the existing one
            }
            
            // Get existing values
            $db = Database::getInstance();
            $existing = $db->fetch("SELECT image, audio_file, sort_order FROM hobby_items WHERE id = ?", [$id]);
            
            // Build update data
            $data = [
                'category_id' => $categoryId,
                'item_name' => $itemName,
                'image' => $uploadedImage ?: ($existing['image'] ?? 'placeholder.jpg'),
                'sort_order' => $existing['sort_order'] ?? 0
            ];
            
            // Add audio if provided
            if ($uploadedAudio) {
                $data['audio_file'] = $uploadedAudio;
            }
            
            $dal->updateHobbyItem($id, $data);
            
            $dal->logActivity($_SESSION['admin_id'], 'hobby_update', 'hobbies', $id, "Updated: {$itemName}");
            echo json_encode(['success' => true, 'message' => 'Hobby updated successfully']);
            
        } elseif ($action === 'restore') {
            // Restore hobbies from backup JSON
            $jsonData = $_POST['data'] ?? '';
            if (empty($jsonData)) throw new Exception('No backup data provided');
            
            $categories = json_decode($jsonData, true);
            if (!is_array($categories)) throw new Exception('Invalid backup format');
            
            // Delete all existing hobby items first
            $existing = $dal->getHobbies();
            $deletedIds = [];
            foreach ($existing as $h) {
                if (!empty($h['item_id']) && !in_array($h['item_id'], $deletedIds)) {
                    $dal->deleteHobbyItem($h['item_id']);
                    $deletedIds[] = $h['item_id'];
                }
            }
            
            // Re-add from backup
            $count = 0;
            foreach ($categories as $cat) {
                $categoryId = $cat['category_id'] ?? 1;
                $items = $cat['items'] ?? [];
                foreach ($items as $item) {
                    $data = [
                        'category_id' => $categoryId,
                        'item_name' => $item['name'] ?? 'Untitled',
                        'image' => $item['image'] ?? 'placeholder.jpg',
                        'sort_order' => $count
                    ];
                    $dal->addHobbyItem($data);
                    $count++;
                }
            }
            
            $dal->logActivity($_SESSION['admin_id'], 'hobby_restore', 'hobbies', null, "Restored {$count} hobby items from backup");
            echo json_encode(['success' => true, 'message' => "{$count} hobby items restored successfully"]);
        }
        
    } elseif ($method === 'DELETE') {
        parse_str(file_get_contents("php://input"), $_DELETE);
        $id = $_DELETE['id'] ?? 0;
        
        if (!$id) throw new Exception('ID is required');
        
        $dal->deleteHobbyItem($id);
        $dal->logActivity($_SESSION['admin_id'], 'hobby_delete', 'hobbies', $id);
        echo json_encode(['success' => true, 'message' => 'Hobby deleted successfully']);
        
    } else {
        throw new Exception('Invalid request method');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
