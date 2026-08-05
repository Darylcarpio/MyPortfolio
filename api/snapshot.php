<?php
// api/snapshot.php - Server-side Backup/Restore Snapshot API

// Suppress all output before headers
error_reporting(0);
ini_set('display_errors', '0');
ob_start();

// Start session FIRST, then set header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// NOW set header
ob_end_clean();
ob_start();
header('Content-Type: application/json');

// Check admin authorization
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../config.php';

// Auto-initialize backup system
try {
    $autoDb = Database::getInstance();
    $backupTableCheck = $autoDb->fetchAll("SHOW TABLES LIKE 'data_snapshots'");
    if (empty($backupTableCheck)) {
        $createSQL = "CREATE TABLE IF NOT EXISTS `data_snapshots` (
            `section` VARCHAR(100) PRIMARY KEY,
            `snapshot_data` LONGTEXT NOT NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $autoDb->query($createSQL);
    }
} catch (Exception $e) {
    // Continue - table might exist or will be created on next call
}

$dal = new PortfolioDAL();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? $_GET['action'] ?? null;
$section = $_POST['section'] ?? $_GET['section'] ?? null;

$valid_sections = ['profile', 'education', 'projects', 'hobbies', 'contacts'];

try {
    if (!$section || !in_array($section, $valid_sections)) {
        throw new Exception('Invalid section. Must be one of: ' . implode(', ', $valid_sections));
    }

    if ($method === 'POST' && $action === 'backup') {
        // ── BACKUP: Save current data as a snapshot ──
        $currentData = $dal->getCurrentSectionData($section);
        if ($currentData === null) {
            throw new Exception("Could not read current data for section: {$section}");
        }
        
        $dal->saveSnapshot($section, $currentData);
        $dal->logActivity($_SESSION['admin_id'], 'snapshot_backup', $section, null, "Backed up {$section} data");
        
        ob_end_clean();
        echo json_encode([
            'success' => true, 
            'message' => ucfirst($section) . ' backup saved successfully!'
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        exit;

    } elseif ($method === 'POST' && $action === 'restore') {
        // ── RESTORE: Load the saved snapshot back (backup stays intact) ──
        $snapshot = $dal->getSnapshot($section);
        if (!$snapshot || empty($snapshot['data'])) {
            throw new Exception("No backup found for {$section}. Please create a backup first.");
        }
        
        // Restore from the saved snapshot (does NOT overwrite the backup)
        $snapshotData = $snapshot['data'];
        restoreSectionData($dal, $section, $snapshotData);
        
        $dal->logActivity($_SESSION['admin_id'], 'snapshot_restore', $section, null, "Restored {$section} from snapshot");
        
        ob_end_clean();
        echo json_encode([
            'success' => true, 
            'message' => ucfirst($section) . ' restored successfully!'
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        exit;

    } elseif ($method === 'GET' && $action === 'status') {
        // ── STATUS: Check if a snapshot exists for this section ──
        $snapshot = $dal->getSnapshot($section);
        $hasBackup = $snapshot !== null;
        
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'has_backup' => $hasBackup,
            'backup_date' => $hasBackup ? $snapshot['updated_at'] : null
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        exit;

    } else {
        throw new Exception('Invalid action. Use: backup, restore, or status');
    }

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    exit;
}

/**
 * Restore data into a section from a snapshot array.
 */
function restoreSectionData($dal, $section, $data) {
    switch ($section) {
        case 'profile':
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
            break;

        case 'education':
            // Delete all existing education records
            $existing = $dal->getEducation();
            foreach ($existing as $e) {
                $dal->deleteEducation($e['id']);
            }
            // Re-add from snapshot
            $sort = 0;
            foreach ($data as $rec) {
                $eduData = [
                    'year_range' => $rec['year'] ?? '',
                    'title' => $rec['title'] ?? '',
                    'school' => $rec['school'] ?? '',
                    'description' => $rec['description'] ?? '',
                    'sort_order' => $rec['sort_order'] ?? $sort,
                    'achievements' => $rec['achievements'] ?? []
                ];
                $dal->addEducation($eduData);
                $sort++;
            }
            break;

        case 'projects':
            // Delete all existing projects
            $existing = $dal->getProjects();
            foreach ($existing as $p) {
                $dal->deleteProject($p['id']);
            }
            // Re-add from snapshot
            $sort = 0;
            foreach ($data as $rec) {
                $projData = [
                    'title' => $rec['title'] ?? '',
                    'description' => $rec['description'] ?? '',
                    'image' => $rec['image'] ?? '',
                    'status_id' => $rec['status_id'] ?? 1,
                    'sort_order' => $rec['sort_order'] ?? $sort,
                    'technologies' => $rec['technologies'] ?? []
                ];
                $dal->addProject($projData);
                $sort++;
            }
            break;

        case 'hobbies':
            // Delete all existing hobby items (not categories)
            $existingHobbies = $dal->getHobbies();
            $deletedIds = [];
            foreach ($existingHobbies as $h) {
                if (!empty($h['item_id']) && !in_array($h['item_id'], $deletedIds)) {
                    $dal->deleteHobbyItem($h['item_id']);
                    $deletedIds[] = $h['item_id'];
                }
            }
            
            // Build a lookup: category_name → actual DB category_id
            $catLookup = [];
            $existingCats = $dal->getHobbyCategories();
            foreach ($existingCats as $cat) {
                $catLookup[$cat['category_name']] = $cat['id'];
            }
            
            // Re-add items from snapshot, matching by category_name for safety
            foreach ($data as $category) {
                // Resolve category_id: prefer lookup by name, fallback to stored id
                $catName = $category['category_name'] ?? '';
                if (isset($catLookup[$catName])) {
                    $categoryId = $catLookup[$catName];
                } else {
                    $categoryId = $category['category_id'] ?? 1;
                }
                
                $items = $category['items'] ?? [];
                $sort = 0;
                foreach ($items as $item) {
                    $dal->addHobbyItem([
                        'category_id' => $categoryId,
                        'item_name' => $item['name'] ?? $item['item_name'] ?? 'Untitled',
                        'image' => $item['image'] ?? 'placeholder.jpg',
                        'sort_order' => $item['sort_order'] ?? $sort
                    ]);
                    $sort++;
                }
            }
            break;

        case 'contacts':
            // Delete all existing contacts
            $existing = $dal->getContactInfo();
            foreach ($existing as $c) {
                $dal->deleteContact($c['id']);
            }
            // Re-add from snapshot
            $sort = 0;
            foreach ($data as $rec) {
                $contactData = [
                    'type_id' => $rec['type_id'] ?? 1,
                    'value' => $rec['value'] ?? '',
                    'link' => $rec['link'] ?? '',
                    'sort_order' => $rec['sort_order'] ?? $sort
                ];
                $dal->addContact($contactData);
                $sort++;
            }
            break;
    }
}
?>
