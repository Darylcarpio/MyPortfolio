<?php
/**
 * Admin Password Reset Helper
 * Use this if you forgot your admin password
 */

session_start();
require_once __DIR__ . '/config.php';

$db = Database::getInstance()->getConnection();

echo "<h2>🔑 Admin Password Reset</h2>";
echo "<hr>";

// Check if admin exists
$stmt = $db->prepare("SELECT id, username, email FROM admin_users LIMIT 1");
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin) {
    echo "<h3>Current Admin Account:</h3>";
    echo "<p><strong>Username:</strong> " . htmlspecialchars($admin['username']) . "</p>";
    echo "<p><strong>Email:</strong> " . htmlspecialchars($admin['email'] ?? 'Not set') . "</p>";
    echo "<p><strong>ID:</strong> " . $admin['id'] . "</p>";

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
        $new_password = $_POST['new_password'];
        if (strlen($new_password) < 8) {
            echo "<p style='color:red'>Password must be at least 8 characters</p>";
        } else {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $admin['id']]);
            echo "<p style='color:green; font-weight:bold;'>✅ Password updated successfully!</p>";
            echo "<p>New password: <strong>" . htmlspecialchars($new_password) . "</strong></p>";
            echo "<p><a href='admin_login.php' style='padding:10px 20px; background:#667eea; color:white; text-decoration:none; border-radius:5px;'>Go to Admin Login →</a></p>";
        }
    }

    echo "<hr>";
    echo "<h3>Reset Password:</h3>";
    echo "<form method='POST'>";
    echo "<label>New Password (min 8 characters):</label><br>";
    echo "<input type='text' name='new_password' required style='padding:10px; width:300px;'>";
    echo "<button type='submit' style='padding:10px 20px; background:#667eea; color:white; border:none; border-radius:5px; cursor:pointer; margin-top:10px;'>Reset Password</button>";
    echo "</form>";

} else {
    echo "<p style='color:red'>No admin account found in database.</p>";
    echo "<p>Creating default admin account...</p>";

    $default_password = 'admin123';
    $hash = password_hash($default_password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO admin_users (username, email, password_hash) VALUES (?, ?, ?)");
    $stmt->execute(['admin', 'admin@portfolio.local', $hash]);

    echo "<p style='color:green'>✅ Default admin account created!</p>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<p><a href='admin_login.php' style='padding:10px 20px; background:#667eea; color:white; text-decoration:none; border-radius:5px;'>Go to Admin Login →</a></p>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Back to Homepage</a></p>";
?>
