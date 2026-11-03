<?php
/**
 * Deployment Diagnostic Script for InfinityFree
 * Run this file on your hosting to check configuration
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>🔍 InfinityFree Deployment Diagnostic</h2>";
echo "<hr>";

// Check PHP version
echo "<h3>PHP Version</h3>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
if (version_compare(PHP_VERSION, '7.4', '>=')) {
    echo "<p style='color:green'>✅ PHP version is compatible (7.4+ required)</p>";
} else {
    echo "<p style='color:red'>❌ PHP version is too old. Need 7.4 or higher</p>";
}

// Check .env file
echo "<h3>.env File Check</h3>";
if (file_exists(__DIR__ . '/.env')) {
    echo "<p style='color:green'>✅ .env file exists</p>";
    $env = parse_ini_file(__DIR__ . '/.env', false, INI_SCANNER_RAW);
    echo "<pre>";
    echo "DB_HOST: " . ($env['DB_HOST'] ?? 'NOT SET') . "\n";
    echo "DB_NAME: " . ($env['DB_NAME'] ?? 'NOT SET') . "\n";
    echo "DB_USER: " . ($env['DB_USER'] ?? 'NOT SET') . "\n";
    echo "DB_PASS: " . (isset($env['DB_PASS']) && !empty($env['DB_PASS']) ? '***SET***' : 'NOT SET') . "\n";
    echo "ENVIRONMENT: " . ($env['ENVIRONMENT'] ?? 'NOT SET') . "\n";
    echo "</pre>";
} else {
    echo "<p style='color:red'>❌ .env file does not exist. Copy .env.example to .env and configure it.</p>";
}

// Check database connection
echo "<h3>Database Connection Test</h3>";
try {
    require_once __DIR__ . '/config.php';
    $db = Database::getInstance();
    echo "<p style='color:green'>✅ Database connection successful</p>";

    // Check if profile table exists
    $table_check = $db->fetch("SHOW TABLES LIKE 'profile'");
    if ($table_check) {
        echo "<p style='color:green'>✅ Profile table exists</p>";

        // Check if profile has data
        $profile = $db->fetch("SELECT * FROM profile LIMIT 1");
        if ($profile) {
            echo "<p style='color:green'>✅ Profile table has data</p>";
            echo "<pre>";
            print_r($profile);
            echo "</pre>";
        } else {
            echo "<p style='color:orange'>⚠️ Profile table exists but is empty. Run database_init.php</p>";
        }
    } else {
        echo "<p style='color:red'>❌ Profile table does not exist. Run database_init.php</p>";
    }

    // Check all tables
    $tables = $db->fetchAll("SHOW TABLES");
    echo "<p><strong>Existing tables (" . count($tables) . "):</strong></p>";
    echo "<ul>";
    foreach ($tables as $table) {
        $table_name = array_values($table)[0];
        echo "<li>$table_name</li>";
    }
    echo "</ul>";

} catch (Exception $e) {
    echo "<p style='color:red'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Possible causes:</strong></p>";
    echo "<ul>";
    echo "<li>Wrong database name (InfinityFree adds prefix)</li>";
    echo "<li>Wrong database username or password</li>";
    echo "<li>Database not created in hosting panel</li>";
    echo "<li>Database user doesn't have permissions</li>";
    echo "</ul>";
}

// Check file permissions
echo "<h3>File Permissions</h3>";
$files_to_check = ['.env', 'config.php', 'dashboard.php'];
foreach ($files_to_check as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $perms = substr(sprintf('%o', fileperms(__DIR__ . '/' . $file)), -4);
        echo "<p>$file: $perms</p>";
    }
}

// Check required directories
echo "<h3>Directory Check</h3>";
$dirs = ['img', 'CSS', 'JS', 'audio'];
foreach ($dirs as $dir) {
    if (is_dir(__DIR__ . '/' . $dir)) {
        echo "<p style='color:green'>✅ $dir directory exists</p>";
    } else {
        echo "<p style='color:orange'>⚠️ $dir directory missing</p>";
    }
}

echo "<hr>";
echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li>If database connection failed: Check your .env file and update with InfinityFree database credentials</li>";
echo "<li>If tables don't exist: Visit <a href='database_init.php'>database_init.php</a> to create tables</li>";
echo "<li>If tables exist but empty: Add data through admin panel or re-run database_init.php</li>";
echo "</ol>";
?>
