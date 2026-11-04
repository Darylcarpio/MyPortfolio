<?php
/**
 * Set/Reset Admin Password
 * Use this to set your admin password correctly
 */

require_once __DIR__ . '/config.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$username = 'Y@iba Carpio';
$password = 'HereI@m123';
$email = 'carpiodarryl@gmail.com';

// Create password hash
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// Check if admin exists
$stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
$stmt->execute([$username]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin) {
    // Update existing admin
    $stmt = $conn->prepare("UPDATE admin_users SET password_hash = ?, email = ? WHERE username = ?");
    $stmt->execute([$password_hash, $email, $username]);
    echo "✅ Admin password updated successfully!<br>";
} else {
    // Create new admin
    $stmt = $conn->prepare("INSERT INTO admin_users (username, password_hash, email) VALUES (?, ?, ?)");
    $stmt->execute([$username, $password_hash, $email]);
    echo "✅ Admin account created successfully!<br>";
}

echo "<br>";
echo "<strong>Admin Credentials:</strong><br>";
echo "Username: " . htmlspecialchars($username) . "<br>";
echo "Password: " . htmlspecialchars($password) . "<br>";
echo "<br>";
echo "<a href='admin_login.php'>Go to Admin Login</a>";
?>
