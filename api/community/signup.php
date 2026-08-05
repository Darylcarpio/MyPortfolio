<?php
/**
 * Community Module - Signup API Endpoint
 * POST: /api/community/signup.php
 * Handles user registration and OTP validation flow
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

session_start();
require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/CommunityAuth_class.php';

// Ensure CommunityAuth class is available
if (!class_exists('CommunityAuth')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'System not configured. Please run setup.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'signup';

try {
    $auth = new CommunityAuth();

    switch ($action) {
        // ─── SIGNUP ACTION ───
        case 'signup':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception('Invalid request data');
            }

            // Sanitize inputs
            $data = array_map('trim', $data);

            $result = $auth->signup($data);
            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
            break;

        // ─── VERIFY OTP ACTION ───
        case 'verify-otp':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception('Invalid request data');
            }

            $email = $data['email'] ?? null;
            $otp_code = $data['otp_code'] ?? null;

            if (!$email || !$otp_code) {
                throw new Exception('Missing email or OTP code');
            }

            $result = $auth->verifyOTP($email, $otp_code);
            
            if ($result['success']) {
                // Start session
                session_start();
                $_SESSION['community_user_id'] = $result['user_id'];
                $_SESSION['community_logged_in'] = true;
            }

            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
            break;

        // ─── RESEND OTP ACTION ───
        case 'resend-otp':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception('Invalid request data');
            }

            $email = $data['email'] ?? null;
            if (!$email) {
                throw new Exception('Email is required');
            }

            $result = $auth->sendOTP($email, 'email_verification');
            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
            break;

        // ─── PASSWORD STRENGTH CHECK ───
        case 'check-password':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception('Invalid request data');
            }

            $password = $data['password'] ?? '';
            $validation = $auth->validatePassword($password);

            echo json_encode([
                'valid' => $validation['valid'],
                'strength' => $validation['strength'],
                'errors' => $validation['errors']
            ]);
            break;

        // ─── CHECK EMAIL ───
        case 'check-email':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception('Invalid request data');
            }

            $email = $data['email'] ?? '';
            $validation = $auth->validateEmail($email);

            echo json_encode([
                'valid' => $validation['valid'],
                'errors' => $validation['errors']
            ]);
            break;

        // ─── CHECK PHONE ───
        case 'check-phone':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception('Invalid request data');
            }

            $phone = $data['phone'] ?? '';
            $validation = $auth->validatePhoneNumber($phone);

            echo json_encode([
                'valid' => $validation['valid'],
                'normalized' => $validation['normalized'],
                'errors' => $validation['errors']
            ]);
            break;

        // ─── GET ASCOT DEPARTMENTS ───
        case 'get-departments':
            $departments = $auth->getASCOTDepartments();
            echo json_encode([
                'success' => true,
                'departments' => $departments
            ]);
            break;

        default:
            throw new Exception('Unknown action: ' . $action);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
