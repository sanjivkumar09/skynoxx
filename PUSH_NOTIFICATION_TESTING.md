# Push Notification Testing Guide

## What We Just Set Up

1. **Firebase Service Account** - Placed in `firebase-credentials/` folder
2. **FCM Notification Service** - PHP class to send push notifications via Firebase API v1
3. **Save FCM Token API** - Endpoint to save device tokens from WebView
4. **FCM Handler JS** - Automatically gets token from Android and sends to backend
5. **Integration** - Tournament creation now sends push notifications to all players

## Next Steps

### 1. Add FCM Token Columns to Database

Open phpMyAdmin at http://localhost/phpmyadmin/ and run this SQL:

```sql
USE tournament_platform;

ALTER TABLE users 
ADD COLUMN fcm_token VARCHAR(255) NULL,
ADD COLUMN fcm_token_updated_at DATETIME NULL;
```

Or import the file: `sql/add_fcm_token_columns.sql`

### 2. Rebuild Android App

In Android Studio:
1. Open the SKYNOXX project
2. Click **File → Sync Project with Gradle Files**
3. Wait for sync to complete (check bottom status bar)
4. Click **Build → Clean Project**
5. Click **Build → Rebuild Project** (or just click Run ▶)
6. Connect your Pixel 9 Pro XL via USB
7. Click Run to install on device

**Check Logcat for:**
- `FCM Token: AAAA...` (confirms token generation)
- `FCM token saved to backend successfully` (confirms JS communication)

### 3. Test End-to-End

**On Phone (WebView App):**
1. Open the SKYNOXX app
2. Login as a player
3. Check Chrome DevTools (USB debugging):
   - Open `chrome://inspect` on PC
   - Click "Inspect" on your device
   - Check Console for: "FCM token saved to backend successfully"

**On PC (XAMPP):**
1. Login as creator at: http://localhost/Free%20fire%201/free-fire-tournament-platform/creator/creator_dashboard.php
2. Click "Create Tournament"
3. Fill in tournament details
4. Click "Create Tournament"
5. Check XAMPP error log: `C:\xampp\apache\logs\error.log`
   - Look for: "Tournament created - Push notifications sent"

**On Phone:**
- **Notification should appear in notification tray** within seconds
- Tap notification → App should open
- Check in-app bell icon → Should also show notification

### 4. Verify in Database

Open phpMyAdmin and run:

```sql
-- Check if FCM tokens are being saved
SELECT user_id, username, role, 
       LEFT(fcm_token, 30) as token_preview, 
       fcm_token_updated_at 
FROM users 
WHERE fcm_token IS NOT NULL;

-- Check notifications created
SELECT * FROM notifications 
ORDER BY created_at DESC 
LIMIT 10;
```

## Troubleshooting

### No Push Notification on Phone

1. **Check Android Permissions:**
   - Settings → Apps → SKYNOXX → Notifications → Allowed

2. **Check FCM Token in Logcat:**
   ```
   FCM Token: AAAA...
   ```
   If missing, Firebase setup issue

3. **Check WebView Console:**
   - Should see "FCM token saved to backend successfully"
   - If not, check network tab for API errors

4. **Check XAMPP Error Log:**
   ```
   C:\xampp\apache\logs\error.log
   ```
   Look for FCM errors

### Black Screen in WebView

Already fixed with:
- Hardware rendering
- White backgrounds
- Visible progress bar

### "Not authenticated" Error

FCM token API requires login. Test with:
```bash
curl -X POST http://172.16.217.80/Free%20fire%201/free-fire-tournament-platform/api/save_fcm_token.php \
  -H "Content-Type: application/json" \
  -d '{"fcm_token":"test_token"}' \
  --cookie "PHPSESSID=your_session_id"
```

## Architecture Overview

```
┌─────────────────┐
│  Android App    │
│  (WebView)      │
└────────┬────────┘
         │
         │ 1. Get FCM Token (JavaScript Interface)
         ↓
┌─────────────────┐
│  fcm-handler.js │
│  (WebView)      │
└────────┬────────┘
         │
         │ 2. POST /api/save_fcm_token.php
         ↓
┌─────────────────┐
│  PHP Backend    │
│  (XAMPP)        │
└────────┬────────┘
         │
         │ 3. Store in users.fcm_token
         ↓
┌─────────────────┐
│  MySQL DB       │
└─────────────────┘

When tournament is created:
┌─────────────────┐
│  Creator        │
│  create_tournament.php │
└────────┬────────┘
         │
         │ 1. Insert tournament
         │ 2. Insert notification
         │ 3. Call FCMNotificationService
         ↓
┌─────────────────┐
│  fcm_notification_service.php │
└────────┬────────┘
         │
         │ 1. Get OAuth token (Service Account)
         │ 2. Get player FCM tokens from DB
         │ 3. Send to Firebase API v1
         ↓
┌─────────────────┐
│  Firebase       │
│  Cloud Messaging│
└────────┬────────┘
         │
         │ Push to devices
         ↓
┌─────────────────┐
│  Player Phones  │
│  (Notification) │
└─────────────────┘
```

## Files Created/Modified

### New Files:
- `firebase-credentials/skynoxx-23f26-firebase-adminsdk-fbsvc-fcce53129d.json`
- `src/fcm_notification_service.php`
- `api/save_fcm_token.php`
- `assets/js/fcm-handler.js`
- `sql/add_fcm_token_columns.sql`
- `PUSH_NOTIFICATION_TESTING.md`

### Modified Files:
- `src/includes/header.php` - Added fcm-handler.js script
- `creator/create_tournament.php` - Added push notification on tournament creation

## Security Notes

1. **Service Account JSON** - Keep secure, don't commit to Git
2. **FCM Tokens** - Stored securely in database
3. **OAuth Tokens** - Generated per request, expire in 1 hour
4. **API Endpoint** - Requires user session authentication

## Performance

- **Single notification**: ~500ms (includes OAuth token generation)
- **100 players**: ~10 seconds (with 0.1s delay between sends)
- **Batch optimization**: Can be improved with multicast messages in future

## Future Enhancements

1. **Multicast Messages** - Send to multiple tokens in one API call
2. **Topic Subscriptions** - Subscribe players to tournament topics
3. **Silent Notifications** - Data-only messages for in-app updates
4. **Notification Icons** - Custom icons per notification type
5. **Rich Notifications** - Images, action buttons
6. **Analytics** - Track delivery rates and click rates
