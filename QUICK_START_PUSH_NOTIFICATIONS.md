# 🚀 Quick Start Guide - Push Notifications

## You're Ready to Test! Here's What to Do:

### Step 1: Add Database Columns (2 minutes)
1. Open http://localhost/phpmyadmin/
2. Click on `tournament_platform` database
3. Click "SQL" tab
4. Paste this and click "Go":
```sql
ALTER TABLE users 
ADD COLUMN fcm_token VARCHAR(255) NULL,
ADD COLUMN fcm_token_updated_at DATETIME NULL;
```

### Step 2: Rebuild Android App (5 minutes)
1. Open Android Studio
2. Open the SKYNOXX project folder
3. Wait for Gradle sync (bottom right corner)
4. Click **Build → Clean Project**
5. Click **Build → Rebuild Project**
6. Connect your Pixel 9 Pro XL via USB
7. Click the green **Run** button ▶

### Step 3: Test on Phone (3 minutes)
1. Open the SKYNOXX app on your phone
2. Login as a player
3. Keep the app open for 5 seconds (let FCM token initialize)

### Step 4: Verify Token Saved (1 minute)
Open in phone's Chrome browser:
```
http://172.16.217.80/Free fire 1/free-fire-tournament-platform/test_fcm.html
```

You should see:
- ✓ Android WebView detected
- ✓ FCM Token received
- ✓ Token saved to backend successfully

### Step 5: Send Test Notification (30 seconds)
On the same test page (test_fcm.html):
1. Scroll to "Send Test Notification"
2. Click "Send Test Push" button
3. **Check your phone's notification tray** - you should get a notification!

### Step 6: Test Real Tournament Creation (2 minutes)
1. **On PC:** Login as creator
2. Go to: http://localhost/Free%20fire%201/free-fire-tournament-platform/creator/create_tournament.php
3. Create a new tournament (fill all fields)
4. Click "Create Tournament"
5. **On Phone:** You should receive a push notification: "🎮 New Tournament!"

## ✅ Success Checklist

- [ ] Database columns added (Step 1)
- [ ] Android app rebuilt (Step 2)
- [ ] FCM token appears in test_fcm.html (Step 4)
- [ ] Test notification received on phone (Step 5)
- [ ] Tournament creation notification received (Step 6)

## 🔍 Quick Checks

### Check if Token is Saved in Database
Open phpMyAdmin → SQL tab:
```sql
SELECT user_id, username, role, 
       LEFT(fcm_token, 30) as token_preview 
FROM users 
WHERE fcm_token IS NOT NULL;
```

### Check XAMPP Error Log
File: `C:\xampp\apache\logs\error.log`
Look for:
- "FCM token saved for user ID: X"
- "Tournament created - Push notifications sent"

### Check Android Logcat (in Android Studio)
Filter: "FCM"
Look for:
- "FCM Token: AAAA..."
- "onNewToken: ..."

## 🐛 Common Issues

### Issue 1: "FCM token column does not exist"
**Fix:** Run Step 1 again - you missed the database migration

### Issue 2: No notification on phone
**Fix:** 
- Check Settings → Apps → SKYNOXX → Notifications → Allow
- Rebuild app (Step 2)
- Check Logcat for FCM token

### Issue 3: "Not authenticated" error in test_fcm.html
**Fix:** Login first in the WebView app, then open test_fcm.html

### Issue 4: "Failed to get access token"
**Fix:** Check that `firebase-credentials/skynoxx-23f26-firebase-adminsdk-fbsvc-fcce53129d.json` exists

## 📱 What Happens When You Create a Tournament?

1. **Creator** clicks "Create Tournament"
2. **Backend** saves tournament to database
3. **Backend** gets all player FCM tokens from database
4. **Backend** gets OAuth token from Firebase Service Account
5. **Backend** sends push notification to Firebase API
6. **Firebase** delivers notification to all player devices
7. **Players** see notification in notification tray
8. **Players** tap notification → App opens

## 🎯 Test URLs

- **Test Page:** http://172.16.217.80/Free%20fire%201/free-fire-tournament-platform/test_fcm.html
- **Creator Dashboard:** http://localhost/Free%20fire%201/free-fire-tournament-platform/creator/creator_dashboard.php
- **Player Dashboard:** http://172.16.217.80/Free%20fire%201/free-fire-tournament-platform/player/player_dashboard.php
- **phpMyAdmin:** http://localhost/phpmyadmin/

## 📝 Files You Can Check

### Backend:
- `src/fcm_notification_service.php` - Push notification service
- `api/save_fcm_token.php` - API to save FCM tokens
- `creator/create_tournament.php` - Line ~230 (sends push notification)

### Frontend:
- `assets/js/fcm-handler.js` - Gets token from Android
- `test_fcm.html` - Test page with diagnostics

### Android:
- `SKYNOXX/app/src/main/java/com/kushwaha/webviewapp/FirebaseMessagingService.java`
- `SKYNOXX/app/src/main/java/com/kushwaha/webviewapp/MainActivity.java`

### Firebase:
- `firebase-credentials/skynoxx-23f26-firebase-adminsdk-fbsvc-fcce53129d.json`

## 🎉 That's It!

You now have a complete push notification system:
- ✅ Android app gets FCM token from Firebase
- ✅ WebView sends token to PHP backend
- ✅ Backend stores token in MySQL database
- ✅ Creator creates tournament → Backend sends push notification
- ✅ Firebase delivers to all player phones
- ✅ Players get instant notification

## 🚀 Next Features You Can Add

1. **Withdrawal Approved** - Notify creator when admin approves withdrawal
2. **Tournament Starting** - Remind players 10 minutes before start
3. **Results Posted** - Notify all participants of match results
4. **Prize Distributed** - Notify winner when prize is credited
5. **Low Balance** - Warn creator when wallet balance is low

Let me know if you encounter any issues during testing!
