<?php
session_start();
require_once '../src/db.php';
require_once '../src/NotificationManager.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../src/login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$notificationManager = new NotificationManager($conn);

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $preferences = [
        'tournament_starting_soon' => isset($_POST['tournament_starting_soon']) ? 1 : 0,
        'tournament_results' => isset($_POST['tournament_results']) ? 1 : 0,
        'prize_credited' => isset($_POST['prize_credited']) ? 1 : 0,
        'withdrawal_updates' => isset($_POST['withdrawal_updates']) ? 1 : 0,
        'low_balance_alert' => isset($_POST['low_balance_alert']) ? 1 : 0,
        'payment_updates' => isset($_POST['payment_updates']) ? 1 : 0,
        'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
        'push_notifications' => isset($_POST['push_notifications']) ? 1 : 0
    ];
    
    if ($notificationManager->updatePreferences($user_id, $preferences)) {
        $message = 'Notification preferences updated successfully!';
        $message_type = 'success';
    } else {
        $message = 'Failed to update preferences. Please try again.';
        $message_type = 'danger';
    }
}

// Get current preferences
$prefs = $notificationManager->getUserPreferences($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/gaming-theme.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="settings-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-bell me-2"></i>Notification Settings</h2>
                        <a href="player_dashboard.php" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-arrow-left me-2"></i>Back
                        </a>
                    </div>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-4">
                            <h5 class="text-muted mb-3">Tournament Notifications</h5>
                            
                            <div class="setting-item">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="setting-icon"><i class="fas fa-clock"></i></div>
                                    <div>
                                        <div class="fw-bold">Starting Soon</div>
                                        <small class="text-muted">Get notified 1 hour before tournament starts</small>
                                    </div>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="tournament_starting_soon" 
                                           <?= $prefs['tournament_starting_soon'] ? 'checked' : '' ?>>
                                </div>
                            </div>
                            
                            <div class="setting-item">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="setting-icon"><i class="fas fa-trophy"></i></div>
                                    <div>
                                        <div class="fw-bold">Tournament Results</div>
                                        <small class="text-muted">Notify when tournament is completed</small>
                                    </div>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="tournament_results" 
                                           <?= $prefs['tournament_results'] ? 'checked' : '' ?>>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h5 class="text-muted mb-3">Payment & Wallet</h5>
                            
                            <div class="setting-item">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="setting-icon"><i class="fas fa-gift"></i></div>
                                    <div>
                                        <div class="fw-bold">Prize Credited</div>
                                        <small class="text-muted">Notify when you win and receive prize money</small>
                                    </div>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="prize_credited" 
                                           <?= $prefs['prize_credited'] ? 'checked' : '' ?>>
                                </div>
                            </div>
                            
                            <div class="setting-item">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="setting-icon"><i class="fas fa-money-bill-wave"></i></div>
                                    <div>
                                        <div class="fw-bold">Withdrawal Updates</div>
                                        <small class="text-muted">Get updates on withdrawal requests (approved/rejected)</small>
                                    </div>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="withdrawal_updates" 
                                           <?= $prefs['withdrawal_updates'] ? 'checked' : '' ?>>
                                </div>
                            </div>
                            
                            <div class="setting-item">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="setting-icon"><i class="fas fa-exclamation-triangle"></i></div>
                                    <div>
                                        <div class="fw-bold">Low Balance Alert</div>
                                        <small class="text-muted">Alert when wallet balance is below ₹50</small>
                                    </div>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="low_balance_alert" 
                                           <?= $prefs['low_balance_alert'] ? 'checked' : '' ?>>
                                </div>
                            </div>
                            
                            <div class="setting-item">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="setting-icon"><i class="fas fa-credit-card"></i></div>
                                    <div>
                                        <div class="fw-bold">Payment Updates</div>
                                        <small class="text-muted">Notify on deposits and transactions</small>
                                    </div>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="payment_updates" 
                                           <?= $prefs['payment_updates'] ? 'checked' : '' ?>>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h5 class="text-muted mb-3">Delivery Methods</h5>
                            
                            <div class="setting-item">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="setting-icon"><i class="fas fa-envelope"></i></div>
                                    <div>
                                        <div class="fw-bold">Email Notifications</div>
                                        <small class="text-muted">Receive notifications via email</small>
                                    </div>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="email_notifications" 
                                           <?= $prefs['email_notifications'] ? 'checked' : '' ?>>
                                </div>
                            </div>
                            
                            <div class="setting-item">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="setting-icon"><i class="fas fa-mobile-alt"></i></div>
                                    <div>
                                        <div class="fw-bold">Push Notifications (Mobile App)</div>
                                        <small class="text-muted">Receive push notifications on mobile app</small>
                                    </div>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="push_notifications" 
                                           <?= $prefs['push_notifications'] ? 'checked' : '' ?>>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i>Save Preferences
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
