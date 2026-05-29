# Enhanced Features Setup Guide

This guide will help you set up the new **Live Tournament Status** and **Enhanced Notifications** features.

## Features Included

### 1. Live Tournament Status Updates
- Real-time participant count tracking
- Tournament capacity management (max players)
- Countdown timer for tournament start
- Auto-start when capacity is reached
- Automated status transitions (upcoming → ongoing → completed)

### 2. Enhanced Notification System
- **Tournament Starting Soon** - 1 hour before start
- **Tournament Results** - When completed
- **Prize Money Credited** - When you win
- **Withdrawal Status** - Approved/Rejected notifications
- **Low Balance Alert** - When balance < ₹50
- **Email & Push Notifications** support

## Installation Steps

### Step 1: Run Database Migration

Execute the SQL file to create new tables and add required fields:

```bash
# Using MySQL command line
mysql -u your_username -p your_database < sql/enhanced_features_migration.sql

# Or using phpMyAdmin
# 1. Open phpMyAdmin
# 2. Select your database
# 3. Click "Import"
# 4. Choose file: sql/enhanced_features_migration.sql
# 5. Click "Go"
```

**What this does:**
- Adds `max_participants`, `current_participants`, `auto_start`, `reminder_sent` to tournaments table
- Creates `tournament_status_log` table for tracking status changes
- Expands notification types (tournament_starting_soon, prize_credited, withdrawal_approved, etc.)
- Creates `notification_preferences` table for user settings
- Creates `push_notification_tokens` table for mobile app support
- Creates `tournament_results` table for storing match results

### Step 2: Test the Migration

Verify the tables were created successfully:

```sql
-- Check if new columns exist
DESCRIBE tournaments;

-- Check new tables
SHOW TABLES LIKE '%notification%';
SHOW TABLES LIKE 'tournament_%';
```

### Step 3: Set Up Automated Tasks (Cron Jobs)

The `scripts/cron_jobs.php` file needs to run every 5-10 minutes to:
- Send 1-hour tournament reminders
- Auto-start tournaments
- Send low balance alerts
- Clean up old notifications

#### Windows (Task Scheduler):

1. Open Task Scheduler
2. Create New Task
3. **General Tab:**
   - Name: "Free Fire Tournament Cron Jobs"
   - Run whether user is logged on or not
4. **Triggers Tab:**
   - New → Repeat every 10 minutes
5. **Actions Tab:**
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `"C:\xampp\htdocs\Free fire 1\free-fire-tournament-platform\scripts\cron_jobs.php"`
6. Click OK and test by right-clicking → Run

#### Linux (Crontab):

```bash
# Edit crontab
crontab -e

# Add this line (runs every 10 minutes)
*/10 * * * * /usr/bin/php /path/to/free-fire-tournament-platform/scripts/cron_jobs.php >> /var/log/tournament-cron.log 2>&1
```

#### Manual Testing:

```bash
# Test the cron job manually
php scripts/cron_jobs.php

# Or via browser (add secret key for security)
http://localhost/Free%20fire%201/free-fire-tournament-platform/scripts/cron_jobs.php?secret_key=your_secret
```

### Step 4: Update Existing Pages

#### Add Live Status to Tournament Pages:

In any page showing tournament details (join_tournament.php, view_tournament.php, etc.), add:

```html
<!-- In the <head> section -->
<script src="../assets/js/tournament-status.js"></script>

<!-- In the tournament details section -->
<div id="tournament-status-badge"></div>
<div id="participant-count"></div>
<div class="progress">
    <div id="participant-progress" class="progress-bar" role="progressbar"></div>
</div>
<div id="tournament-countdown"></div>

<!-- At the end of <body> -->
<script>
const statusTracker = new TournamentStatusTracker(<?= $tournament_id ?>, {
    showCountdown: true,
    showParticipants: true,
    onStatusChange: (newStatus, oldStatus) => {
        console.log(`Tournament status changed from ${oldStatus} to ${newStatus}`);
        // Reload page or show alert
        if (newStatus === 'ongoing') {
            alert('Tournament has started!');
        }
    },
    onCapacityFull: (tournament) => {
        alert('Tournament is now full!');
        // Disable join button
        document.getElementById('joinButton')?.setAttribute('disabled', 'true');
    }
});
</script>
```

#### Add Notification Settings Link:

In player dashboard or profile menu, add:

```php
<a href="notification_settings.php" class="list-group-item">
    <i class="fas fa-bell me-2"></i>Notification Settings
</a>
```

### Step 5: Test Notifications

#### Test Low Balance Alert:

```php
// In player dashboard or any page
require_once '../src/NotificationManager.php';
$nm = new NotificationManager($conn);
$nm->notifyLowBalance($_SESSION['user_id'], 25.00); // Test with low amount
```

#### Test Tournament Reminder:

1. Create a tournament with date/time set to 1 hour from now
2. Register for it as a player
3. Run cron job: `php scripts/cron_jobs.php`
4. Check notifications bell - should show new notification

#### Test Withdrawal Notifications:

1. Request a withdrawal as creator/player
2. As admin, approve or reject it
3. Check notifications - user should receive notification

### Step 6: Enable Tournament Capacity (Optional)

When creating tournaments, set max_participants:

```php
// In creator/create_tournament.php
// Add input field:
<input type="number" name="max_participants" 
       min="2" max="100" placeholder="e.g., 50">

// In the INSERT query, add max_participants field
```

### Step 7: Configure Push Notifications (Mobile App)

For WebView mobile apps, add this JavaScript:

```javascript
// When device token is available (from FCM/Firebase)
async function registerPushToken(token, deviceType = 'android') {
    await fetch('../api/register_push_token.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            token: token,
            device_type: deviceType,
            device_info: navigator.userAgent
        })
    });
}
```

Create `api/register_push_token.php`:

```php
<?php
session_start();
require_once '../src/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
$token = $data['token'] ?? '';
$device_type = $data['device_type'] ?? 'web';
$device_info = $data['device_info'] ?? '';

$stmt = $conn->prepare("
    INSERT INTO push_notification_tokens (user_id, token, device_type, device_info)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
    device_type = VALUES(device_type),
    device_info = VALUES(device_info),
    is_active = 1,
    last_used_at = CURRENT_TIMESTAMP
");
$stmt->bind_param('isss', $user_id, $token, $device_type, $device_info);
$stmt->execute();
echo json_encode(['success' => true]);
```

## Usage Examples

### Send Custom Notification:

```php
require_once 'src/NotificationManager.php';
$nm = new NotificationManager($conn);

// To all players
$nm->sendNotification(
    'system_announcement',
    '🎉 Special Event!',
    'Double prize pool on all tournaments this weekend!',
    'players'
);

// To specific user
$nm->sendNotification(
    'payment_received',
    'Payment Received',
    'Your deposit of ₹500 has been confirmed.',
    $user_id,
    ['send_email' => true]
);
```

### Update Tournament Status Manually:

```php
require_once 'src/TournamentStatusManager.php';
$tsm = new TournamentStatusManager($conn);

// Start a tournament
$tsm->updateStatus($tournament_id, 'ongoing', $_SESSION['user_id'], 'Started by admin');

// Cancel with refunds
$tsm->updateStatus($tournament_id, 'cancelled', $_SESSION['user_id'], 'Server issues');
```

### Submit Tournament Results:

```php
$results = [
    ['player_id' => 5, 'position' => 1, 'prize_amount' => 5000, 'kills' => 15, 'points' => 85],
    ['player_id' => 8, 'position' => 2, 'prize_amount' => 3000, 'kills' => 12, 'points' => 72],
    ['player_id' => 12, 'position' => 3, 'prize_amount' => 2000, 'kills' => 10, 'points' => 65],
];

$tsm->submitResults($tournament_id, $results);
// This will:
// - Save results to database
// - Credit prize money to winners
// - Send notifications to all winners
// - Mark tournament as completed
```

## Troubleshooting

### Notifications Not Showing:

1. Check if migration ran successfully: `SELECT * FROM notification_preferences LIMIT 1;`
2. Verify user preferences are enabled: `SELECT * FROM notification_preferences WHERE user_id = ?`
3. Check browser console for JavaScript errors
4. Verify API endpoint works: Visit `api/api_notifications.php?action=summary`

### Cron Jobs Not Running:

1. Test manually: `php scripts/cron_jobs.php`
2. Check PHP path: `where php` (Windows) or `which php` (Linux)
3. Verify file permissions (Linux): `chmod +x scripts/cron_jobs.php`
4. Check logs: Look for errors in error_log

### Tournament Status Not Updating:

1. Verify TournamentStatusManager.php has no syntax errors
2. Check if API endpoint works: Visit `api/tournament_status.php?id=1`
3. Open browser console and look for errors in tournament-status.js
4. Verify JavaScript is included: View page source and search for "tournament-status.js"

## Next Steps

1. **Test all features** thoroughly before going live
2. **Customize notification messages** to match your brand
3. **Set up email SMTP** for actual email sending (currently logs only)
4. **Integrate Firebase Cloud Messaging** for real push notifications
5. **Add admin panel** to manually send announcements
6. **Create notification history page** for users to view old notifications

## Support

If you encounter issues:
1. Check error logs: `tail -f /path/to/error.log`
2. Enable debug mode in PHP: `ini_set('display_errors', 1);`
3. Test each component individually
4. Verify database migrations completed successfully

Enjoy your enhanced tournament platform! 🎮🏆
