<?php
/**
 * Community Module - Profile API Endpoint
 * GET/POST/PUT: /api/community/profile.php
 * Handles user profile operations
 */

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');

require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/CommunityAuth_class.php';

if (!class_exists('CommunityAuth')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'System not configured']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'get';

try {
    // Check if user is logged in
    if (!isset($_SESSION['community_logged_in']) || $_SESSION['community_logged_in'] !== true) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login first.']);
        exit;
    }

    $user_id = $_SESSION['community_user_id'];
    $auth = new CommunityAuth();

    switch ($action) {
        // ─── GET PROFILE ───
        case 'get':
            $user = $auth->getUserById($user_id);
            
            if (empty($user)) {
                throw new Exception('User not found');
            }

            $user = reset($user);
            echo json_encode([
                'success' => true,
                'user' => $user
            ]);
            break;

        // ─── UPDATE PROFILE (Complete Profile) ───
        case 'update':
            if ($method !== 'POST' && $method !== 'PUT') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            // Also accept regular form-encoded or multipart POSTs
            if (!$data) {
                $data = $_POST ?: [];
                if (!empty($_FILES['profile_image'])) {
                    $data['profile_image'] = $_FILES['profile_image'];
                }
            }

            if (!$data) {
                throw new Exception('Invalid request data');
            }

            $result = $auth->completeProfile($user_id, $data);
            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
            break;

        // ─── UPLOAD PROFILE PICTURE ───
        case 'upload-picture':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }

            if (!isset($_FILES['profile_image'])) {
                throw new Exception('No file uploaded');
            }

            $result = $auth->completeProfile($user_id, [
                'profile_image' => $_FILES['profile_image']
            ]);

            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
            break;

        // ─── CHECK PROFILE COMPLETION ───
        case 'check-completion':
            $user = $auth->getUserById($user_id);
            
            if (empty($user)) {
                throw new Exception('User not found');
            }

            $user = reset($user);
            $is_complete = !empty($user['sex_at_birth']) 
                        && !empty($user['date_of_birth']) 
                        && !empty($user['location_province'])
                        && !empty($user['department']);

            echo json_encode([
                'success' => true,
                'profile_complete' => $is_complete,
                'missing_fields' => $is_complete ? [] : $this->getMissingFields($user)
            ]);
            break;

        // ─── GET DEPARTMENTS ───
        case 'get-departments':
            try {
                $db = Database::getInstance();
                $stmt = $db->prepare("SELECT id, department_name FROM ascot_departments WHERE is_active = TRUE ORDER BY department_name ASC");
                $stmt->execute();
                $departments = $stmt->fetchAll();

                echo json_encode([
                    'success' => true,
                    'departments' => $departments
                ]);
            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to load departments: ' . $e->getMessage()
                ]);
            }
            break;

        default:
            throw new Exception('Unknown action: ' . $action);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
