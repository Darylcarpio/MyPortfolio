<?php
/**
 * Admin Logout Handler - Portfolio Website
 * Destroys admin session and redirects to login
 */
session_start();

// Destroy session
@session_unset();
@session_destroy();

// Clear session cookie
setcookie(session_name(), '', time() - 3600, '/');

// Additional cache prevention headers
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Redirect to index/login page
header('Location: index.php', true, 302);
exit('Redirecting...');
?>
