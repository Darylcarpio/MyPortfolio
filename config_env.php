<?php
/**
 * Environment-aware Configuration Loader
 * Loads configuration from .env file or uses defaults
 * This file should be committed to git (unlike config.php)
 */

// Load environment variables from .env file
function loadEnv($file = '.env') {
    if (!file_exists($file)) {
        return false;
    }
    
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
    return true;
}

// Load environment variables
loadEnv(__DIR__ . '/.env');

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'db_portfolio');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

// Admin Credentials
define('ADMIN_USERNAME', getenv('ADMIN_USERNAME') ?: 'admin');
define('ADMIN_PASSWORD', getenv('ADMIN_PASSWORD') ?: 'ChangeThisToStrongPassword123!');
define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: 'admin@example.com');

// Security Settings
define('SESSION_LIFETIME', (int)(getenv('SESSION_LIFETIME') ?: 3600));
define('MAX_LOGIN_ATTEMPTS', (int)(getenv('MAX_LOGIN_ATTEMPTS') ?: 3));
define('LOCKOUT_DURATION', (int)(getenv('LOCKOUT_DURATION') ?: 7200));

// Site Configuration
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost');
define('SITE_NAME', getenv('SITE_NAME') ?: 'Portfolio System');

// Email Configuration (Optional)
define('MAIL_HOST', getenv('MAIL_HOST') ?: 'localhost');
define('MAIL_PORT', (int)(getenv('MAIL_PORT') ?: 25);
define('MAIL_USER', getenv('MAIL_USER') ?: '');
define('MAIL_PASS', getenv('MAIL_PASS') ?: '');
define('MAIL_ENCRYPTION', getenv('MAIL_ENCRYPTION') ?: '');

// Production Check
define('IS_PRODUCTION', getenv('ENVIRONMENT') === 'production');

// Error Reporting (disable in production)
if (IS_PRODUCTION) {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
