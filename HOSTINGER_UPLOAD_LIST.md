# 📤 Hostinger Upload List - SKYNOXX Platform

## ✅ UPLOAD THESE FILES & FOLDERS

### 📁 Root Files (Upload to `/public_html/`)
```
✅ .htaccess                      (Apache configuration - IMPORTANT!)
✅ index.php                      (Root redirect)
✅ robots.txt                     (SEO & security)
✅ complete_database.sql          (Keep as backup reference)
✅ health_check.php               (For testing only - DELETE AFTER USE!)
```

### 📁 Required Folders (Upload All)
```
✅ admin/                         (Entire folder with all PHP files)
✅ api/                           (Entire folder with all PHP files)
✅ assets/                        (Entire folder with subfolders)
   ├── css/                       (All CSS files)
   ├── images/                    (All images)
   ├── js/                        (All JavaScript files)
   └── vendor/                    (All vendor libraries)
✅ creator/                       (Entire folder with all PHP files)
✅ firebase-credentials/          (Entire folder - IMPORTANT for notifications)
✅ player/                        (Entire folder with all PHP files)
✅ scripts/                       (Entire folder - contains cron jobs)
✅ src/                           (Entire folder - CORE SYSTEM FILES)
✅ uploads/                       (Empty folder or with existing uploads)
   ├── banners/                   (Create if not exists)
   ├── profiles/                  (Create if not exists)
   └── tournaments/               (Create if not exists)
```

### 📁 Optional Folders (Can skip to save space)
```
⚠️ docs/                          (Documentation - optional)
⚠️ sql/                           (Extra SQL files - optional)
```

---

## ❌ DO NOT UPLOAD THESE

### Files to EXCLUDE:
```
❌ config_template.php            (Local reference only)
❌ phpinfo.php                    (Already deleted)
❌ Any *_test.html files          (Already deleted)
❌ Any test_*.php files           (Already deleted)
❌ Any debug_*.php files          (Already deleted)
❌ *.md files                     (Documentation - keep local)
   ❌ README.md
   ❌ DEPLOYMENT_GUIDE.md
   ❌ UPLOAD_GUIDE.md
   ❌ START_HERE.md
   ❌ PRE_DEPLOYMENT_CHECKLIST.md
   ❌ DEPLOYMENT_READY.md
   ❌ HOSTINGER_DEPLOYMENT_COMPLETE.md
   ❌ All other .md files
```

### Folders to EXCLUDE:
```
❌ SKYNOXX/                       (Android app - separate project)
❌ .git/                          (If exists - version control)
❌ node_modules/                  (If exists - not needed)
```

---

## 📦 Complete Upload Structure

Your Hostinger `/public_html/` should look like this:

```
/public_html/
├── .htaccess                    ✅ MUST UPLOAD
├── index.php                    ✅ MUST UPLOAD
├── robots.txt                   ✅ MUST UPLOAD
├── complete_database.sql        ✅ UPLOAD (keep as backup)
├── health_check.php             ✅ UPLOAD (delete after testing)
│
├── admin/                       ✅ ENTIRE FOLDER
│   ├── admin_dashboard.php
│   ├── admin_wallet.php
│   ├── admin_withdrawals.php
│   ├── analytics_dashboard.php
│   ├── announcements.php
│   ├── manage_tournaments.php
│   ├── manage_users.php
│   ├── payment_management.php
│   ├── payments.php
│   ├── player_wallet.php
│   ├── settle_tournament.php
│   ├── settlement_preview.php
│   ├── view_creator_profile.php
│   ├── view_player_profile.php
│   ├── wallet_deposits.php
│   └── withdrawal_history.php
│
├── api/                         ✅ ENTIRE FOLDER
│   ├── api_auth.php
│   ├── api_notifications.php
│   ├── api_payments.php
│   ├── api_registrations.php
│   ├── api_tournaments.php
│   ├── save_fcm_token.php
│   ├── team_search.php
│   └── tournament_status.php
│
├── assets/                      ✅ ENTIRE FOLDER
│   ├── css/                     (All CSS files)
│   ├── images/                  (All images)
│   ├── js/                      (All JavaScript files)
│   └── vendor/                  (All vendor libraries)
│
├── creator/                     ✅ ENTIRE FOLDER
│   ├── create_tournament.php
│   ├── creator_dashboard.php
│   ├── creator_profile_details.php
│   ├── view_tournament.php
│   ├── wallet_dashboard.php
│   ├── wallet_deposit.php
│   └── wallet_withdraw.php
│
├── firebase-credentials/        ✅ ENTIRE FOLDER (IMPORTANT!)
│   └── skynoxx-23f26-firebase-adminsdk-fbsvc-fcce53129d.json
│
├── player/                      ✅ ENTIRE FOLDER
│   (All player module PHP files)
│
├── scripts/                     ✅ ENTIRE FOLDER
│   └── cron_jobs.php
│
├── src/                         ✅ ENTIRE FOLDER (CRITICAL!)
│   ├── config.php               (Has Hostinger credentials)
│   ├── db.php                   (Database connection)
│   ├── auth.php
│   ├── index.php                (Homepage)
│   ├── login.php
│   ├── register.php
│   ├── razorpay_config.php
│   ├── wallet_deposit_create_order.php
│   ├── wallet_deposit_verify.php
│   └── (All other core files)
│
└── uploads/                     ✅ CREATE/UPLOAD
    ├── banners/                 (Tournament banners)
    ├── profiles/                (User profile pictures)
    └── tournaments/             (Tournament related files)
```

---

## 🎯 Quick Upload Checklist

### Before You Start:
- [ ] Have Hostinger login ready
- [ ] Know your upload method (File Manager or FTP)
- [ ] Have stable internet connection
- [ ] Allocate 30-45 minutes

### Upload Order (Recommended):
1. **Root files first** (.htaccess, index.php, robots.txt)
2. **src/ folder** (core system)
3. **assets/ folder** (CSS, JS, images)
4. **Module folders** (admin/, creator/, player/, api/)
5. **Supporting folders** (firebase-credentials/, scripts/, uploads/)
6. **Database file** (complete_database.sql)
7. **Health check** (health_check.php)

### After Upload:
- [ ] Import `complete_database.sql` via phpMyAdmin
- [ ] Test with `health_check.php`
- [ ] **DELETE `health_check.php`** immediately
- [ ] Enable SSL certificate
- [ ] Test homepage and admin login

---

## 📏 Estimated Upload Size

| Folder/File | Approx Size |
|-------------|-------------|
| assets/ | ~5-15 MB |
| src/ | ~2-5 MB |
| admin/ | ~500 KB - 2 MB |
| creator/ | ~500 KB - 2 MB |
| player/ | ~500 KB - 2 MB |
| api/ | ~200-500 KB |
| firebase-credentials/ | ~5 KB |
| scripts/ | ~50-100 KB |
| uploads/ | Variable (your content) |
| Root files | ~50-100 KB |
| **TOTAL** | **~10-30 MB** |

Upload time: 3-10 minutes depending on connection speed

---

## ⚡ Fast Upload Methods

### Method 1: File Manager (Best for Hostinger)
1. Login to hPanel
2. Files → File Manager
3. Go to public_html/
4. Upload folders one by one
5. ⏱️ Time: 5-10 minutes

### Method 2: ZIP Upload (Faster)
1. On your PC: Compress project to ZIP (exclude .md files and SKYNOXX folder)
2. Upload ZIP to public_html/
3. Right-click → Extract
4. Delete ZIP file
5. ⏱️ Time: 3-5 minutes

### Method 3: FTP (Most Reliable)
1. Use FileZilla or WinSCP
2. Connect to ftp.skynoxx.live
3. Drag all folders to public_html/
4. ⏱️ Time: 3-8 minutes

---

## 🔐 File Permissions After Upload

Set these via File Manager → Right-click → Permissions:

| Type | Permission | Code |
|------|-----------|------|
| Folders | 755 | drwxr-xr-x |
| PHP files | 644 | -rw-r--r-- |
| .htaccess | 644 | -rw-r--r-- |
| Firebase JSON | 600 | -rw------- (recommended) |
| uploads/ folder | 775 | drwxrwxr-x |

---

## ✅ Final Verification

After upload, your public_html should have:
- ✅ 1 .htaccess file
- ✅ 1 index.php file
- ✅ 1 robots.txt file
- ✅ 8 folders (admin, api, assets, creator, firebase-credentials, player, scripts, src)
- ✅ 1 uploads folder (with subfolders)
- ✅ 1 health_check.php (temporary)
- ✅ 1 complete_database.sql (backup)

**Total folders**: 8-9  
**Total files**: ~100-200+ files across all folders

---

## 🚀 Ready to Upload!

**Start uploading now using any method above.**

**Next steps after upload:**
1. Import database (complete_database.sql)
2. Run health check
3. Delete health check
4. Test your site!

**See `UPLOAD_GUIDE.md` for detailed step-by-step instructions.**

---

**Good luck! 🎉**
