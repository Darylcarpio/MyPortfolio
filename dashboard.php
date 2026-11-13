<?php
/**
 * dashboard.php — Public Portfolio Page
 * 
 * ISO 25010 Quality Attributes:
 * - Functional Suitability: Complete portfolio display
 * - Performance Efficiency: Lazy loading, optimized assets
 * - Compatibility: Cross-browser, responsive design
 * - Usability: Accessible, intuitive navigation
 * - Reliability: Error handling, fallbacks
 * - Security: XSS protection, input sanitization
 * - Maintainability: Modular structure, comments
 * - Portability: Standards-compliant HTML5
 * 
 * @author Bryan Darryl Carpio
 * @version 1.0.0
 * @since 2024
 */
session_start();

// Include database configuration and DAL
require_once 'config.php';
$dal = new PortfolioDAL();

// Log this page view (Analytics - ISO: Performance Efficiency)
$dal->logPageView('dashboard');

// Get profile data with error handling (ISO: Reliability)
$profile = $dal->getProfile();
if (!$profile) {
    $profile = [
        'full_name' => 'Portfolio Owner',
        'professional_title' => 'Student Portfolio',
        'location' => 'Philippines',
        'bio' => 'Building a future in technology.',
        'profile_image' => 'Me.jpg'
    ];
}

// Normalize column names for backward compatibility
if (isset($profile['full_name'])) {
    $profile['name'] = $profile['full_name'];
}
if (isset($profile['professional_title'])) {
    $profile['title'] = $profile['professional_title'];
}
if (isset($profile['bio'])) {
    $profile['vision'] = $profile['bio'];
}

// Helper function to group hobbies by category
function groupHobbiesByCategory($hobbies_data) {
    $grouped = [];
    foreach ($hobbies_data as $hobby) {
        $category_key = $hobby['category_name'];
        if (!isset($grouped[$category_key])) {
            $grouped[$category_key] = [
                'display_name' => $hobby['display_name'],
                'category_icon' => $hobby['category_icon'],
                'items' => []
            ];
        }
        if (!empty($hobby['item_name'])) {
            $grouped[$category_key]['items'][] = [
                'name' => $hobby['item_name'],
                'image' => $hobby['image'],
                'audio_file' => $hobby['audio_file'] ?? '',
                'description' => $hobby['description'] ?? '',
                'proficiency' => $hobby['proficiency_level'] ?? 0
            ];
        }
    }
    return $grouped;
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <!-- ISO 25010: Compatibility - Character Encoding -->
    <meta charset="UTF-8">
    
    <!-- ISO 25010: Compatibility - Responsive Design -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    
    <!-- ISO 25010: Usability - SEO Meta Tags -->
    <title><?php echo htmlspecialchars($profile['name']); ?> | ASCOT IT Student Portfolio</title>
    <meta name="description" content="Official portfolio of <?php echo htmlspecialchars($profile['name']); ?>, a BSIT student at Aurora State College of Technology (ASCOT). Showcasing academic achievements, projects, and skills.">
    <meta name="keywords" content="ASCOT, BSIT, IT Student, Portfolio, Aurora State College of Technology, <?php echo htmlspecialchars($profile['name']); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($profile['name']); ?>">
    <meta name="robots" content="index, follow">
    
    <!-- ISO 25010: Compatibility - Open Graph for Social Sharing -->
    <meta property="og:title" content="<?php echo htmlspecialchars($profile['name']); ?> | IT Student Portfolio">
    <meta property="og:description" content="BSIT Student Portfolio - Aurora State College of Technology">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="en_PH">
    
    <!-- ISO 25010: Security - Content Security Policy -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- ISO 25010: Usability - Theme Color -->
    <meta name="theme-color" content="#667eea">
    
    <!-- ISO 25010: Performance Efficiency - Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- ISO 25010: Usability - Favicon -->
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link rel="apple-touch-icon" href="img/favicon.ico">
    
    <!-- Stylesheet -->
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Security: Prevent Inspect & Screenshot Protection -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <script>
        // Prevent flash of wrong theme on load
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.style.setProperty('--bg-overlay', 'rgba(245, 245, 250, 0.9)');
            document.documentElement.style.setProperty('--card-bg', 'rgba(255, 255, 255, 0.9)');
            document.documentElement.style.setProperty('--text-main', '#333333');
            document.documentElement.style.setProperty('--text-secondary', '#555555');
        }
    </script>
    
    <style>
        /* Prevent text selection and right-click context menu */
        body {
            user-select: none !important;
            -webkit-user-select: none !important;
            -moz-user-select: none !important;
            -ms-user-select: none !important;
            -webkit-touch-callout: none !important;
        }
        * {
            user-select: none !important;
            -webkit-user-select: none !important;
        }
        input, textarea, [contenteditable] {
            user-select: auto !important;
            -webkit-user-select: auto !important;
        }
    </style>
    
    <script>
        // ===== COMPREHENSIVE PROTECTION AGAINST INSPECTION & COPYING =====
        (function() {
            'use strict';
            
            // Detect and block developer tools
            let devtoolsOpen = false;
            
            // Detect F12, Ctrl+Shift+I, Ctrl+Shift+C, Ctrl+Shift+J
            document.addEventListener('keydown', function(e) {
                // F12 - Opens Developer Tools
                if (e.key === 'F12') {
                    e.preventDefault();
                    return false;
                }
                // Ctrl+Shift+I or Cmd+Shift+I - Inspect Element
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'I') {
                    e.preventDefault();
                    return false;
                }
                // Ctrl+Shift+C or Cmd+Shift+C - Inspect with pointer
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'C') {
                    e.preventDefault();
                    return false;
                }
                // Ctrl+Shift+J or Cmd+Shift+J - Console
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'J') {
                    e.preventDefault();
                    return false;
                }
                // Ctrl+Shift+K - WebKit Console
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'K') {
                    e.preventDefault();
                    return false;
                }
                // Ctrl+Shift+M - Toggle device toolbar
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'M') {
                    e.preventDefault();
                    return false;
                }
                // Ctrl+R or F5 - Page reload prevention (optional - commented out)
                // if ((e.ctrlKey && e.key === 'r') || e.key === 'F5') {
                //     e.preventDefault();
                //     return false;
                // }
            }, true);
            
            // Block right-click context menu
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }, true);
            
            // Block selection of text
            document.addEventListener('selectstart', function(e) {
                e.preventDefault();
                return false;
            }, true);
            
            // Block mouse selection
            document.addEventListener('mousedown', function(e) {
                if (e.detail > 1) { // Double/triple click detection
                    e.preventDefault();
                    return false;
                }
            }, true);
            
            // Disable copy
            document.addEventListener('copy', function(e) {
                e.preventDefault();
                e.clipboardData.setData('text/plain', '');
                return false;
            }, true);
            
            // Disable cut
            document.addEventListener('cut', function(e) {
                e.preventDefault();
                return false;
            }, true);
            
            // Check for devtools periodically
            setInterval(function() {
                // This is a basic check - won't detect all scenarios
                if (window.outerHeight - window.innerHeight > 150 || 
                    window.outerWidth - window.innerWidth > 150) {
                    devtoolsOpen = true;
                }
            }, 1000);
        })();
    </script>
    <style>
        /* Horizontal Timeline Styles */
        .timeline-horizontal {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 40px;
        }
        
        .timeline-horizontal-item {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 25px;
            backdrop-filter: blur(10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .timeline-horizontal-item:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        
        .timeline-horizontal-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--gradient-1);
        }
        
        .timeline-horizontal-year {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--highlight);
            margin-bottom: 15px;
            display: inline-block;
            padding: 5px 15px;
            background: rgba(240,84,84,0.1);
            border-radius: 20px;
        }
        
        .timeline-horizontal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 10px;
        }
        
        .timeline-horizontal-subtitle {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .timeline-horizontal-achievements {
            list-style: none;
            padding: 0;
        }
        
        .timeline-horizontal-achievements li {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 8px;
            padding-left: 20px;
            position: relative;
        }
        
        .timeline-horizontal-achievements li::before {
            content: '✓';
            color: var(--highlight);
            position: absolute;
            left: 0;
            font-weight: bold;
        }
        
        /* Allow specific contact links to be clickable */
        .contact-info a.contact-link {
            text-decoration: underline;
            color: var(--highlight);
            cursor: pointer;
            pointer-events: auto;
        }

        .contact-item {
            cursor: default;
        }

        .contact-item[onclick] {
            cursor: pointer;
        }

        .contact-item[onclick]:hover {
            transform: translateX(10px);
        }
        
        /* Projects Section */
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 40px;
        }
        
        .project-card-link {
            text-decoration: none;
            color: inherit;
            display: block;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .project-link-btn {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102,126,234,0.3);
        }

        .project-link-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102,126,234,0.5);
        }

        /* Project Gallery Modal */
        .project-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .project-modal.active {
            display: flex;
            opacity: 1;
        }

        .project-modal-content {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            max-width: 900px;
            max-height: 90%;
            overflow-y: auto;
            padding: 0;
            position: relative;
            backdrop-filter: blur(10px);
            display: flex;
            flex-direction: row;
            transform: scale(0.9) translateY(20px);
            transition: transform 0.3s ease;
        }

        .project-modal.active .project-modal-content {
            transform: scale(1) translateY(0);
        }

        .project-modal-left {
            width: 60%;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0.2);
        }

        .project-modal-image {
            width: 100%;
            max-height: 500px;
            object-fit: contain;
            border-radius: 12px;
            transition: opacity 0.2s ease;
        }

        .project-modal-right {
            width: 40%;
            padding: 30px;
            display: flex;
            flex-direction: column;
            background: rgba(26,26,46,0.5);
        }

        .project-modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.1);
            border: none;
            color: white;
            font-size: 1.5rem;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .project-modal-close:hover {
            background: rgba(255,255,255,0.2);
            transform: rotate(90deg);
        }

        .project-modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 15px;
            margin-top: 0;
            transition: opacity 0.2s ease;
        }

        .project-modal-description {
            color: var(--text-secondary);
            font-size: 1rem;
            line-height: 1.6;
            flex: 1;
            overflow-y: auto;
            max-height: 300px;
            padding-right: 10px;
            transition: opacity 0.2s ease;
            text-align: justify;
        }

        .project-modal-nav {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid var(--border);
        }

        .project-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            backdrop-filter: blur(10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }

        .project-card-link:hover .project-card {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        
        .project-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-bottom: 3px solid var(--gradient-1);
        }
        
        .project-content {
            padding: 20px;
        }
        
        .project-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 10px;
        }
        
        .project-description {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .project-tech {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .tech-tag {
            background: rgba(102,126,234,0.2);
            color: #667eea;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .project-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            background: var(--gradient-2);
            color: white;
        }
        
        /* Coming Soon Badge */
        .coming-soon-badge {
            text-align: center;
            padding: 40px;
            background: rgba(255,255,255,0.05);
            border-radius: 20px;
            border: 2px dashed var(--border);
            margin-top: 20px;
        }
        
        .coming-soon-badge p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }
        
        .coming-soon-badge span {
            color: var(--highlight);
            font-weight: 700;
        }

        /* Hobbies Item Description */
        .item-description {
            color: var(--text-secondary);
            font-size: 0.85rem;
            line-height: 1.5;
            margin-top: 8px;
            padding: 8px;
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
        }

        /* Hobby Modal */
        .hobby-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .hobby-modal.active {
            display: flex;
            opacity: 1;
        }

        .hobby-modal-content {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            max-width: 900px;
            max-height: 90%;
            overflow-y: auto;
            padding: 0;
            position: relative;
            backdrop-filter: blur(10px);
            display: flex;
            flex-direction: row;
            transform: scale(0.9) translateY(20px);
            transition: transform 0.3s ease;
        }

        .hobby-modal.active .hobby-modal-content {
            transform: scale(1) translateY(0);
        }

        .hobby-modal-content {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            max-width: 900px;
            max-height: 90%;
            overflow-y: auto;
            padding: 0;
            position: relative;
            backdrop-filter: blur(10px);
            display: flex;
            flex-direction: row;
        }

        .hobby-modal-left {
            flex: 0 0 60%;
            background: rgba(0,0,0,0.3);
            padding: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 20px 0 0 20px;
        }

        .hobby-modal-right {
            flex: 0 0 40%;
            padding: 30px;
            position: relative;
            background: var(--card-bg);
            border-radius: 0 20px 20px 0;
            display: flex;
            flex-direction: column;
        }

        .hobby-modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0,0,0,0.7);
            color: #ffffff;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .hobby-modal-close:hover {
            background: rgba(240,84,84,0.9);
            color: #ffffff;
            border-color: rgba(240,84,84,0.5);
        }

        .hobby-modal-image {
            width: 100%;
            max-height: 500px;
            object-fit: contain;
            border-radius: 12px;
            transition: opacity 0.2s ease;
        }

        .hobby-modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 15px;
            margin-top: 0;
            transition: opacity 0.2s ease;
        }

        .hobby-modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 15px;
            margin-top: 0;
        }

        .hobby-modal-description {
            color: var(--text-secondary);
            font-size: 1rem;
            line-height: 1.6;
            flex: 1;
            overflow-y: auto;
            max-height: 300px;
            padding-right: 10px;
            transition: opacity 0.2s ease;
            text-align: justify;
        }

        .hobby-modal-description::-webkit-scrollbar {
            width: 6px;
        }

        .hobby-modal-description::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.05);
            border-radius: 3px;
        }

        .hobby-modal-description::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.2);
            border-radius: 3px;
        }

        .hobby-modal-description::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.3);
        }

        .hobby-modal-proficiency {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid var(--border);
        }

        .proficiency-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 8px;
            font-weight: 600;
        }

        .proficiency-bar-container {
            height: 8px;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 5px;
        }

        .proficiency-bar {
            height: 100%;
            background: linear-gradient(90deg, #f05454 0%, #f6b93b 50%, #43d98c 100%);
            border-radius: 4px;
            width: 0%;
            transition: width 1s ease-out;
        }

        .proficiency-value {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .hobby-modal-nav {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid var(--border);
        }

        .nav-btn {
            flex: 1;
            padding: 10px 15px;
            background: rgba(102,126,234,0.1);
            border: 1px solid rgba(102,126,234,0.3);
            border-radius: 8px;
            color: var(--text-main);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .nav-btn:hover {
            background: rgba(102,126,234,0.2);
            border-color: rgba(102,126,234,0.5);
            transform: translateY(-2px);
        }

        .nav-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
            transform: none;
        }

        .nav-btn:disabled:hover {
            background: rgba(102,126,234,0.1);
            border-color: rgba(102,126,234,0.3);
            transform: none;
        }

        /* Custom Toast Notification */
        .toast-notification {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(-100px);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(102,126,234,0.4);
            z-index: 20000;
            opacity: 0;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .toast-notification.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }

        .toast-icon {
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
<!-- ISO 25010: Reliability - Theme initialization -->
<script>if(localStorage.getItem('theme')==='light')document.body.classList.add('light-theme');</script>

<!-- ISO 25010: Reliability - No JavaScript Fallback -->
<noscript>
    <style>
        .theme-switcher, .back-to-top, .progress-bar { display: none !important; }
        .hobby-list { display: grid !important; }
    </style>
    <div style="background: #f05454; color: white; padding: 15px; text-align: center; font-family: sans-serif;">
        <strong>Notice:</strong> JavaScript is disabled. Some interactive features may not work properly.
    </div>
</noscript>

<!-- ISO 25010: Accessibility - Skip Navigation Link -->
<a href="#main-content" class="skip-link" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;z-index:9999;padding:15px;background:#667eea;color:white;text-decoration:none;font-weight:bold;">
    Skip to main content
</a>

<!-- MAIN PORTFOLIO CONTENT -->
<div class="overlay-wrapper" role="main" id="main-content">
    <div class="bg-element"></div>
    <div class="bg-element"></div>
    <div class="bg-element"></div>

    <div class="progress-bar"></div>
    <button class="back-to-top" aria-label="Back to top">↑</button>

    <div class="notification">
        <div class="notification-content">
            <div class="notification-icon">📧</div>
            <div class="notification-text">Email copied to clipboard!</div>
        </div>
    </div>

    <header>
        <button class="menu-toggle" aria-label="Toggle navigation menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <nav>
            <ul>
                <li><a href="#home" class="nav-link">Home</a></li>
                <li><a href="#about" class="nav-link">About</a></li>
                <li><a href="#education" class="nav-link">Education</a></li>
                <li><a href="#hobbies" class="nav-link">Skills &amp; Hobbies</a></li>
                <li><a href="#projects" class="nav-link">Projects</a></li>
                <li><a href="#contact" class="nav-link">Contact</a></li>
            </ul>
        </nav>

        <div class="header-controls">
            <div class="theme-switcher" aria-label="Switch theme" role="button" tabindex="0"></div>
            <a href="index.php" class="logout-btn" aria-label="Back to Home">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                <span>Home</span>
            </a>
        </div>
    </header>

    <!-- Toast Notification -->
    <div id="toastNotification" class="toast-notification">
        <span class="toast-icon">📭</span>
        <span id="toastMessage">No items in this category yet!</span>
    </div>

    <!-- HOME SECTION - ISO 25010: Functional Suitability -->
    <section id="home" aria-label="Hero Section">
        <div class="profile-container">
            <img src="img/<?php echo htmlspecialchars(basename($profile['profile_image'] ?? 'Me.jpg')); ?>" 
                 alt="Profile photo of <?php echo htmlspecialchars($profile['name']); ?>, BSIT Student at ASCOT" 
                 class="profile-img"
                 loading="eager"
                 decoding="async"
                 width="260"
                 height="260"
                 sizes="(max-width: 768px) 200px, (max-width: 480px) 180px, 260px"
                 srcset="img/<?php echo htmlspecialchars(basename($profile['profile_image'] ?? 'Me.jpg')); ?> 260w">
        </div>
        <div class="hero-text">
            <h1><?php echo htmlspecialchars($profile['name']); ?></h1>
            <p><?php echo htmlspecialchars($profile['title']); ?></p>
            <div class="btn-container">
                <a href="#about" class="btn">Explore My Journey</a>
                <a href="#contact" class="btn btn-secondary">Get In Touch</a>
            </div>
        </div>
    </section>

    <!-- ABOUT SECTION - ISO 25010: Usability -->
    <section id="about" aria-label="About Me Section">
        <h2>About Me</h2>
        <div class="about-content">
            <div class="about-text">
                <div><?php echo $profile['bio']; ?></div>
                <?php if (!empty($profile['vision'])): ?>
                <p style="margin-top: 35px; margin-bottom: 15px;"><strong>My Vision &amp; Aspirations</strong></p>
                <div><?php echo $profile['vision']; ?></div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- EDUCATION SECTION - ISO 25010: Functional Suitability -->
    <section id="education" aria-label="Education Timeline Section">
        <h2>Education Timeline</h2>
        <p style="color: var(--text-secondary); margin-bottom: 20px; font-size: 1.2rem;">My academic journey from elementary to college</p>

        <div class="timeline-horizontal">
            <?php
            $education_data = $dal->getEducation();
            foreach ($education_data as $edu):
                $achievements = formatAchievements($edu['achievements']);
            ?>
            <div class="timeline-horizontal-item">
                <div class="timeline-horizontal-year"><?php echo htmlspecialchars($edu['year_range']); ?></div>
                <h3 class="timeline-horizontal-title"><?php echo htmlspecialchars($edu['title']); ?></h3>
                <div class="timeline-horizontal-subtitle"><?php echo htmlspecialchars($edu['school']); ?></div>
                <?php if (!empty($edu['description'])): ?>
                <p style="color: var(--text-secondary); margin-bottom: 15px;"><?php echo htmlspecialchars($edu['description']); ?></p>
                <?php endif; ?>
                <?php if (!empty($achievements)): ?>
                <ul class="timeline-horizontal-achievements">
                    <?php foreach ($achievements as $achievement): ?>
                    <li><?php echo htmlspecialchars($achievement); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- HOBBIES SECTION - ISO 25010: Functional Suitability -->
    <section id="hobbies" aria-label="Skills and Hobbies Section">
        <h2>Skills &amp; Hobbies</h2>
        <p style="color: var(--text-secondary); margin-bottom: 20px; font-size: 1.2rem;">Discover my passions and interests beyond coding</p>

        <div class="grid-container">
            <?php
            $hobbies_data = $dal->getHobbies();
            $grouped_hobbies = groupHobbiesByCategory($hobbies_data);
            
            foreach ($grouped_hobbies as $category => $data):
            ?>
            <div class="card">
                <h3><?php echo htmlspecialchars($data['display_name']); ?> <?php echo htmlspecialchars($data['category_icon']); ?></h3>
                <p>Discover my passions and interests in this category.</p>
                <button onclick="openFirstItem('<?php echo $category; ?>')" class="hobby-btn" id="<?php echo $category; ?>-btn">View Collection</button>
                <div id="<?php echo $category; ?>-list" class="hobby-list" style="display: none;">
                    <?php
                    if (!empty($data['items'])):
                        foreach ($data['items'] as $item):
                    ?>
                    <div class="hobby-item" tabindex="0" data-category="<?php echo htmlspecialchars($category); ?>" data-index="<?php echo array_search($item, $data['items']); ?>">
                        <?php
                        // Better image path handling with dark fallback
                        $image_path = !empty($item['image']) && $item['image'] !== 'placeholder.jpg'
                            ? 'img/' . htmlspecialchars($item['image'])
                            : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzFhMWEyZSIvPjwvc3ZnPg==';
                        $fallback_img = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzFhMWEyZSIvPjwvc3ZnPg==';
                        ?>
                        <div style="position:relative;cursor:pointer;" class="hobby-img-wrapper">
                            <img src="<?php echo $image_path; ?>"
                                 alt="<?php echo htmlspecialchars($item['name']); ?> - Hobby/Skill item"
                                 class="item-img"
                                 loading="lazy"
                                 decoding="async"
                                 onerror="this.src='<?php echo $fallback_img; ?>'">>>
                        </div>
                        <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <?php if (!empty($item['audio_file'])): ?>
                        <div class="item-audio" style="width: 100%; margin-top: 0;">
                            <audio controls style="width: 100%; height: 18px;">
                                <source src="audio/<?php echo htmlspecialchars($item['audio_file']); ?>" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php
                        endforeach;
                    else:
                    ?>
                    <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                        <p>No items in this category yet.</p>
                        <p style="font-size: 0.9rem;">Add items through the admin panel!</p>
                    </div>
                    <?php
                    endif;
                    ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- PROJECTS SECTION - ISO 25010: Functional Suitability -->
    <section id="projects" aria-label="Projects Portfolio Section">
        <h2>Projects</h2>
        <p style="color: var(--text-secondary); margin-bottom: 20px; font-size: 1.2rem;">Creative works and designs</p>
        
        <?php
        $db_projects = $dal->getProjects();
        if (!empty($db_projects)):
        ?>
        <div class="projects-grid">
            <?php
            // Store project data for modal
            $projectDataJson = json_encode($db_projects, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
            ?>
            <script>const projectDataFromServer = <?php echo $projectDataJson; ?>;</script>
            <?php foreach ($db_projects as $p):
                // Use first image from images array
                $imgSrc = '';
                if (!empty($p['images']) && is_array($p['images']) && count($p['images']) > 0) {
                    $imgSrc = htmlspecialchars($p['images'][0]);
                }
                $placeholder = 'https://via.placeholder.com/300x180/2a2a4a/ffffff?text=' . urlencode(htmlspecialchars($p['title']));
                $projectLink = !empty($p['link']) ? htmlspecialchars($p['link']) : '#';
                $hasLink = !empty($p['link']);
                $hasImages = !empty($p['images']) && is_array($p['images']) && count($p['images']) > 0;
            ?>
            <article class="project-card" data-project-id="<?php echo htmlspecialchars($p['id']); ?>" data-has-images="<?php echo $hasImages ? 'true' : 'false'; ?>" style="cursor: <?php echo $hasImages ? 'pointer' : 'default'; ?>;">
                <img src="<?php echo !empty($imgSrc) ? $imgSrc : $placeholder; ?>"
                     alt="Project: <?php echo htmlspecialchars($p['title']); ?>"
                     class="project-image"
                     loading="lazy"
                     decoding="async"
                     onerror="this.src='<?php echo $placeholder; ?>'">
                <div class="project-content">
                    <h3 class="project-title"><?php echo htmlspecialchars($p['title']); ?></h3>
                    <p class="project-description"><?php echo htmlspecialchars($p['description']); ?></p>
                    <?php if ($hasImages && count($p['images']) > 1): ?>
                    <small style="color: var(--text-secondary); margin-bottom: 8px; display: block;"><?php echo count($p['images']); ?> images - click to view gallery</small>
                    <?php endif; ?>
                    <?php if ($hasLink): ?>
                    <a href="<?php echo $projectLink; ?>" target="_blank" rel="noopener noreferrer" class="project-link-btn">View Project →</a>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="coming-soon-badge">
            <p>✨ <span>Projects coming soon!</span> Check back later.</p>
        </div>
        <?php endif; ?>
    </section>

    <!-- PROJECT GALLERY MODAL -->
    <div id="projectModal" class="project-modal">
        <div class="project-modal-content">
            <!-- Left: Image Panel -->
            <div class="project-modal-left">
                <img id="projectModalImage" src="" alt="Project Image" class="project-modal-image">
            </div>

            <!-- Right: Content Panel -->
            <div class="project-modal-right">
                <button class="project-modal-close" onclick="closeProjectModal()">✕</button>
                <h3 id="projectModalTitle" class="project-modal-title"></h3>
                <p id="projectModalDescription" class="project-modal-description"></p>

                <!-- Navigation Buttons -->
                <div class="project-modal-nav">
                    <button class="nav-btn nav-prev" onclick="navigateProjectImage(-1)">← Previous</button>
                    <button class="nav-btn nav-next" onclick="navigateProjectImage(1)">Next →</button>
                </div>

                <!-- External Link Button -->
                <a id="projectModalLink" href="#" target="_blank" rel="noopener noreferrer" class="project-link-btn" style="display: none;">View Project →</a>
            </div>
        </div>
    </div>

    <!-- CONTACT SECTION - ISO 25010: Usability -->
    <section id="contact" aria-label="Contact Information Section">
        <h2>Connect With Me</h2>
        <p style="color: var(--text-secondary); margin-bottom: 40px; font-size: 1.2rem;">Let's collaborate or chat about technology, gaming, or anime!</p>

        <div class="contact-info">
            <div class="contact-grid">
                <!-- Email - Clickable for inquiries -->
                <?php
                $email = $profile['email'] ?? 'carpiodarryl@gmail.com';
                ?>
                <div class="contact-item" onclick="copyEmail()">
                    <div class="contact-icon">📧</div>
                    <div class="contact-text">
                        <strong>Email Address</strong>
                        <p><?php echo htmlspecialchars($email); ?></p>
                        <small style="color: var(--highlight); font-size: 0.85rem;">Click to copy or <a href="mailto:<?php echo htmlspecialchars($email); ?>" class="contact-link">send email</a></small>
                    </div>
                </div>

                <!-- Facebook - Clickable if configured -->
                <?php
                $facebook = $profile['facebook'] ?? '';
                $fb_name = $profile['full_name'] ?? $profile['name'] ?? 'Bryan Darryl Carpio';
                ?>
                <div class="contact-item" <?php echo !empty($facebook) ? 'onclick="window.open(\'' . htmlspecialchars($facebook) . '\', \'_blank\')"' : ''; ?>>
                    <div class="contact-icon">👤</div>
                    <div class="contact-text">
                        <strong>Facebook Profile</strong>
                        <p><?php echo htmlspecialchars($fb_name); ?></p>
                        <small style="font-size: 0.85rem; color: <?php echo !empty($facebook) ? 'var(--highlight)' : 'var(--text-secondary)'; ?>;">
                            <?php echo !empty($facebook) ? 'Click to visit' : 'Link not configured'; ?>
                        </small>
                    </div>
                </div>

                <!-- Institution - Display only, no link -->
                <div class="contact-item">
                    <div class="contact-icon">🎓</div>
                    <div class="contact-text">
                        <strong>Academic Institution</strong>
                        <p>Aurora State College of Technology</p>
                        <small style="font-size: 0.85rem;">ASCOT</small>
                    </div>
                </div>

                <!-- Location - Display only, not clickable -->
                <?php
                $location = $profile['location'] ?? 'Brgy. Diteki San Luis, Aurora, Philippines';
                ?>
                <div class="contact-item">
                    <div class="contact-icon">📍</div>
                    <div class="contact-text">
                        <strong>Location</strong>
                        <p><?php echo htmlspecialchars($location); ?></p>
                        <small style="font-size: 0.85rem;">Privacy protected</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER - ISO 25010: Usability & Maintainability -->
    <footer role="contentinfo">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($profile['name']); ?>. All Rights Reserved.</p>
        <p style="margin-top: 10px; font-size: 1rem; opacity: 0.8;">ASCOT BSIT Student | Future Web Developer</p>
        <p style="margin-top: 5px; font-size: 0.9rem; opacity: 0.6;">Last updated: <?php echo date('F Y'); ?></p>
        <p style="margin-top: 10px; font-size: 0.8rem; opacity: 0.5;">
            <em>This portfolio follows ISO/IEC 25010 software quality standards for educational purposes.</em>
        </p>
    </footer>
</div>

<!-- Hobby Modal -->
<div id="hobbyModal" class="hobby-modal">
    <div class="hobby-modal-content">
        <!-- Left: Image Panel -->
        <div class="hobby-modal-left">
            <img id="hobbyModalImage" class="hobby-modal-image" src="" alt="">
        </div>
        
        <!-- Right: Content Panel -->
        <div class="hobby-modal-right">
            <button class="hobby-modal-close" onclick="closeHobbyModal()">✕</button>
            <h3 id="hobbyModalTitle" class="hobby-modal-title"></h3>
            <p id="hobbyModalDescription" class="hobby-modal-description"></p>

            <!-- Proficiency Bar (hidden by default until feature is implemented) -->
            <div id="hobbyModalProficiency" class="hobby-modal-proficiency" style="display:none;">
                <div class="proficiency-label">Proficiency</div>
                <div class="proficiency-bar-container">
                    <div id="hobbyModalProfBar" class="proficiency-bar"></div>
                </div>
                <div id="hobbyModalProfValue" class="proficiency-value"></div>
            </div>

            <!-- Navigation Buttons -->
            <div class="hobby-modal-nav">
                <button class="nav-btn nav-prev" onclick="navigateHobby(-1)">← Previous</button>
                <button class="nav-btn nav-next" onclick="navigateHobby(1)">Next →</button>
            </div>
        </div>
    </div>
</div>

<!-- ISO 25010: Performance Efficiency - Deferred Script Loading -->
<script src="js/main.js" defer></script>

<!-- Copy Email Function -->
<script>
function copyEmail() {
    const email = "<?php echo htmlspecialchars($profile['email'] ?? 'carpiodarryl@gmail.com'); ?>";
    navigator.clipboard.writeText(email).then(function() {
        alert('Email copied to clipboard!');
    }, function(err) {
        // Fallback for older browsers
        const textArea = document.createElement("textarea");
        textArea.value = email;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand("copy");
        document.body.removeChild(textArea);
        alert('Email copied to clipboard!');
    });
}
</script>

<!-- ISO 25010: Maintainability - Structured Data for Educational Portfolio -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "ProfilePage",
    "mainEntity": {
        "@type": "Person",
        "name": "<?php echo htmlspecialchars($profile['name']); ?>",
        "description": "BSIT Student at Aurora State College of Technology",
        "alumniOf": {
            "@type": "EducationalOrganization",
            "name": "Aurora State College of Technology",
            "alternateName": "ASCOT"
        },
        "knowsAbout": ["Information Technology", "Web Development", "Programming"]
    }
}
</script>
<script src="JS/security.js"></script>

<!-- Hobby Modal Functions -->
<script>
// Store hobbies data for navigation
let hobbiesData = <?= json_encode($grouped_hobbies, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
let currentCategory = '';
let currentItemIndex = 0;

// Event delegation for hobby item clicks
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.hobby-item').forEach(item => {
        item.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            const index = parseInt(this.getAttribute('data-index'));
            openHobbyModal(category, index);
        });
    });
});

function openHobbyModal(category, itemIndex) {
    const categoryData = hobbiesData[category];
    if (!categoryData || !categoryData.items || !categoryData.items[itemIndex]) {
        console.error('Item not found:', category, itemIndex);
        return;
    }

    const item = categoryData.items[itemIndex];
    const modal = document.getElementById('hobbyModal');
    const modalImage = document.getElementById('hobbyModalImage');
    const modalTitle = document.getElementById('hobbyModalTitle');
    const modalDescription = document.getElementById('hobbyModalDescription');
    const modalProficiency = document.getElementById('hobbyModalProficiency');
    const modalProfBar = document.getElementById('hobbyModalProfBar');
    const modalProfValue = document.getElementById('hobbyModalProfValue');

    // Store current position
    currentCategory = category;
    currentItemIndex = itemIndex;

    const image_path = (item.image && item.image !== 'placeholder.jpg')
        ? 'img/' + item.image
        : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzFhMWEyZSIvPjwvc3ZnPg==';

    modalImage.src = image_path;
    modalTitle.textContent = item.name;
    modalDescription.textContent = item.description || 'No description available';

    // Handle proficiency bar
    if (item.proficiency && item.proficiency > 0) {
        modalProficiency.style.display = 'block';
        modalProfBar.style.width = '0%'; // Start at 0 for animation
        modalProfValue.textContent = '0%';

        // Set color based on proficiency level
        if (item.proficiency >= 70) {
            modalProfBar.style.background = 'linear-gradient(90deg, #43d98c 0%, #1a9e62 100%)';
        } else if (item.proficiency >= 40) {
            modalProfBar.style.background = 'linear-gradient(90deg, #f6b93b 0%, #e55039 100%)';
        } else {
            modalProfBar.style.background = 'linear-gradient(90deg, #f05454 0%, #d63031 100%)';
        }

        // Animate the bar after modal opens
        setTimeout(() => {
            modalProfBar.style.width = item.proficiency + '%';
            modalProfValue.textContent = item.proficiency + '%';
        }, 300); // Wait 300ms for modal animation to start
    } else {
        modalProficiency.style.display = 'none';
    }

    // Update navigation buttons
    updateNavButtons();

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function navigateHobby(direction) {
    const categoryItems = hobbiesData[currentCategory]?.items || [];
    if (categoryItems.length === 0) return;

    const newIndex = currentItemIndex + direction;

    // Check bounds
    if (newIndex < 0 || newIndex >= categoryItems.length) return;

    const item = categoryItems[newIndex];
    const image_path = (item.image && item.image !== 'placeholder.jpg')
        ? 'img/' + item.image
        : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzFhMWEyZSIvPjwvc3ZnPg==';

    // Update content with animation
    const modalImage = document.getElementById('hobbyModalImage');
    const modalTitle = document.getElementById('hobbyModalTitle');
    const modalDescription = document.getElementById('hobbyModalDescription');

    // Fade out
    modalImage.style.opacity = '0';
    modalTitle.style.opacity = '0';
    modalDescription.style.opacity = '0';

    setTimeout(() => {
        currentItemIndex = newIndex;
        modalImage.src = image_path;
        modalTitle.textContent = item.name;
        modalDescription.textContent = item.description || 'No description available';

        // Reset proficiency bar
        const modalProficiency = document.getElementById('hobbyModalProficiency');
        const modalProfBar = document.getElementById('hobbyModalProfBar');
        const modalProfValue = document.getElementById('hobbyModalProfValue');

        if (item.proficiency && item.proficiency > 0) {
            modalProficiency.style.display = 'block';
            modalProfBar.style.width = '0%';
            modalProfValue.textContent = '0%';

            if (item.proficiency >= 70) {
                modalProfBar.style.background = 'linear-gradient(90deg, #43d98c 0%, #1a9e62 100%)';
            } else if (item.proficiency >= 40) {
                modalProfBar.style.background = 'linear-gradient(90deg, #f6b93b 0%, #e55039 100%)';
            } else {
                modalProfBar.style.background = 'linear-gradient(90deg, #f05454 0%, #d63031 100%)';
            }

            setTimeout(() => {
                modalProfBar.style.width = item.proficiency + '%';
                modalProfValue.textContent = item.proficiency + '%';
            }, 300);
        } else {
            modalProficiency.style.display = 'none';
        }

        updateNavButtons();

        // Fade in
        modalImage.style.opacity = '1';
        modalTitle.style.opacity = '1';
        modalDescription.style.opacity = '1';
    }, 200);
}

function updateNavButtons() {
    const categoryItems = hobbiesData[currentCategory]?.items || [];
    const prevBtn = document.querySelector('.nav-prev');
    const nextBtn = document.querySelector('.nav-next');

    prevBtn.disabled = currentItemIndex <= 0;
    nextBtn.disabled = currentItemIndex >= categoryItems.length - 1;
}

function closeHobbyModal() {
    const modal = document.getElementById('hobbyModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

function openFirstItem(category) {
    const categoryData = hobbiesData[category];
    if (!categoryData || !categoryData.items || categoryData.items.length === 0) {
        showToast('No items in this category yet!');
        return;
    }

    // Open modal with first item (index 0)
    openHobbyModal(category, 0);
}

function showToast(message) {
    const toast = document.getElementById('toastNotification');
    const toastMessage = document.getElementById('toastMessage');
    
    toastMessage.textContent = message;
    toast.classList.add('show');
    
    // Hide after 1.5 seconds
    setTimeout(() => {
        toast.classList.remove('show');
    }, 1500);
}

// Project Gallery Modal Functions
let projectImages = [];
let currentProjectImageIndex = 0;

// Event delegation for project cards
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.project-card').forEach(card => {
        card.addEventListener('click', function() {
            const hasImages = this.getAttribute('data-has-images') === 'true';
            if (!hasImages) return;

            const projectId = this.getAttribute('data-project-id');
            const title = this.querySelector('.project-title').textContent;
            const description = this.querySelector('.project-description').textContent;
            const linkBtn = this.querySelector('.project-link-btn');
            const link = linkBtn ? linkBtn.href : '#';

            // Get images from the projects data
            const projectData = projectDataFromServer.find(p => p.id == projectId);
            if (projectData && projectData.images) {
                openProjectModal(projectId, projectData.images, title, description, link);
            }
        });
    });
});

function openProjectModal(projectId, images, title, description, link) {
    const modal = document.getElementById('projectModal');
    const modalImage = document.getElementById('projectModalImage');
    const modalTitle = document.getElementById('projectModalTitle');
    const modalDescription = document.getElementById('projectModalDescription');
    const modalLink = document.getElementById('projectModalLink');

    projectImages = images || [];
    currentProjectImageIndex = 0;

    if (projectImages.length === 0) {
        showToast('No images for this project');
        return;
    }

    modalImage.src = projectImages[0];
    modalTitle.textContent = title;
    modalDescription.textContent = description || 'No description available';

    if (link && link !== '#') {
        modalLink.href = link;
        modalLink.style.display = 'inline-block';
    } else {
        modalLink.style.display = 'none';
    }

    updateProjectNavButtons();
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeProjectModal() {
    const modal = document.getElementById('projectModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

function navigateProjectImage(direction) {
    const newIndex = currentProjectImageIndex + direction;

    if (newIndex < 0 || newIndex >= projectImages.length) return;

    const modalImage = document.getElementById('projectModalImage');

    // Fade out
    modalImage.style.opacity = '0';

    setTimeout(() => {
        currentProjectImageIndex = newIndex;
        modalImage.src = projectImages[newIndex];
        updateProjectNavButtons();

        // Fade in
        modalImage.style.opacity = '1';
    }, 200);
}

function updateProjectNavButtons() {
    const prevBtn = document.querySelector('.project-modal-nav .nav-prev');
    const nextBtn = document.querySelector('.project-modal-nav .nav-next');

    prevBtn.disabled = currentProjectImageIndex <= 0;
    nextBtn.disabled = currentProjectImageIndex >= projectImages.length - 1;
}
</script>

</body>
</html>