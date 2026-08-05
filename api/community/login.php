<?php
/**
 * Community Module - Login API Endpoint
 * POST: /api/community/login.php
 * Handles user authentication
 */

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/CommunityAuth_class.php';

if (!class_exists('CommunityAuth')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'System not configured']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'login';

try {
    $auth = new CommunityAuth();

    switch ($action) {
        // ─── LOGIN ACTION ───
        case 'login':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }

            // Handle both JSON and form-encoded POST
            if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
                $data = json_decode(file_get_contents('php://input'), true);
            } else {
                $data = $_POST;
            }
            
            if (!$data) {
                throw new Exception('Invalid request data');
            }

            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';

            if (!$email || !$password) {
                throw new Exception('Email and password are required');
            }

            $result = $auth->login($email, $password);

            // --- debug logging for failed logins ---
            $logPath = dirname(__DIR__, 1) . '/../Temp/login_debug.log';
            try {
                $dbInst = Database::getInstance();
                $connLog = $dbInst->getConnection();
                $stmtLog = $connLog->prepare("SELECT id, email, username, password_hash, status FROM community_users WHERE email = ? OR username = ? LIMIT 1");
                $stmtLog->execute([$email, $email]);
                $userLog = $stmtLog->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $le) {
                $userLog = null;
            }

            if ($result['success']) {
                // Session already started at top of file, set variables directly
                $_SESSION['community_user_id'] = $result['user']['id'];
                $_SESSION['community_user_email'] = $result['user']['email'];
                $_SESSION['community_user_name'] = $result['user']['first_name'] . ' ' . $result['user']['last_name'];
                $_SESSION['community_user_role'] = $result['user']['role'];
                $_SESSION['community_logged_in'] = true;
                
                // Force session to be written to storage before response
                session_write_close();
                
                // If form POST (not JSON), redirect instead of JSON
                $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
                if (strpos($content_type, 'application/json') === false) {
                    header('Location: /MyWebsite/community/dashboard.php', true, 302);
                    exit('Redirecting to dashboard...');
                }
            } else {
                // write debug entry
                $entry = date('c') . " | LOGIN_FAIL | email=" . $email . " | code=" . ($result['code'] ?? 'invalid') . "";
                if (!empty($userLog)) {
                    $entry .= " | user_id=" . $userLog['id'] . " | status=" . $userLog['status'] . " | hash_prefix=" . substr($userLog['password_hash'], 0, 12);
                } else {
                    $entry .= " | no_user_found";
                }
                $entry .= PHP_EOL;
                @file_put_contents($logPath, $entry, FILE_APPEND | LOCK_EX);
                // If form POST, redirect with specific error code for UI notifications
                $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
                $errorCode = $result['code'] ?? 'invalid';
                if (strpos($content_type, 'application/json') === false) {
                    header('Location: /MyWebsite/community/index-new.php?error=' . urlencode($errorCode), true, 302);
                    exit('Login failed. Redirecting...');
                }
            }

            http_response_code($result['success'] ? 200 : 401);
            echo json_encode($result);
            break;

        // ─── LOGOUT ACTION ───
        case 'logout':
            session_start();
            session_destroy();
            echo json_encode([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
            break;

        // ─── CHECK SESSION ───
        case 'check-session':
            session_start();
            
            if (isset($_SESSION['community_logged_in']) && $_SESSION['community_logged_in'] === true) {
                $auth_instance = new CommunityAuth();
                $user = $auth_instance->getUserById($_SESSION['community_user_id']);
                
                if (!empty($user)) {
                    $user = reset($user);
                    echo json_encode([
                        'logged_in' => true,
                        'user' => [
                            'id' => $user['id'],
                            'email' => $user['email'],
                            'first_name' => $user['first_name'],
                            'last_name' => $user['last_name'],
                            'profile_image' => $user['profile_image'],
                            'role' => $user['role'],
                            'status' => $user['status']
                        ]
                    ]);
                } else {
                    session_destroy();
                    echo json_encode(['logged_in' => false]);
                }
            } else {
                echo json_encode(['logged_in' => false]);
            }
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
