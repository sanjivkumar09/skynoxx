<?php
// Cron Jobs Script
// Run this script every 5-10 minutes via cron or task scheduler
//
// Windows Task Scheduler:
//   C:\xampp\php\php.exe "C:\xampp\htdocs\Free fire 1\free-fire-tournament-platform\scripts\cron_jobs.php"
// Linux Cron (every 5 minutes):
//   */5 * * * * /usr/bin/php /path/to/cron_jobs.php

// Prevent direct browser access (optional security)
if (php_sapi_name() !== 'cli' && !isset($_GET['secret_key'])) {
    die('Access denied');
}

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/TournamentStatusManager.php';
require_once __DIR__ . '/../src/NotificationManager.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting cron jobs...\n";

$tournamentManager = new TournamentStatusManager($conn);
$notificationManager = new NotificationManager($conn);

// 1. Send tournament reminders (1 hour before start)
echo "Checking for upcoming tournament reminders...\n";
$reminders_sent = $tournamentManager->sendUpcomingReminders();
echo "Sent $reminders_sent tournament reminders.\n";

// 2. Auto-update tournament statuses (start tournaments whose time has come)
echo "Auto-updating tournament statuses...\n";
$statuses_updated = $tournamentManager->autoUpdateStatuses();
echo "Updated $statuses_updated tournament statuses.\n";

// 3. Check for low balance users
echo "Checking for low balance alerts...\n";
$low_balance_stmt = $conn->prepare("
    SELECT id, wallet_balance 
    FROM users 
    WHERE role IN ('player', 'creator') 
    AND wallet_balance < 50 
    AND wallet_balance > 0
");
$low_balance_stmt->execute();
$low_balance_users = $low_balance_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$low_balance_stmt->close();

$low_balance_alerts = 0;
foreach ($low_balance_users as $user) {
    if ($notificationManager->notifyLowBalance($user['id'], $user['wallet_balance'])) {
        $low_balance_alerts++;
    }
}
echo "Sent $low_balance_alerts low balance alerts.\n";

// 4. Clean up old notification reads (optional - keep last 30 days)
echo "Cleaning up old notification data...\n";
$cleanup = $conn->query("
    DELETE FROM notifications 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$deleted = $conn->affected_rows;
echo "Cleaned up $deleted old notifications.\n";

// 5. Update participant counts for all active tournaments
echo "Updating participant counts...\n";
$active_tournaments = $conn->query("
    SELECT id FROM tournaments WHERE status IN ('upcoming', 'ongoing')
");
$count_updates = 0;
while ($row = $active_tournaments->fetch_assoc()) {
    if ($tournamentManager->updateParticipantCount($row['id'])) {
        $count_updates++;
    }
}
echo "Updated $count_updates participant counts.\n";

echo "[" . date('Y-m-d H:i:s') . "] Cron jobs completed!\n";
echo str_repeat("-", 50) . "\n";
