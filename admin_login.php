<?php
/**
 * Admin Login Fix & Access
 * Direct login without redirect issues
 */

session_start();

require_once __DIR__ . '/config.php';

function ensureAuthLockTable(PDO $conn): void {
    $conn->exec("CREATE TABLE IF NOT EXISTS auth_login_locks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lock_key VARCHAR(150) NOT NULL UNIQUE,
        failed_attempts INT NOT NULL DEFAULT 0,
        locked_until DATETIME DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function getLockRow(PDO $conn, string $lockKey): array {
    $stmt = $conn->prepare('SELECT failed_attempts, locked_until FROM auth_login_locks WHERE lock_key = ? LIMIT 1');
    $stmt->execute([$lockKey]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : [];
}

function resetLock(PDO $conn, string $lockKey): void {
    $stmt = $conn->prepare('DELETE FROM auth_login_locks WHERE lock_key = ?');
    $stmt->execute([$lockKey]);
}

function recordFailedLock(PDO $conn, string $lockKey): array {
    $row = getLockRow($conn, $lockKey);
    $attempts = (int) ($row['failed_attempts'] ?? 0) + 1;
    $lockedUntil = null;

    if ($attempts >= 3) {
        $lockedUntil = date('Y-m-d H:i:s', time() + (2 * 60 * 60));
        $attempts = 0;
    }

    $stmt = $conn->prepare(
        'INSERT INTO auth_login_locks (lock_key, failed_attempts, locked_until) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE failed_attempts = VALUES(failed_attempts), locked_until = VALUES(locked_until), updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute([$lockKey, $attempts, $lockedUntil]);

    return [
        'attempts' => $attempts,
        'locked_until' => $lockedUntil,
    ];
}

$db = Database::getInstance();
$conn = $db->getConnection();
ensureAuthLockTable($conn);

echo "<h1>🔐 Direct Admin Access</h1>";
echo "<hr>";

try {
    // Use the existing Database connection (configured via config.php / .env)
    // This ensures the same credentials (including DB_PASS) are used instead of the hard-coded empty password.
    $pdo = $conn;
    
    // Check if form submitted
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['do_login'])) {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $lockKey = 'admin-login:' . strtolower($username);
        $lockRow = getLockRow($conn, $lockKey);

        if (!empty($lockRow['locked_until']) && strtotime($lockRow['locked_until']) > time()) {
            $remainingMinutes = (int) ceil((strtotime($lockRow['locked_until']) - time()) / 60);
            echo '<p style="color: red;">❌ Too many failed attempts. Try again in ' . $remainingMinutes . ' minutes.</p>';
            exit();
        }
        
        echo "<h3>Processing Login...</h3>";
        
        if (empty($username) || empty($password)) {
            echo '<p style="color: red;">❌ Please enter both username and password</p>';
        } else {
            // Query admin user
            $stmt = $pdo->prepare("SELECT id, username, email, password_hash FROM admin_users WHERE BINARY username = BINARY ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$admin) {
                $lock = recordFailedLock($conn, $lockKey);
                if ($lock['locked_until']) {
                    echo '<p style="color: red;">❌ Too many failed attempts. Login locked for 2 hours.</p>';
                } else {
                    $remaining = 3 - (int) $lock['attempts'];
                    echo '<p style="color: red;">❌ Username not found. ' . $remaining . ' attempt(s) left before lockout.</p>';
                }
            } else {
                echo '<p>Username found: ' . htmlspecialchars($admin['username']) . '</p>';
                
                // Verify password
                if (password_verify($password, $admin['password_hash'])) {
                    resetLock($conn, 'admin-login:' . strtolower($username));
                    echo '<p style="color: green;">✅ Password verified!</p>';
                    
                    // Set session
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_user'] = $admin['username'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_login_time'] = time();
                    
                    echo '<p style="color: green;">✅ Session created!</p>';
                    
                    // Show redirect button
                    echo '<p style="margin-top: 30px;">';
                    echo '<a href="panel.php" style="display: inline-block; padding: 15px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;">Go to Admin Panel →</a>';
                    echo '</p>';
                    
                    // Also try auto-redirect
                    echo '<script>';
                    echo 'setTimeout(function() {';
                    echo '  window.location.href = "panel.php";';
                    echo '}, 2000);';
                    echo '</script>';
                    
                } else {
                    $lock = recordFailedLock($conn, 'admin-login:' . strtolower($username));
                    if ($lock['locked_until']) {
                        echo '<p style="color: red;">❌ Too many failed attempts. Login locked for 2 hours.</p>';
                    } else {
                        $remaining = 3 - (int) $lock['attempts'];
                        echo '<p style="color: red;">❌ Password incorrect. ' . $remaining . ' attempt(s) left before lockout.</p>';
                    }
                }
            }
        }
    } else {
        // Show login form
        echo "<h3>Admin Login Form</h3>";
        
        // Check if admin exists
        $stmt = $pdo->query("SELECT username, email FROM admin_users");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo '<p style="color: blue;">ℹ️ Existing admin accounts in database:</p>';
        echo '<ul>';
        foreach ($admins as $admin) {
            echo '<li>' . htmlspecialchars($admin['username']) . ' (' . htmlspecialchars($admin['email']) . ')</li>';
        }
        echo '</ul>';

        if (empty($admins)) {
            echo '<p style="color: red;">❌ No admin account found - creating one...</p>';

            $hash = password_hash('HereI@m123', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash, email) VALUES (?, ?, ?)");
            $stmt->execute(['Y@iba Carpio', $hash, 'carpiodarryl@gmail.com']);

            echo '<p style="color: green;">✅ Admin account created with username: Y@iba Carpio</p>';
        }

        echo '<form method="POST" style="background: #f9f9f9; padding: 30px; border-radius: 8px; max-width: 400px; margin: 20px 0;">';
        echo '<input type="hidden" name="do_login" value="1">';

        echo '<div style="margin-bottom: 20px;">';
        echo '<label style="display: block; margin-bottom: 8px; font-weight: bold;">Username:</label>';
        echo '<input type="text" name="username" value="Y@iba Carpio" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;" required autofocus>';
        echo '</div>';

        echo '<div style="margin-bottom: 20px;">';
        echo '<label style="display: block; margin-bottom: 8px; font-weight: bold;">Password:</label>';
        echo '<input type="password" name="password" placeholder="Enter your password" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;" required>';
        echo '</div>';

        echo '<button type="submit" style="width: 100%; padding: 12px; background: #667eea; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 16px;">Login to Admin Panel</button>';
        echo '</form>';

        echo '<hr>';
        echo '<p><strong>Your Admin Credentials:</strong></p>';
        echo '<ul>';
        echo '<li><strong>Username:</strong> Y@iba Carpio</li>';
        echo '<li><strong>Password:</strong> HereI@m123</li>';
        echo '</ul>';
        
        echo '<p><strong>What you can do in Admin Panel:</strong></p>';
        echo '<ul>';
        echo '<li>✏️ Edit your profile (name, bio, vision)</li>';
        echo '<li>📚 Add/Edit/Delete education entries</li>';
        echo '<li>🚀 Add/Edit/Delete projects</li>';
        echo '<li>🎯 Manage hobbies and skills</li>';
        echo '<li>📧 Update contact information</li>';
        echo '<li>🗑️ Delete sample data if you want</li>';
        echo '</ul>';
    }
    
} catch (Exception $e) {
    echo '<p style="color: red;">❌ Error: ' . $e->getMessage() . '</p>';
}
?>

<style>
body { font-family: Arial; margin: 20px; background-color: #f5f5f5; }
h1, h2, h3 { color: #333; }
hr { margin: 30px 0; border: none; border-top: 1px solid #e0e0e0; }
a { color: #667eea; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
</body>
</html>
