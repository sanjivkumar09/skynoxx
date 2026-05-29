# =====================================================
# SKYNOXX Tournament Platform - InfinityFree Deployment Guide
# =====================================================

## 📋 Database Setup (COMPLETED ✅)

Your database credentials have been configured:
- **MySQL Hostname:** sql102.infinityfree.com
- **MySQL Username:** if0_40257554
- **MySQL Password:** 6zZ0Mf1gLN
- **MySQL Database:** if0_40257554_skynox_ff
- **MySQL Port:** 3306

## 🚀 Step-by-Step Deployment Instructions

### Step 1: Import Database ✅

1. **Open phpMyAdmin** from your InfinityFree dashboard
   - URL: Click "phpMyAdmin" button on your database page
   
2. **Select your database:** `if0_40257554_skynox_ff`

3. **Import the SQL file:**
   - Click "Import" tab
   - Click "Choose File"
   - Select: `complete_database.sql`
   - Scroll down and click "Go"
   - Wait for success message

4. **Verify tables created:**
   - Click on database name in left sidebar
   - You should see all 11 tables:
     - users
     - players_profile
     - creators
     - tournaments
     - registrations
     - payments
     - payment_transactions
     - wallet_transactions
     - player_wallets
     - withdrawals
     - announcements

### Step 2: Upload Files to InfinityFree

1. **Open File Manager** in InfinityFree Control Panel
   - OR use FTP client (FileZilla recommended)

2. **Navigate to `htdocs` folder**
   - This is your web root directory

3. **Upload ALL files from:**
   ```
   C:\xampp\htdocs\Free fire 1\free-fire-tournament-platform\
   ```
   
4. **Important folders to upload:**
   - ✅ `/src/` - All PHP core files
   - ✅ `/assets/` - CSS, JS, images
   - ✅ `/admin/` - Admin panel files
   - ✅ `/creator/` - Creator dashboard files
   - ✅ `/player/` - Player dashboard files
   - ✅ `/api/` - API endpoints
   - ✅ `.htaccess` - Server configuration
   - ✅ All root PHP files

### Step 3: Configure File Permissions

Set these permissions via File Manager or FTP:

```
/uploads/                    - 755 (writable)
/uploads/players/            - 755 (writable)
/uploads/creators/           - 755 (writable)
/uploads/tournament_banners/ - 755 (writable)
/src/uploads/                - 755 (writable)
```

### Step 4: Update Configuration Files (ALREADY DONE ✅)

The following files have been updated with your credentials:

1. ✅ `src/config.php` - Updated with InfinityFree DB credentials
2. ✅ `src/db.php` - Updated with InfinityFree DB credentials
3. ✅ `src/razorpay_config.php` - Updated branding to SKYNOXX

### Step 5: Update BASE_URL

After uploading, update the BASE_URL in `src/config.php`:

```php
// Replace 'yourdomain' with your actual InfinityFree subdomain
define('BASE_URL', 'http://yourdomain.infinityfreeapp.com/');

// OR if you have custom domain:
define('BASE_URL', 'http://yoursite.com/');
```

### Step 6: Test Your Site

1. **Visit your website:**
   - http://yourdomain.infinityfreeapp.com/src/index.php
   
2. **Test admin login:**
   - URL: http://yourdomain.infinityfreeapp.com/src/login.php
   - Email: `skynoxx@gmail.com`
   - Password: `skynoxx@09`

3. **Check pages:**
   - ✅ Homepage
   - ✅ Tournaments page
   - ✅ Admin dashboard
   - ✅ Player registration
   - ✅ Creator registration

### Step 7: Enable SSL Certificate (Optional but Recommended)

1. **Go to InfinityFree Control Panel**
2. **Navigate to SSL Certificates**
3. **Generate free SSL certificate**
4. **Wait 24-48 hours for activation**
5. **Update BASE_URL to use https://**

### Step 8: Configure Razorpay (For Live Payments)

1. **Get Razorpay API Keys:**
   - Visit: https://dashboard.razorpay.com/
   - Sign up/Login
   - Go to Settings > API Keys
   - Copy Test/Live keys

2. **Update `src/razorpay_config.php`:**
   ```php
   define('RAZORPAY_KEY_ID', 'rzp_test_YOUR_ACTUAL_KEY');
   define('RAZORPAY_KEY_SECRET', 'YOUR_ACTUAL_SECRET');
   ```

3. **Set webhook:**
   - Razorpay Dashboard > Webhooks
   - Add: http://yoursite.com/player/razorpay_webhook.php

## 📊 Admin Access

**Admin Login Credentials:**
- **URL:** http://yoursite.com/src/login.php
- **Email:** skynoxx@gmail.com
- **Password:** skynoxx@09

**Admin Dashboard Features:**
- ✅ Manage Users (Players & Creators)
- ✅ Manage Tournaments
- ✅ View Payments & Transactions
- ✅ Process Withdrawal Requests
- ✅ View Wallet Deposits Report
- ✅ Post Announcements
- ✅ Analytics Dashboard

## 🎮 Sample Accounts (for Testing)

**Creator Account:**
- Email: creator@example.com
- Password: password123

**Player Account:**
- Email: player@example.com
- Password: password123

## ⚠️ InfinityFree Limitations to Know

1. **Daily Hit Limit:** 50,000 hits per day
2. **Storage:** Check your plan limit
3. **No Node.js/Python:** Only PHP supported
4. **MySQL:** Remote connections not allowed
5. **Cron Jobs:** Limited or unavailable
6. **File Upload:** Max 10MB per file

## 🔧 Troubleshooting

### Database Connection Error:
- Verify database name: `if0_40257554_skynox_ff`
- Check username: `if0_40257554`
- Ensure password is correct: `6zZ0Mf1gLN`

### 404 Errors:
- Check .htaccess is uploaded
- Verify file paths are correct
- Ensure src/ folder exists

### Upload Folder Permissions:
```bash
chmod 755 uploads/
chmod 755 uploads/players/
chmod 755 uploads/creators/
chmod 755 uploads/tournament_banners/
```

### Images Not Loading:
- Check file paths use relative paths
- Verify assets/images/ folder uploaded
- Check image filenames match (case-sensitive)

## 📞 Support Contact

**SKYNOXX Support:**
- Email: gameshear09@gmail.com
- Platform: SKYNOXX Tournament Platform

## ✅ Deployment Checklist

- [ ] Database imported successfully
- [ ] All files uploaded to htdocs
- [ ] File permissions set correctly
- [ ] BASE_URL updated in config.php
- [ ] Admin login tested
- [ ] Tournament creation tested
- [ ] Player registration tested
- [ ] Payment flow tested
- [ ] Razorpay configured (for live)
- [ ] SSL certificate enabled

## 🎉 Your site is now live!

Visit: http://yourdomain.infinityfreeapp.com/src/index.php

---
**Generated:** October 26, 2025
**Platform:** SKYNOXX Tournament Platform v1.0
