# Free Hosting Deployment Guide

## Overview
This guide is specifically for deploying your Portfolio System on free hosting platforms like 000webhost, InfinityFree, AwardSpace, or similar services.

## Free Hosting Limitations

**What to expect:**
- Limited database size (usually 100MB-1GB)
- Limited storage space (1GB-5GB)
- No SSL/HTTPS on free plans
- Subdomain only (yourname.host.com)
- Limited bandwidth
- No SSH access
- Ads on your site
- Limited support

**What still works:**
- PHP and MySQL database
- Your portfolio system features
- Admin panel
- All core functionality

## Recommended Free Hosting Platforms

### 000webhost
- **Pros:** Easy setup, good for beginners
- **Cons:** Ads, limited features
- **URL:** https://www.000webhost.com/

### InfinityFree
- **Pros:** No ads, unlimited bandwidth
- **Cons:** Limited database size
- **URL:** https://infinityfree.net/

### AwardSpace
- **Pros:** Reliable, good uptime
- **Cons:** Limited storage
- **URL:** https://www.awardspace.com/

## Deployment Steps

### Step 1: Sign Up for Free Hosting

1. Choose a platform from above
2. Create an account
3. Choose a subdomain name (e.g., `yourportfolio.000webhostapp.com`)
4. Wait for account activation (usually instant)

### Step 2: Access Hosting Control Panel

1. Login to your hosting account
2. Find the control panel (cPanel, VistaPanel, or similar)
3. Look for "File Manager" or "File Upload"

### Step 3: Create Database

1. In control panel, find "MySQL Databases"
2. Create a new database:
   - Database name: `portfolio_db` (or similar)
   - Database user: `portfolio_user`
   - Database password: **Choose a strong password**
3. Note down these credentials - you'll need them

### Step 4: Upload Your Files

**Option A: Using File Manager**
1. Open File Manager
2. Go to `public_html` or `htdocs` folder
3. Upload all files from your MyWebsite folder EXCEPT:
   - `.git/` folder
   - `backups/` folder
   - `Temp/` folder
   - `logs/` folder
   - `*.bat` files
   - Development files (diagnostic, test files)

**Option B: Using FTP**
1. Get FTP credentials from hosting panel
2. Use FileZilla or similar FTP client
3. Upload files to `public_html` or `htdocs`

### Step 5: Configure Environment

1. In File Manager, find `.env.example`
2. Copy it and rename to `.env`
3. Edit `.env` with your hosting database credentials:

```env
# Database Configuration (Free Hosting)
DB_HOST=localhost
DB_NAME=your_actual_database_name
DB_USER=your_actual_database_user
DB_PASS=your_actual_database_password
DB_CHARSET=utf8mb4

# Admin Credentials
ADMIN_USERNAME=admin
ADMIN_PASSWORD=YourStrongPassword123!
ADMIN_EMAIL=your-email@example.com

# Security Settings
SESSION_LIFETIME=3600
MAX_LOGIN_ATTEMPTS=3
LOCKOUT_DURATION=7200

# Site Configuration
SITE_URL=http://your-subdomain.host.com
SITE_NAME=Portfolio System

# Environment
ENVIRONMENT=production
```

### Step 6: Initialize Database

1. Access your website: `http://your-subdomain.host.com/database_init.php`
2. This will create all required tables
3. If this doesn't work, use phpMyAdmin from hosting panel:
   - Open phpMyAdmin
   - Select your database
   - Import the SQL structure from `database_init.php`

### Step 7: Test Your Site

1. Visit: `http://your-subdomain.host.com/`
2. Test dashboard: `http://your-subdomain.host.com/dashboard.php`
3. Test admin login: `http://your-subdomain.host.com/admin_login.php`
4. Login with your admin credentials

## Free Hosting Security Adjustments

**What you CAN do on free hosting:**
- ✅ Strong database password
- ✅ Strong admin password
- ✅ Use .env file for credentials
- ✅ Remove development files
- ✅ Basic file protection via .htaccess

**What you CANNOT do on free hosting:**
- ❌ SSL/HTTPS (usually requires paid plan)
- ❌ Custom domain (usually requires paid plan)
- ❌ SSH access
- ❌ Advanced security headers
- ❌ File permission changes (limited)

## Simplified .htaccess for Free Hosting

Create or update `.htaccess` in your root folder:

```apache
# Disable Directory Browsing
Options -Indexes

# Protect sensitive files
<FilesMatch "^\.env">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "config\.php">
    Order allow,deny
    Deny from all
</FilesMatch>

# Protect backup files
<FilesMatch "\.(sql|zip|bak)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

## Files to Remove Before Upload

Delete these files before uploading to free hosting:
- All `*.bat` files
- `*_DIAGNOSTIC.php`
- `*_TESTER.php`
- `diagnostic.php`
- `full_diagnostic.php`
- `check_status.php`
- `verify_database.php`
- `admin_bypass.php`
- `admin_recover.php`
- `BACKUP_STATUS.php`
- `SYSTEM_BACKUP.php`

## Troubleshooting Free Hosting Issues

### Database Connection Failed
- Check database credentials in `.env`
- Verify database name includes prefix (some hosts add prefix)
- Ensure database user has permissions
- Check if MySQL service is running

### 500 Internal Server Error
- Check file permissions (usually 644 for files, 755 for folders)
- Verify PHP version compatibility (need PHP 7.4+)
- Check error logs in hosting panel

### Files Not Uploading
- Check file size limits (free hosting has limits)
- Upload files in smaller batches
- Use FTP instead of web upload

### Admin Login Not Working
- Clear browser cache
- Verify admin credentials in database
- Check session configuration

## Free Hosting Best Practices

1. **Choose a memorable subdomain** - easier to share
2. **Use strong passwords** - even on free hosting
3. **Regular backups** - download database backups monthly
4. **Monitor storage usage** - free hosting has limits
5. **Keep files clean** - remove unnecessary files
6. **Test regularly** - free hosting can be unstable

## Limitations to Expect

- **No HTTPS** - your site will use HTTP only
- **Subdomain only** - no custom domain on free plans
- **Ads** - hosting provider may show ads
- **Limited support** - free hosting has minimal support
- **Uptime** - may not be 99.9% like paid hosting
- **Performance** - slower than paid hosting

## When to Upgrade to Paid Hosting

Consider upgrading when:
- You need HTTPS/SSL
- You want a custom domain
- You need more storage
- You want better performance
- You need professional support
- You want to remove ads

## Alternative: GitHub Pages (Static Only)

If you only need a static portfolio (no admin panel, no database):
- Convert your PHP to static HTML
- Use GitHub Pages (free, no database needed)
- Better performance and reliability
- HTTPS included
- Custom domain supported

## Quick Checklist

Before deploying to free hosting:
- [ ] Signed up for hosting account
- [ ] Created database and noted credentials
- [ ] Removed development files
- [ ] Created .env with correct credentials
- [ ] Uploaded files to public_html
- [ ] Initialized database
- [ ] Tested homepage
- [ ] Tested admin login
- [ ] Changed default admin password
- [ ] Created initial backup

---

**Last Updated:** August 2026  
**Version:** 1.0 - Free Hosting Edition
