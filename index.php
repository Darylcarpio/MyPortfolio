<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BDC HUB</title>
    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(255,255,255,0.18), transparent 30%),
                linear-gradient(135deg, #07111f 0%, #0f2b4f 40%, #2563eb 100%);
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .hero {
            max-width: 760px;
            width: 100%;
            background: rgba(255,255,255,0.10);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 28px;
            padding: 42px;
            backdrop-filter: blur(14px);
            box-shadow: 0 25px 60px rgba(0,0,0,0.28);
            text-align: center;
        }
        .brand-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            margin-bottom: 18px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.10);
            border: 1px solid rgba(255,255,255,0.12);
        }
        .brand {
            font-size: 0.82rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            font-weight: 700;
            color: #f8fafc;
        }
        .brand-logo {
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
        }
        h1 {
            font-size: clamp(2rem, 4vw, 3rem);
            margin: 0 0 10px;
            line-height: 1.1;
            font-weight: 700;
        }
        .lead {
            font-size: 1rem;
            color: #e2e8f0;
            margin: 0 auto 24px;
            max-width: 620px;
            line-height: 1.7;
        }
        .actions {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 13px 24px;
            text-decoration: none;
            border-radius: 999px;
            font-weight: 700;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.2); }
        .btn-primary { background: linear-gradient(135deg, #f59e0b, #fbbf24); color: #111827; }
        @media (max-width: 768px) {
            .hero { padding: 28px; }
        }
    </style>
</head>
<body>
    <div class="hero">
        <div class="brand-wrap" aria-label="BDC HUB logo and name">
            <div class="brand">BDC HUB</div>
            <svg class="brand-logo" viewBox="0 0 64 64" role="img" aria-hidden="true">
                <defs>
                    <linearGradient id="hubGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#f59e0b" />
                        <stop offset="100%" stop-color="#60a5fa" />
                    </linearGradient>
                </defs>
                <circle cx="32" cy="32" r="26" fill="rgba(255,255,255,0.10)" stroke="url(#hubGrad)" stroke-width="3" />
                <circle cx="32" cy="18" r="4" fill="#f8fafc" />
                <circle cx="18" cy="36" r="4" fill="#f8fafc" />
                <circle cx="46" cy="36" r="4" fill="#f8fafc" />
                <path d="M32 22 L22 33" stroke="url(#hubGrad)" stroke-width="3" stroke-linecap="round" />
                <path d="M32 22 L42 33" stroke="url(#hubGrad)" stroke-width="3" stroke-linecap="round" />
                <path d="M22 36 H42" stroke="url(#hubGrad)" stroke-width="3" stroke-linecap="round" />
            </svg>
        </div>
        <h1>Welcome To My World</h1>
        <p class="lead">Bryan Darryl Carpio - Software Developer. View my projects, skills, and experience.</p>

        <div class="actions">
            <a class="btn btn-primary" href="dashboard.php">View Portfolio</a>
            <a class="btn" href="admin_login.php" style="background: rgba(255,255,255,0.10); color: #f8fafc; border: 1px solid rgba(255,255,255,0.18);">Sign In Your Account</a>
        </div>
    </div>
</body>
</html>
