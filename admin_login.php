<?php
/**
 * Admin Login Page
 * Professional login interface for admin access
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

$error_message = '';
$success_message = '';

try {
    $pdo = $conn;

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['do_login'])) {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $lockKey = 'admin-login:' . strtolower($username);
        $lockRow = getLockRow($conn, $lockKey);

        if (!empty($lockRow['locked_until']) && strtotime($lockRow['locked_until']) > time()) {
            $remainingMinutes = (int) ceil((strtotime($lockRow['locked_until']) - time()) / 60);
            $error_message = 'Too many failed attempts. Try again in ' . $remainingMinutes . ' minutes.';
        } else {
            if (empty($username) || empty($password)) {
                $error_message = 'Please enter both username and password';
            } else {
                $stmt = $pdo->prepare("SELECT id, username, email, password_hash FROM admin_users WHERE BINARY username = BINARY ?");
                $stmt->execute([$username]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$admin) {
                    $lock = recordFailedLock($conn, $lockKey);
                    if ($lock['locked_until']) {
                        $error_message = 'Too many failed attempts. Login locked for 2 hours.';
                    } else {
                        $remaining = 3 - (int) $lock['attempts'];
                        $error_message = 'Username not found. ' . $remaining . ' attempt(s) left before lockout.';
                    }
                } else {
                    if (password_verify($password, $admin['password_hash'])) {
                        resetLock($conn, 'admin-login:' . strtolower($username));

                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['admin_id'] = $admin['id'];
                        $_SESSION['admin_user'] = $admin['username'];
                        $_SESSION['admin_email'] = $admin['email'];
                        $_SESSION['admin_login_time'] = time();

                        header('Location: panel.php');
                        exit();
                    } else {
                        $lock = recordFailedLock($conn, 'admin-login:' . strtolower($username));
                        if ($lock['locked_until']) {
                            $error_message = 'Too many failed attempts. Login locked for 2 hours.';
                        } else {
                            $remaining = 3 - (int) $lock['attempts'];
                            $error_message = 'Password incorrect. ' . $remaining . ' attempt(s) left before lockout.';
                        }
                    }
                }
            }
        }
    }
} catch (Exception $e) {
    $error_message = 'An error occurred. Please try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - BDC World</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(255,255,255,0.18), transparent 30%),
                linear-gradient(135deg, #07111f 0%, #0f2b4f 40%, #2563eb 100%);
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-container {
            max-width: 420px;
            width: 100%;
            background: rgba(255,255,255,0.10);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 28px;
            padding: 42px;
            backdrop-filter: blur(14px);
            box-shadow: 0 25px 60px rgba(0,0,0,0.28);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-section h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #f59e0b, #60a5fa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo-section p {
            color: #e2e8f0;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #f8fafc;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 12px;
            background: rgba(255,255,255,0.08);
            color: #f8fafc;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: rgba(255,255,255,0.12);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        .form-group input::placeholder {
            color: rgba(248, 250, 252, 0.5);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-back {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #e2e8f0;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .btn-back:hover {
            color: #667eea;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
        }

        @media (max-width: 768px) {
            .login-container {
                padding: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <h1>Admin Login</h1>
            <p>Sign in to manage your portfolio</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="do_login" value="1">

            <div class="form-group">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Enter your username"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >
            </div>

            <button type="submit" class="btn-submit">Sign In</button>
        </form>

        <a href="index.php" class="btn-back">← Back to Home</a>
    </div>
</body>
</html>
