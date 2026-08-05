<?php
/**
 * Community Admin - Forgot Password API
 * Sends password reset link via email
 */
session_start();
header('Content-Type: application/json');

require_once dirname(__FILE__) . '/../../config.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        header('Location: /MyWebsite/community/forgot-password.php?error=invalid');
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: /MyWebsite/community/forgot-password.php?error=invalid');
        exit();
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Find admin by email or username
    $stmt = $conn->prepare("
        SELECT id, email, first_name, last_name, role
        FROM community_users
        WHERE (email = ? OR username = ?) AND role IN ('community_admin', 'super_admin')
        LIMIT 1
    ");
    $stmt->execute([$email, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: /MyWebsite/community/forgot-password.php?error=not_found');
        exit();
    }

    // Generate unique reset token
    $reset_token = bin2hex(random_bytes(50));
    $expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry

    // Save reset token
    $stmt = $conn->prepare("
        INSERT INTO community_password_reset_tokens (user_id, email, reset_token, expires_at)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$user['id'], $user['email'], $reset_token, $expires_at]);

    // Create reset link
    $reset_link = "http://localhost/MyWebsite/community/reset-password.php?token=" . $reset_token;

    // Send email with reset link
    $to = $user['email'];
    $subject = "🔐 ASCOT Community - Password Reset Request";
    $message = "
        <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; background: #f5f7ff; padding: 20px; border-radius: 10px; }
                    .header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 20px; }
                    .content { background: white; padding: 20px; border-radius: 10px; }
                    .button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                    .footer { text-align: center; color: #999; font-size: 0.85rem; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>🔐 Password Reset Request</h1>
                    </div>
                    <div class='content'>
                        <p>Hello <strong>" . htmlspecialchars($user['first_name']) . "</strong>,</p>
                        <p>We received a request to reset your ASCOT Community admin password. Click the button below to create a new password.</p>
                        
                        <center>
                            <a href='" . $reset_link . "' class='button'>Reset Password</a>
                        </center>
                        
                        <p><strong>Or copy this link:</strong><br><small>" . $reset_link . "</small></p>
                        
                        <p style='color: #f44336;'><strong>⚠️ Security Note:</strong> This link expires in 1 hour. If you didn't request this, please ignore this email.</p>
                        
                        <p>Best regards,<br><strong>ASCOT Community Team</strong></p>
                    </div>
                    <div class='footer'>
                        <p>&copy; 2026 ASCOT Community. All rights reserved.</p>
                    </div>
                </div>
            </body>
        </html>
    ";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: noreply@ascotcommunity.com\r\n";

    // Send email (enable this when you have email configured)
    // mail($to, $subject, $message, $headers);

    // For now, just log it (in production, uncomment mail() above)
    error_log("Password Reset Email would be sent to: " . $to);
    error_log("Reset Link: " . $reset_link);

    // Redirect with success message
    header('Location: /MyWebsite/community/forgot-password.php?success=1');
    exit();

} catch (Exception $e) {
    error_log("Forgot Password Error: " . $e->getMessage());
    header('Location: /MyWebsite/community/forgot-password.php?error=server');
    exit();
}
?>
