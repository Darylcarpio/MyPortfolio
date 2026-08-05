// ========== ENHANCED JAVASCRIPT FEATURES ==========

// 1. Theme Switcher (pill toggle)
const themeSwitcher = document.querySelector('.theme-switcher');
let isDarkMode = localStorage.getItem('theme') !== 'light';

// Apply saved theme on load
if (!isDarkMode) {
    applyLightTheme();
}

themeSwitcher.addEventListener('click', () => {
    isDarkMode = !isDarkMode;
    
    if (isDarkMode) {
        applyDarkTheme();
        showNotification('Dark theme activated');
    } else {
        applyLightTheme();
        showNotification('Light theme activated');
    }
});

function applyDarkTheme() {
    document.body.classList.remove('light-theme');
    themeSwitcher.classList.remove('light-mode');
    document.documentElement.style.setProperty('--bg-overlay', 'rgba(18, 18, 35, 0.9)');
    document.documentElement.style.setProperty('--card-bg', 'rgba(25, 35, 65, 0.85)');
    document.documentElement.style.setProperty('--text-main', '#ffffff');
    document.documentElement.style.setProperty('--text-secondary', '#b8c1ec');
    localStorage.setItem('theme', 'dark');
}

function applyLightTheme() {
    document.body.classList.add('light-theme');
    themeSwitcher.classList.add('light-mode');
    document.documentElement.style.setProperty('--bg-overlay', 'rgba(255, 255, 255, 0.85)');
    document.documentElement.style.setProperty('--card-bg', 'rgba(255, 255, 255, 0.9)');
    document.documentElement.style.setProperty('--text-main', '#333333');
    document.documentElement.style.setProperty('--text-secondary', '#555555');
    localStorage.setItem('theme', 'light');
}

// 2. Back to Top Button
const backToTopBtn = document.querySelector('.back-to-top');

window.addEventListener('scroll', () => {
    if (window.pageYOffset > 300) {
        backToTopBtn.classList.add('visible');
    } else {
        backToTopBtn.classList.remove('visible');
    }

    // Progress Bar
    const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
    const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    const scrolled = (winScroll / height) * 100;
    document.querySelector('.progress-bar').style.width = scrolled + "%";
});

backToTopBtn.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

// 3. Email Copy Functionality
function copyEmail() {
    const email = 'carpiodarryl@gmail.com';
    navigator.clipboard.writeText(email).then(() => {
        showNotification('Email copied to clipboard!');
    });
}

// 4. Notification System
function showNotification(message) {
    const notification = document.querySelector('.notification');
    const notificationText = notification.querySelector('.notification-text');
    
    notificationText.textContent = message;
    notification.classList.add('show');
    
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}

// 5. Enhanced Interactive Lists - Accordion (only one open at a time)
function collapseList(list) {
    // Animate closing: set max-height to current height, then to 0
    list.style.maxHeight = list.scrollHeight + 'px';
    // Force reflow so browser registers the starting value
    list.offsetHeight;
    list.style.maxHeight = '0';
    list.classList.remove('open');

    const btnId = list.id.replace('-list', '-btn');
    const btn = document.getElementById(btnId);
    if (btn) {
        btn.innerHTML = btn.innerHTML.replace("▲", "▼");
        btn.style.borderColor = "";
        btn.style.background = "";
        btn.style.color = "";
    }
    localStorage.setItem(list.id, 'closed');
}

function expandList(list) {
    list.classList.add('open');
    // Set max-height to actual content height for accurate transition
    list.style.maxHeight = list.scrollHeight + 'px';

    // After transition ends, remove inline max-height so content can grow dynamically
    const onEnd = () => {
        if (list.classList.contains('open')) {
            list.style.maxHeight = 'none';
        }
        list.removeEventListener('transitionend', onEnd);
    };
    list.addEventListener('transitionend', onEnd);

    const btnId = list.id.replace('-list', '-btn');
    const btn = document.getElementById(btnId);
    if (btn) {
        btn.innerHTML = btn.innerHTML.replace("▼", "▲");
        btn.style.borderColor = "transparent";
        btn.style.background = "linear-gradient(135deg, #667eea 0%, #764ba2 100%)";
        btn.style.color = "white";
    }
    localStorage.setItem(list.id, 'open');

    // Staggered item reveal
    const items = list.querySelectorAll('.hobby-item');
    items.forEach((item, index) => {
        item.style.opacity = "0";
        item.style.transform = "translateY(20px)";
        setTimeout(() => {
            item.style.transition = "opacity 0.4s ease, transform 0.4s ease";
            item.style.opacity = "1";
            item.style.transform = "translateY(0)";
        }, 80 + index * 100);
    });
}

function toggleList(listId, buttonId) {
    var list = document.getElementById(listId);
    if (!list) return;

    const isOpen = list.classList.contains('open');

    // Close ALL other open lists first (accordion)
    document.querySelectorAll('.hobby-list.open').forEach(openList => {
        if (openList.id !== listId) {
            collapseList(openList);
        }
    });

    if (!isOpen) {
        expandList(list);
    } else {
        collapseList(list);
    }
}

// 6. Restore list states from localStorage on page load (only last opened one)
document.addEventListener('DOMContentLoaded', () => {
    const allLists = document.querySelectorAll('.hobby-list');
    let restoredOne = false;
    
    allLists.forEach(list => {
        const listId = list.id;
        const state = localStorage.getItem(listId);
        
        if (state === 'open' && !restoredOne) {
            restoredOne = true;
            // Expand without animation on page load
            list.classList.add('open');
            list.style.maxHeight = 'none';
            list.style.transition = 'none';
            const btn = document.getElementById(listId.replace('-list', '-btn'));
            if (btn) {
                btn.innerHTML = btn.innerHTML.replace("▼", "▲");
                btn.style.borderColor = "transparent";
                btn.style.background = "linear-gradient(135deg, #667eea 0%, #764ba2 100%)";
                btn.style.color = "white";
            }
            // Re-enable transitions after paint
            requestAnimationFrame(() => {
                list.style.transition = '';
            });
        } else {
            localStorage.setItem(listId, 'closed');
        }
    });
});

// 7. Mobile menu toggle
const menuToggle = document.querySelector('.menu-toggle');
const nav = document.querySelector('nav');
const navLinks = document.querySelectorAll('.nav-link');

menuToggle.addEventListener('click', () => {
    nav.classList.toggle('active');
    menuToggle.classList.toggle('active');
});

navLinks.forEach(link => {
    link.addEventListener('click', () => {
        nav.classList.remove('active');
        menuToggle.classList.remove('active');
        navLinks.forEach(l => l.classList.remove('active'));
        link.classList.add('active');
    });
});

// 8. Smooth scrolling with offset for fixed header
document.querySelectorAll('nav a, .btn').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        if (targetId === '#' || !targetId.startsWith('#')) return;

        const targetElement = document.querySelector(targetId);
        if (!targetElement) return;

        const headerHeight = document.querySelector('header').offsetHeight;
        const targetPosition = targetElement.offsetTop - headerHeight - 20;

        window.scrollTo({ top: targetPosition, behavior: 'smooth' });
    });
});

// 9. Active nav link based on scroll position
window.addEventListener('scroll', () => {
    const sections = document.querySelectorAll('section');
    const scrollPosition = window.scrollY + 100;
    
    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.clientHeight;
        const sectionId = section.getAttribute('id');
        
        if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${sectionId}`) {
                    link.classList.add('active');
                }
            });
        }
    });
});

// 10. Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.nav-link').classList.add('active');
    
    document.body.addEventListener('mousedown', () => {
        document.body.classList.remove('using-keyboard');
    });
    
    document.body.addEventListener('keydown', () => {
        document.body.classList.add('using-keyboard');
    });
});

// 11. Touch device optimizations
if ('ontouchstart' in window) {
    document.body.classList.add('touch-device');
    document.addEventListener('touchstart', () => {}, { passive: true });
}

// 12. Close mobile menu when clicking outside
document.addEventListener('click', (e) => {
    if (!nav.contains(e.target) && !menuToggle.contains(e.target) && nav.classList.contains('active')) {
        nav.classList.remove('active');
        menuToggle.classList.remove('active');
    }
});
