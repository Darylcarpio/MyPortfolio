<?php
// admin_recover.php - Admin-only credential management
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php', true, 302);
    exit();
}

require_once 'config.php';

function isStrongPassword($password) {
    return strlen($password) >= 8
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[0-9]/', $password)
        && preg_match('/[^a-zA-Z0-9]/', $password);
}

$message = '';
$messageType = 'error';
$redirectToPanel = false;

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $currentAdminId = (int) ($_SESSION['admin_id'] ?? 0);
    $currentAdminUser = $_SESSION['admin_user'] ?? '';

    if ($currentAdminId <= 0 && $currentAdminUser !== '') {
        $lookupStmt = $conn->prepare('SELECT id, username FROM admin_users WHERE username = ? OR email = ? LIMIT 1');
        $lookupStmt->execute([$currentAdminUser, $currentAdminUser]);
        $currentAdmin = $lookupStmt->fetch(PDO::FETCH_ASSOC);

        if (!empty($currentAdmin)) {
            $currentAdminId = (int) $currentAdmin['id'];
            $currentAdminUser = $currentAdmin['username'];
            $_SESSION['admin_id'] = $currentAdminId;
        }
    }

    if ($currentAdminId <= 0 || $currentAdminUser === '') {
        throw new Exception('Missing admin session. Please log in again.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($password === '' || $confirmPassword === '') {
            throw new Exception('Password fields are required.');
        }

        if (!isStrongPassword($password)) {
            throw new Exception('Password must be 8+ chars with uppercase, number, and special character.');
        }

        if ($password !== $confirmPassword) {
            throw new Exception('Passwords do not match.');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $updateStmt = $conn->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?');
        $updateStmt->execute([$passwordHash, $currentAdminId]);

        $message = 'Password updated successfully for ' . htmlspecialchars($currentAdminUser) . '.';

        $messageType = 'success';
        $redirectToPanel = true;
    }
} catch (Exception $e) {
    $message = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Recovery</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #0d0d1a;
            color: #eef0ff;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .card {
            width: 100%;
            max-width: 480px;
            background: #16162e;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            padding: 22px;
        }
        h1 {
            margin: 0 0 6px;
            font-size: 1.5rem;
        }
        .note {
            margin: 0 0 16px;
            color: #b8c1ec;
            font-size: 0.92rem;
        }
        label {
            display: block;
            margin: 12px 0 6px;
            font-size: 0.9rem;
            color: #b8c1ec;
        }
        input {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 11px 12px;
            background: #101025;
            color: #eef0ff;
        }
        .input-wrap {
            position: relative;
        }
        .input-wrap input {
            padding-right: 44px;
        }
        .toggle-pass {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #b8c1ec;
            cursor: pointer;
            padding: 4px;
            width: auto;
            margin: 0;
            font-size: 1rem;
            line-height: 1;
        }
        .toggle-pass:hover {
            color: #eef0ff;
        }
        button {
            margin-top: 16px;
            width: 100%;
            border: 0;
            border-radius: 10px;
            padding: 12px;
            cursor: pointer;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            font-weight: 700;
        }
        .msg {
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 12px;
            font-size: 0.9rem;
        }
        .msg.error {
            background: rgba(240, 84, 84, 0.2);
            border: 1px solid rgba(240, 84, 84, 0.7);
        }
        .msg.success {
            background: rgba(67, 217, 140, 0.2);
            border: 1px solid rgba(67, 217, 140, 0.7);
        }
        .links {
            margin-top: 14px;
            text-align: center;
            font-size: 0.9rem;
        }
        .links a {
            color: #9db1ff;
            text-decoration: none;
        }
        .toast {
            margin-bottom: 12px;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 0.9rem;
        }
        .toast.success {
            background: rgba(67, 217, 140, 0.18);
            border: 1px solid rgba(67, 217, 140, 0.7);
        }
        .toast.error {
            background: rgba(240, 84, 84, 0.18);
            border: 1px solid rgba(240, 84, 84, 0.7);
        }
        .pw-meter-wrap {
            margin-top: 10px;
            margin-bottom: 6px;
        }
        .pw-meter {
            width: 100%;
            height: 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.14);
        }
        .pw-meter-fill {
            height: 100%;
            width: 0%;
            transition: width 0.2s ease, background 0.2s ease;
            background: #f05454;
        }
        .pw-meter-label {
            margin-top: 6px;
            font-size: 0.82rem;
            color: #b8c1ec;
        }
        .criteria {
            margin: 8px 0 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 5px;
        }
        .criteria li {
            font-size: 0.82rem;
            color: #b8c1ec;
        }
        .criteria .ok {
            color: #43d98c;
        }
        .criteria .no {
            color: #f05454;
        }
        .helper {
            margin-top: 6px;
            font-size: 0.82rem;
            color: #b8c1ec;
        }
        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Admin Recovery</h1>
        <p class="note">Only logged-in admins can access this page. This screen resets your current admin password only.</p>

        <?php if ($message !== ''): ?>
            <div id="recoveryMsg" class="toast <?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST" action="admin_recover.php" autocomplete="off" id="resetForm" novalidate>
            <label>Current Admin Username</label>
            <input type="text" value="<?php echo htmlspecialchars($_SESSION['admin_user'] ?? ''); ?>" readonly>

            <label for="password">New Password</label>
            <div class="input-wrap">
                <input id="password" name="password" type="password" required>
                <button type="button" class="toggle-pass" data-target="password" title="Show password" aria-label="Show password">👁</button>
            </div>
            <div class="pw-meter-wrap">
                <div class="pw-meter"><div id="pwMeterFill" class="pw-meter-fill"></div></div>
                <div id="pwMeterLabel" class="pw-meter-label">Strength: very weak</div>
            </div>
            <ul class="criteria" id="criteriaList">
                <li id="cLen" class="no">NO - At least 8 characters</li>
                <li id="cUpper" class="no">NO - At least 1 uppercase letter</li>
                <li id="cNum" class="no">NO - At least 1 number</li>
                <li id="cSpec" class="no">NO - At least 1 special character</li>
            </ul>

            <label for="confirm_password">Confirm Password</label>
            <div class="input-wrap">
                <input id="confirm_password" name="confirm_password" type="password" required>
                <button type="button" class="toggle-pass" data-target="confirm_password" title="Show password" aria-label="Show password">👁</button>
            </div>
            <div id="confirmHint" class="helper">Passwords must match.</div>

            <button type="submit" id="saveBtn" disabled>Save Admin Account</button>
        </form>

        <div class="links">
            <a href="panel.php">Back to Dashboard</a>
        </div>
    </div>

    <script>
        (function () {
            const redirectToPanel = <?php echo $redirectToPanel ? 'true' : 'false'; ?>;
            const recoveryMsg = document.getElementById('recoveryMsg');
            const form = document.getElementById('resetForm');
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('confirm_password');
            const saveBtn = document.getElementById('saveBtn');
            const meterFill = document.getElementById('pwMeterFill');
            const meterLabel = document.getElementById('pwMeterLabel');
            const confirmHint = document.getElementById('confirmHint');
            const toggleButtons = document.querySelectorAll('.toggle-pass');

            const cLen = document.getElementById('cLen');
            const cUpper = document.getElementById('cUpper');
            const cNum = document.getElementById('cNum');
            const cSpec = document.getElementById('cSpec');

            function setCriterion(node, ok, text) {
                node.className = ok ? 'ok' : 'no';
                node.textContent = (ok ? 'OK - ' : 'NO - ') + text;
            }

            function evaluate() {
                const pwd = passwordInput.value;
                const confirmPwd = confirmInput.value;

                const hasLen = pwd.length >= 8;
                const hasUpper = /[A-Z]/.test(pwd);
                const hasNum = /[0-9]/.test(pwd);
                const hasSpec = /[^a-zA-Z0-9]/.test(pwd);

                setCriterion(cLen, hasLen, 'At least 8 characters');
                setCriterion(cUpper, hasUpper, 'At least 1 uppercase letter');
                setCriterion(cNum, hasNum, 'At least 1 number');
                setCriterion(cSpec, hasSpec, 'At least 1 special character');

                const score = [hasLen, hasUpper, hasNum, hasSpec].filter(Boolean).length;
                const pct = (score / 4) * 100;
                meterFill.style.width = pct + '%';

                if (score <= 1) {
                    meterFill.style.background = '#f05454';
                    meterLabel.textContent = 'Strength: weak';
                } else if (score <= 2) {
                    meterFill.style.background = '#f6b93b';
                    meterLabel.textContent = 'Strength: fair';
                } else if (score === 3) {
                    meterFill.style.background = '#667eea';
                    meterLabel.textContent = 'Strength: good';
                } else {
                    meterFill.style.background = '#43d98c';
                    meterLabel.textContent = 'Strength: strong';
                }

                const matches = pwd !== '' && pwd === confirmPwd;
                confirmHint.textContent = matches ? 'Passwords match.' : 'Passwords must match.';
                confirmHint.style.color = matches ? '#43d98c' : '#f05454';

                const valid = hasLen && hasUpper && hasNum && hasSpec && matches;
                saveBtn.disabled = !valid;

                passwordInput.setCustomValidity(valid || pwd === '' ? '' : 'Password does not meet all required criteria.');
                confirmInput.setCustomValidity(matches || confirmPwd === '' ? '' : 'Passwords do not match.');
            }

            passwordInput.addEventListener('input', evaluate);
            confirmInput.addEventListener('input', evaluate);

            toggleButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const targetId = btn.getAttribute('data-target');
                    const input = document.getElementById(targetId);
                    if (!input) {
                        return;
                    }
                    const isHidden = input.type === 'password';
                    input.type = isHidden ? 'text' : 'password';
                    btn.textContent = isHidden ? '🙈' : '👁';
                    btn.title = isHidden ? 'Hide password' : 'Show password';
                    btn.setAttribute('aria-label', btn.title);
                });
            });

            form.addEventListener('submit', function (e) {
                evaluate();
                if (saveBtn.disabled) {
                    e.preventDefault();
                }
            });

            evaluate();

            if (redirectToPanel && recoveryMsg) {
                window.setTimeout(function () {
                    window.location.href = 'panel.php';
                }, 1500);
            }
        })();
    </script>
</body>
</html>