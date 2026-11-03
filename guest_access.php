<?php
require_once __DIR__ . '/config.php';

// Set session lifetime for guest accounts (24 hours by default) BEFORE starting session
$guest_session_lifetime = defined('GUEST_SESSION_LIFETIME') ? GUEST_SESSION_LIFETIME : 86400;
ini_set('session.gc_maxlifetime', $guest_session_lifetime);
session_set_cookie_params($guest_session_lifetime);

session_start();

if (isset($_SESSION['guest_logged_in']) && $_SESSION['guest_logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();
$active_tab = $_GET['tab'] ?? 'signin';
$guest_flash = $_SESSION['guest_flash'] ?? null;
unset($_SESSION['guest_flash']);

function ensureAuthLockTable(PDO $conn): void {
    $conn->exec("CREATE TABLE IF NOT EXISTS auth_login_locks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lock_key VARCHAR(150) NOT NULL UNIQUE,
        failed_attempts INT NOT NULL DEFAULT 0,
        locked_until DATETIME DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function setGuestFlash(string $type, string $message, ?string $redirect = null, string $tab = 'signin'): void {
    $_SESSION['guest_flash'] = [
        'type' => $type,
        'message' => $message,
        'redirect' => $redirect,
        'tab' => $tab,
    ];
}

function ensureCommunityTables(PDO $conn): void {
    $conn->exec("CREATE TABLE IF NOT EXISTS community_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) UNIQUE NOT NULL,
        username VARCHAR(50) UNIQUE NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        middle_name VARCHAR(100) DEFAULT NULL,
        last_name VARCHAR(100) DEFAULT NULL,
        suffix VARCHAR(20) DEFAULT NULL,
        phone_number VARCHAR(20) DEFAULT NULL,
        password_hash VARCHAR(255) NOT NULL,
        profile_image VARCHAR(255) DEFAULT NULL,
        role VARCHAR(30) DEFAULT 'user',
        account_type VARCHAR(30) DEFAULT 'guest',
        status VARCHAR(20) DEFAULT 'active',
        email_verified TINYINT(1) DEFAULT 1,
        email_verified_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->exec("CREATE TABLE IF NOT EXISTS community_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        province VARCHAR(100) DEFAULT NULL,
        municipality VARCHAR(100) DEFAULT NULL,
        barangay VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_profile (user_id),
        CONSTRAINT fk_community_profiles_user FOREIGN KEY (user_id) REFERENCES community_users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->exec("CREATE TABLE IF NOT EXISTS community_session_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        ip_address VARCHAR(45) DEFAULT NULL,
        user_agent VARCHAR(255) DEFAULT NULL,
        status VARCHAR(20) DEFAULT 'success',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_community_session_user (user_id),
        INDEX idx_community_session_action (action),
        CONSTRAINT fk_community_session_user FOREIGN KEY (user_id) REFERENCES community_users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

ensureCommunityTables($conn);
ensureAuthLockTable($conn);

function getLoginLockKey(string $prefix, string $identifier): string {
    return $prefix . ':' . strtolower(trim($identifier));
}

function getLockRow(PDO $conn, string $lockKey): array {
    $stmt = $conn->prepare('SELECT failed_attempts, locked_until FROM auth_login_locks WHERE lock_key = ? LIMIT 1');
    $stmt->execute([$lockKey]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : [];
}

function resetLock(PDO $conn, string $lockKey): void {
    $stmt = $conn->prepare('DELETE FROM auth_login_locks WHERE lock_key = ?');
    $stmt->execute([$lockKey]);
}

function recordFailedLock(PDO $conn, string $lockKey): array {
    $row = getLockRow($conn, $lockKey);
    $attempts = (int) ($row['failed_attempts'] ?? 0) + 1;
    $lockedUntil = null;

    if ($attempts >= 3) {
        $lockedUntil = date('Y-m-d H:i:s', time() + (2 * 60 * 60));
        $attempts = 0;
    }

    $stmt = $conn->prepare(
        'INSERT INTO auth_login_locks (lock_key, failed_attempts, locked_until) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE failed_attempts = VALUES(failed_attempts), locked_until = VALUES(locked_until), updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute([$lockKey, $attempts, $lockedUntil]);

    return [
        'attempts' => $attempts,
        'locked_until' => $lockedUntil,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $password === '') {
        $message = 'Please enter both your name and password.';
    } else {
        if ($action === 'signup') {
            // Require full name on signup (first and last)
            if (substr_count(trim($name), ' ') < 1) {
                setGuestFlash('error', 'Please enter your full name (first and last).', null, 'signup');
                header('Location: guest_access.php?tab=signup');
                exit();
            }

            $username = preg_replace('/[^a-zA-Z0-9_]+/', '', strtolower($name));
            if ($username === '') {
                $username = 'user' . time();
            }

            try {
                $stmt = $db->prepare("SELECT id FROM community_users WHERE LOWER(username) = LOWER(?) LIMIT 1");
                $stmt->execute([$username]);

                if ($stmt->fetch()) {
                    setGuestFlash('error', 'That name is already taken. Please choose another name.', null, 'signup');
                    header('Location: guest_access.php?tab=signup');
                    exit();
                } else {
                    $passwordCheck = $db->prepare("SELECT password_hash FROM community_users WHERE account_type = 'guest' OR role = 'user'");
                    $passwordCheck->execute();
                    while ($existingHash = $passwordCheck->fetchColumn()) {
                        if (password_verify($password, $existingHash)) {
                            setGuestFlash('error', 'Password already exists for an account. Try a unique password.', null, 'signup');
                            header('Location: guest_access.php?tab=signup');
                            exit();
                        }
                    }

                    $email = $username . '@user.local';
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare(
                        "INSERT INTO community_users (email, username, first_name, last_name, phone_number, password_hash, status, email_verified, email_verified_at, created_at)
                        VALUES (?, ?, ?, '', ?, ?, 'active', 1, NOW(), NOW())"
                    );
                    $stmt->execute([
                        $email,
                        $username,
                        $name,
                        '09000000000',
                        $password_hash
                    ]);

                    setGuestFlash('success', 'Account created successfully. Please sign in now.', null, 'signin');
                    header('Location: guest_access.php?tab=signin');
                    exit();
                }
            } catch (Exception $e) {
                setGuestFlash('error', 'Sign up failed. Please try a different name.', null, 'signup');
                header('Location: guest_access.php?tab=signup');
                exit();
            }

        } elseif ($action === 'signin') {
            $signinLockKey = getLoginLockKey('signin', $name);
            $signinLockRow = getLockRow($conn, $signinLockKey);

            if (!empty($signinLockRow['locked_until']) && strtotime($signinLockRow['locked_until']) > time()) {
                $remainingMinutes = (int) ceil((strtotime($signinLockRow['locked_until']) - time()) / 60);
                setGuestFlash('error', 'Too many failed attempts. Try again in ' . $remainingMinutes . ' minutes.', null, 'signin');
                header('Location: guest_access.php?tab=signin');
                exit();
            }

            $adminStmt = $db->prepare("SELECT id, username, email, password_hash FROM admin_users WHERE BINARY username = BINARY ? LIMIT 1");
            $adminStmt->execute([$name]);
            $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);

            if ($admin) {
                $adminValid = password_verify($password, $admin['password_hash']);
                if ($adminValid) {
                    resetLock($conn, $signinLockKey);
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = (int) $admin['id'];
                    $_SESSION['admin_user'] = $admin['username'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_login_time'] = time();
                    setGuestFlash('success', 'Admin sign in successful.', 'panel.php', 'signin');
                    header('Location: guest_access.php?tab=signin');
                    exit();
                }
            }

            // If not an admin, require full name (first and last) for sign-in
            if (strpos(trim($name), ' ') === false) {
                setGuestFlash('error', 'Please enter your full name (first and last) to sign in.', null, 'signin');
                header('Location: guest_access.php?tab=signin');
                exit();
            }

            $lookupName = preg_replace('/[^a-zA-Z0-9_]+/', '', strtolower($name));
            $guestStmt = $db->prepare("SELECT id, username, first_name, password_hash, status FROM community_users WHERE LOWER(username) = LOWER(?) OR LOWER(first_name) = LOWER(?) OR LOWER(email) = LOWER(?) LIMIT 1");
            $guestStmt->execute([$name, $name, $name]);
            $guest = $guestStmt->fetch(PDO::FETCH_ASSOC);

            if (!$guest && $lookupName !== '') {
                $guestStmt->execute([$lookupName, $name, $lookupName . '@guest.local']);
                $guest = $guestStmt->fetch(PDO::FETCH_ASSOC);
            }

            if ($guest && $guest['status'] === 'active' && password_verify($password, $guest['password_hash'])) {
                    resetLock($conn, $signinLockKey);
                    $_SESSION['guest_logged_in'] = true;
                    $_SESSION['guest_name'] = $guest['first_name'] ?: $guest['username'];
                    $_SESSION['guest_role'] = 'guest';
                    setGuestFlash('success', 'Guest sign in successful.', 'dashboard.php', 'signin');
                    header('Location: guest_access.php?tab=signin');
                    exit();
            } else {
                $lock = recordFailedLock($conn, $signinLockKey);

                if ($lock['locked_until']) {
                    setGuestFlash('error', 'Too many failed attempts. Try again in 2 hours.', null, 'signin');
                } else {
                    $remaining = 3 - (int) $lock['attempts'];
                    setGuestFlash('error', 'No matching account was found. ' . $remaining . ' attempt(s) left.', null, 'signin');
                }
                header('Location: guest_access.php?tab=signin');
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BDC HUB - Sign In</title>
    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #07111f 0%, #0f2b4f 40%, #2563eb 100%);
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .wrap {
            width: 100%;
            max-width: 420px;
            background: rgba(255,255,255,0.08);
            border-radius: 20px;
            padding: 40px 32px;
            backdrop-filter: blur(16px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }
        .brand {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,0.12);
            font-size: 0.75rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            margin-bottom: 16px;
            font-weight: 600;
        }
        h1 { margin: 0 0 8px; font-size: 1.75rem; font-weight: 700; }
        p { color: #cbd5e1; line-height: 1.6; margin-bottom: 28px; font-size: 0.95rem; }
        .panel {
            display: none;
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 0.25s ease, transform 0.25s ease;
        }
        .panel.active {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }
        .panel h3 {
            margin: 0 0 24px;
            font-size: 1.3rem;
            font-weight: 600;
            color: #fff;
        }
        .panel-actions {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 0.9rem;
            color: #cbd5e1;
        }
        .panel-actions a {
            color: #fbbf24;
            text-decoration: none;
            font-weight: 600;
        }
        .panel-actions a:hover {
            text-decoration: underline;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            color: #e2e8f0;
        }
        .password-field {
            position: relative;
        }
        input {
            width: 100%;
            padding: 14px 50px 14px 16px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            background: rgba(255,255,255,0.08);
            color: #fff;
            margin-bottom: 16px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        input::placeholder { color: #64748b; }
        input:focus {
            outline: none;
            border-color: #fbbf24;
            background: rgba(255,255,255,0.12);
            box-shadow: 0 0 0 3px rgba(251,191,36,0.15);
        }
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear {
            display: none;
        }
        input[type="password"]::-webkit-credentials-auto-fill-button,
        input[type="password"]::-webkit-contacts-auto-fill-button {
            visibility: hidden;
            display: none !important;
            pointer-events: none;
        }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 32px;
            height: 32px;
            padding: 0;
            border: none;
            background: transparent;
            color: #94a3b8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 2;
            transition: color 0.2s ease;
        }
        .toggle-password:hover {
            color: #fbbf24;
        }
        .toggle-password.hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
        button[type="submit"] {
            width: 100%;
            padding: 14px 20px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            color: #111827;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(251,191,36,0.35);
        }
        .error { color: #fecaca; margin-top: 12px; font-size: 0.9rem; }
        .toggle-password svg {
            width: 18px;
            height: 18px;
            display: block;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .toast {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            min-width: 280px;
            max-width: min(92vw, 540px);
            padding: 14px 20px;
            border-radius: 12px;
            color: #fff;
            font-weight: 600;
            font-size: 0.95rem;
            box-shadow: 0 12px 28px rgba(0,0,0,0.3);
            z-index: 9999;
            opacity: 0;
            animation: toastIn 0.25s ease forwards;
        }
        .toast.success { background: linear-gradient(135deg, #16a34a, #22c55e); }
        .toast.error { background: linear-gradient(135deg, #dc2626, #ef4444); }
        .toast.hide { animation: toastOut 0.25s ease forwards; }
        @keyframes toastIn {
            from { opacity: 0; transform: translateX(-50%) translateY(-10px); }
            to { opacity: 1; transform: translateX(-50%) translateY(0); }
        }
        @keyframes toastOut {
            from { opacity: 1; transform: translateX(-50%) translateY(0); }
            to { opacity: 0; transform: translateX(-50%) translateY(-10px); }
        }
    </style>
</head>
<body>
    <?php if (!empty($guest_flash)) : ?>
        <div id="flashToast" class="toast <?= htmlspecialchars($guest_flash['type']) ?>">
            <?= htmlspecialchars($guest_flash['message']) ?>
        </div>
    <?php endif; ?>

    <div class="wrap">
        <div class="brand">BDC HUB</div>
        <h1>Welcome</h1>
        <p>Sign in to your account or sign up to create a new account.</p>

        <?php if (!empty($message)) : ?>
            <div class="error"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div id="signinPanel" class="panel">
            <h3>Sign In to Your Account</h3>
            <form method="post" autocomplete="off">
                <input type="hidden" name="action" value="signin">
                <label for="signin_name">Name</label>
                <input id="signin_name" name="name" autocomplete="off" required>
                <label for="signin_password">Password</label>
                <div class="password-field">
                    <input id="signin_password" name="password" type="password" autocomplete="new-password" required>
                    <button type="button" class="toggle-password" data-target="signin_password" aria-label="Show password">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
                <button type="submit">Sign In</button>
            </form>
            <div class="panel-actions">
                Don't have an account? <a href="#" id="goSignup">Sign Up</a>
            </div>
        </div>

        <div id="signupPanel" class="panel">
            <h3>Sign Up</h3>
            <form method="post" autocomplete="off">
                <input type="hidden" name="action" value="signup">
                <label for="signup_name">Name</label>
                <input id="signup_name" name="name" autocomplete="off" required>
                <label for="signup_password">Password</label>
                <div class="password-field">
                    <input id="signup_password" name="password" type="password" autocomplete="new-password" required>
                    <button type="button" class="toggle-password" data-target="signup_password" aria-label="Show password">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
                <button type="submit">Sign Up</button>
            </form>
            <div class="panel-actions">
                Already have an account? <a href="#" id="goSignin">Sign In</a>
            </div>
        </div>
    </div>

    <script>
        const signinPanel = document.getElementById('signinPanel');
        const signupPanel = document.getElementById('signupPanel');
        const activeTab = <?= json_encode($active_tab) ?>;
        const flashRedirect = <?= json_encode($guest_flash['redirect'] ?? null) ?>;
        const flashToast = document.getElementById('flashToast');

        function openPanel(panel) {
            signinPanel.classList.remove('active');
            signupPanel.classList.remove('active');
            panel.classList.add('active');
        }

        document.getElementById('goSignup').addEventListener('click', function (event) {
            event.preventDefault();
            openPanel(signupPanel);
            document.getElementById('signup_name').focus();
        });

        document.getElementById('goSignin').addEventListener('click', function (event) {
            event.preventDefault();
            openPanel(signinPanel);
            document.getElementById('signin_name').focus();
        });

        document.querySelectorAll('.toggle-password').forEach(function (button) {
            const target = document.getElementById(button.getAttribute('data-target'));

            function syncVisibility() {
                if (target.value.trim().length > 0) {
                    button.classList.remove('hidden');
                } else {
                    button.classList.add('hidden');
                }
            }

            syncVisibility();

            target.addEventListener('input', syncVisibility);

            button.addEventListener('click', function () {
                const isPassword = target.type === 'password';
                target.type = isPassword ? 'text' : 'password';
                button.innerHTML = isPassword
                    ? '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18"></path><path d="M10.6 10.6A3 3 0 0 0 13.4 13.4"></path><path d="M9.88 5.09A10.47 10.47 0 0 1 12 5c6.5 0 10 7 10 7a18.4 18.4 0 0 1-3.2 4.4"></path><path d="M6.1 6.1C3.6 8.1 2 12 2 12s3.5 7 10 7a10.9 10.9 0 0 0 4.7-1"></path></svg>'
                    : '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
                button.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
                syncVisibility();
            });
        });

        openPanel(activeTab === 'signup' ? signupPanel : signinPanel);

        if (flashToast) {
            window.setTimeout(function () {
                flashToast.classList.add('hide');
            }, 1500);

            if (flashRedirect) {
                window.setTimeout(function () {
                    window.location.href = flashRedirect;
                }, 1500);
            }
        }
    </script>
</body>
</html>
