# Portfolio System - Documentation

## Overview

**Portfolio System** is a comprehensive web-based portfolio management application built with PHP, MySQL, and JavaScript. It now includes a branded public visitor entry page, a separate visitor/admin access flow, and an admin panel for content management.

**Built by:** Bryan Darryl Carpio  
**Program:** ASCOT - BSIT Student

---

## Features

### 👤 For Visitors (Public Portfolio)
- **Profile Display** - View professional information, bio, vision, and contact details
- **Project Showcase** - Browse implemented projects with technologies used
- **Education Timeline** - View educational background and achievements
- **Hobbies & Interests** - Discover personal interests and skills
- **Contact Information** - Easy access to contact channels with clickable email and Facebook links
- **Responsive Design** - Works on desktop, tablet, and mobile devices
- **Visitor Portal** - Public sign-in and sign-up entry for visitor accounts (24-hour session)
- **Simplified UI** - Clean, modern interface without nested borders

### 🔐 For Admin (Management Panel)
- **Profile Management** - Edit profile information, professional title, bio, vision, and Facebook URL
- **Project Management** - Add, edit, delete projects with technology tags
- **Education Tracking** - Manage education records and achievements
- **Hobbies & Skills** - Organize personal interests by category
- **Contact Management** - Configure contact types and information
- **Activity Logging** - Track all system activities and changes
- **Data Snapshots** - Backup individual sections before making changes
- **Full System Backup** - Create complete backup of database and files
- **System Restore** - Restore from previous backups quickly
- **Admin Credential Management** - Access via settings icon in top bar
- **Database Migration Tools** - Easy column addition and structure fixes

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
│   ├── backup.php               # Backup creation/deletion
│   ├── restore.php              # Restoration services
│   ├── profile.php              # Profile data API
│   ├── education.php            # Education API
│   ├── projects.php             # Projects API
│   ├── hobbies.php              # Hobbies API
│   ├── contact.php              # Contact info API
│   ├── activity.php             # Activity logging API
│   └── snapshot.php             # Section snapshots
├── CSS/                         # Stylesheets
│   ├── style.css                # Main styles
│   └── css_admin.css            # Admin panel styles
├── JS/                          # JavaScript files
│   ├── main.js                  # Main functionality
│   └── security.js              # Security features
├── images/                      # Project images
├── img/                         # Media files
│   └── projects/                # Project media
├── audio/                       # Audio files
├── backups/                     # Backup storage
└── Temp/                        # Temporary files
```

### Database Schema

**Core Tables:**
- `profile` - User profile information
- `admin_users` - Admin credentials
- `education` - Education records
- `education_achievements` - Academic achievements
- `projects` - Project portfolio
- `project_status` - Project status types
- `project_technologies` - Technology associations
- `technologies` - Technology database
- `hobby_items` - Personal interests
- `hobby_categories` - Interest categories
- `contact_info` - Contact details
- `contact_types` - Contact type definitions
- `activity_log` - System activity tracking
- `page_views` - Visitor statistics
- `data_snapshots` - Section backups
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
4. **Backup/Restore?** Double-click `BACKUP.bat` or `RESTORE.bat`

#### Option 2: Direct Browser Access
1. Start XAMPP (Apache + MySQL)
2. Open browser and go to:
   - **Home:** `http://localhost/MyWebsite/`
   - **Portfolio:** `http://localhost/MyWebsite/dashboard.php`
   - **Admin Login:** `http://localhost/MyWebsite/admin_login.php`
   - **Admin Panel:** `http://localhost/MyWebsite/panel.php`
   - **Backup/Restore:** `http://localhost/MyWebsite/FULL_BACKUP_RESTORE.php`

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

### For Public Users (Dashboard)
1. **Open Dashboard:** Go to `http://localhost/MyWebsite/dashboard.php`
2. **Browse Portfolio:**
   - Scroll through profile and introduction
   - View project showcases
   - Check education background
   - See hobbies and interests
   - Access contact information

### For Admin (Management Panel)

#### Logging In
1. Go to `http://localhost/MyWebsite/admin_login.php`
2. Enter admin credentials
3. Click "Login"

#### Managing Content
1. **Profile:** Edit personal information, title, bio
2. **Education:** Add degrees, institutions, years
3. **Projects:** Create project entries with technologies
4. **Hobbies:** Organize interests by category
5. **Contact:** Update contact information

#### Creating Backups
1. Click "🔐 Backup & Restore" in admin panel
2. Choose backup type:
   - **Database Only** - Quick database backup
   - **Full System** - Complete backup with files
   - **Snapshots** - Backup single sections
3. Download backups to safe location

#### Restoring Data
1. Go to "Backup & Restore" manager
2. Click "Restore" tab
3. Upload backup file (.zip or .sql)
4. Confirm restoration
5. System automatically restores all data

---

## Batch Files Reference

| File | Purpose | Opens |
|------|---------|-------|
| **RUN.bat** | Main menu | http://localhost/MyWebsite/ |
| **BACKUP.bat** | Backup interface | Backup page |
| **RESTORE.bat** | Restore interface | Restore page |

Run any batch file by double-clicking it in File Explorer.

---

## Security Features

### Implemented Security
- ✅ Session-based authentication
- ✅ Password hashing (PASSWORD_DEFAULT)
- ✅ SQL injection prevention (Prepared statements)
- ✅ Visitor/admin separation on the public access flow
- ✅ 3-attempt sign-in lockout with 2-hour cooldown
- ✅ Secure backup management
- ✅ CSRF protection
- ✅ Activity logging and tracking

### Best Practices
- Always change default admin password
- Keep backups in secure location
- Test restore procedure regularly
- Monitor activity logs for suspicious actions
- Use strong passwords for admin account

---

## Backup & Restore

### Creating Backups
**Database Only:**
- Lightweight and fast
- Ideal for daily backups
- Size: 1-5 MB typically

**Full System:**
- Includes database, files, media
- Comprehensive backup
- Size: 50-500 MB depending on media

**Section Snapshots:**
- Individual section backups
- Quick restore single sections
- Used before making major changes

### Restore Procedure
1. Access "Backup & Restore" from admin panel
2. Click "Restore" tab
3. Select backup file (.zip or .sql)
4. Confirm restoration
5. System restores to selected backup point

⚠️ **Warning:** Restoration overwrites current data. Always verify you have recent backups.

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

### Issue: Backup/Restore Not Working
**Solution:**
1. Ensure `/backups` folder exists (writable)
2. Check disk space available
3. Verify database permissions
4. Try creating new backup first

---

## File Permissions

For proper functionality, ensure:
- `/backups/` folder is writable (755)
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

### Backup API (`api/backup.php`)
- `GET ?action=full-backup` - Create database backup
- `GET ?action=full-backup-with-files` - Full system backup
- `POST action=restore` - Restore from backup
- `GET ?action=delete&file=filename` - Delete backup

### Profile API (`api/profile.php`)
- `GET` - Retrieve profile data
- `POST` - Update profile

### Projects API (`api/projects.php`)
- `GET` - List all projects
- `POST` - Add new project
- `PUT` - Update project
- `DELETE` - Remove project

### Other APIs
- `education.php` - Education management
- `hobbies.php` - Hobbies management
- `contact.php` - Contact information
- `activity.php` - Activity logging

---

## Performance Tips

1. **Optimize Images** - Keep project images under 500KB
2. **Database Cleanup** - Archive old activity logs quarterly
3. **Backup Management** - Delete old backups to save space
4. **Browser Caching** - Leverages CSS/JS caching
5. **CDN Ready** - Can integrate CDN for static assets

---

## Support & Maintenance

### Regular Maintenance
- Test backup/restore monthly
- Review activity logs weekly
- Update project information as needed
- Verify backup integrity periodically

### Common Maintenance Tasks
1. **Change Admin Password**
   - Admin Panel → Account Settings
2. **Backup System**
   - Admin Panel → Backup & Restore → Create Backup
3. **View Activity**
   - Admin Panel → Activity Log
4. **Check Statistics**
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
5. Test with backup/restore

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

- **System Version:** 2.0
- **Release Date:** April 2026
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
| Backup/Restore | http://localhost/MyWebsite/FULL_BACKUP_RESTORE.php |

### Configuration Reference
| Item | Notes |
|------|-------|
| Admin Login | Configured during setup - must use strong passwords |
| Password Requirements | 8+ characters, uppercase, number, special character |
| Database Name | `db_portfolio` |
| Session Lifetime | 3600 seconds (1 hour) |

### Files
| Batch | Purpose |
|-------|---------|
| RUN.bat | Main Menu |
| BACKUP.bat | Backup Manager |
| RESTORE.bat | Restore Manager |

---

## Contact & Support

For questions or issues with the portfolio system, contact:
- **Developer:** Bryan Darryl Carpio
- **Program:** ASCOT BSIT
- **Year:** 2026

---

**Last Updated:** April 20, 2026  
**Status:** Production Ready ✅
