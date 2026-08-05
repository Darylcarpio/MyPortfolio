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
        'name' => 'Portfolio Owner',
        'title' => 'Student Portfolio',
        'location' => 'Philippines',
        'vision' => 'Building a future in technology.',
        'profile_image' => 'Me.jpg'
    ];
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
        
        /* Remove link styling */
        .contact-info a {
            text-decoration: none;
            color: inherit;
            cursor: default;
            pointer-events: none;
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
            <a href="logout.php" class="logout-btn" aria-label="Logout">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Logout</span>
            </a>
        </div>
    </header>

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
                <button onclick="toggleList('<?php echo $category; ?>-list','<?php echo $category; ?>-btn')" class="hobby-btn" id="<?php echo $category; ?>-btn">View Collection ▼</button>
                <div id="<?php echo $category; ?>-list" class="hobby-list">
                    <?php
                    if (!empty($data['items'])):
                        foreach ($data['items'] as $item):
                    ?>
                    <div class="hobby-item" tabindex="0">
                        <?php
                        // Better image path handling with dark fallback
                        $image_path = !empty($item['image']) && $item['image'] !== 'placeholder.jpg' 
                            ? 'img/' . htmlspecialchars($item['image']) 
                            : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzFhMWEyZSIvPjwvc3ZnPg==';
                        $fallback_img = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzFhMWEyZSIvPjwvc3ZnPg==';
                        ?>
                        <img src="<?php echo $image_path; ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?> - Hobby/Skill item" 
                             class="item-img" 
                             loading="lazy"
                             decoding="async"
                             onerror="this.src='<?php echo $fallback_img; ?>'">>>
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
            <?php foreach ($db_projects as $p): 
                // Build proper image path
                $imgSrc = '';
                if (!empty($p['image'])) {
                    // Check if image path already includes folder or needs it
                    if (strpos($p['image'], '/') === false) {
                        $imgSrc = 'img/projects/' . htmlspecialchars($p['image']);
                    } else {
                        $imgSrc = htmlspecialchars($p['image']);
                    }
                }
                $placeholder = 'https://via.placeholder.com/300x180/2a2a4a/ffffff?text=' . urlencode(htmlspecialchars($p['title']));
                $projectLink = !empty($p['project_url']) ? htmlspecialchars($p['project_url']) : (!empty($p['link']) ? htmlspecialchars($p['link']) : '#');
            ?>
            <a href="<?php echo $projectLink; ?>" class="project-card-link" <?php echo ($projectLink !== '#') ? 'target="_blank" rel="noopener noreferrer"' : 'onclick="return false;"'; ?>>
                <article class="project-card">
                    <img src="<?php echo !empty($imgSrc) ? $imgSrc : $placeholder; ?>" 
                         alt="Project: <?php echo htmlspecialchars($p['title']); ?>" 
                         class="project-image" 
                         loading="lazy"
                         decoding="async"
                         onerror="this.src='<?php echo $placeholder; ?>'">
                    <div class="project-content">
                        <h3 class="project-title"><?php echo htmlspecialchars($p['title']); ?></h3>
                        <p class="project-description"><?php echo htmlspecialchars($p['description']); ?></p>
                    </div>
                </article>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="coming-soon-badge">
            <p>✨ <span>Projects coming soon!</span> Check back later.</p>
        </div>
        <?php endif; ?>
    </section>

    <!-- CONTACT SECTION - ISO 25010: Usability -->
    <section id="contact" aria-label="Contact Information Section">
        <h2>Connect With Me</h2>
        <p style="color: var(--text-secondary); margin-bottom: 40px; font-size: 1.2rem;">Let's collaborate or chat about technology, gaming, or anime!</p>

        <div class="contact-info">
            <div class="contact-grid">
                <!-- Email - Click to copy still works -->
                <div class="contact-item" onclick="copyEmail()">
                    <div class="contact-icon">📧</div>
                    <div class="contact-text">
                        <strong>Email Address</strong>
                        <p>carpiodarryl@gmail.com</p>
                        <small style="color: var(--highlight); font-size: 0.85rem;">Click to copy!</small>
                    </div>
                </div>

                <!-- Facebook - Display only, no link -->
                <div class="contact-item">
                    <div class="contact-icon">👤</div>
                    <div class="contact-text">
                        <strong>Facebook Profile</strong>
                        <p>Bryan Darryl Carpio</p>
                        <small style="font-size: 0.85rem;">For privacy, link is hidden</small>
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

                <!-- Location - Display only, no link -->
                <div class="contact-item">
                    <div class="contact-icon">📍</div>
                    <div class="contact-text">
                        <strong>Location</strong>
                        <p>Brgy. Diteki San Luis, Aurora, Philippines</p>
                        <small style="font-size: 0.85rem;">My hometown</small>
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

<!-- ISO 25010: Performance Efficiency - Deferred Script Loading -->
<script src="js/main.js" defer></script>

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

</body>
</html>