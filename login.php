<?php
/**
 * Admin Login Processor - Portfolio Website
 * Handles authentication for main website admin panel
 */
session_start();

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: panel.php', true, 302);
    exit();
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config.php';
    
    try {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            throw new Exception('Username and password required');
        }
        
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Query admin_users table
        $stmt = $conn->prepare("
            SELECT id, username, email, password_hash 
            FROM admin_users 
            WHERE username = ? 
            LIMIT 1
        ");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            throw new Exception('Invalid username or password');
        }
        
        // Verify password - handle both bcrypt hashes and plaintext passwords (for legacy admin_users)
        $password_valid = false;
        
        // Try bcrypt verification first
        if (password_verify($password, $admin['password_hash'])) {
            $password_valid = true;
        }
        // Fall back to plaintext comparison for legacy entries (password_hash field contains plaintext)
        elseif ($password === $admin['password_hash']) {
            $password_valid = true;
        }
        
        if (!$password_valid) {
            throw new Exception('Invalid username or password');
        }
        
        // Login successful - set session
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_user'] = $admin['username'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_login_time'] = time();
        
        // Redirect to panel
        header('Location: panel.php', true, 302);
        exit();
        
    } catch (Exception $e) {
        // Distinguish invalid credentials from server/db errors.
        $isInvalidCreds = stripos($e->getMessage(), 'Invalid username or password') !== false
            || stripos($e->getMessage(), 'Username and password required') !== false;
        $errorCode = $isInvalidCreds ? '1' : 'db';
        header('Location: index.php?error=' . urlencode($errorCode), true, 302);
        exit();
    }
}

// If not POST, redirect to index
header('Location: index.php', true, 302);
exit();
?>
