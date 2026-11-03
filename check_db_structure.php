<?php
/**
 * Database Structure Checker
 * Verifies all required columns exist in the database
 */

session_start();
require_once __DIR__ . '/config.php';

$db = Database::getInstance()->getConnection();

echo "<h2>🔍 Database Structure Checker</h2>";
echo "<hr>";

// Check profile table structure
echo "<h3>Profile Table Structure:</h3>";
try {
    $columns = $db->query("DESCRIBE profile")->fetchAll();
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Check for required columns
    $required_columns = ['id', 'full_name', 'email', 'location', 'professional_title', 'bio', 'facebook', 'profile_image'];
    $existing_columns = array_column($columns, 'Field');
    $missing = array_diff($required_columns, $existing_columns);

    if (!empty($missing)) {
        echo "<p style='color:red'>❌ Missing columns: " . implode(', ', $missing) . "</p>";
        echo "<p>Run <a href='add_facebook_column.php'>add_facebook_column.php</a> to fix.</p>";
    } else {
        echo "<p style='color:green'>✅ All required columns exist!</p>";
    }

} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";

// Check current profile data
echo "<h3>Current Profile Data:</h3>";
try {
    $profile = $db->fetch("SELECT * FROM profile LIMIT 1");
    if ($profile) {
        echo "<pre>";
        print_r($profile);
        echo "</pre>";
    } else {
        echo "<p style='color:orange'>⚠️ No profile data found.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='panel.php' style='padding:10px 20px; background:#667eea; color:white; text-decoration:none; border-radius:5px;'>Go to Admin Panel →</a></p>";
?>
