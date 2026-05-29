# SKYNOXX Tournament Platform - Hostinger Deployment Guide

## Pre-Deployment Checklist

### 1. Database Setup (Hostinger)
- [ ] Create MySQL database in hPanel → Databases
- [ ] Note down:
  - Database name: `u93857826_ff` (or your assigned DB name)
  - Database user: `u93857826_skynoxx` (or your assigned user)
  - Database password: (set during creation)
  - Database host: `localhost`

### 2. Update Database Credentials
**File: `src/db.php` and `src/config.php`**

The production block is already configured with your details:
```php
$host = 'localhost';
$user = 'u93857826_skynoxx';
$password = 'Skysanjiv';  // ← Update if you change password
$database = 'u93857826_ff';
$port = 3306;
```

### 3. Upload Files to Hostinger
**Option A: File Manager (Recommended)**
1. Open hPanel → Websites → skynoxx.live → File Manager
2. Navigate to `public_html` folder
3. Delete any default files (index.html, etc.)
4. Upload ALL files from your local project to `public_html`:
   - index.php
   - .htaccess
   - complete_database.sql
   - src/
   - assets/
   - admin/
   - api/
   - creator/
   - player/
   - uploads/
   - docs/ (optional)

**Option B: FTP (If File Manager is slow)**
1. Get FTP credentials: hPanel → Files → FTP Accounts
2. Use FileZilla:
   - Host: `ftp.skynoxx.live` or `217.21.90.5`
   - Port: 21
   - Upload to `public_html` directory

### 4. Import Database
1. Open hPanel → Databases → phpMyAdmin
2. Select your database (e.g., `u93857826_ff`)
3. Click **Import** tab
4. Choose file: `complete_database.sql`
5. Click **Go**
6. Verify tables are created

### 5. Set Folder Permissions
Ensure these folders have write permissions (755):
```
uploads/
uploads/tournaments/
uploads/profiles/
uploads/qr_codes/
```

In File Manager:
- Right-click folder → Permissions → Set to **755**

### 6. Install SSL Certificate
1. hPanel → Security → SSL
2. Click **Install** for Let's Encrypt
3. Wait 5-10 minutes for activation
4. Your `.htaccess` already forces HTTPS

### 7. Test Your Site
Visit: `https://skynoxx.live/`

**Admin Login:**
- Email: `skynoxx@2005`
- Password: `opjs`

**Test Checklist:**
- [ ] Home page loads with tournaments
- [ ] Login works (admin/creator/player)
- [ ] Create tournament (creator)
- [ ] Register for tournament (player)
- [ ] Upload images/banners work
- [ ] Filters (Solo/Duo/Squad/Clash Squad) work

### 8. Razorpay Configuration
**File: `src/razorpay_config.php`**

Currently in **TEST mode**. To go live:
1. Replace test keys with live keys from Razorpay Dashboard
2. Change mode from 'test' to 'live'
3. Set webhook URL: `https://skynoxx.live/player/razorpay_webhook.php`
4. Update webhook secret in config

### 9. Firebase Push Notifications (Optional)
**File: `firebase-credentials/skynoxx-23f26-firebase-adminsdk-fbsvc-fcce53129d.json`**

Already included. Ensure this file is uploaded and accessible.

---

## Production URLs

| Resource | URL |
|----------|-----|
| Website | https://skynoxx.live |
| Admin Dashboard | https://skynoxx.live/admin/admin_dashboard.php |
| Creator Dashboard | https://skynoxx.live/creator/creator_dashboard.php |
| Player Dashboard | https://skynoxx.live/player/player_dashboard.php |

---

## Troubleshooting

### Database Connection Error
**Error:** "Connection failed: Access denied"

**Fix:**
1. Verify credentials in `src/db.php` and `src/config.php`
2. Ensure database user has all privileges
3. Check database name matches exactly

### 404 Not Found
**Fix:**
1. Ensure `.htaccess` is uploaded to `public_html`
2. Check that index.php is in `public_html` root
3. Verify file permissions (644 for files, 755 for folders)

### Images Not Loading
**Fix:**
1. Check `uploads/` folder exists with 755 permissions
2. Verify asset paths don't contain local Windows paths
3. Clear browser cache

### HTTPS Not Working
**Fix:**
1. Wait 10-15 minutes after SSL installation
2. Check SSL status in hPanel → Security → SSL
3. Verify `.htaccess` has HTTPS redirect rules

---

## Security Checklist

- [x] Admin password is strong and bcrypt-hashed
- [x] Database credentials are environment-based
- [x] HTTPS is enforced via .htaccess
- [ ] Razorpay webhook secret is set (after going live)
- [ ] Firebase credentials are secured (already in protected folder)
- [ ] Test/debug files removed from production

---

## Post-Deployment Tasks

1. **Test all features** on live site
2. **Monitor error logs**: hPanel → Advanced → Error Log
3. **Set up backups**: hPanel → Backups (weekly recommended)
4. **Update DNS TTL** to 1 hour for faster future changes
5. **Enable Cloudflare** (optional, for CDN and DDoS protection)

---

## Support Resources

- Hostinger Documentation: https://support.hostinger.com
- Razorpay Docs: https://razorpay.com/docs/
- Your Admin Email: gameshear09@gmail.com

---

**Deployment Date:** October 30, 2025  
**Version:** 1.0.0  
**Domain:** skynoxx.live
