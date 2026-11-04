# Deployment Guide for InfinityFree

## Overview

This guide will help you deploy your Portfolio System to InfinityFree (or any PHP hosting) while keeping your local development environment separate.

## Pre-Deployment Checklist

- [ ] You have an InfinityFree account
- [ ] You have created a database in InfinityFree control panel
- [ ] You have noted your database credentials
- [ ] You have the latest code from GitHub

## Step 1: Get Your InfinityFree Database Credentials

1. Log in to your InfinityFree control panel
2. Go to **MySQL Databases**
3. Note down:
   - **Database Name** (includes prefix, e.g., `yourname_portfolio`)
   - **Database Username** (includes prefix, e.g., `yourname_user`)
   - **Database Password** (the one you set when creating the database)
   - **Database Host** (usually `localhost`)

## Step 2: Prepare Your Files for Upload

### Files to Upload:
Upload ALL files from your repository EXCEPT:
- `.env` (contains your local credentials)
- `.git/` folder
- `backups/` folder
- `Temp/` folder
- `logs/` folder
- `*.bat` files (Windows-only)

### Files to Keep:
- ✅ All PHP files
- ✅ CSS folder
- ✅ JS folder
- ✅ img folder
- ✅ .env.example (template)
- ✅ Deployment tools (deployment_check.php, fix_all_columns.php, db_init_safe.php)

## Step 3: Upload to InfinityFree

### Option A: Using File Manager
1. Open InfinityFree File Manager
2. Go to `htdocs` folder
3. Upload all your files
4. Make sure `.env.example` is uploaded

### Option B: Using FTP
1. Get FTP credentials from InfinityFree panel
2. Use FileZilla or similar FTP client
3. Upload files to `htdocs` folder

## Step 4: Configure Production Environment

### Method A: Create .env File in File Manager
1. In File Manager, find `.env.example`
2. Copy it and rename to `.env`
3. Edit `.env` with your InfinityFree credentials:

```env
# Database Configuration (InfinityFree)
DB_HOST=localhost
DB_NAME=your_actual_database_name_with_prefix
DB_USER=your_actual_username_with_prefix
DB_PASS=your_actual_database_password
DB_CHARSET=utf8mb4

# Admin Credentials (CHANGE THESE!)
ADMIN_USERNAME=admin
ADMIN_PASSWORD=YourStrongPassword123!
ADMIN_EMAIL=your-email@example.com

# Security Settings
SESSION_LIFETIME=3600
GUEST_SESSION_LIFETIME=86400
MAX_LOGIN_ATTEMPTS=3
LOCKOUT_DURATION=7200

# Site Configuration
SITE_URL=http://yoursite.infinityfreeapp.com
SITE_NAME=Portfolio System

# Environment
ENVIRONMENT=production
```

### Method B: Edit .env.example and Rename
1. Edit `.env.example` directly
2. Replace with your InfinityFree credentials
3. Rename to `.env`

## Step 5: Initialize Database

### Option A: Run Safe Database Initialization
1. Visit: `http://yoursite.infinityfreeapp.com/db_init_safe.php`
2. This will create all tables without deleting existing data
3. Wait for success message

### Option B: Use phpMyAdmin
1. Open phpMyAdmin from InfinityFree panel
2. Select your database
3. Import `database_init.php` or run the SQL manually

## Step 6: Run Deployment Check

Visit: `http://yoursite.infinityfreeapp.com/deployment_check.php`

This will show you:
- ✅ Database connection status
- ✅ Table existence
- ✅ Profile data status
- ❌ Any configuration issues

## Step 7: Test Your Site

1. **Homepage:** `http://yoursite.infinityfreeapp.com/`
2. **Dashboard:** `http://yoursite.infinityfreeapp.com/dashboard.php`
3. **Admin Login:** `http://yoursite.infinityfreeapp.com/admin_login.php`
4. **Guest Access:** `http://yoursite.infinityfreeapp.com/guest_access.php`

## Step 8: Fix Any Missing Columns

If you see errors about missing columns, run:
`http://yoursite.infinityfreeapp.com/fix_all_columns.php`

This will automatically add any missing database columns.

## Common Issues & Solutions

### Issue: Database Connection Failed
**Solution:**
- Check that DB_NAME includes the prefix (e.g., `portfolio123_db_portfolio`)
- Verify DB_USER includes the prefix
- Ensure database user has all privileges
- Check DB_PASS is correct

### Issue: Tables Don't Exist
**Solution:**
- Run `db_init_safe.php` to create tables
- Or use phpMyAdmin to import the schema

### Issue: Column Not Found Error
**Solution:**
- Run `fix_all_columns.php` to add missing columns
- This is common when updating from older versions

### Issue: 500 Internal Server Error
**Solution:**
- Check file permissions (755 for folders, 644 for files)
- Check PHP version (need 7.4+)
- View error logs in InfinityFree panel

## Keeping Local and Production Separate

### Local Development (.env):
```env
DB_HOST=localhost
DB_NAME=db_portfolio
DB_USER=root
DB_PASS=
ENVIRONMENT=development
```

### Production (.env on InfinityFree):
```env
DB_HOST=localhost
DB_NAME=yourname_portfolio
DB_USER=yourname_user
DB_PASS=your_password
ENVIRONMENT=production
```

### How to Deploy New Changes:

1. **Make changes locally** on your computer
2. **Test locally** with XAMPP
3. **Commit to GitHub:**
   ```bash
   git add .
   git commit -m "Your message"
   git push
   ```
4. **Upload to InfinityFree:**
   - Upload changed files via File Manager or FTP
   - Do NOT upload `.env` (it has local credentials)
5. **Test on InfinityFree**

## Important Notes

⚠️ **NEVER commit .env to GitHub** - it contains your database password!

⚠️ **Keep local and production databases separate** - they can have different data

⚠️ **Always backup before deployment** - use the backup system

⚠️ **Test database changes locally first** - then deploy to production

## Deployment Checklist

Before deploying to production:
- [ ] All changes tested locally
- [ ] Database migrations work locally
- [ ] New files committed to GitHub
- [ ] Ready database credentials for production
- [ ] Know how to upload files (File Manager or FTP)
- [ ] Have access to phpMyAdmin on hosting

After deploying:
- [ ] Files uploaded correctly
- [ ] .env configured with production credentials
- [ ] Database initialized (tables created)
- [ ] Deployment check passed
- [ ] Homepage loads correctly
- [ ] Admin login works
- [ ] Guest access works
- [ ] All features tested

## Quick Reference

| Task | Command/URL |
|------|-------------|
| Local Development | http://localhost/MyWebsite/ |
| Production Site | http://yoursite.infinityfreeapp.com/ |
| Deploy Check | deployment_check.php |
| Fix Database | fix_all_columns.php |
| Init Database | db_init_safe.php |
| Admin Reset | admin_reset.php |

---

**Last Updated:** October 2026
**Version:** 2.1
