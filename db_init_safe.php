<?php
/**
 * Safe Database Initialization for Free Hosting
 * Creates tables only if they don't exist (doesn't drop existing data)
 */

$env = [];
if (file_exists(__DIR__ . '/.env')) {
    $parsedEnv = parse_ini_file(__DIR__ . '/.env', false, INI_SCANNER_RAW);
    if (is_array($parsedEnv)) {
        $env = $parsedEnv;
    }
}

$host = $env['DB_HOST'] ?? 'localhost';
$user = $env['DB_USER'] ?? 'root';
$pass = $env['DB_PASS'] ?? '';
$db = $env['DB_NAME'] ?? 'db_portfolio';

try {
    $pdo = new PDO(
        "mysql:host=$host;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<h2>🔧 Safe Database Initialization</h2>";
    echo "<hr>";

    // CREATE database if not exists
    echo "Checking database...<br>";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db`");
    echo "✓ Database ready<br><hr>";

    // Admin Users
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100),
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL,
        is_active BOOLEAN DEFAULT 1
    )");
    echo "✓ admin_users table<br>";

    // Profile
    $pdo->exec("CREATE TABLE IF NOT EXISTS profile (
        id INT PRIMARY KEY DEFAULT 1,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        phone VARCHAR(20),
        location VARCHAR(100),
        professional_title VARCHAR(100),
        bio TEXT,
        facebook VARCHAR(255),
        profile_image VARCHAR(255),
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "✓ profile table<br>";

    // Education
    $pdo->exec("CREATE TABLE IF NOT EXISTS education (
        id INT AUTO_INCREMENT PRIMARY KEY,
        year_range VARCHAR(20),
        title VARCHAR(100),
        school VARCHAR(100),
        description TEXT,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "✓ education table<br>";

    // Education Achievements
    $pdo->exec("CREATE TABLE IF NOT EXISTS education_achievements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        education_id INT NOT NULL,
        achievement TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (education_id) REFERENCES education(id) ON DELETE CASCADE
    )");
    echo "✓ education_achievements table<br>";

    // Project Status
    $pdo->exec("CREATE TABLE IF NOT EXISTS project_status (
        id INT AUTO_INCREMENT PRIMARY KEY,
        status_name VARCHAR(50) UNIQUE NOT NULL
    )");
    echo "✓ project_status table<br>";

    // Projects
    $pdo->exec("CREATE TABLE IF NOT EXISTS projects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(100) NOT NULL,
        description TEXT,
        image VARCHAR(255),
        status_id INT DEFAULT 1,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (status_id) REFERENCES project_status(id)
    )");
    echo "✓ projects table<br>";

    // Technologies
    $pdo->exec("CREATE TABLE IF NOT EXISTS technologies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tech_name VARCHAR(100) UNIQUE NOT NULL
    )");
    echo "✓ technologies table<br>";

    // Project Technologies
    $pdo->exec("CREATE TABLE IF NOT EXISTS project_technologies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        technology_id INT NOT NULL,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
        FOREIGN KEY (technology_id) REFERENCES technologies(id) ON DELETE CASCADE,
        UNIQUE KEY unique_project_tech (project_id, technology_id)
    )");
    echo "✓ project_technologies table<br>";

    // Hobby Categories
    $pdo->exec("CREATE TABLE IF NOT EXISTS hobby_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(50) UNIQUE NOT NULL,
        display_name VARCHAR(100),
        category_icon VARCHAR(50),
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ hobby_categories table<br>";

    // Hobby Items
    $pdo->exec("CREATE TABLE IF NOT EXISTS hobby_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NOT NULL,
        item_name VARCHAR(100) NOT NULL,
        image VARCHAR(255),
        audio_file VARCHAR(255),
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES hobby_categories(id) ON DELETE CASCADE
    )");
    echo "✓ hobby_items table<br>";

    // Contact Types
    $pdo->exec("CREATE TABLE IF NOT EXISTS contact_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type_name VARCHAR(50) UNIQUE NOT NULL,
        icon VARCHAR(50),
        sort_order INT DEFAULT 0
    )");
    echo "✓ contact_types table<br>";

    // Contact Info
    $pdo->exec("CREATE TABLE IF NOT EXISTS contact_info (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type_id INT NOT NULL,
        value VARCHAR(255) NOT NULL,
        link VARCHAR(255),
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (type_id) REFERENCES contact_types(id) ON DELETE CASCADE
    )");
    echo "✓ contact_info table<br>";

    // Activity Log
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT,
        action VARCHAR(100) NOT NULL,
        table_name VARCHAR(50),
        record_id INT,
        details TEXT,
        ip_address VARCHAR(45),
        user_agent VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_admin_id (admin_id),
        INDEX idx_created_at (created_at),
        FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
    )");
    echo "✓ activity_log table<br>";

    // Page Views
    $pdo->exec("CREATE TABLE IF NOT EXISTS page_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page VARCHAR(100),
        ip_address VARCHAR(45),
        user_agent VARCHAR(255),
        referer VARCHAR(255),
        viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_page (page),
        INDEX idx_viewed_at (viewed_at)
    )");
    echo "✓ page_views table<br>";

    // ISO Feedback
    $pdo->exec("CREATE TABLE IF NOT EXISTS iso_feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reviewer_name VARCHAR(100),
        functional_suitability INT,
        performance_efficiency INT,
        compatibility INT,
        usability INT,
        reliability INT,
        security INT,
        feedback TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ iso_feedback table<br>";

    // Data Snapshots
    $pdo->exec("CREATE TABLE IF NOT EXISTS data_snapshots (
        section VARCHAR(100) PRIMARY KEY,
        snapshot_data LONGTEXT NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "✓ data_snapshots table<br>";

    // Community Users (for guest access)
    $pdo->exec("CREATE TABLE IF NOT EXISTS community_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) UNIQUE NOT NULL,
        username VARCHAR(50) UNIQUE NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        middle_name VARCHAR(100) DEFAULT NULL,
        last_name VARCHAR(100) DEFAULT NULL,
        suffix VARCHAR(20) DEFAULT NULL,
        phone_number VARCHAR(20) DEFAULT NULL,
        password_hash VARCHAR(255) NOT NULL,
        profile_image VARCHAR(255) DEFAULT NULL,
        role VARCHAR(30) DEFAULT 'user',
        account_type VARCHAR(30) DEFAULT 'guest',
        status VARCHAR(20) DEFAULT 'active',
        email_verified TINYINT(1) DEFAULT 1,
        email_verified_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "✓ community_users table<br>";

    // Community Profiles
    $pdo->exec("CREATE TABLE IF NOT EXISTS community_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        province VARCHAR(100) DEFAULT NULL,
        municipality VARCHAR(100) DEFAULT NULL,
        barangay VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_profile (user_id),
        CONSTRAINT fk_community_profiles_user FOREIGN KEY (user_id) REFERENCES community_users(id) ON DELETE CASCADE
    )");
    echo "✓ community_profiles table<br>";

    // Community Session Logs
    $pdo->exec("CREATE TABLE IF NOT EXISTS community_session_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        ip_address VARCHAR(45) DEFAULT NULL,
        user_agent VARCHAR(255) DEFAULT NULL,
        status VARCHAR(20) DEFAULT 'success',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_community_session_user (user_id),
        INDEX idx_community_session_action (action),
        CONSTRAINT fk_community_session_user FOREIGN KEY (user_id) REFERENCES community_users(id) ON DELETE CASCADE
    )");
    echo "✓ community_session_logs table<br>";

    echo "<hr>";
    echo "<h3>✓ All tables created or already exist!</h3>";

    // Insert Default Data (only if tables are empty)
    echo "<h3>📋 Checking Default Data...</h3>";

    // Check if admin exists
    $admin_count = $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
    if ($admin_count == 0) {
        $default_password = bin2hex(random_bytes(12));
        $hash = password_hash($default_password, PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO admin_users (username, email, password_hash) VALUES (?, ?, ?)")
            ->execute(['Daryl Carpio', 'daryl@portfolio.local', $hash]);
        echo "✓ Admin user created (Password: $default_password)<br>";
    } else {
        echo "✓ Admin user already exists<br>";
    }

    // Check if profile exists
    $profile_count = $pdo->query("SELECT COUNT(*) FROM profile")->fetchColumn();
    if ($profile_count == 0) {
        $pdo->prepare("INSERT INTO profile (id, full_name, email, professional_title, bio) VALUES (?, ?, ?, ?, ?)")
            ->execute([1, 'Bryan Darryl Carpio', 'carpiodarryl@gmail.com', 'Software Developer', 'Welcome to my portfolio']);
        echo "✓ Profile created<br>";
    } else {
        echo "✓ Profile already exists<br>";
    }

    // Check if project statuses exist
    $status_count = $pdo->query("SELECT COUNT(*) FROM project_status")->fetchColumn();
    if ($status_count == 0) {
        foreach (['Completed', 'In Progress', 'Planning'] as $status) {
            $pdo->prepare("INSERT INTO project_status (status_name) VALUES (?)")->execute([$status]);
        }
        echo "✓ Project statuses created<br>";
    } else {
        echo "✓ Project statuses already exist<br>";
    }

    // Check if contact types exist
    $contact_count = $pdo->query("SELECT COUNT(*) FROM contact_types")->fetchColumn();
    if ($contact_count == 0) {
        $contacts = [
            ['Email', '✉️'],
            ['Phone', '📱'],
            ['LinkedIn', '💼'],
            ['Facebook', '👤'],
            ['GitHub', '💻'],
            ['Twitter', '🐦'],
            ['Address', '📍']
        ];
        foreach ($contacts as [$name, $icon]) {
            $pdo->prepare("INSERT INTO contact_types (type_name, icon) VALUES (?, ?)")->execute([$name, $icon]);
        }
        echo "✓ Contact types created<br>";
    } else {
        echo "✓ Contact types already exist<br>";
    }

    echo "<hr>";
    echo "<h2 style='color:green'>✅ DATABASE READY!</h2>";
    echo "<p><a href='index.php' style='padding:10px 20px; background:#667eea; color:white; text-decoration:none; border-radius:5px; display:inline-block;'>Go to Homepage →</a></p>";
    echo "<p><a href='admin_login.php' style='padding:10px 20px; background:#43d98c; color:white; text-decoration:none; border-radius:5px; display:inline-block; margin-left:10px;'>Go to Admin Login →</a></p>";

} catch (Exception $e) {
    echo "<h2 style='color:red'>❌ ERROR</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Common Issues:</strong></p>";
    echo "<ul>";
    echo "<li>Database name may include a prefix (e.g., yourname_portfolio)</li>";
    echo "<li>Database user may not have permissions</li>";
    echo "<li>Database credentials in .env may be incorrect</li>";
    echo "</ul>";
}
?>
