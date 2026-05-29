# 🚀 Pre-Deployment Checklist for Hostinger

**Before uploading to Hostinger, verify all items below are complete.**

---

## ✅ 1. Security Audit

### Error Reporting
- [ ] **CRITICAL**: Remove or comment out the following lines in these files:
  - `admin/admin_withdrawals.php` - Remove error_reporting/display_errors at top
  - `creator/test_pay_prize.php` - **DELETE THIS FILE** (test file)
  - `creator/view_tournament.php` - Remove error_reporting/display_errors
  - `creator/wallet_deposit.php` - Remove error_reporting/display_errors
  - `creator/wallet_withdraw.php` - Remove error_reporting/display_errors
  - `player/wallet_deposit.php` - Remove error_reporting/display_errors
  - `player/wallet_withdraw.php` - Remove error_reporting/display_errors

### Test Files to Delete
- [ ] `health_check.php` - **DELETE AFTER first deployment verification**
- [ ] `creator/test_pay_prize.php` - **DELETE NOW**
- [ ] Any remaining files starting with `test_` or `debug_`

### Configuration
- [x] `src/config.php` - Environment detection working
- [x] `src/db.php` - Hostinger credentials configured
- [x] `.htaccess` - Security headers and HTTPS enforcement enabled
- [x] `robots.txt` - Created with sensitive directory blocking

---

## ✅ 2. Database Preparation

- [x] `complete_database.sql` - Ready with admin credentials
  - Admin Email: `skynoxx@2005`
  - Admin Password: `opjs` (hashed in SQL)
  
### On Hostinger:
1. Go to **Databases** → **MySQL Databases**
2. Database already created: `u93857826_ff`
3. User already created: `u93857826_skynoxx`
4. Import `complete_database.sql` via phpMyAdmin

---

## ✅ 3. File Structure Review

### Required Directories:
```
/public_html/
├── .htaccess ✓
├── index.php ✓ (redirects to src/)
├── robots.txt ✓
├── admin/ ✓
├── api/ ✓
├── assets/ ✓
│   ├── css/
│   ├── images/
│   ├── js/
│   └── vendor/
├── creator/ ✓
├── docs/ ✓
├── firebase-credentials/ ✓
├── player/ ✓
├── scripts/ ✓
├── src/ ✓
├── sql/ ✓
└── uploads/ ✓
```

### Files to EXCLUDE from upload:
- ❌ `phpinfo.php`
- ❌ `config_template.php` (keep local reference only)
- ❌ `health_check.php` (upload, verify, then delete)
- ❌ `*.md` files (documentation, keep local)
- ❌ `SKYNOXX/` folder (Android app source)
- ❌ All `test_*.php` and `debug_*.php` files

---

## ✅ 4. Asset Path Verification

- [x] All asset paths converted to root-relative (`/assets/...`)
- [x] No Windows paths (`/Free%20fire%201/...`) in code
- [x] Video/image paths use `BASE_URL` constant

### Quick Grep Check:
```bash
# Should return NO results:
grep -r "Free fire 1" --include="*.php" .
grep -r "xampp" --include="*.php" .
```

---

## ✅ 5. Razorpay Configuration

**Current Status**: Test Mode

### Before Going Live:
1. Log in to [Razorpay Dashboard](https://dashboard.razorpay.com)
2. Navigate to **Settings** → **API Keys**
3. Generate **Live Mode** keys
4. Update in `src/config.php`:
   ```php
   define('RAZORPAY_KEY_ID', 'rzp_live_XXXXXXXXXX');
   define('RAZORPAY_KEY_SECRET', 'LIVE_SECRET_KEY');
   ```
5. Update in `creator/wallet_deposit.php` and `player/wallet_deposit.php`

⚠️ **Do NOT switch to live mode until SSL is installed and site is fully tested!**

---

## ✅ 6. SSL Certificate

### After Upload:
1. Go to Hostinger **Hosting** → **SSL**
2. Enable **Free Let's Encrypt SSL**
3. Wait 10-15 minutes for propagation
4. Verify HTTPS: `https://skynoxx.live`

### Test:
- Open browser: `http://skynoxx.live` → should redirect to `https://skynoxx.live`
- Check padlock icon in browser address bar

---

## ✅ 7. File Permissions

Set via Hostinger File Manager or FTP:

### Directories:
- `755` (drwxr-xr-x) for all folders

### Files:
- `644` (-rw-r--r--) for `.php`, `.html`, `.css`, `.js`
- `600` (-rw-------) for `firebase-credentials/*.json`
- `644` for `.htaccess`

### Writable Directories:
- `775` for `uploads/` and subdirectories

---

## ✅ 8. Environment Variables

- [x] `BASE_URL` set to `https://skynoxx.live/`
- [x] Database credentials for production
- [x] Error logging enabled, display_errors disabled (needs manual fix)

---

## 📋 Upload Methods

### Method 1: File Manager (Recommended for Hostinger)
1. Log in to [Hostinger Control Panel](https://hpanel.hostinger.com)
2. Go to **Files** → **File Manager**
3. Navigate to `public_html/`
4. Delete default files (index.html, etc.)
5. Upload entire project structure
6. Extract if uploaded as ZIP

### Method 2: FTP (For large uploads)
1. Get FTP credentials from Hostinger panel
2. Use FileZilla or WinSCP
3. Connect to: `ftp.skynoxx.live`
4. Upload to `/public_html/`

---

## 🧪 Post-Deployment Testing

### Step 1: Health Check
1. Upload `health_check.php` to root
2. Visit: `https://skynoxx.live/health_check.php`
3. Verify all checks pass (green)
4. **DELETE** `health_check.php` immediately after verification

### Step 2: Test Core Functions
- [ ] Homepage loads: `https://skynoxx.live`
- [ ] Tournament listing shows with correct match types
- [ ] Filter buttons work (Solo/Duo/Squad/Clash Squad)
- [ ] Admin login: `https://skynoxx.live/admin/admin_dashboard.php`
  - Email: `skynoxx@2005`
  - Password: `opjs`

### Step 3: Test User Flows
- [ ] Player registration
- [ ] Creator registration
- [ ] Tournament creation (creator)
- [ ] Tournament registration (player)
- [ ] Wallet deposit (TEST MODE with Razorpay)

### Step 4: Security Verification
- [ ] Test directory listing: `https://skynoxx.live/admin/` → should show 403 Forbidden
- [ ] Test file access: `https://skynoxx.live/src/config.php` → should show 403 or no output
- [ ] View page source → no PHP errors or warnings visible

---

## 🔧 Troubleshooting

### 500 Internal Server Error
- Check `.htaccess` syntax
- Verify PHP version (8.0+) in Hostinger panel
- Check error logs in Hostinger File Manager → `public_html/error_log`

### Database Connection Failed
- Verify credentials in `src/db.php`:
  - Host: `localhost` (not IP)
  - User: `u93857826_skynoxx`
  - Pass: `Skysanjiv`
  - DB: `u93857826_ff`
- Check database exists in phpMyAdmin

### Blank White Page
- Enable error display temporarily:
  ```php
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```
- Check error_log file
- Verify file paths are correct (no Windows paths)

### Assets Not Loading (CSS/JS/Images)
- Check browser console (F12)
- Verify asset paths start with `/assets/`
- Check file permissions (644)

---

## 📞 Support

**Hostinger Support**: Available 24/7 via chat in hPanel

**Project Issues**: Check `HOSTINGER_DEPLOYMENT_COMPLETE.md` for detailed guide

---

## ✨ Final Steps Before Upload

1. **Run this command to verify no test files remain:**
   ```powershell
   Get-ChildItem -Recurse -Filter "*test*.php" | Select-Object FullName
   Get-ChildItem -Recurse -Filter "*debug*.php" | Select-Object FullName
   ```

2. **Remove error_reporting from production files** (see Section 1)

3. **Create a local backup:**
   ```powershell
   Compress-Archive -Path "c:\xampp\htdocs\Free fire 1\free-fire-tournament-platform" -DestinationPath "c:\xampp\htdocs\skynoxx-backup-$(Get-Date -Format 'yyyy-MM-dd').zip"
   ```

4. **Upload to Hostinger**

5. **Import database**

6. **Test with health_check.php**

7. **Delete health_check.php**

8. **Test all major features**

9. **Enable SSL certificate**

10. **Switch Razorpay to Live Mode** (after thorough testing)

---

**You're ready to deploy! 🎉**
