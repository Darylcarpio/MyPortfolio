<?php
// config.php - Database Configuration and Connection Class
// Portfolio Website Database Management

$env = [];
if (file_exists(__DIR__ . '/.env')) {
    $parsedEnv = parse_ini_file(__DIR__ . '/.env', false, INI_SCANNER_RAW);
    if (is_array($parsedEnv)) {
        $env = $parsedEnv;
    }
}

// Database Configuration
define('DB_HOST', $env['DB_HOST'] ?? 'localhost');
define('DB_NAME', $env['DB_NAME'] ?? 'db_portfolio');
define('DB_USER', $env['DB_USER'] ?? 'root');
define('DB_PASS', $env['DB_PASS'] ?? '');
define('DB_CHARSET', 'utf8mb4');

define('ENVIRONMENT', $env['ENVIRONMENT'] ?? 'development');

// Application Settings
define('APP_NAME', 'Bryan Darryl Carpio Portfolio');
define('APP_VERSION', '2.0');

// Security Settings
define('HASH_ALGO', PASSWORD_DEFAULT);
define('SESSION_LIFETIME', 3600);
define('GUEST_SESSION_LIFETIME', 86400); // 24 hours for guest accounts

// Email Configuration (Gmail SMTP - Production Ready)
// IMPORTANT: Use environment variables or a separate config file for production
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_ENCRYPTION', 'tls');

// Load email credentials from environment or secure config
define('MAIL_USERNAME', $env['MAIL_USERNAME'] ?? '');
define('MAIL_PASSWORD', $env['MAIL_PASSWORD'] ?? '');

define('MAIL_FROM_NAME', 'Portfolio Website');
define('MAIL_FROM_ADDRESS', 'noreply@portfoliosite.local');

// Email Feature Toggle (true = real emails, false = test mode)
define('ENABLE_EMAIL_SENDING', true);

// Error Reporting (disable in production)
if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/error.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/error.log');
}

// Timezone
date_default_timezone_set('Asia/Manila');

// Database Connection Class
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Helper method for prepared statements
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    // Execute query with parameters
    public function query($sql, $params = []) {
        try {
            $stmt = $this->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Query failed: " . $e->getMessage());
        }
    }
    
    // Fetch single record
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    // Fetch multiple records
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    // Insert data and return last insert ID
    public function insert($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $this->connection->lastInsertId();
    }
    
    // Update data
    public function update($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    // Delete data
    public function delete($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    // Check if record exists
    public function exists($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn() > 0;
    }
    
    // Get last insert ID
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    // Begin transaction
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    // Commit transaction
    public function commit() {
        return $this->connection->commit();
    }
    
    // Rollback transaction
    public function rollback() {
        return $this->connection->rollback();
    }
}

// Portfolio Data Access Layer
class PortfolioDAL {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // ===== PROFILE METHODS =====
    public function getProfile() {
        $profile = $this->db->fetch("SELECT * FROM profile LIMIT 1");
        if ($profile) {
            // Normalize column names for backward compatibility
            if (isset($profile['full_name'])) {
                $profile['name'] = $profile['full_name'];
            }
            if (isset($profile['professional_title'])) {
                $profile['title'] = $profile['professional_title'];
            }
            if (isset($profile['bio'])) {
                $profile['vision'] = $profile['bio'];
            }
        }
        return $profile;
    }
    
    public function updateProfile($data) {
        // Build dynamic UPDATE statement based on whether image is included
        // Use correct column names: full_name, professional_title, bio
        if (!empty($data['profile_image'])) {
            $sql = "UPDATE profile SET full_name = ?, professional_title = ?, bio = ?, location = ?, email = ?, facebook = ?, profile_image = ?, updated_at = NOW() WHERE id = 1";
            return $this->db->update($sql, [
                $data['name'] ?? $data['full_name'],
                $data['title'] ?? $data['professional_title'],
                $data['bio'] ?? $data['vision'],
                $data['location'],
                $data['email'],
                $data['facebook'] ?? '',
                $data['profile_image']
            ]);
        } else {
            $sql = "UPDATE profile SET full_name = ?, professional_title = ?, bio = ?, location = ?, email = ?, facebook = ?, updated_at = NOW() WHERE id = 1";
            return $this->db->update($sql, [
                $data['name'] ?? $data['full_name'],
                $data['title'] ?? $data['professional_title'],
                $data['bio'] ?? $data['vision'],
                $data['location'],
                $data['email'],
                $data['facebook'] ?? ''
            ]);
        }
    }
    
    // ===== EDUCATION METHODS =====
    public function getEducation() {
        $sql = "SELECT e.*, GROUP_CONCAT(ea.achievement SEPARATOR '|') as achievements 
                FROM education e 
                LEFT JOIN education_achievements ea ON e.id = ea.education_id 
                GROUP BY e.id 
                ORDER BY e.sort_order";
        return $this->db->fetchAll($sql);
    }
    
    public function addEducation($data) {
        $sql = "INSERT INTO education (year_range, title, school, description, sort_order) VALUES (?, ?, ?, ?, ?)";
        $id = $this->db->insert($sql, [$data['year_range'], $data['title'], $data['school'], $data['description'], $data['sort_order']]);
        
        // Add achievements if provided
        if (!empty($data['achievements'])) {
            foreach ($data['achievements'] as $achievement) {
                $this->db->insert("INSERT INTO education_achievements (education_id, achievement) VALUES (?, ?)", [$id, $achievement]);
            }
        }
        
        return $id;
    }
    
    public function updateEducation($id, $data) {
        $sql = "UPDATE education SET year_range = ?, title = ?, school = ?, description = ?, sort_order = ? WHERE id = ?";
        $this->db->update($sql, [$data['year_range'], $data['title'], $data['school'], $data['description'], $data['sort_order'], $id]);
        
        // Update achievements
        $this->db->delete("DELETE FROM education_achievements WHERE education_id = ?", [$id]);
        if (!empty($data['achievements'])) {
            foreach ($data['achievements'] as $achievement) {
                $this->db->insert("INSERT INTO education_achievements (education_id, achievement) VALUES (?, ?)", [$id, $achievement]);
            }
        }
        
        return true;
    }
    
    public function deleteEducation($id) {
        return $this->db->delete("DELETE FROM education WHERE id = ?", [$id]);
    }
    
    // ===== PROJECTS METHODS =====
    public function getProjects() {
        $sql = "SELECT p.*, ps.status_name,
                        GROUP_CONCAT(DISTINCT t.tech_name SEPARATOR '|') as technologies 
                FROM projects p 
                LEFT JOIN project_status ps ON p.status_id = ps.id
                LEFT JOIN project_technologies pt ON p.id = pt.project_id
                LEFT JOIN technologies t ON pt.technology_id = t.id
                GROUP BY p.id 
                ORDER BY p.sort_order";
        return $this->db->fetchAll($sql);
    }
    
    public function addProject($data) {
        $sql = "INSERT INTO projects (title, description, image, status_id, sort_order) VALUES (?, ?, ?, ?, ?)";
        $id = $this->db->insert($sql, [$data['title'], $data['description'], $data['image'], $data['status_id'], $data['sort_order']]);
        
        // Add technologies if provided
        if (!empty($data['technologies'])) {
            foreach ($data['technologies'] as $tech) {
                // Check if technology exists
                $tech_id = $this->db->fetch("SELECT id FROM technologies WHERE tech_name = ?", [$tech]);
                if (!$tech_id) {
                    // Create new technology
                    $tech_id = $this->db->insert("INSERT INTO technologies (tech_name) VALUES (?)", [$tech]);
                } else {
                    $tech_id = $tech_id['id'];
                }
                $this->db->insert("INSERT INTO project_technologies (project_id, technology_id) VALUES (?, ?)", [$id, $tech_id]);
            }
        }
        
        return $id;
    }
    
    public function updateProject($id, $data) {
        $sql = "UPDATE projects SET title = ?, description = ?, image = ?, status_id = ?, sort_order = ? WHERE id = ?";
        $this->db->update($sql, [$data['title'], $data['description'], $data['image'], $data['status_id'], $data['sort_order'], $id]);
        
        // Update technologies
        $this->db->delete("DELETE FROM project_technologies WHERE project_id = ?", [$id]);
        if (!empty($data['technologies'])) {
            foreach ($data['technologies'] as $tech) {
                $tech_id = $this->db->fetch("SELECT id FROM technologies WHERE tech_name = ?", [$tech]);
                if (!$tech_id) {
                    $tech_id = $this->db->insert("INSERT INTO technologies (tech_name) VALUES (?)", [$tech]);
                } else {
                    $tech_id = $tech_id['id'];
                }
                $this->db->insert("INSERT INTO project_technologies (project_id, technology_id) VALUES (?, ?)", [$id, $tech_id]);
            }
        }
        
        return true;
    }
    
    public function deleteProject($id) {
        return $this->db->delete("DELETE FROM projects WHERE id = ?", [$id]);
    }
    
    // ===== HOBBIES METHODS =====
    public function getHobbies() {
        $sql = "SELECT hc.id as category_id, hc.category_name, hc.display_name, hc.category_icon,
                        hi.id as item_id, hi.item_name, hi.image, hi.audio_file, hi.sort_order as item_sort
                FROM hobby_categories hc 
                LEFT JOIN hobby_items hi ON hc.id = hi.category_id 
                ORDER BY hc.sort_order, hi.sort_order";
        return $this->db->fetchAll($sql);
    }
    
    public function getHobbyCategories() {
        return $this->db->fetchAll("SELECT * FROM hobby_categories ORDER BY sort_order");
    }

    public function addHobbyCategory($data) {
        // $data should contain: category_name, display_name, category_icon, sort_order
        $sql = "INSERT INTO hobby_categories (category_name, display_name, category_icon, sort_order) VALUES (?, ?, ?, ?)";
        return $this->db->insert($sql, [
            $data['category_name'], 
            $data['display_name'], 
            $data['category_icon'] ?? '⭐',
            $data['sort_order'] ?? 0
        ]);
    }

    public function updateHobbyCategory($id, $data) {
        $sql = "UPDATE hobby_categories SET display_name = ?, category_icon = ?, sort_order = ? WHERE id = ?";
        return $this->db->update($sql, [
            $data['display_name'],
            $data['category_icon'] ?? '⭐',
            $data['sort_order'] ?? 0,
            $id
        ]);
    }

    public function deleteHobbyCategory($id) {
        return $this->db->delete("DELETE FROM hobby_categories WHERE id = ?", [$id]);
    }
    
    public function addHobbyItem($data) {
        $sql = "INSERT INTO hobby_items (category_id, item_name, image, audio_file, sort_order) VALUES (?, ?, ?, ?, ?)";
        return $this->db->insert($sql, [
            $data['category_id'], 
            $data['item_name'], 
            $data['image'] ?? '',
            $data['audio_file'] ?? '',
            $data['sort_order'] ?? 0
        ]);
    }
    
    public function updateHobbyItem($id, $data) {
        $sql = "UPDATE hobby_items SET category_id = ?, item_name = ?, image = ?, audio_file = ?, sort_order = ? WHERE id = ?";
        return $this->db->update($sql, [
            $data['category_id'], 
            $data['item_name'], 
            $data['image'] ?? '',
            $data['audio_file'] ?? '',
            $data['sort_order'] ?? 0,
            $id
        ]);
    }
    
    public function updateHobbyItemImage($id, $image) {
        $sql = "UPDATE hobby_items SET image = ? WHERE id = ?";
        return $this->db->update($sql, [$image, $id]);
    }
    
    public function deleteHobbyItem($id) {
        return $this->db->delete("DELETE FROM hobby_items WHERE id = ?", [$id]);
    }
    
    // ===== CONTACT METHODS =====
    public function getContactInfo() {
        $sql = "SELECT ci.*, ct.type_name, ct.icon 
                FROM contact_info ci 
                LEFT JOIN contact_types ct ON ci.type_id = ct.id 
                ORDER BY ci.sort_order";
        return $this->db->fetchAll($sql);
    }
    
    public function addContact($data) {
        $sql = "INSERT INTO contact_info (type_id, value, link, sort_order) VALUES (?, ?, ?, ?)";
        return $this->db->insert($sql, [$data['type_id'], $data['value'], $data['link'], $data['sort_order']]);
    }
    
    public function updateContact($id, $data) {
        $sql = "UPDATE contact_info SET type_id = ?, value = ?, link = ?, sort_order = ? WHERE id = ?";
        return $this->db->update($sql, [$data['type_id'], $data['value'], $data['link'], $data['sort_order'], $id]);
    }
    
    public function deleteContact($id) {
        return $this->db->delete("DELETE FROM contact_info WHERE id = ?", [$id]);
    }
    
    // ===== ISO 25010 FEEDBACK METHODS =====
    public function addFeedback($data) {
        $sql = "INSERT INTO iso_feedback (reviewer_name, functional_suitability, performance_efficiency, 
                compatibility, usability, reliability, security, maintainability, portability, 
                overall_rating, comments, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $ratings = [
            $data['dashboard_features'], $data['dashboard_performance'],
            $data['dashboard_compatibility'], $data['dashboard_usability'], $data['dashboard_reliability'],
            $data['dashboard_security'], $data['dashboard_maintainability'], $data['dashboard_portability']
        ];
        $overall = count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 1) : 0;
        return $this->db->insert($sql, [
            $data['reviewer_name'], $data['dashboard_features'], $data['dashboard_performance'],
            $data['dashboard_compatibility'], $data['dashboard_usability'], $data['dashboard_reliability'],
            $data['dashboard_security'], $data['dashboard_maintainability'], $data['dashboard_portability'],
            $overall, $data['comments'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }
    
    public function getFeedback($limit = 50) {
        return $this->db->fetchAll("SELECT * FROM iso_feedback ORDER BY created_at DESC LIMIT ?", [$limit]);
    }
    
    public function getFeedbackStats() {
        return $this->db->fetch("SELECT 
            COUNT(*) as total_reviews,
            ROUND(AVG(functional_suitability), 1) as avg_functional,
            ROUND(AVG(performance_efficiency), 1) as avg_performance,
            ROUND(AVG(compatibility), 1) as avg_compatibility,
            ROUND(AVG(usability), 1) as avg_usability,
            ROUND(AVG(reliability), 1) as avg_reliability,
            ROUND(AVG(security), 1) as avg_security,
            ROUND(AVG(maintainability), 1) as avg_maintainability,
            ROUND(AVG(portability), 1) as avg_portability,
            ROUND(AVG(overall_rating), 1) as avg_overall
            FROM iso_feedback");
    }
    
    // ===== ADMIN METHODS =====
    public function getAdminStats() {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM education) as education_count,
                    (SELECT COUNT(*) FROM projects) as projects_count,
                    (SELECT COUNT(*) FROM hobby_items) as hobbies_count,
                    (SELECT COUNT(*) FROM contact_info) as contact_count,
                    (SELECT COUNT(*) FROM activity_log WHERE DATE(created_at) = CURDATE()) as today_activities";
        return $this->db->fetch($sql);
    }
    
    public function logActivity($admin_id, $action, $table_name = null, $record_id = null, $details = null) {
        $sql = "INSERT INTO activity_log (admin_id, action, table_name, record_id, details, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        return $this->db->insert($sql, [
            $admin_id, $action, $table_name, $record_id, $details,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }
    
    public function getRecentActivity($limit = 10) {
        $sql = "SELECT al.*, au.username 
                FROM activity_log al 
                LEFT JOIN admin_users au ON al.admin_id = au.id 
                ORDER BY al.created_at DESC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }
    
    public function deleteActivity($id) {
        // Hard delete - remove record completely
        $sql = "DELETE FROM activity_log WHERE id = ?";
        return $this->db->update($sql, [$id]);
    }
    
    public function formatRelativeTime($timestamp) {
        $created = strtotime($timestamp);
        $now = time();
        $diff = $now - $created;
        
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . 'm ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . 'h ago';
        } elseif ($diff < 172800) {
            return 'Yesterday';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . 'd ago';
        } elseif ($diff < 2592000) {
            $weeks = floor($diff / 604800);
            return $weeks . 'w ago';
        } else {
            return date('M d, Y', $created);
        }
    }
    
    public function getActivityIcon($action) {
        $icons = [
            'profile_update' => '✏️',
            'profile_view' => '👁️',
            'education_add' => '🎓+',
            'education_edit' => '✏️',
            'education_delete' => '🗑️',
            'project_add' => '💻+',
            'project_edit' => '✏️',
            'project_delete' => '🗑️',
            'hobby_add' => '🎮+',
            'hobby_edit' => '✏️',
            'hobby_image' => '🖼️',
            'hobby_delete' => '🗑️',
            'contact_add' => '📱+',
            'contact_edit' => '✏️',
            'contact_delete' => '🗑️',
            'admin_login' => '🔓',
            'admin_logout' => '🔒',
            'failed_login' => '⚠️',
        ];
        return $icons[$action] ?? '📝';
    }
    
    public function getActivityLabel($action) {
        $labels = [
            'profile_update' => 'Profile Updated',
            'profile_view' => 'Portfolio Viewed',
            'education_add' => 'Education Added',
            'education_edit' => 'Education Edited',
            'education_delete' => 'Education Deleted',
            'project_add' => 'Project Added',
            'project_edit' => 'Project Edited',
            'project_delete' => 'Project Deleted',
            'hobby_add' => 'Hobby Added',
            'hobby_edit' => 'Hobby Edited',
            'hobby_image' => 'Hobby Image Updated',
            'hobby_delete' => 'Hobby Deleted',
            'contact_add' => 'Contact Added',
            'contact_edit' => 'Contact Edited',
            'contact_delete' => 'Contact Deleted',
            'admin_login' => 'Admin Login',
            'admin_logout' => 'Admin Logout',
            'failed_login' => 'Failed Login Attempt',
        ];
        return $labels[$action] ?? ucfirst(str_replace('_', ' ', $action));
    }
    
    public function getActivityColor($action) {
        if (strpos($action, 'add') !== false) return '#43d98c'; // Green
        if (strpos($action, 'delete') !== false) return '#f05454'; // Red
        if (strpos($action, 'view') !== false) return '#8b92c0'; // Gray
        if ($action === 'admin_login') return '#43d98c'; // Green - successful login
        if ($action === 'admin_logout') return '#f6b93b'; // Yellow - logout
        if ($action === 'failed_login') return '#f05454'; // Red - failed attempt
        return '#667eea'; // Blue (edit/update)
    }
    
    // ===== AUTHENTICATION METHODS =====
    public function authenticateAdmin($username, $password) {
        $sql = "SELECT id, username, email FROM admin_users WHERE username = ? AND password_hash = ?";
        return $this->db->fetch($sql, [$username, $password]);
    }
    
    public function getAdminById($id) {
        $sql = "SELECT id, username, email FROM admin_users WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }
    
    // ===== PAGE VIEW TRACKING METHODS =====
    public function logPageView($page = 'dashboard') {
        // Ensure page_views table exists
        try {
            $sql = "INSERT INTO page_views (page, ip_address, user_agent, referer) 
                    VALUES (?, ?, ?, ?)";
            return $this->db->insert($sql, [
                $page,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                $_SERVER['HTTP_REFERER'] ?? null
            ]);
        } catch (Exception $e) {
            // Table might not exist yet, silently fail
            error_log("Page view logging failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function getPageViewCount($page = 'dashboard') {
        try {
            $sql = "SELECT COUNT(*) as total FROM page_views WHERE page = ?";
            $result = $this->db->fetch($sql, [$page]);
            return $result ? $result['total'] : 0;
        } catch (Exception $e) {
            // Table might not exist yet, return 0
            error_log("Page view count failed: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getPageViewStats($page = 'dashboard', $days = 30) {
        try {
            $sql = "SELECT COUNT(*) as total, DATE(visited_at) as visit_date 
                    FROM page_views 
                    WHERE page = ? AND visited_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY DATE(visited_at)
                    ORDER BY visit_date DESC";
            return $this->db->fetchAll($sql, [$page, $days]);
        } catch (Exception $e) {
            error_log("Page view stats failed: " . $e->getMessage());
            return [];
        }
    }

    // ===== SNAPSHOT BACKUP/RESTORE METHODS =====
    
    /**
     * Save a snapshot of current data for a section.
     * Uses REPLACE INTO so only 1 snapshot per section exists.
     */
    public function saveSnapshot($section, $data) {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $sql = "REPLACE INTO data_snapshots (section, snapshot_data) VALUES (?, ?)";
        return $this->db->insert($sql, [$section, $json]);
    }
    
    /**
     * Get the saved snapshot for a section. Returns null if none exists.
     */
    public function getSnapshot($section) {
        $row = $this->db->fetch("SELECT snapshot_data, updated_at FROM data_snapshots WHERE section = ?", [$section]);
        if (!$row) return null;
        return [
            'data' => json_decode($row['snapshot_data'], true),
            'updated_at' => $row['updated_at']
        ];
    }
    
    /**
     * Check if a snapshot exists for a section.
     */
    public function hasSnapshot($section) {
        $row = $this->db->fetch("SELECT COUNT(*) as cnt FROM data_snapshots WHERE section = ?", [$section]);
        return ($row && $row['cnt'] > 0);
    }
    
    /**
     * Get current data for any section (used to auto-backup before restore).
     */
    public function getCurrentSectionData($section) {
        switch ($section) {
            case 'profile':
                return $this->getProfile();
            case 'education':
                $raw = $this->getEducation();
                return array_map(function($e) {
                    return [
                        'id' => $e['id'],
                        'year' => $e['year_range'],
                        'title' => $e['title'],
                        'school' => $e['school'],
                        'description' => $e['description'] ?? '',
                        'achievements' => !empty($e['achievements']) ? explode('|', $e['achievements']) : [],
                        'sort_order' => $e['sort_order'] ?? 0
                    ];
                }, $raw);
            case 'projects':
                $raw = $this->getProjects();
                return array_map(function($p) {
                    return [
                        'id' => $p['id'],
                        'title' => $p['title'],
                        'description' => $p['description'],
                        'image' => $p['image'] ?? '',
                        'status' => $p['status_name'] ?? '',
                        'status_id' => $p['status_id'] ?? 1,
                        'technologies' => !empty($p['technologies']) ? explode('|', $p['technologies']) : [],
                        'sort_order' => $p['sort_order'] ?? 0
                    ];
                }, $raw);
            case 'hobbies':
                return groupHobbiesByCategory($this->getHobbies());
            case 'contacts':
                $raw = $this->getContactInfo();
                return array_map(function($c) {
                    return [
                        'id' => $c['id'],
                        'type_id' => $c['type_id'],
                        'type' => $c['type_name'],
                        'icon' => $c['icon'],
                        'value' => $c['value'],
                        'link' => $c['link']
                    ];
                }, $raw);
            default:
                return null;
        }
    }
}

// ===== HELPER FUNCTIONS =====
function formatAchievements($achievements_string) {
    return $achievements_string ? explode('|', $achievements_string) : [];
}

function formatTechnologies($technologies_string) {
    return $technologies_string ? explode('|', $technologies_string) : [];
}

function groupHobbiesByCategory($hobbies) {
    $grouped = [];
    foreach ($hobbies as $hobby) {
        $category = $hobby['category_name'];
        if (!isset($grouped[$category])) {
            $grouped[$category] = [
                'category_id' => $hobby['category_id'],
                'category_name' => $hobby['category_name'],
                'display_name' => $hobby['display_name'],
                'category_icon' => $hobby['category_icon'],
                'items' => []
            ];
        }
        if ($hobby['item_id']) {
            $grouped[$category]['items'][] = [
                'id' => $hobby['item_id'],
                'name' => $hobby['item_name'],
                'image' => $hobby['image'],
                'audio_file' => $hobby['audio_file'],
                'sort_order' => $hobby['item_sort']
            ];
        }
    }
    return $grouped;
}

// Test Connection Function
function testDatabaseConnection() {
    try {
        $db = Database::getInstance();
        $result = $db->fetch("SELECT 1 as test");
        return $result && $result['test'] == 1;
    } catch (Exception $e) {
        error_log("Database connection test failed: " . $e->getMessage());
        return false;
    }
}
?>
