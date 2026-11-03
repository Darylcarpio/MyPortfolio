<?php
/**
 * ASCOT Community - Admin Login API
 * Handles admin (community_admin + super_admin) authentication
 * Single credential for both admin and super admin roles
 */
session_start();

require_once dirname(__FILE__) . '/../../config.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Location: ../../community/index-new.php?error=invalid', true, 302);
        exit();
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        header('Location: ../../community/index-new.php?error=invalid', true, 302);
        exit();
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Find admin user by email OR username (must be admin or super_admin)
    $stmt = $conn->prepare("
        SELECT id, email, username, password_hash, first_name, last_name, role, status, profile_picture
        FROM community_users
        WHERE (email = ? OR username = ?) AND role IN ('community_admin', 'super_admin')
        LIMIT 1
    ");
    $stmt->execute([$email, $email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        header('Location: ../../community/index-new.php?error=invalid', true, 302);
        exit();
    }

    // Verify password
    if (!password_verify($password, $admin['password_hash'])) {
        // Legacy hashes migration: accept md5 or sha1 and rehash to bcrypt
        $stored = $admin['password_hash'];
        $legacy_ok = false;

        if (preg_match('/^[0-9a-f]{32}$/i', $stored) && md5($password) === $stored) {
            $legacy_ok = true;
        }
        if (!$legacy_ok && preg_match('/^[0-9a-f]{40}$/i', $stored) && sha1($password) === $stored) {
            $legacy_ok = true;
        }

        if ($legacy_ok) {
            try {
                $new_hash = password_hash($password, PASSWORD_BCRYPT);
                $upd = $conn->prepare("UPDATE community_users SET password_hash = ? WHERE id = ?");
                $upd->execute([$new_hash, $admin['id']]);
            } catch (Exception $e) {
                // ignore update failure
            }
        } else {
            // Log admin login failure for debugging
            $logPath = dirname(__DIR__, 1) . '/../Temp/login_debug.log';
            $entry = date('c') . " | ADMIN_LOGIN_FAIL | email=" . $email . " | reason=wrong_password | admin_id=" . ($admin['id'] ?? 'n/a') . PHP_EOL;
            @file_put_contents($logPath, $entry, FILE_APPEND | LOCK_EX);

            header('Location: ../../community/index-new.php?error=invalid', true, 302);
            exit();
        }
    }

    // Check status
    if ($admin['status'] !== 'active') {
        header('Location: ../../community/index-new.php?error=invalid', true, 302);
        exit();
    }

    // Update last login
    $update = $conn->prepare("UPDATE community_users SET last_login = NOW() WHERE id = ?");
    $update->execute([$admin['id']]);

    // Set session for BOTH admin and super admin access
    $_SESSION['community_admin_id'] = $admin['id'];
    $_SESSION['community_admin_email'] = $admin['email'];
    $_SESSION['community_admin_username'] = $admin['username'];
    $_SESSION['community_admin_first_name'] = $admin['first_name'];
    $_SESSION['community_admin_last_name'] = $admin['last_name'];
    $_SESSION['community_admin_role'] = $admin['role'];
    $_SESSION['community_admin_logged_in'] = true;

    // Redirect to admin dashboard
    header('Location: ../../community/admin/', true, 302);
    exit();

} catch (Exception $e) {
    header('Location: ../../community/index-new.php?error=invalid', true, 302);
    exit();
}
?>
