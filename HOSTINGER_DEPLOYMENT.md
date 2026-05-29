# 🚀 Hostinger Deployment Guide for skynoxx.live

## ✅ Database Configuration Complete!

Your config files have been updated with automatic environment detection:
- **Local (XAMPP)**: Uses `root` with no password
- **Production (Hostinger)**: Uses your Hostinger credentials

---

## 📋 Step-by-Step Deployment

### **Step 1: Get Your Database Password**

1. Go to Hostinger hPanel: https://hpanel.hostinger.com
2. Click on **skynoxx.live** → **Databases** → **MySQL Databases**
3. Click the **eye icon** 👁️ next to the password to reveal it
4. **Copy the password**

---

### **Step 2: Update Password in Config Files**

You need to replace `YOUR_DATABASE_PASSWORD_HERE` in these files:

**File 1: `src/config.php`**
- Line 20: Replace with your actual database password

**File 2: `src/db.php`**
- Line 15: Replace with your actual database password

**How to update:**
1. Open each file in VS Code
2. Press `Ctrl + F` and search for: `YOUR_DATABASE_PASSWORD_HERE`
3. Replace with your actual Hostinger database password
4. Save both files

---

### **Step 3: Import Your Database**

**Option A: Using phpMyAdmin (Recommended)**

1. **Go to Hostinger hPanel** → Databases → phpMyAdmin
2. Click on database: `u93857826_ff`
3. Click **"Import"** tab at top
4. Click **"Choose File"**
5. Select: `complete_database.sql` from your project
6. Scroll down and click **"Go"**
7. Wait for success message ✅

**Option B: Using MySQL Import (Advanced)**

```bash
# If you have MySQL command line access
mysql -h localhost -u u93857826_skynoxx -p u93857826_ff < complete_database.sql
```

---

### **Step 4: Upload Files to Hostinger**

**Using File Manager (Easy):**

1. **Go to hPanel** → Files → **File Manager**
2. Navigate to: `public_html`
3. **Delete** default `index.html` if present
4. Click **Upload** button (top right)
5. Upload ALL files from your project folder:
   ```
   ✅ src/
   ✅ player/
   ✅ creator/
   ✅ admin/
   ✅ api/
   ✅ assets/
   ✅ uploads/
   ✅ firebase-credentials/
   ✅ index.php
   ```
6. Make sure folder structure looks like:
   ```
   public_html/
   ├── src/
   ├── player/
   ├── creator/
   ├── admin/
   ├── api/
   ├── assets/
   ├── index.php
   └── ... (other files)
   ```

**Using FTP (FileZilla - Faster for large projects):**

1. **Get FTP credentials:**
   - hPanel → Files → **FTP Accounts**
   - Note: Hostname, Username, Password, Port (21)

2. **Connect with FileZilla:**
   - Host: `ftp.skynoxx.live` (or IP from hPanel)
   - Username: From FTP Accounts
   - Password: From FTP Accounts
   - Port: 21
   - Click **Quickconnect**

3. **Upload files:**
   - Navigate to `public_html` on remote side (right panel)
   - Drag ALL project folders/files from left panel to right panel
   - Wait for upload to complete

---

### **Step 5: Set Up Firebase for Push Notifications**

1. **Upload Firebase credentials:**
   - Make sure `firebase-credentials/` folder is uploaded
   - Check file permissions are set to `644`

2. **Update Firebase config** if needed:
   - Path: `assets/js/fcm-handler.js`
   - Update API paths if different

---

### **Step 6: Enable SSL (HTTPS)**

1. **Go to hPanel** → Advanced → **SSL**
2. Click **"Install"** next to `skynoxx.live`
3. Wait 10-15 minutes for activation ⏳
4. Enable **"Force HTTPS"** toggle
5. All traffic will redirect to `https://`

---

### **Step 7: Test Your Website**

**Test these URLs:**

✅ **Homepage:**
```
https://skynoxx.live/
https://skynoxx.live/src/index.php
```

✅ **Login/Signup:**
```
https://skynoxx.live/src/login.php
https://skynoxx.live/src/signup.php
```

✅ **Player Dashboard:**
```
https://skynoxx.live/player/player_dashboard.php
```

✅ **Creator Dashboard:**
```
https://skynoxx.live/creator/creator_dashboard.php
```

✅ **Admin Panel:**
```
https://skynoxx.live/admin/admin_dashboard.php
```

✅ **Database Test:**
```
https://skynoxx.live/src/test_db.php
```
(Should show "Connected successfully" or database info)

---

## 🔧 File Permissions (Important!)

Set correct permissions in Hostinger File Manager:

| Path | Permission | Purpose |
|------|-----------|---------|
| `uploads/` | 755 | User uploads (profile pics, etc.) |
| `firebase-credentials/` | 644 | JSON credentials file |
| All `.php` files | 644 | PHP scripts |
| All folders | 755 | Directories |

**To change permissions:**
1. Right-click file/folder in File Manager
2. Click **"Permissions"**
3. Set appropriate value

---

## 📊 Your Database Details

**Copy these for reference:**

```
Database Host: localhost
Database Name: u93857826_ff
Database User: u93857826_skynoxx
Database Password: [Get from Hostinger hPanel]
Database Port: 3306
```

**Connection String Example:**
```php
$conn = new mysqli('localhost', 'u93857826_skynoxx', 'YOUR_PASSWORD', 'u93857826_ff', 3306);
```

---

## 🎯 Razorpay Webhook Setup (After SSL)

Once SSL is active, update your Razorpay webhooks:

1. **Go to Razorpay Dashboard:** https://dashboard.razorpay.com/
2. **Settings → Webhooks**
3. Add webhook URL:
   ```
   https://skynoxx.live/player/razorpay_webhook.php
   ```
4. Select events:
   - ✅ payment.captured
   - ✅ payment.failed
   - ✅ refund.created
5. Copy webhook secret and update:
   - File: `src/razorpay_config.php`
   - Line: `RAZORPAY_WEBHOOK_SECRET`

---

## 🐛 Troubleshooting

### **"Database connection failed"**
- ✅ Check password is correct in `config.php` and `db.php`
- ✅ Verify database name: `u93857826_ff`
- ✅ Check phpMyAdmin - database exists and has tables

### **"Page not found" / 404 errors**
- ✅ Files uploaded to `public_html` root (not subfolder)
- ✅ Check `.htaccess` file exists
- ✅ Verify file names match exactly (case-sensitive on Linux)

### **"Internal Server Error" / 500**
- ✅ Check PHP version in hPanel (needs PHP 7.4+)
- ✅ Verify file permissions (644 for files, 755 for folders)
- ✅ Check error logs: hPanel → Advanced → Error Logs

### **Images/CSS not loading**
- ✅ Check `assets/` folder uploaded completely
- ✅ Verify paths use relative URLs (not absolute localhost paths)
- ✅ Clear browser cache (Ctrl + Shift + Delete)

### **Payments not working**
- ✅ Wait for SSL activation (HTTPS required for Razorpay)
- ✅ Update `BASE_URL` in config to `https://skynoxx.live/`
- ✅ Check Razorpay keys are test keys for testing

---

## 📱 Mobile App Connection

Update your Android app's WebView URL:

**File:** `SKYNOXX/app/src/main/java/com/kushwaha/webviewapp/MainActivity.java`

**Line 147:** Change to:
```java
String url = "https://skynoxx.live/src/login.php";
```

Then rebuild APK.

---

## ✅ Deployment Checklist

Before going live:

- [ ] Database password updated in both config files
- [ ] Complete database imported successfully
- [ ] All files uploaded to `public_html`
- [ ] File permissions set correctly
- [ ] SSL certificate installed and active
- [ ] Test login/signup works
- [ ] Test player dashboard loads
- [ ] Test tournament creation works
- [ ] Test wallet deposit (with test Razorpay keys)
- [ ] Firebase push notifications configured
- [ ] Update Razorpay webhook URL
- [ ] Test on mobile device
- [ ] Check error logs are empty

---

## 🆘 Need Help?

**Hostinger Support:**
- Live Chat: hPanel → Help icon
- Knowledge Base: https://support.hostinger.com

**Quick Commands:**

**Check if site is live:**
```bash
ping skynoxx.live
```

**Test SSL:**
```
https://www.ssllabs.com/ssltest/analyze.html?d=skynoxx.live
```

**Check DNS propagation:**
```
https://www.whatsmydns.net/#A/skynoxx.live
```

---

**🎉 Your site will be live at: https://skynoxx.live**

After completing all steps, your tournament platform will be fully operational on Hostinger!
