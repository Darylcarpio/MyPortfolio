# Production Deployment Guide

## Overview
This guide will help you deploy your Portfolio System to a live production server.

## Pre-Deployment Checklist

### 1. Server Requirements
- ✅ PHP 7.4 or higher
- ✅ MySQL 5.7+ or MariaDB 10.2+
- ✅ Apache web server with mod_rewrite enabled
- ✅ SSL certificate (HTTPS required for production)
- ✅ At least 500MB disk space

### 2. Security Preparation
- ✅ Change all default passwords
- ✅ Configure strong database credentials
- ✅ Set up SSL certificate
- ✅ Configure firewall rules
- ✅ Disable directory browsing

## Deployment Steps

### Step 1: Prepare Environment File

1. Copy the example environment file:
```bash
cp .env.example .env
```

2. Edit `.env` with your production values:
```env
# Database Configuration
DB_HOST=localhost
DB_NAME=db_portfolio
DB_USER=your_production_db_user
DB_PASS=your_strong_password_here
DB_CHARSET=utf8mb4

# Admin Credentials (CHANGE THESE!)
ADMIN_USERNAME=admin
ADMIN_PASSWORD=YourStrongPassword123!@#
ADMIN_EMAIL=your-email@example.com

# Security Settings
SESSION_LIFETIME=3600
MAX_LOGIN_ATTEMPTS=3
LOCKOUT_DURATION=7200

# Site Configuration
SITE_URL=https://yourdomain.com
SITE_NAME=Portfolio System

# Environment
ENVIRONMENT=production
```

### Step 2: Upload Files to Server

**Option A: Using Git (Recommended)**
```bash
git clone https://github.com/Darylcarpio/MyPortfolio.git
cd MyPortfolio
cp .env.example .env
# Edit .env with production values
```

**Option B: Using FTP/SFTP**
- Upload all files except:
  - `.git/` directory
  - `backups/` directory
  - `Temp/` directory
  - `logs/` directory
  - Development batch files (*.bat)

### Step 3: Set File Permissions

```bash
# Set proper permissions
chmod 755 /var/www/html/MyWebsite
chmod 644 /var/www/html/MyWebsite/*.php
chmod 644 /var/www/html/MyWebsite/*.css
chmod 644 /var/www/html/MyWebsite/*.js

# Create and set permissions for writable directories
mkdir -p /var/www/html/MyWebsite/backups
mkdir -p /var/www/html/MyWebsite/Temp
mkdir -p /var/www/html/MyWebsite/logs
chmod 755 /var/www/html/MyWebsite/backups
chmod 755 /var/www/html/MyWebsite/Temp
chmod 755 /var/www/html/MyWebsite/logs

# Protect .env file
chmod 600 /var/www/html/MyWebsite/.env
```

### Step 4: Configure Database

1. Create database and user:
```sql
CREATE DATABASE db_portfolio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'portfolio_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON db_portfolio.* TO 'portfolio_user'@'localhost';
FLUSH PRIVILEGES;
```

2. Import database structure:
```bash
mysql -u portfolio_user -p db_portfolio < database_init.php
# Or run the initialization script through your browser
# https://yourdomain.com/database_init.php
```

### Step 5: Update Configuration

Include the new environment-aware config in your files:

**At the top of each PHP file, replace:**
```php
require_once 'config.php';
```

**With:**
```php
require_once 'config_env.php';
```

**Or update config.php to use environment variables:**
```php
// Add this at the top of config.php
if (file_exists(__DIR__ . '/config_env.php')) {
    require_once __DIR__ . '/config_env.php';
}
```

### Step 6: Configure Apache

Create or update `.htaccess`:
```apache
# Security Headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

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

# Enable HTTPS redirect
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
```

### Step 7: Test Deployment

1. **Test homepage:** `https://yourdomain.com/`
2. **Test dashboard:** `https://yourdomain.com/dashboard.php`
3. **Test admin login:** `https://yourdomain.com/admin_login.php`
4. **Test admin panel:** `https://yourdomain.com/panel.php`

### Step 8. Post-Deployment Security

1. **Remove development files:**
```bash
rm -f *.bat
rm -f *_DIAGNOSTIC.php
rm -f *_TESTER.php
rm -f diagnostic.php
rm -f full_diagnostic.php
rm -f check_status.php
rm -f verify_database.php
```

2. **Update admin password:**
   - Log in to admin panel
   - Change password immediately

3. **Create initial backup:**
   - Access admin panel
   - Create full system backup
   - Download and store securely

## Ongoing Maintenance

### Weekly Tasks
- Review activity logs
- Check for suspicious login attempts
- Verify backup integrity

### Monthly Tasks
- Create full system backup
- Update software dependencies
- Review and rotate passwords
- Check disk space usage

### Security Best Practices
- Keep PHP and MySQL updated
- Monitor server logs
- Use strong, unique passwords
- Enable two-factor authentication if available
- Regular security audits

## Troubleshooting

### Database Connection Failed
- Check database credentials in `.env`
- Verify MySQL service is running
- Test database user permissions

### 500 Internal Server Error
- Check Apache error logs: `/var/log/apache2/error.log`
- Verify file permissions
- Check PHP error logs

### Admin Login Not Working
- Clear browser cache and cookies
- Verify admin credentials in database
- Check session configuration in `.env`

### Files Not Uploading
- Verify directory permissions (755)
- Check PHP upload limits in `php.ini`
- Ensure sufficient disk space

## Support

For deployment issues:
1. Check this documentation
2. Review server error logs
3. Test database connection
4. Verify file permissions

## Backup Strategy

### Automated Backups
Set up cron jobs for automated backups:

```bash
# Daily database backup
0 2 * * * /usr/bin/mysqldump -u portfolio_user -p'password' db_portfolio > /backups/db_$(date +\%Y\%m\%d).sql

# Weekly full backup
0 3 * * 0 tar -czf /backups/full_$(date +\%Y\%m\%d).tar.gz /var/www/html/MyWebsite
```

### Backup Retention
- Keep daily backups for 7 days
- Keep weekly backups for 4 weeks
- Keep monthly backups for 12 months

## SSL Certificate Setup

### Let's Encrypt (Free SSL)
```bash
sudo apt-get install certbot python3-certbot-apache
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com
```

### Auto-renewal
```bash
sudo certbot renew --dry-run
```

---

**Last Updated:** August 2026  
**Version:** 1.0
