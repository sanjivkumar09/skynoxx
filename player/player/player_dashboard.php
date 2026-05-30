<?php
session_start();
include '../src/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'player') {
    header('Location: ../../src/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle payment status messages
$payment_message = '';
$payment_type = '';
if (isset($_GET['payment'])) {
    if ($_GET['payment'] === 'success') {
        $payment_message = 'Payment successful! Your registration has been confirmed.';
        $payment_type = 'success';
        if (isset($_GET['txn'])) {
            $payment_message .= ' Transaction ID: ' . htmlspecialchars($_GET['txn']);
        }
    } elseif ($_GET['payment'] === 'failed') {
        $payment_message = 'Payment failed. Please try again or contact support.';
        $payment_type = 'danger';
    }
}

// Flash success after registration (from join page)
$flash_success = '';
if (isset($_SESSION['success_message']) && is_string($_SESSION['success_message'])) {
    $flash_success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_GET['msg']) && $_GET['msg'] === 'already_paid') {
    $payment_message = 'This tournament registration has already been paid.';
    $payment_type = 'info';
}

// Fetch player's joined tournaments (show both pending and paid, including accepted team registrations)
$stmt = $conn->prepare("
    SELECT 
        t.id, 
        t.title, 
        t.date, 
        t.time, 
        t.entry_fee, 
        t.prize_pool,
        t.max_players,
        t.status,
        t.match_type,
        t.room_id,
        t.room_password,
        r.id as registration_id,
        r.joined_at,
        r.payment_status,
        COALESCE(r.slot_no, 0) as slot_no,
        COALESCE(r.prize_won, 0) as prize_won,
        CASE 
            WHEN r.player_id = ? THEN 'captain'
            ELSE 'member'
        END as role
    FROM registrations r
    INNER JOIN tournaments t ON r.tournament_id = t.id
    WHERE r.player_id = ?
    UNION
    SELECT 
        t.id, 
        t.title, 
        t.date, 
        t.time, 
        t.entry_fee, 
        t.prize_pool,
        t.max_players,
        t.status,
        t.match_type,
        t.room_id,
        t.room_password,
        r.id as registration_id,
        r.joined_at,
        r.payment_status,
        COALESCE(r.slot_no, 0) as slot_no,
        COALESCE(r.prize_won, 0) as prize_won,
        tr.role
    FROM team_registrations tr
    INNER JOIN registrations r ON tr.registration_id = r.id
    INNER JOIN tournaments t ON r.tournament_id = t.id
    WHERE tr.user_id = ? AND tr.invitation_status = 'accepted'
    ORDER BY joined_at DESC
");
$stmt->bind_param('iii', $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$joinedTournaments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Extract IDs of joined tournaments
$joinedIds = array_column($joinedTournaments, 'id');

// Fetch player's avatar/profile picture
$profile_pic = null;
$profile_stmt = $conn->prepare("SELECT avatar FROM players_profile WHERE user_id = ? LIMIT 1");
if ($profile_stmt) {
    $profile_stmt->bind_param('i', $user_id);
    $profile_stmt->execute();
    $profile_result = $profile_stmt->get_result();
    if ($profile_row = $profile_result->fetch_assoc()) {
        $profile_pic = $profile_row['avatar'];
    }
    $profile_stmt->close();
}

// Fetch all available tournaments
$allTournamentsQuery = "
    SELECT 
        id, 
        title, 
        date, 
        time, 
        entry_fee, 
        prize_pool, 
        max_players,
        match_type,
        map_name,
        status
    FROM tournaments
    WHERE status IN ('Upcoming', 'Ongoing')
    ORDER BY date ASC, time ASC
    LIMIT 50
";
$allTournamentsResult = $conn->query($allTournamentsQuery);
$allTournaments = $allTournamentsResult->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Free Fire Tournament Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/gaming-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg gaming-navbar">
        <div class="container d-flex justify-content-between align-items-center">
            <a class="navbar-brand" href="../../src/index.php">
                <img src="../../assets/images/logo.svg" alt="SKYNOXX FF Logo" class="brand-logo-img">
            </a>
            <div class="d-flex align-items-center">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="../signup.php" class="btn btn-gaming-outline me-2">Sign Up</a>
                    <a href="../login.php" class="btn btn-gaming">Login</a>
                <?php else:
                    $role = $_SESSION['role'] ?? '';
                    $user_name = $_SESSION['user_name'] ?? '';
                    $initials = '';
                    if ($user_name) {
                        $parts = explode(' ', trim($user_name));
                        $initials = strtoupper(substr($parts[0],0,1) . (isset($parts[1]) ? substr($parts[1],0,1) : ''));
                    }
                    if ($role === 'admin') {
                        $dash = '../admin/admin_dashboard.php';
                    } elseif ($role === 'creator') {
                        $dash = '../creator/creator_dashboard.php';
                    } else {
                        $dash = '../player/player_dashboard.php';
                    }
                ?>
                    <?php if ($role === 'player'): ?>
                    
                    <button id="notifBell" class="notif-btn" aria-label="Notifications">
                        <i class="bi bi-bell"></i>
                        <span id="notifCount" class="notif-count">0</span>
                    </button>
                    <div id="notifMenu" class="notif-menu">
                        <div class="notif-header">
                            <div class="fw-bold">Notifications</div>
                            <button id="notifMarkAll" class="btn btn-sm btn-gaming-outline">Mark all read</button>
                        </div>
                        <div id="notifList" class="notif-list"></div>
                    </div>
                    <?php endif; ?>

                    <div class="position-relative">
                        <button id="profileBtn" class="profile-btn" type="button" aria-haspopup="true" aria-expanded="false">
                            <?php if (!empty($profile_pic) && file_exists('../' . $profile_pic)): ?>
                                <img src="../<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" class="profile-avatar" style="object-fit: cover;">
                            <?php else: ?>
                                <span class="profile-avatar" role="img" aria-label="User avatar"><?php echo htmlspecialchars($initials ?: 'U'); ?></span>
                            <?php endif; ?>
                        </button>
                        <a href="wallet_dashboard.php" class="btn btn-wallet ms-2" style="background:#00f5ff;color:#101a24;font-weight:600;border-radius:8px;">Wallet</a>
                        <div id="profileMenu" class="profile-menu">
                            <div class="profile-header">
                                <div class="d-flex align-items-center gap-2 px-2">
                                    <?php if (!empty($profile_pic) && file_exists('../' . $profile_pic)): ?>
                                        <img src="../<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" class="profile-avatar" style="width:48px;height:48px;object-fit:cover;">
                                    <?php else: ?>
                                        <div class="profile-avatar" style="width:48px;height:48px;font-size:16px;"><?php echo htmlspecialchars($initials ?: 'U'); ?></div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($user_name ?: 'User'); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($role); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group list-group-flush mt-2">
                                <a href="../player/profile_details.php" class="list-group-item list-group-item-action">View all details</a>
                                <a href="../../src/logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container dashboard-container">
        <div class="dashboard-header">
            <h1>Player Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Player'); ?>! Ready to dominate the battlefield? 🔥</p>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/notifications.js"></script>
        
        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-controller"></i>
                </div>
                <div class="stat-number"><?php echo count($joinedTournaments); ?></div>
                <div class="stat-label">Joined Tournaments</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-number">
                    <?php 
                    $paidCount = array_filter($joinedTournaments, function($t) {
                        $ps = strtolower($t['payment_status'] ?? '');
                        return in_array($ps, ['success', 'paid', 'completed'], true);
                    });
                    echo count($paidCount);
                    ?>
                </div>
                <div class="stat-label">Paid Entries</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-trophy-fill"></i>
                </div>
                <div class="stat-number">
                    <?php 
                    $prizes = array_sum(array_column($joinedTournaments, 'prize_won'));
                    echo '₹' . number_format($prizes, 0);
                    ?>
                </div>
                <div class="stat-label">Total Earnings</div>
            </div>
        </div>
        
        <div class="tournaments-section">
            
            <h2 class="section-title">Joined Tournaments</h2>
            <div class="tournament-grid">
                <?php if (empty($joinedTournaments)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="bi bi-trophy"></i>
                        </div>
                        <h4>No Tournaments Joined Yet</h4>
                        <p>Join a tournament to get started with competitive gaming!</p>
                        <a href="#available-tournaments" class="btn btn-gaming mt-2">Browse Tournaments</a>
                    </div>
                <?php else: 
                    foreach ($joinedTournaments as $row): 
                ?>
                    <div class="tournament-card">
                        <div class="tournament-header">
                            <h3 class="tournament-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                            <div class="tournament-meta">
                                <span><?php echo htmlspecialchars($row['date']); ?></span>
                                <span><?php echo htmlspecialchars($row['time']); ?></span>
                            </div>
                        </div>
                        <div class="tournament-body">
                            <div class="tournament-details">
                                <div class="detail-item">
                                    <span class="detail-label">Slot Number</span>
                                    <span class="detail-value">
                                        <span class="badge badge-gaming badge-upcoming">#<?php echo htmlspecialchars($row['slot_no'] ?? '0'); ?></span>
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Match Type</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($row['match_type'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Room ID</span>
                                    <span class="detail-value">
                                        <span class="badge badge-gaming badge-live" style="font-family: monospace; font-size: 0.9rem;">
                                            <?php echo htmlspecialchars($row['room_id'] ?? 'Not Set'); ?>
                                        </span>
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Room Password</span>
                                    <span class="detail-value">
                                        <span class="badge badge-gaming badge-live" style="font-family: monospace; font-size: 0.9rem;">
                                            <?php echo htmlspecialchars($row['room_password'] ?? 'Not Set'); ?>
                                        </span>
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Payment Status</span>
                                    <span class="detail-value">
                                        <span class="badge badge-gaming badge-completed">Completed</span>
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Prize Won</span>
                                    <span class="detail-value">
                                        <?php 
                                        if ($row['prize_won'] > 0) {
                                            echo '₹' . number_format($row['prize_won'], 2);
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <div class="tournament-actions">
                                <a href="view_leaderboard.php?tournament_id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm" style="margin-right: 10px;">
                                    <i class="bi bi-trophy-fill"></i> View Leaderboard
                                </a>
                                <?php if (isset($row['role']) && in_array($row['match_type'], ['duo', 'squad', 'clash squad'])): ?>
                                    <button class="btn btn-danger btn-sm leave-team-btn" data-registration-id="<?php echo $row['registration_id']; ?>" data-tournament-title="<?php echo htmlspecialchars($row['title']); ?>" style="margin-right: 10px;">
                                        <i class="bi bi-box-arrow-left"></i> Leave Team
                                    </button>
                                <?php endif; ?>
                                <span class="text-success d-flex align-items-center justify-content-center">
                                    <i class="bi bi-check-circle-fill me-1"></i> Payment Completed
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
            
            <h2 class="section-title" id="available-tournaments">All Tournaments</h2>
            <div class="tournament-grid">
                <?php if (empty($allTournaments)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="bi bi-calendar-x"></i>
                        </div>
                        <h4>No Tournaments Available</h4>
                        <p>Check back later for new tournaments!</p>
                    </div>
                <?php else: 
                    foreach ($allTournaments as $t): 
                ?>
                    <div class="tournament-card">
                        <div class="tournament-header">
                            <h3 class="tournament-title"><?php echo htmlspecialchars($t['title']); ?></h3>
                            <div class="tournament-meta">
                                <span><?php echo htmlspecialchars(date('M d, Y', strtotime($t['date']))); ?></span>
                                <span><?php echo htmlspecialchars(date('H:i', strtotime($t['time']))); ?></span>
                            </div>
                        </div>
                        <div class="tournament-body">
                            <div class="tournament-details">
                                <div class="detail-item">
                                    <span class="detail-label">Entry Fee</span>
                                    <span class="detail-value">₹<?php echo htmlspecialchars($t['entry_fee']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Prize Pool</span>
                                    <span class="detail-value">₹<?php echo htmlspecialchars($t['prize_pool']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Type</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($t['match_type']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Status</span>
                                    <span class="detail-value">
                                        <span class="badge badge-gaming badge-upcoming"><?php echo htmlspecialchars($t['status']); ?></span>
                                    </span>
                                </div>
                            </div>
                            <div class="tournament-actions">
                                <?php if (in_array((int)$t['id'], $joinedIds, true)): ?>
                                    <span class="badge badge-gaming badge-completed">Already Joined</span>
                                <?php elseif (strtolower($t['status']) === 'upcoming'): ?>
                                    <a href="join_tournament.php?id=<?php echo (int)$t['id']; ?>" class="btn btn-gaming">Join Tournament</a>
                                <?php else: ?>
                                    <span class="text-muted">Registration Closed</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <footer>
        <div class="container text-center">
            <p class="mb-0 text-muted">&copy; 2023 SKYNOXX. All rights reserved.</p>
        </div>
    </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <?php 
                $finalSuccess = '';
                if (!empty($flash_success)) { $finalSuccess = $flash_success; }
                elseif (!empty($payment_message) && $payment_type === 'success') { $finalSuccess = $payment_message; }
        ?>
        <?php if (!empty($finalSuccess)): ?>
        <!-- Success Modal -->
        <div class="modal fade" id="registrationSuccessModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="bi bi-check-circle-fill me-2"></i>Success</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-dark">
                        <?php echo htmlspecialchars($finalSuccess); ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function(){
                var el = document.getElementById('registrationSuccessModal');
                if (el) { new bootstrap.Modal(el).show(); }
            });
        </script>
        <?php endif; ?>
    <script>
        // Profile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const profileBtn = document.getElementById('profileBtn');
            const profileMenu = document.getElementById('profileMenu');
            
            if (profileBtn && profileMenu) {
                profileBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    profileMenu.classList.toggle('show');
                });
                
                // Close menu when clicking outside
                document.addEventListener('click', function() {
                    profileMenu.classList.remove('show');
                });
                
                // Prevent menu from closing when clicking inside
                profileMenu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
            
            // Leave team functionality
            document.querySelectorAll('.leave-team-btn').forEach(btn => {
                btn.addEventListener('click', async function() {
                    const registrationId = this.dataset.registrationId;
                    const tournamentTitle = this.dataset.tournamentTitle;
                    
                    if (!confirm(`Are you sure you want to leave the team for "${tournamentTitle}"?\n\nWARNING: Leaving will cancel the ENTIRE team registration and all members will be removed from the tournament!`)) {
                        return;
                    }
                    
                    try {
                        const response = await fetch('../api/leave_team.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `registration_id=${registrationId}`
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            alert(data.message);
                            location.reload();
                        } else {
                            alert('Error: ' + (data.error || 'Failed to leave team'));
                        }
                    } catch (error) {
                        alert('Error: Failed to connect to server');
                    }
                });
            });
        });
    </script>
</body>
</html>