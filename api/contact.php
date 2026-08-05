<?php
// api/contact.php - Contact Info CRUD API
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
            // Add new contact info
            $data = [
                'type_id' => (int)($_POST['type_id'] ?? 0),
                'value' => $_POST['value'] ?? '',
                'link' => $_POST['link'] ?? '',
                'sort_order' => (int)($_POST['sort_order'] ?? 0)
            ];
            
            if (!$data['type_id'] || empty($data['value'])) {
                throw new Exception('Type and Value are required');
            }
            
            $id = $dal->addContact($data);
            $dal->logActivity($_SESSION['admin_id'], 'contact_add', 'contact_info', $id, "Added: {$data['value']}");
            echo json_encode(['success' => true, 'message' => 'Contact info added successfully', 'id' => $id]);
            
        } elseif ($action === 'update') {
            // Update existing contact info
            $id = $_POST['id'] ?? 0;
            if (!$id) throw new Exception('ID is required');
            
            $data = [
                'type_id' => (int)($_POST['type_id'] ?? 0),
                'value' => $_POST['value'] ?? '',
                'link' => $_POST['link'] ?? '',
                'sort_order' => (int)($_POST['sort_order'] ?? 0)
            ];
            
            if (!$data['type_id'] || empty($data['value'])) {
                throw new Exception('Type and Value are required');
            }
            
            $dal->updateContact($id, $data);
            $dal->logActivity($_SESSION['admin_id'], 'contact_update', 'contact_info', $id, "Updated: {$data['value']}");
            echo json_encode(['success' => true, 'message' => 'Contact info updated successfully']);
        }
        
    } elseif ($action === 'restore') {
            // Restore contacts from backup JSON
            $jsonData = $_POST['data'] ?? '';
            if (empty($jsonData)) throw new Exception('No backup data provided');
            
            $records = json_decode($jsonData, true);
            if (!is_array($records)) throw new Exception('Invalid backup format');
            
            // Delete all existing contacts
            $existing = $dal->getContactInfo();
            foreach ($existing as $c) {
                $dal->deleteContact($c['id']);
            }
            
            // Re-add from backup
            $count = 0;
            foreach ($records as $rec) {
                $data = [
                    'type_id' => $rec['type_id'] ?? 1,
                    'value' => $rec['value'] ?? '',
                    'link' => $rec['link'] ?? '',
                    'sort_order' => $count
                ];
                $dal->addContact($data);
                $count++;
            }
            
            $dal->logActivity($_SESSION['admin_id'], 'contact_restore', 'contact_info', null, "Restored {$count} contact records from backup");
            echo json_encode(['success' => true, 'message' => "{$count} contact records restored successfully"]);
        }
        
    } elseif ($method === 'DELETE') {
        // Delete contact info
        parse_str(file_get_contents("php://input"), $_DELETE);
        $id = $_DELETE['id'] ?? 0;
        
        if (!$id) throw new Exception('ID is required');
        
        $dal->deleteContact($id);
        $dal->logActivity($_SESSION['admin_id'], 'contact_delete', 'contact_info', $id);
        echo json_encode(['success' => true, 'message' => 'Contact info deleted successfully']);
        
    } else {
        throw new Exception('Invalid request method');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
