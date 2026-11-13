# Portfolio System - Documentation

## Overview

**Portfolio System** is a comprehensive web-based portfolio management application built with PHP, MySQL, and JavaScript. It now includes a branded public visitor entry page, a separate visitor/admin access flow, and an admin panel for content management.

**Built by:** Bryan Darryl Carpio  
**Program:** ASCOT - BSIT Student

---

## Features

### 👤 For Visitors (Public Portfolio)
- **No Account Required** - View portfolio directly without creating an account
- **Profile Display** - View professional information, bio, vision, and contact details
- **Project Showcase** - Browse implemented projects with technologies used
- **Project Gallery** - View multiple images per project with gallery modal
- **Project Links** - Click to visit project URLs (GitHub, demos, etc.)
- **Education Timeline** - View educational background and achievements
- **Hobbies & Interests** - Discover personal interests with proficiency bars
- **Interactive Modal** - Click hobbies to view details with Next/Prev navigation
- **Contact Information** - Easy access to contact channels with clickable email and Facebook links
- **Responsive Design** - Works on desktop, tablet, and mobile devices
- **Simplified UI** - Clean, modern interface without nested borders
- **Perfect for Job Applications** - Employers can view portfolio easily

### 🔐 For Admin (Management Panel)
- **Profile Management** - Edit profile information, professional title, bio, vision, and Facebook URL
- **Project Management** - Add, edit, delete projects with multiple image uploads and link field
- **Project Gallery** - Upload multiple images per project for gallery display
- **Education Tracking** - Manage education records and achievements
- **Hobbies & Skills** - Organize personal interests by category with proficiency levels
- **Proficiency Bars** - Set skill levels (0-100%) with animated display on public dashboard
- **Contact Management** - Configure contact types and information
- **Activity Logging** - Track all system activities and changes
- **Admin Credential Management** - Access via settings icon in top bar
- **Database Migration Tools** - Easy column addition and structure fixes
- **Clean UI** - Removed backup/restore to simplify interface

---

## System Architecture

### Technology Stack
- **Backend:** PHP 7.x+ with PDO Database Abstraction
- **Database:** MySQL/MariaDB with UTF-8 Encoding
- **Frontend:** HTML5, CSS3, Responsive Design
- **Server:** Apache (via XAMPP)
- **Security:** Session-based authentication, Password hashing

### File Structure
```
MyWebsite/
├── index.php                    # Public home page / branded entry point
├── dashboard.php                # Public portfolio display
├── guest_access.php             # Visitor portal for sign in / sign up
├── panel.php                    # Admin control panel
├── admin_login.php              # Admin login page
├── config.php                   # Database configuration & DAL
├── home.php                     # Legacy redirect stub
├── FULL_BACKUP_RESTORE.php      # Backup & restore manager
├── api/                         # API endpoints
│   ├── profile.php              # Profile data API
│   ├── education.php            # Education API
│   ├── projects.php             # Projects API (multiple images + link)
│   ├── hobbies.php              # Hobbies API (proficiency + descriptions)
│   ├── contact.php              # Contact info API
│   └── activity.php             # Activity logging API
├── CSS/                         # Stylesheets
│   ├── style.css                # Main styles
│   └── css_admin.css            # Admin panel styles
├── JS/                          # JavaScript files
│   ├── main.js                  # Main functionality
│   └── security.js              # Security features
├── images/                      # Project images
├── img/                         # Media files
│   └── projects/                # Project media (gallery)
├── audio/                       # Audio files
└── Temp/                        # Temporary files
```

### Database Schema

**Core Tables:**
- `profile` - User profile information
- `admin_users` - Admin credentials
- `education` - Education records
- `education_achievements` - Academic achievements
- `projects` - Project portfolio (with link field)
- `project_status` - Project status types
- `project_technologies` - Technology associations
- `project_images` - Multiple images per project (gallery)
- `technologies` - Technology database
- `hobby_items` - Personal interests (with proficiency_level)
- `hobby_categories` - Interest categories
- `contact_info` - Contact details
- `contact_types` - Contact type definitions
- `activity_log` - System activity tracking
- `page_views` - Visitor statistics
- `iso_feedback` - Feedback collection

---

## Getting Started

### Prerequisites
- XAMPP (Apache + MySQL) installed
- Windows/Linux/Mac operating system
- Web browser (Chrome, Firefox, Safari, Edge)

### Installation & Setup

#### Option 1: Quick Start with Batch Files
1. **First time?** Double-click `RUN.bat` to open the main menu
2. **View portfolio?** Double-click `PORTFOLIO.bat` 
3. **Admin access?** Double-click `ADMIN.bat`

#### Option 2: Direct Browser Access
1. Start XAMPP (Apache + MySQL)
2. Open browser and go to:
   - **Home:** `http://localhost/MyWebsite/` (Start here - redirects to portfolio)
   - **Portfolio:** `http://localhost/MyWebsite/dashboard.php` (Direct access)
   - **Admin Login:** `http://localhost/MyWebsite/admin_login.php` (For content management)
   - **Admin Panel:** `http://localhost/MyWebsite/panel.php`

**Note:** The portfolio is now publicly accessible without requiring visitors to create accounts. Perfect for job applications and employer viewing.

### Initial Setup
1. **Database Initialization**
   - Database structure is set up automatically on deployment
   - Admin credentials must be configured securely before deployment

2. **Admin Login**
   - Access admin panel via login page after setup
   - Credentials configured in database setup process
   
   ⚠️ **Important:** Always use strong passwords (8+ chars, uppercase, number, special char)

### Database Connection
Default configuration in `config.php`:
```php
Host: localhost
Database: db_portfolio
User: root
Password: (empty by default in XAMPP)
Charset: utf8mb4
```

---

## Usage Guide

### For Public Users (Viewing Portfolio)
1. **Open Portfolio:** Go to `http://localhost/MyWebsite/` (automatically redirects to dashboard)
2. **Browse Portfolio:**
   - Scroll through profile and introduction
   - View project showcases
   - Check education background
   - See hobbies and interests
   - Access contact information
3. **No Account Required** - Portfolio is publicly accessible

### For Admin (Management Panel)

#### Logging In
1. Go to `http://localhost/MyWebsite/admin_login.php`
2. Enter admin credentials
3. Click "Login"

#### Managing Content
1. **Profile:** Edit personal information, title, bio
2. **Education:** Add degrees, institutions, years
3. **Projects:** Create project entries with multiple images and optional link
4. **Hobbies:** Organize interests by category with proficiency levels
5. **Contact:** Update contact information

#### Managing Projects
1. Click "Add Project" in Projects section
2. Enter project name, description, and optional link
3. Upload multiple images (gallery feature)
4. Save - images display in gallery modal on public dashboard

#### Managing Hobbies
1. Click "Add Hobby" in Hobbies section
2. Select category and enter hobby name
3. Add description (optional)
4. Set proficiency level (0-100% slider)
5. Upload image and optional audio
6. Save - displays with animated proficiency bar on dashboard

---

## Batch Files Reference

| File | Purpose | Opens |
|------|---------|-------|
| **RUN.bat** | Main menu | http://localhost/MyWebsite/ |

Run any batch file by double-clicking it in File Explorer.

---

## Security Features

### Implemented Security
- ✅ Session-based authentication
- ✅ Password hashing (PASSWORD_DEFAULT)
- ✅ SQL injection prevention (Prepared statements)
- ✅ Visitor/admin separation on the public access flow
- ✅ 3-attempt sign-in lockout with 2-hour cooldown
- ✅ CSRF protection
- ✅ Activity logging and tracking

### Best Practices
- Always change default admin password
- Use strong passwords for admin account
- Monitor activity logs for suspicious actions
- Keep database backups via hosting provider

---

## Troubleshooting

### Issue: Database Connection Error
**Solution:**
1. Check if MySQL is running
2. Verify database `db_portfolio` exists
3. Run `database_init.php` to recreate tables
4. Check credentials in `config.php`

### Issue: Admin Login Not Working
**Solution:**
1. Verify MySQL is running
2. Check admin_users table exists and has at least one admin account
3. Verify admin credentials are configured correctly
4. Clear browser cache and cookies

### Issue: Visitor Sign-In / Sign-Up Not Working
**Solution:**
1. Confirm `community_users`, `community_profiles`, `community_session_logs`, and `auth_login_locks` exist
2. Check credentials in `config.php`
3. Try a fresh browser session if a lockout message appears
4. Use `guest_access.php` for visitor access instead of the admin login page

### Issue: Pages Not Loading
**Solution:**
1. Verify Apache is running (XAMPP)
2. Check file paths are correct
3. Ensure PHP parsing is enabled
4. Check error logs in XAMPP

### Issue: Project Images Not Displaying
**Solution:**
1. Ensure `/img/projects/` folder exists and is writable
2. Check file permissions (755 or 777)
3. Verify image files were uploaded correctly
4. Check browser console for image loading errors

### Issue: Hobby Proficiency Not Working
**Solution:**
1. Verify `proficiency_level` column exists in `hobby_items` table
2. Run migration script if column is missing
3. Check database connection in `config.php`
4. Clear browser cache and refresh

---

## File Permissions

For proper functionality, ensure:
- `/img/projects/` folder is writable (755)
- `/Temp/` folder is writable (755)
- Database can read/write tables
- Upload folders have appropriate permissions

### Windows (XAMPP)
Folders typically have correct permissions by default. If issues occur:
1. Right-click folder → Properties
2. Security → Edit
3. Ensure "Full Control" for SYSTEM and Users

---

## API Endpoints Reference

### Profile API (`api/profile.php`)
- `GET` - Retrieve profile data
- `POST` - Update profile

### Projects API (`api/projects.php`)
- `GET` - List all projects with images
- `POST` - Add new project (supports multiple images + link)
- `PUT` - Update project (preserves existing images/link)
- `DELETE` - Remove project

### Hobbies API (`api/hobbies.php`)
- `GET` - List all hobbies with proficiency
- `POST` - Add new hobby (with proficiency + description)
- `PUT` - Update hobby details
- `DELETE` - Remove hobby

### Other APIs
- `education.php` - Education management
- `contact.php` - Contact information
- `activity.php` - Activity logging

---

## Performance Tips

1. **Optimize Images** - Keep project images under 500KB
2. **Database Cleanup** - Archive old activity logs quarterly
3. **Browser Caching** - Leverages CSS/JS caching
4. **CDN Ready** - Can integrate CDN for static assets
5. **Image Optimization** - Compress images before upload

---

## Support & Maintenance

### Regular Maintenance
- Review activity logs weekly
- Update project information as needed
- Verify image uploads are working
- Test admin panel functionality monthly

### Common Maintenance Tasks
1. **Change Admin Password**
   - Admin Panel → Settings (gear icon) → Change Password
2. **View Activity**
   - Admin Panel → Activity Log
3. **Check Statistics**
   - Dashboard → Page views, visitor info

---

## Development Information

### Code Standards
- **PHP:** PSR-12 style (mostly)
- **Database:** Normalized schema
- **Security:** OWASP Top 10 compliance

### Adding New Features
1. Add database table if needed in `config.php`
2. Create API endpoint in `/api/`
3. Update admin panel in `panel.php`
4. Add dashboard display in `dashboard.php`
5. Test on localhost before deploying

### Extending the System
The modular architecture allows easy extension:
- Add new portfolio sections
- Integrate external APIs
- Add social media links
- Implement messaging system
- Add resume download
- Integrate analytics

---

## Version Information

- **System Version:** 2.2
- **Release Date:** October 2026
- **PHP Version:** 7.x+
- **MySQL Version:** 5.7+
- **Database:** db_portfolio

---

## License & Credits

**Created By:** Bryan Darryl Carpio  
**Program:** ASCOT - Bachelor of Science in Information Technology  
**Year:** 2026

This portfolio system is designed to showcase professional work in web development and database management.

---

## Changelog

### Version 2.2 (October 2026)
- ✅ Enhanced hobbies module with proficiency bars (0-100%)
- ✅ Added animated proficiency bar display on public dashboard
- ✅ Implemented hobby modal with Next/Prev navigation
- ✅ Added custom toast notifications for empty categories
- ✅ Implemented project image gallery (multiple images per project)
- ✅ Added project gallery modal with image navigation
- ✅ Added optional project link field
- ✅ Fixed project image preservation on edit
- ✅ Fixed project link preservation on edit
- ✅ Removed backup/restore functionality for simpler UI
- ✅ Added project_images database table
- ✅ Updated database schema for proficiency_level column
- ✅ Enhanced admin panel with cleaner interface
- ✅ Improved image upload handling for projects

### Version 2.1 (October 2026)
- ✅ Added Facebook URL field to profile
- ✅ Made email and Facebook clickable for visitors
- ✅ Simplified visitor portal UI (removed nested borders)
- ✅ Extended guest session to 24 hours
- ✅ Updated UI text from "visitor" to generic "account" terms
- ✅ Added settings icon in admin panel for credential management
- ✅ Created database migration tools (add_facebook_column.php, fix_all_columns.php)
- ✅ Added deployment diagnostic tools (deployment_check.php, check_db_structure.php)
- ✅ Fixed column name mismatch (full_name vs name, professional_title vs title)
- ✅ Added null checks to prevent admin panel errors
- ✅ Created safe database initialization (db_init_safe.php)
- ✅ Added admin password reset tool (admin_reset.php)

### Version 1.0
- Core portfolio functionality
- Admin panel
- Database integration
- Authentication system

---

## Quick Reference

### URLs
| Page | URL |
|------|-----|
| Home | http://localhost/MyWebsite/ |
| Dashboard | http://localhost/MyWebsite/dashboard.php |
| Admin Login | http://localhost/MyWebsite/admin_login.php |
| Admin Panel | http://localhost/MyWebsite/panel.php |

### Configuration Reference
| Item | Notes |
|------|-------|
| Admin Login | Configured during setup - must use strong passwords |
| Password Requirements | 8+ characters, uppercase, number, special character |
| Database Name | `db_portfolio` |
| Session Lifetime | 3600 seconds (1 hour) |

### Batch Files
| Batch | Purpose |
|-------|---------|
| RUN.bat | Main Menu |

---

## Contact & Support

For questions or issues with the portfolio system, contact:
- **Developer:** Bryan Darryl Carpio
- **Program:** ASCOT BSIT
- **Year:** 2026

---

**Last Updated:** October 12, 2026  
**Status:** Production Ready ✅
