<?php
// admin_recover.php — Manage Admin Credentials
session_start();
require_once __DIR__ . '/config.php';

// Only allow access from logged-in admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php', true, 302);
    exit();
}

$db = Database::getInstance()->getConnection();

$message = '';

try {
    // Ensure admin_users table exists (basic schema if missing)
    $db->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        email VARCHAR(200) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_admin'])) {
        $newUsername = trim($_POST['username'] ?? '');
        $newPassword = $_POST['password'] ?? '';
        $currentAdminId = (int) ($_SESSION['admin_id'] ?? 0);

        if ($currentAdminId <= 0) {
            $message = 'Your admin session is invalid. Please log in again.';
        } elseif (empty($newUsername)) {
            $message = 'Username cannot be empty.';
        } elseif (!empty($newPassword) && strlen($newPassword) < 8) {
            $message = 'Password must be at least 8 characters.';
        } else {
            // Update the currently authenticated admin record.
            $stmt = $db->prepare('SELECT id FROM admin_users WHERE username = ? AND id <> ? LIMIT 1');
            $stmt->execute([$newUsername, $currentAdminId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $message = 'That username is already in use.';
            } else {
                $fields = ['username = ?'];
                $params = [$newUsername];
                if (!empty($newPassword)) {
                    $fields[] = 'password_hash = ?';
                    $params[] = password_hash($newPassword, PASSWORD_BCRYPT);
                }
                $params[] = $currentAdminId;
                $stmt = $db->prepare('UPDATE admin_users SET ' . implode(', ', $fields) . ' WHERE id = ?');
                $stmt->execute($params);
                $_SESSION['admin_user'] = $newUsername;
                $message = !empty($newPassword) ? 'Admin username and password updated.' : 'Admin username updated.';
            }
        }
    }

    // Load current admin (if any)
    $currentAdminId = (int) ($_SESSION['admin_id'] ?? 0);
    $stmt = $db->prepare('SELECT id, username FROM admin_users WHERE id = ? LIMIT 1');
    $stmt->execute([$currentAdminId]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

} catch (Exception $e) {
    $message = 'Error: ' . $e->getMessage();
}

?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Admin Credentials</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{ --bg:#0d0d1a; --card:#16162e; --border:rgba(255,255,255,0.12); --accent1:#667eea; --accent2:#764ba2; --muted:#8b92c0; --text:#eef0ff; --r:14px; }
body{font-family:'Outfit',sans-serif;background:var(--bg);color:var(--text);padding:32px;min-height:100vh;display:flex;align-items:center;justify-content:center}
.box{width:100%;max-width:720px;background:var(--card);padding:28px;border-radius:var(--r);box-shadow:0 20px 50px rgba(0,0,0,0.6);border:1px solid var(--border)}
h2{margin:0 0 8px;color:var(--text);}
.small{color:var(--muted);margin-bottom:12px}
.msg{margin-top:12px;padding:12px;border-radius:10px}
.success{background:rgba(67,217,140,0.12);color:#43d98c;border:1px solid rgba(67,217,140,0.22)}
.error{background:rgba(240,84,84,0.12);color:#f05454;border:1px solid rgba(240,84,84,0.22)}
label{display:block;margin-top:14px;color:var(--muted);font-weight:600;font-size:0.9rem}
input[type=text],input[type=password]{width:100%;padding:12px;border-radius:10px;border:1px solid rgba(255,255,255,0.06);background:rgba(255,255,255,0.02);color:var(--text);margin-top:6px}
button{margin-top:18px;padding:12px 16px;border:none;border-radius:10px;background:linear-gradient(135deg,var(--accent1),var(--accent2));color:#fff;font-weight:700;cursor:pointer;box-shadow:0 8px 30px rgba(102,126,234,0.12)}
.readonly-input{background:transparent;border:1px dashed rgba(255,255,255,0.04);padding:10px;border-radius:8px;color:var(--muted)}
.actions{margin-top:14px;display:flex;gap:12px;align-items:center}
.back{color:var(--muted);text-decoration:none}
@media (max-width:640px){.box{padding:18px;margin:16px}}
</style>
</head>
<body>
<div class="box">
    <h2>Manage Admin Credentials</h2>
    <p class="small">Only logged-in admins can access this page. Use this to create or update the admin account.</p>

    <?php if (!empty($message)): ?>
        <div class="msg <?php echo strpos($message,'Error:')===0 ? 'error' : 'success'; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <label for="username">Username</label>
        <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($current['username'] ?? 'admin'); ?>" required>

        <label for="password">New Password (leave blank to keep current)</label>
        <input type="password" name="password" id="password" placeholder="At least 8 characters">

        <input type="hidden" name="update_admin" value="1">
        <button type="submit">Save Admin</button>
    </form>

    <p class="small">After changing credentials, log out and log back in if necessary.</p>
    <p><a href="panel.php">← Back to Admin Panel</a></p>
</div>
</body>
</html>
