<?php
/**
 * Migration Script: Add facebook column to profile table
 * Run this to update existing databases without losing data
 */

session_start();
require_once __DIR__ . '/config.php';

$db = Database::getInstance()->getConnection();

echo "<h2>🔧 Database Migration: Add Facebook Column</h2>";
echo "<hr>";

try {
    // Check if facebook column already exists
    $columns = $db->query("SHOW COLUMNS FROM profile LIKE 'facebook'")->fetchAll();

    if (count($columns) > 0) {
        echo "<p style='color:green'>✅ Facebook column already exists in profile table.</p>";
    } else {
        // Add the facebook column
        $db->exec("ALTER TABLE profile ADD COLUMN facebook VARCHAR(255) AFTER bio");
        echo "<p style='color:green'>✅ Facebook column added successfully to profile table.</p>";
    }

    // Check current profile data
    $profile = $db->fetch("SELECT * FROM profile LIMIT 1");
    if ($profile) {
        echo "<hr>";
        echo "<h3>Current Profile Data:</h3>";
        echo "<pre>";
        print_r($profile);
        echo "</pre>";

        // Check if facebook is set
        if (empty($profile['facebook'])) {
            echo "<p style='color:orange'>⚠️ Facebook link is not set in the database.</p>";
            echo "<p>You can add it through the admin panel or update it directly.</p>";
        }
    }

    echo "<hr>";
    echo "<p><a href='dashboard.php' style='padding:10px 20px; background:#667eea; color:white; text-decoration:none; border-radius:5px;'>View Dashboard →</a></p>";
    echo "<p><a href='panel.php' style='padding:10px 20px; background:#43d98c; color:white; text-decoration:none; border-radius:5px; margin-left:10px;'>Go to Admin Panel →</a></p>";

} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
