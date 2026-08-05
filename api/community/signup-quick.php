<?php
/**
 * Quick Signup Without OTP (Temporary Workaround)
 * For immediate testing - no email verification needed
 * Remove this file when proper OTP is implemented
 */

header('Content-Type: application/json');
session_start();
require_once dirname(dirname(__FILE__)) . '/config.php';
require_once dirname(dirname(__FILE__)) . '/CommunityAuth_class.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'signup';

try {
    if ($method !== 'POST') {
        throw new Exception('Method not allowed');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        throw new Exception('Invalid request data');
    }

    // Sanitize inputs
    $data = array_map('trim', $data);

    // Validate required fields
    $required = ['email', 'first_name', 'last_name', 'phone_number', 'password', 'password_confirm'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Check password match
    if ($data['password'] !== $data['password_confirm']) {
        throw new Exception('Passwords do not match');
    }

    // Validate password strength
    $auth = new CommunityAuth();
    $pwd_validation = $auth->validatePassword($data['password']);
    if (!$pwd_validation['valid']) {
        throw new Exception(implode(', ', $pwd_validation['errors']));
    }

    // Validate email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Check if email already exists
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT id FROM community_users WHERE email = ? LIMIT 1");
    $stmt->execute([$data['email']]);
    if ($stmt->rowCount() > 0) {
        throw new Exception('Email already registered');
    }

    // Validate phone
    $phone_validation = $auth->validatePhoneNumber($data['phone_number']);
    if (!$phone_validation['valid']) {
        throw new Exception(implode(', ', $phone_validation['errors']));
    }
    $phone = $phone_validation['normalized'];

    // Check phone doesn't exist
    $stmt = $db->prepare("SELECT id FROM community_users WHERE phone_number = ? LIMIT 1");
    $stmt->execute([$phone]);
    if ($stmt->rowCount() > 0) {
        throw new Exception('Phone number already registered');
    }

    // Generate unique username
    $username = explode('@', $data['email'])[0] . rand(1000, 9999);
    $stmt = $db->prepare("SELECT id FROM community_users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    while ($stmt->rowCount() > 0) {
        $username = explode('@', $data['email'])[0] . rand(1000, 9999);
        $stmt->execute([$username]);
    }

    // Create account (SKIP OTP - AUTO VERIFY)
    $stmt = $db->prepare(
        "INSERT INTO community_users 
        (email, username, first_name, last_name, phone_number, password_hash, status, email_verified, email_verified_at, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'active', TRUE, NOW(), NOW())"
    );
    
    $stmt->execute([
        $data['email'],
        $username,
        htmlspecialchars($data['first_name']),
        htmlspecialchars($data['last_name']),
        $phone,
        password_hash($data['password'], PASSWORD_BCRYPT)
    ]);

    $user_id = $db->lastInsertId();

    // Log the signup
    $stmt = $db->prepare(
        "INSERT INTO community_session_logs (user_id, action, ip_address, user_agent, status) 
        VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $user_id,
        'signup',
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        'success'
    ]);

    // Return success
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully! Please complete your profile.',
        'user_id' => $user_id,
        'action' => 'redirect_to_profile'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
