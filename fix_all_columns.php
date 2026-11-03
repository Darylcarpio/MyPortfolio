<?php
/**
 * Comprehensive Database Fix Script
 * Ensures all required columns exist in the profile table
 */

session_start();
require_once __DIR__ . '/config.php';

$db = Database::getInstance()->getConnection();

echo "<h2>🔧 Comprehensive Database Fix</h2>";
echo "<hr>";

try {
    // Get current columns
    $columns = $db->query("DESCRIBE profile")->fetchAll();
    $existing_columns = array_column($columns, 'Field');

    echo "<h3>Current columns in profile table:</h3>";
    echo "<ul>";
    foreach ($existing_columns as $col) {
        echo "<li>" . htmlspecialchars($col) . "</li>";
    }
    echo "</ul>";

    // Required columns and their definitions
    $required_columns = [
        'full_name' => "ALTER TABLE profile ADD COLUMN full_name VARCHAR(100) NOT NULL AFTER id",
        'email' => "ALTER TABLE profile ADD COLUMN email VARCHAR(100) AFTER full_name",
        'phone' => "ALTER TABLE profile ADD COLUMN phone VARCHAR(20) AFTER email",
        'location' => "ALTER TABLE profile ADD COLUMN location VARCHAR(100) AFTER phone",
        'professional_title' => "ALTER TABLE profile ADD COLUMN professional_title VARCHAR(100) AFTER location",
        'bio' => "ALTER TABLE profile ADD COLUMN bio TEXT AFTER professional_title",
        'facebook' => "ALTER TABLE profile ADD COLUMN facebook VARCHAR(255) AFTER bio",
        'profile_image' => "ALTER TABLE profile ADD COLUMN profile_image VARCHAR(255) AFTER facebook",
        'updated_at' => "ALTER TABLE profile ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER profile_image"
    ];

    echo "<h3>Adding missing columns...</h3>";
    $added = 0;
    foreach ($required_columns as $column => $sql) {
        if (!in_array($column, $existing_columns)) {
            try {
                $db->exec($sql);
                echo "<p style='color:green'>✅ Added column: $column</p>";
                $added++;
            } catch (Exception $e) {
                echo "<p style='color:red'>❌ Failed to add $column: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            echo "<p style='color:gray'>⊘ Column already exists: $column</p>";
        }
    }

    if ($added > 0) {
        echo "<hr>";
        echo "<p style='color:green; font-weight:bold;'>✅ Successfully added $added column(s)!</p>";
    } else {
        echo "<hr>";
        echo "<p style='color:green; font-weight:bold;'>✅ All columns already exist!</p>";
    }

    // Verify the structure
    echo "<hr>";
    echo "<h3>Updated Profile Table Structure:</h3>";
    $columns = $db->query("DESCRIBE profile")->fetchAll();
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<hr>";
    echo "<p><a href='panel.php' style='padding:10px 20px; background:#667eea; color:white; text-decoration:none; border-radius:5px;'>Go to Admin Panel →</a></p>";
    echo "<p><a href='dashboard.php' style='padding:10px 20px; background:#43d98c; color:white; text-decoration:none; border-radius:5px; margin-left:10px;'>View Dashboard →</a></p>";

} catch (Exception $e) {
    echo "<h2 style='color:red'>❌ ERROR</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
