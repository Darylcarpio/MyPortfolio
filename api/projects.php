<?php
// api/projects.php - Projects CRUD API with File Upload & Backup/Restore
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

// Handle file upload - returns relative path or empty string
function handleImageUpload() {
    if (!isset($_FILES['image'])) {
        return '';
    }

    $file = $_FILES['image'];

    // Check for PHP upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit (php.ini). Increase upload_max_filesize.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => '',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder on server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'Upload blocked by a PHP extension.',
        ];
        $msg = $errors[$file['error']] ?? 'Unknown upload error (code ' . $file['error'] . ')';
        if ($file['error'] === UPLOAD_ERR_NO_FILE) return ''; // no file selected
        throw new Exception($msg);
    }

    $maxSize = 30 * 1024 * 1024; // 30MB

    if ($file['size'] > $maxSize) {
        throw new Exception('File size exceeds 30MB limit (' . round($file['size'] / 1024 / 1024, 1) . 'MB uploaded)');
    }

    $uploadDir = __DIR__ . '/../img/projects/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'project_' . time() . '_' . uniqid() . ($ext ? '.' . $ext : '');
    $destPath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new Exception('Failed to upload file');
    }

    return 'img/projects/' . $filename;
}

// Handle multiple image uploads - returns array of paths
function handleMultipleImageUploads() {
    if (!isset($_FILES['images'])) {
        return [];
    }

    $files = $_FILES['images'];
    $uploadDir = __DIR__ . '/../img/projects/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $paths = [];
    $maxSize = 30 * 1024 * 1024; // 30MB per file

    // Handle single file or multiple files
    if (is_array($files['name'])) {
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue; // Skip failed uploads
            }

            if ($files['size'][$i] > $maxSize) {
                throw new Exception('File size exceeds 30MB limit');
            }

            $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
            $filename = 'project_' . time() . '_' . uniqid() . '_' . $i . ($ext ? '.' . $ext : '');
            $destPath = $uploadDir . $filename;

            if (move_uploaded_file($files['tmp_name'][$i], $destPath)) {
                $paths[] = 'img/projects/' . $filename;
            }
        }
    } else {
        // Single file upload
        if ($files['error'] !== UPLOAD_ERR_OK) {
            return [];
        }

        if ($files['size'] > $maxSize) {
            throw new Exception('File size exceeds 30MB limit');
        }

        $ext = pathinfo($files['name'], PATHINFO_EXTENSION);
        $filename = 'project_' . time() . '_' . uniqid() . ($ext ? '.' . $ext : '');
        $destPath = $uploadDir . $filename;

        if (move_uploaded_file($files['tmp_name'], $destPath)) {
            $paths[] = 'img/projects/' . $filename;
        }
    }

    return $paths;
}

try {
    if ($method === 'POST') {
        if ($action === 'add') {
            // Handle multiple image uploads
            $imagePaths = handleMultipleImageUploads();

            $data = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'link' => $_POST['link'] ?? '',
                'images' => $imagePaths,
                'status_id' => 1,
                'sort_order' => 0,
                'technologies' => []
            ];

            if (empty($data['title'])) {
                throw new Exception('Title is required');
            }

            $id = $dal->addProject($data);
            $dal->logActivity($_SESSION['admin_id'], 'project_add', 'projects', $id, "Added: {$data['title']}");
            echo json_encode(['success' => true, 'message' => 'Project added successfully', 'id' => $id]);

        } elseif ($action === 'update') {
            $id = $_POST['id'] ?? 0;
            if (!$id) throw new Exception('ID is required');

            // Fetch existing project data
            $existing = $dal->getProjects();
            $existingProject = null;
            foreach ($existing as $p) {
                if ($p['id'] == $id) {
                    $existingProject = $p;
                    break;
                }
            }

            // Handle multiple image uploads
            $imagePaths = handleMultipleImageUploads();

            // Handle existing images that weren't re-uploaded
            $existingImages = [];
            if (isset($_POST['existing_images'])) {
                $existingImages = json_decode($_POST['existing_images'], true);
                if (!is_array($existingImages)) {
                    $existingImages = [];
                }
            }

            // Log for debugging
            error_log("Update project $id: " . count($existingImages) . " existing images, " . count($imagePaths) . " new uploads");

            // Combine new uploads with existing images
            $imagePaths = array_merge($existingImages, $imagePaths);

            // If no images at all, keep existing
            if (empty($imagePaths) && $existingProject) {
                $imagePaths = $existingProject['images'] ?? [];
            }

            // Handle link - allow clearing by checking if field was submitted
            $link = $_POST['link'] ?? '';
            // Only preserve existing link if link field wasn't submitted at all
            if (!isset($_POST['link']) && $existingProject) {
                $link = $existingProject['link'] ?? '';
            }

            $data = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'link' => $link,
                'images' => $imagePaths,
                'status_id' => 1,
                'sort_order' => 0,
                'technologies' => []
            ];

            if (empty($data['title'])) {
                throw new Exception('Title is required');
            }

            $dal->updateProject($id, $data);
            $dal->logActivity($_SESSION['admin_id'], 'project_update', 'projects', $id, "Updated: {$data['title']} - Images: " . count($data['images']));
            echo json_encode(['success' => true, 'message' => 'Project updated successfully', 'image_count' => count($data['images'])]);
            
        } elseif ($action === 'restore') {
            // Restore projects from backup JSON
            $jsonData = $_POST['data'] ?? '';
            if (empty($jsonData)) throw new Exception('No backup data provided');
            
            $projects = json_decode($jsonData, true);
            if (!is_array($projects)) throw new Exception('Invalid backup format');
            
            // Delete all existing projects first
            $existing = $dal->getProjects();
            foreach ($existing as $p) {
                $dal->deleteProject($p['id']);
            }
            
            // Re-add from backup
            $count = 0;
            foreach ($projects as $p) {
                $data = [
                    'title' => $p['title'] ?? 'Untitled',
                    'description' => $p['description'] ?? '',
                    'image' => $p['image'] ?? '',
                    'status_id' => 1,
                    'sort_order' => $count,
                    'technologies' => []
                ];
                $dal->addProject($data);
                $count++;
            }
            
            $dal->logActivity($_SESSION['admin_id'], 'project_restore', 'projects', null, "Restored {$count} projects from backup");
            echo json_encode(['success' => true, 'message' => "{$count} projects restored successfully"]);
        }
        
    } elseif ($method === 'DELETE') {
        parse_str(file_get_contents("php://input"), $_DELETE);
        $id = $_DELETE['id'] ?? 0;
        
        if (!$id) throw new Exception('ID is required');
        
        // Delete associated image file
        $existing = $dal->getProjects();
        foreach ($existing as $p) {
            if ($p['id'] == $id && !empty($p['image'])) {
                $imgPath = __DIR__ . '/../' . $p['image'];
                if (file_exists($imgPath)) {
                    unlink($imgPath);
                }
                break;
            }
        }
        
        $dal->deleteProject($id);
        $dal->logActivity($_SESSION['admin_id'], 'project_delete', 'projects', $id);
        echo json_encode(['success' => true, 'message' => 'Project deleted successfully']);
        
    } else {
        throw new Exception('Invalid request method');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
