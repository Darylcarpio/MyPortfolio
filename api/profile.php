<?php
// api/profile.php - Profile Update API
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

// Handle profile image upload
function handleProfileImageUpload() {
    if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
        // Check for PHP upload errors
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors = [
                UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
                UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit.',
                UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder on server.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION  => 'Upload blocked by a PHP extension.',
            ];
            $msg = $errors[$_FILES['profile_image']['error']] ?? 'Unknown upload error';
            throw new Exception($msg);
        }
        return '';
    }
    
    $file = $_FILES['profile_image'];
    $maxSize = 10 * 1024 * 1024; // 10MB
    
    if ($file['size'] > $maxSize) {
        throw new Exception('File size exceeds 10MB limit (' . round($file['size'] / 1024 / 1024, 1) . 'MB uploaded)');
    }
    
    $upload_dir = __DIR__ . '/../img/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . time() . '_' . uniqid() . '.' . strtolower($ext);
    $upload_path = $upload_dir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        throw new Exception('Failed to save image file');
    }
    
    return 'img/' . $filename;
}

$method = $_SERVER['REQUEST_METHOD'];

$action = $_POST['action'] ?? null;

try {
    if ($method === 'POST') {
        if ($action === 'restore') {
            // Restore profile from backup JSON
            $jsonData = $_POST['data'] ?? '';
            if (empty($jsonData)) throw new Exception('No backup data provided');
            
            $data = json_decode($jsonData, true);
            if (!is_array($data)) throw new Exception('Invalid backup format');
            
            $profileData = [
                'name' => $data['name'] ?? '',
                'title' => $data['title'] ?? '',
                'bio' => $data['bio'] ?? '',
                'vision' => $data['vision'] ?? '',
                'location' => $data['location'] ?? '',
                'email' => $data['email'] ?? '',
                'facebook' => $data['facebook'] ?? ''
            ];
            
            $dal->updateProfile($profileData);
            $dal->logActivity($_SESSION['admin_id'], 'profile_restore', 'profile', null, 'Restored profile from backup');
            echo json_encode(['success' => true, 'message' => 'Profile restored successfully']);
            
        } else {
            // Update profile with optional image upload
            // Try to upload new image if provided
            $uploadedImage = '';
            try {
                $uploadedImage = handleProfileImageUpload();
            } catch (Exception $e) {
                // No image uploaded or error - that's okay, we'll keep the existing one
            }
            
            $data = [
                'name' => $_POST['name'] ?? '',
                'title' => $_POST['title'] ?? '',
                'bio' => $_POST['bio'] ?? '',
                'vision' => $_POST['vision'] ?? '',
                'location' => $_POST['location'] ?? '',
                'email' => $_POST['email'] ?? '',
                'facebook' => $_POST['facebook'] ?? ''
            ];
            
            // Debug log - remove later
            error_log("Profile update data: " . json_encode($data));
            
            // Add image if uploaded
            if (!empty($uploadedImage)) {
                $data['profile_image'] = $uploadedImage;
            }
            
            if (empty($data['name']) || empty($data['email'])) {
                throw new Exception('Name and Email are required');
            }
            
            $dal->updateProfile($data);
            $dal->logActivity($_SESSION['admin_id'], 'profile_update', 'profile', null, "Updated: {$data['name']}");
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
        }
        
    } else {
        throw new Exception('Invalid request method');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
