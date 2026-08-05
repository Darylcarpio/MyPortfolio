<?php
// admin_bypass.php - emergency login by token
// IMPORTANT: Keep this file private and delete it after recovery.

session_start();

require_once 'config.php';

function getBypassToken() {
    $token = getenv('ADMIN_BYPASS_TOKEN');
    if (!empty($token)) {
        return trim($token);
    }

    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $env = parse_ini_file($envFile, false, INI_SCANNER_RAW);
        if (!empty($env['ADMIN_BYPASS_TOKEN'])) {
            return trim($env['ADMIN_BYPASS_TOKEN']);
        }
    }

    return '';
}

function deny($msg, $status = 403) {
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit();
}

$configuredToken = getBypassToken();
$providedToken = trim($_GET['token'] ?? '');
$targetUser = trim($_GET['user'] ?? '');

if ($configuredToken === '') {
    deny("ADMIN_BYPASS_TOKEN is not configured. Add it in .env first.");
}

if ($providedToken === '' || !hash_equals($configuredToken, $providedToken)) {
    deny('Invalid or missing token.');
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    if ($targetUser !== '') {
        $stmt = $conn->prepare('SELECT id, username, email FROM admin_users WHERE username = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$targetUser]);
    } else {
        $stmt = $conn->query('SELECT id, username, email FROM admin_users WHERE is_active = 1 ORDER BY id ASC LIMIT 1');
    }

    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$admin) {
        deny('No active admin account found.');
    }

    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_user'] = $admin['username'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_login_time'] = time();

    header('Location: panel.php', true, 302);
    exit();
} catch (Exception $e) {
    deny('Bypass failed: ' . $e->getMessage(), 500);
}
