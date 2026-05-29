# 📤 UPLOAD TO HOSTINGER - Quick Guide

## Step-by-Step Upload Process

### Method 1: File Manager (Recommended) 🎯

#### Step 1: Login to Hostinger
1. Go to: https://hpanel.hostinger.com
2. Login with your credentials
3. Select your hosting plan for **skynoxx.live**

#### Step 2: Access File Manager
1. In hPanel, click **Files** → **File Manager**
2. Navigate to `/public_html/` folder
3. **Important**: Delete any default files (index.html, .htaccess from Hostinger)

#### Step 3: Upload Files
**Option A: Upload Individual Folders (Recommended for reliability)**
1. Click **Upload** button in File Manager
2. Select folders one by one:
   - `admin/` folder
   - `api/` folder
   - `assets/` folder
   - `creator/` folder
   - `docs/` folder (optional)
   - `firebase-credentials/` folder
   - `player/` folder
   - `scripts/` folder
   - `src/` folder
   - `sql/` folder (optional)
   - `uploads/` folder
3. Upload root files:
   - `.htaccess`
   - `index.php`
   - `robots.txt`
   - `complete_database.sql`
   - `health_check.php`

**Option B: Upload as ZIP (Faster but may timeout)**
1. On your local PC, compress the project folder to ZIP
2. Upload the ZIP file to `/public_html/`
3. Right-click the ZIP → **Extract**
4. Delete the ZIP file after extraction

#### Step 4: Set Permissions
1. Select all folders → Right-click → **Permissions** → Set to `755`
2. Select all PHP files → Right-click → **Permissions** → Set to `644`
3. Select `firebase-credentials/` folder files → Set to `600` (extra security)

---

### Method 2: FTP Upload (Alternative) 📡

#### FTP Credentials
- **Host**: `ftp.skynoxx.live`
- **Username**: [Get from Hostinger hPanel → FTP Accounts]
- **Password**: [Your FTP password]
- **Port**: 21

#### Using FileZilla:
1. Download FileZilla: https://filezilla-project.org/
2. Open FileZilla
3. Enter FTP credentials at top
4. Click **Quickconnect**
5. Navigate to `/public_html/` in right pane (server)
6. Navigate to project folder in left pane (local)
7. Drag and drop all folders/files from left to right
8. Wait for upload to complete (may take 5-15 minutes)

---

## Database Import 🗄️

### Step 1: Access phpMyAdmin
1. In Hostinger hPanel, go to **Databases** → **MySQL Databases**
2. Find database: `u93857826_ff`
3. Click **Enter phpMyAdmin**

### Step 2: Import SQL File
1. In phpMyAdmin, select database `u93857826_ff` from left sidebar
2. Click **Import** tab at top
3. Click **Choose File** button
4. Select `complete_database.sql` from your local project folder
5. Scroll down and click **Go** button
6. Wait for import to complete
7. You should see: "Import has been successfully finished"

### Step 3: Verify Import
1. Click **Structure** tab
2. Verify tables exist:
   - `users` (should have at least 1 admin user)
   - `tournaments`
   - `registrations`
   - `payments`
   - `wallet_transactions`
   - `withdrawals`
   - etc.
3. Click `users` table → **Browse**
4. Verify admin user exists with email: `skynoxx@2005`

---

## First-Time Verification ✅

### Step 1: Run Health Check
1. Visit: `https://skynoxx.live/health_check.php`
2. Check all items:
   - ✅ PHP Version (8.0+)
   - ✅ Extensions (mysqli, json, etc.)
   - ✅ Database Connection
   - ✅ File Permissions
   - ✅ .htaccess Active
3. **If all green**: Proceed to Step 2
4. **If any red**: See troubleshooting section

### Step 2: Delete Health Check
**IMPORTANT**: For security, delete the health check file:
1. Go back to File Manager
2. Navigate to `/public_html/`
3. Find `health_check.php`
4. Right-click → **Delete**
5. Confirm deletion

### Step 3: Test Homepage
1. Visit: `https://skynoxx.live`
2. Should see tournament platform homepage
3. Check if tournaments are listed
4. Test filter buttons (Solo/Duo/Squad/Clash Squad)

### Step 4: Test Admin Login
1. Go to: `https://skynoxx.live/admin/admin_dashboard.php`
2. Login with:
   - **Email**: `skynoxx@2005`
   - **Password**: `opjs`
3. Should see admin dashboard
4. Check statistics and tournament list

### Step 5: Enable SSL Certificate
1. Go to Hostinger hPanel
2. Click **SSL** section
3. Enable **Let's Encrypt SSL** for skynoxx.live
4. Wait 10-15 minutes for activation
5. Test HTTPS redirect:
   - Visit `http://skynoxx.live` (without 's')
   - Should automatically redirect to `https://skynoxx.live`

---

## Post-Upload Checklist 📋

- [ ] All files uploaded to `/public_html/`
- [ ] Database imported successfully
- [ ] `health_check.php` run and shows all green
- [ ] `health_check.php` **DELETED**
- [ ] Homepage accessible at https://skynoxx.live
- [ ] Admin login working (skynoxx@2005 / opjs)
- [ ] SSL certificate enabled
- [ ] HTTPS redirect working
- [ ] No PHP errors visible on pages
- [ ] Tournament filters working
- [ ] Mobile responsive layout working

---

## Common Issues & Fixes 🔧

### Issue: 500 Internal Server Error
**Solution:**
1. Check `.htaccess` file uploaded correctly
2. Verify PHP version is 8.0+ in Hostinger: **Advanced** → **PHP Configuration**
3. Check error log: File Manager → `public_html/error_log`

### Issue: Database Connection Failed
**Solution:**
1. Open `src/db.php` in File Manager editor
2. Verify credentials:
   ```php
   $host = 'localhost'; // NOT an IP address
   $username = 'u93857826_skynoxx';
   $password = 'Skysanjiv';
   $database = 'u93857826_ff';
   ```
3. Ensure database imported via phpMyAdmin

### Issue: Blank White Page
**Solution:**
1. Check if all files uploaded (especially `src/` folder)
2. View error log in File Manager
3. Temporarily enable errors by editing the problematic PHP file:
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```
4. Refresh page to see error
5. Fix the error, then disable display_errors again

### Issue: CSS/Images Not Loading
**Solution:**
1. Open browser console (F12) → Check for 404 errors
2. Verify `assets/` folder uploaded completely
3. Check file permissions (644 for files)
4. Clear browser cache (Ctrl+F5)

### Issue: Cannot Upload Large Files
**Solution:**
1. Split upload into smaller batches
2. Use FTP instead of File Manager
3. Or upload as ZIP and extract on server

---

## File Permissions Reference 🔐

| Item | Permission | Code | Description |
|------|------------|------|-------------|
| Directories | `755` | `drwxr-xr-x` | Owner can write, others can read/execute |
| PHP/HTML files | `644` | `-rw-r--r--` | Owner can write, others can only read |
| Firebase JSON | `600` | `-rw-------` | Only owner can read/write (recommended) |
| .htaccess | `644` | `-rw-r--r--` | Standard for Apache config |
| uploads/ folder | `775` | `drwxrwxr-x` | Writable for file uploads |

---

## Contact & Support 📞

**Hostinger Support:**
- 24/7 Live Chat: Available in hPanel
- Knowledge Base: https://support.hostinger.com
- Ticket System: Via hPanel

**Project Documentation:**
- `DEPLOYMENT_READY.md` - Complete deployment overview
- `PRE_DEPLOYMENT_CHECKLIST.md` - Pre-upload checklist
- `HOSTINGER_DEPLOYMENT_COMPLETE.md` - Detailed technical guide

---

## Estimated Timeline ⏱️

| Task | Time Estimate |
|------|---------------|
| File upload (File Manager) | 5-10 minutes |
| File upload (FTP) | 3-5 minutes |
| Database import | 1-2 minutes |
| Health check & verification | 2-3 minutes |
| SSL certificate activation | 10-15 minutes |
| Full testing | 10-15 minutes |
| **Total** | **30-45 minutes** |

---

## 🎉 Ready to Upload!

**Your project is fully prepared for Hostinger deployment.**

**What's configured:**
- ✅ Production database credentials
- ✅ HTTPS enforcement
- ✅ Security headers
- ✅ Asset paths (root-relative)
- ✅ Admin credentials in SQL
- ✅ Error handling (production-safe)
- ✅ File protections
- ✅ Environment detection

**Start uploading now! Follow the steps above and your site will be live in under an hour.**

**Good luck! 🚀**
