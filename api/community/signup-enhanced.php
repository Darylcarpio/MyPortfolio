<?php
/**
 * Enhanced User Signup API
 * Handles signup with full address and new name fields
 */
header('Content-Type: application/json');
require_once '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    $required_fields = ['first_name', 'last_name', 'email', 'phone_number', 'password', 'password_confirm', 'province', 'municipality', 'barangay'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: " . str_replace('_', ' ', $field));
        }
    }

    // Validate email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Validate passwords match
    if ($data['password'] !== $data['password_confirm']) {
        throw new Exception('Passwords do not match');
    }

    // Validate password strength
    if (strlen($data['password']) < 8) {
        throw new Exception('Password must be at least 8 characters');
    }

    if (!preg_match('/[A-Z]/', $data['password']) || !preg_match('/[0-9]/', $data['password']) || !preg_match('/[@#$%^&*!]/', $data['password'])) {
        throw new Exception('Password must include uppercase, number, and special character');
    }

    // Validate Philippine phone number
    $phone = preg_replace('/\D/', '', $data['phone_number']);
    if (!(strlen($phone) === 11 && substr($phone, 0, 2) === '09') && 
        !(strlen($phone) === 12 && substr($phone, 0, 2) === '63')) {
        throw new Exception('Invalid Philippine phone number');
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM community_users WHERE email = ?");
    $stmt->bind_param("s", $data['email']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Email already registered');
    }

    // Prepare name fields
    $first_name = trim($data['first_name']);
    $middle_name = isset($data['middle_name']) ? trim($data['middle_name']) : '';
    $last_name = trim($data['last_name']);
    $suffix = isset($data['suffix']) ? trim($data['suffix']) : '';
    $email = strtolower(trim($data['email']));
    $phone = $data['phone_number'];
    $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);

    // Build full name
    $full_name = $first_name . (!empty($middle_name) ? ' ' . $middle_name : '') . ' ' . $last_name . (!empty($suffix) ? ' ' . $suffix : '');

    // Insert into community_users
    $stmt = $conn->prepare("INSERT INTO community_users (email, password_hash, first_name, middle_name, last_name, suffix, phone_number, account_type, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, 'user', NOW())");
    $stmt->bind_param("sssssss", $email, $password_hash, $first_name, $middle_name, $last_name, $suffix, $phone);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create user account');
    }

    $user_id = $conn->insert_id;

    // Insert into community_profiles with address
    $stmt = $conn->prepare("INSERT INTO community_profiles (user_id, province, municipality, barangay, created_at) 
                           VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("isss", $user_id, $data['province'], $data['municipality'], $data['barangay']);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create user profile');
    }

    // Log the signup activity
    $activity_description = "User {$email} signed up";
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action_type, description, created_at) 
                           VALUES (?, 'signup', ?, NOW())");
    $stmt->bind_param("is", $user_id, $activity_description);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully',
        'user_id' => $user_id
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
