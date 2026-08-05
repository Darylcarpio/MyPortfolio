<?php
// api/education.php - Education CRUD API
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

try {
    if ($method === 'POST') {
        if ($action === 'add') {
            // Add new education record
            $data = [
                'year_range' => $_POST['year'] ?? '',
                'title' => $_POST['title'] ?? '',
                'school' => $_POST['school'] ?? '',
                'description' => $_POST['description'] ?? '',
                'sort_order' => (int)($_POST['sort_order'] ?? 0)
            ];
            
            if (empty($data['year_range']) || empty($data['school'])) {
                throw new Exception('Year and School are required');
            }
            
            // Parse and sanitize achievements
            $achievements = [];
            if (!empty($_POST['achievements'])) {
                // Split by newlines (both actual and escaped)
                $raw_achievements = preg_split('/\r?\n|\r/', $_POST['achievements']);
                // Filter out empty lines and trim whitespace
                $achievements = array_filter(array_map('trim', $raw_achievements));
                // Sanitize each achievement
                $achievements = array_map(function($a) {
                    return htmlspecialchars($a, ENT_QUOTES, 'UTF-8');
                }, $achievements);
            }
            $data['achievements'] = $achievements;
            
            $id = $dal->addEducation($data);
            $dal->logActivity($_SESSION['admin_id'], 'education_add', 'education', $id, "Added: {$data['school']}");
            echo json_encode(['success' => true, 'message' => 'Education added successfully', 'id' => $id], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
            
            
            
        } elseif ($action === 'update') {
            // Update existing education record
            $id = $_POST['id'] ?? 0;
            if (!$id) throw new Exception('ID is required');
            
            $data = [
                'year_range' => $_POST['year'] ?? '',
                'title' => $_POST['title'] ?? '',
                'school' => $_POST['school'] ?? '',
                'description' => $_POST['description'] ?? '',
                'sort_order' => (int)($_POST['sort_order'] ?? 0)
            ];
            
            if (empty($data['year_range']) || empty($data['school'])) {
                throw new Exception('Year and School are required');
            }
            
            // Parse and sanitize achievements
            $achievements = [];
            if (!empty($_POST['achievements'])) {
                // Split by newlines (both actual and escaped)
                $raw_achievements = preg_split('/\r?\n|\r/', $_POST['achievements']);
                // Filter out empty lines and trim whitespace
                $achievements = array_filter(array_map('trim', $raw_achievements));
                // Sanitize each achievement
                $achievements = array_map(function($a) {
                    return htmlspecialchars($a, ENT_QUOTES, 'UTF-8');
                }, $achievements);
            }
            $data['achievements'] = $achievements;
            
            $dal->updateEducation($id, $data);
            $dal->logActivity($_SESSION['admin_id'], 'education_update', 'education', $id, "Updated: {$data['school']}");
            
            echo json_encode(['success' => true, 'message' => 'Education updated successfully'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        
        } elseif ($action === 'restore') {
            // Restore education from backup JSON
            $jsonData = $_POST['data'] ?? '';
            if (empty($jsonData)) throw new Exception('No backup data provided');
            
            $records = json_decode($jsonData, true);
            if (!is_array($records)) throw new Exception('Invalid backup format');
            
            // Delete all existing education records
            $existing = $dal->getEducation();
            foreach ($existing as $e) {
                $dal->deleteEducation($e['id']);
            }
            
            // Re-add from backup
            $count = 0;
            foreach ($records as $rec) {
                $data = [
                    'year_range' => $rec['year'] ?? '',
                    'title' => $rec['title'] ?? '',
                    'school' => $rec['school'] ?? '',
                    'description' => $rec['description'] ?? '',
                    'sort_order' => $count,
                    'achievements' => $rec['achievements'] ?? []
                ];
                $dal->addEducation($data);
                $count++;
            }
            
            $dal->logActivity($_SESSION['admin_id'], 'education_restore', 'education', null, "Restored {$count} education records from backup");
            echo json_encode(['success' => true, 'message' => "{$count} education records restored successfully"], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        }
        
    } elseif ($method === 'DELETE') {
        // Delete education record
        parse_str(file_get_contents("php://input"), $_DELETE);
        $id = $_DELETE['id'] ?? 0;
        
        if (!$id) throw new Exception('ID is required');
        
        $dal->deleteEducation($id);
        $dal->logActivity($_SESSION['admin_id'], 'education_delete', 'education', $id);
        echo json_encode(['success' => true, 'message' => 'Education deleted successfully'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        
    } else {
        throw new Exception('Invalid request method');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
}
?>
