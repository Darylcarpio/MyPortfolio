<?php
/**
 * Reset Admin Login Lock & Set Password
 * Use this to clear the login lock and set admin password
 */

require_once __DIR__ . '/config.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Step 1: Reset login lock
$conn->exec("DELETE FROM auth_login_locks");
echo "✅ Login lock reset!<br>";

// Step 2: Set admin password
$username = 'Y@iba Carpio';
$password = 'HereI@m123';
$email = 'carpiodarryl@gmail.com';
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// Check if admin exists
$stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
$stmt->execute([$username]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin) {
    $stmt = $conn->prepare("UPDATE admin_users SET password_hash = ?, email = ? WHERE username = ?");
    $stmt->execute([$password_hash, $email, $username]);
    echo "✅ Admin password updated!<br>";
} else {
    $stmt = $conn->prepare("INSERT INTO admin_users (username, password_hash, email) VALUES (?, ?, ?)");
    $stmt->execute([$username, $password_hash, $email]);
    echo "✅ Admin account created!<br>";
}

echo "<br>";
echo "<h3>🔐 Admin Credentials</h3>";
echo "<strong>Username:</strong> " . htmlspecialchars($username) . "<br>";
echo "<strong>Password:</strong> " . htmlspecialchars($password) . "<br>";
echo "<br>";
echo "<a href='admin_login.php' style='display: inline-block; padding: 15px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; font-weight: bold;'>Go to Admin Login →</a>";
?>
