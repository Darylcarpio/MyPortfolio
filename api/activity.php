<?php
// api/activity.php - Activity Log Management API
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
    if ($method === 'DELETE' || ($method === 'POST' && $action === 'delete')) {
        // Delete (soft delete) activity
        $id = $_POST['id'] ?? $_GET['id'] ?? null;
        
        // Parse from body if DELETE request
        if ($method === 'DELETE') {
            parse_str(file_get_contents("php://input"), $_DELETE);
            $id = $_DELETE['id'] ?? null;
        }
        
        if (!$id) {
            throw new Exception('Activity ID is required');
        }
        
        $dal->deleteActivity($id);
        echo json_encode(['success' => true, 'message' => 'Activity deleted successfully']);
        
    } else {
        throw new Exception('Invalid request method or action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
