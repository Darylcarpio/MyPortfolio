<?php
/**
 * Admin Credential Management
 * Allow admin to change username and password
 */

session_start();
require_once __DIR__ . '/config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'update_credentials') {
        $new_username = trim($_POST['new_username'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $current_password = $_POST['current_password'] ?? '';

        // Verify current password
        $stmt = $conn->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin || !password_verify($current_password, $admin['password_hash'])) {
            $error_message = 'Current password is incorrect';
        } else {
            // Validate new password if provided
            if (!empty($new_password)) {
                // Password criteria: at least 8 characters, at least 1 uppercase, 1 lowercase, 1 number
                if (strlen($new_password) < 8) {
                    $error_message = 'Password must be at least 8 characters';
                } elseif (!preg_match('/[A-Z]/', $new_password)) {
                    $error_message = 'Password must contain at least 1 uppercase letter';
                } elseif (!preg_match('/[a-z]/', $new_password)) {
                    $error_message = 'Password must contain at least 1 lowercase letter';
                } elseif (!preg_match('/[0-9]/', $new_password)) {
                    $error_message = 'Password must contain at least 1 number';
                } elseif ($new_password !== $confirm_password) {
                    $error_message = 'Passwords do not match';
                } else {
                    // Update password
                    $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
                    $stmt = $conn->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
                    $stmt->execute([$password_hash, $_SESSION['admin_id']]);

                    if (!empty($new_username)) {
                        // Also update username if provided
                        $stmt = $conn->prepare("UPDATE admin_users SET username = ? WHERE id = ?");
                        $stmt->execute([$new_username, $_SESSION['admin_id']]);
                        $_SESSION['admin_user'] = $new_username;
                        $success_message = 'Username and password updated successfully!';
                    } else {
                        $success_message = 'Password updated successfully!';
                    }
                }
            } elseif (!empty($new_username)) {
                // Only update username
                $stmt = $conn->prepare("UPDATE admin_users SET username = ? WHERE id = ?");
                $stmt->execute([$new_username, $_SESSION['admin_id']]);
                $_SESSION['admin_user'] = $new_username;
                $success_message = 'Username updated successfully!';
            } else {
                $error_message = 'Please enter a new username or password';
            }
        }
    }
}

// Get current admin info
$stmt = $conn->prepare("SELECT username, email FROM admin_users WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admin Account - BDC World</title>
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

        .manage-container {
            max-width: 500px;
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

        .current-info {
            background: rgba(255,255,255,0.08);
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
        }

        .current-info p {
            margin: 8px 0;
            font-size: 0.95rem;
        }

        .current-info strong {
            color: #667eea;
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
            .manage-container {
                padding: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="manage-container">
        <div class="logo-section">
            <h1>Manage Admin Account</h1>
            <p>Update your username and password</p>
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
            <input type="hidden" name="action" value="update_credentials">

            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input
                    type="password"
                    id="current_password"
                    name="current_password"
                    placeholder="Enter current password"
                    required
                >
            </div>

            <div class="form-group">
                <label for="new_username">New Username</label>
                <input
                    type="text"
                    id="new_username"
                    name="new_username"
                    placeholder="Leave blank to keep current username"
                >
                <small style="color: rgba(248, 250, 252, 0.6); font-size: 0.8rem;">Username must be unique. Letters, numbers, and spaces allowed.</small>
            </div>

            <div class="form-group">
                <label for="new_password">New Password</label>
                <input
                    type="password"
                    id="new_password"
                    name="new_password"
                    placeholder="Leave blank to keep current password"
                >
                <small style="color: rgba(248, 250, 252, 0.6); font-size: 0.8rem;">Minimum 8 characters, 1 uppercase, 1 lowercase, 1 number</small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Re-enter new password"
                >
            </div>

            <button type="submit" class="btn-submit">Update Credentials</button>
        </form>

        <a href="panel.php" class="btn-back">← Back to Admin Panel</a>
    </div>
</body>
</html>
