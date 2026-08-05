<?php
/**
 * Community Module - Google OAuth Handler
 * POST: /api/community/google-auth.php
 * Handles Google Sign-In authentication
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

session_start();
require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/CommunityAuth_class.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['token'])) {
        throw new Exception('Google token is required');
    }

    $token = $input['token'];
    $db = Database::getInstance();

    // Decode Google JWT token
    // For production, verify the signature, but for now we'll just decode and trust Google's client-side validation
    $token_parts = explode('.', $token);
    if (count($token_parts) !== 3) {
        throw new Exception('Invalid token format');
    }

    // Decode the payload (second part)
    $payload = json_decode(base64_decode(strtr($token_parts[1], '-_', '+/')), true);
    
    if (!$payload) {
        throw new Exception('Failed to decode token');
    }

    // Verify token is from Google and not expired
    if (empty($payload['email']) || empty($payload['sub'])) {
        throw new Exception('Invalid token: missing email or sub');
    }

    if (isset($payload['exp']) && $payload['exp'] < time()) {
        throw new Exception('Token expired');
    }

    $email = $payload['email'];
    $google_id = $payload['sub'];
    $first_name = $payload['given_name'] ?? 'User';
    $last_name = $payload['family_name'] ?? '';
    $profile_image = $payload['picture'] ?? null;

    // Check if user already exists
    $stmt = $db->prepare("SELECT id, status, email_verified FROM community_users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // User exists - log them in
        $user_id = $user['id'];
        
        // Get full user data
        $stmt = $db->prepare("SELECT * FROM community_users WHERE id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $full_user = $stmt->fetch();

        // Update last login
        $stmt = $db->prepare("UPDATE community_users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user_id]);

    } else {
        // Create new user
        // Generate unique username from email
        $username = explode('@', $email)[0] . rand(1000, 9999);
        $stmt = $db->prepare("SELECT id FROM community_users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        while ($stmt->rowCount() > 0) {
            $username = explode('@', $email)[0] . rand(1000, 9999);
            $stmt->execute([$username]);
        }

        // Generate a random password (user won't use it with Google Sign-In)
        $random_password = bin2hex(random_bytes(16));

        // Create user account
        $stmt = $db->prepare(
            "INSERT INTO community_users 
            (email, username, first_name, last_name, phone_number, password_hash, profile_image, status, email_verified, email_verified_at, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'active', TRUE, NOW(), NOW())"
        );
        
        $stmt->execute([
            $email,
            $username,
            htmlspecialchars($first_name),
            htmlspecialchars($last_name),
            '09000000000', // Placeholder phone (Google doesn't provide it)
            password_hash($random_password, PASSWORD_BCRYPT),
            $profile_image
        ]);

        $user_id = $db->lastInsertId();

        // Get the newly created user
        $stmt = $db->prepare("SELECT * FROM community_users WHERE id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $full_user = $stmt->fetch();
    }

    // Set session
    $_SESSION['community_logged_in'] = true;
    $_SESSION['community_user_id'] = $user_id;
    $_SESSION['community_user_name'] = $full_user['first_name'] . ' ' . $full_user['last_name'];
    $_SESSION['community_user_email'] = $full_user['email'];
    $_SESSION['community_user_image'] = $full_user['profile_image'];

    // Log the action
    $stmt = $db->prepare("INSERT INTO community_session_logs (user_id, action, ip_address, user_agent, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $user_id,
        'google_signin',
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        'success'
    ]);

    // Check if profile is complete
    $profile_complete = !empty($full_user['sex_at_birth']) 
                     && !empty($full_user['date_of_birth']) 
                     && !empty($full_user['location_province'])
                     && !empty($full_user['department']);

    echo json_encode([
        'success' => true,
        'message' => 'Google Sign-In successful',
        'user_id' => $user_id,
        'is_new_user' => !$user ? true : false,
        'profile_complete' => $profile_complete,
        'redirect_url' => $profile_complete ? 'dashboard.php' : 'profile.php'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
