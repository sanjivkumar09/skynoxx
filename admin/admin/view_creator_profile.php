<?php
session_start();
require_once '../src/db.php';

// Only admins allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../src/login.php');
    exit();
}

// Get creator ID from URL (supports both user_id and creator_id parameters)
if (!isset($_GET['user_id']) && !isset($_GET['creator_id'])) {
    header('Location: admin_dashboard.php');
    exit();
}

$creator_user_id = isset($_GET['creator_id']) ? (int)$_GET['creator_id'] : (int)$_GET['user_id'];

// Fetch user basic info (including is_active status)
$stmt = $conn->prepare("SELECT id, name, email, phone, joined_at, is_active FROM users WHERE id = ? AND role = 'creator' LIMIT 1");
$stmt->bind_param('i', $creator_user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header('Location: admin_dashboard.php');
    exit();
}

// Ensure profile picture columns exist
$check_columns = "SHOW COLUMNS FROM creators LIKE 'profile_pic'";
$col_result = $conn->query($check_columns);
if ($col_result->num_rows === 0) {
    $conn->query("ALTER TABLE creators ADD COLUMN profile_pic VARCHAR(255) AFTER yt_channel_name");
    $conn->query("ALTER TABLE creators ADD COLUMN game_profile_pic VARCHAR(255) AFTER profile_pic");
}

// Ensure yt_channel_link column exists
$check_yt_link = "SHOW COLUMNS FROM creators LIKE 'yt_channel_link'";
$yt_link_result = $conn->query($check_yt_link);
if ($yt_link_result->num_rows === 0) {
    $conn->query("ALTER TABLE creators ADD COLUMN yt_channel_link VARCHAR(500) AFTER yt_channel_name");
}

// Fetch creator profile details
$profile_stmt = $conn->prepare("SELECT name, mobile_no, email, game_uid, yt_channel_name, yt_channel_link, profile_pic, game_profile_pic, created_at, updated_at FROM creators WHERE user_id = ? LIMIT 1");
$profile_stmt->bind_param('i', $creator_user_id);
$profile_stmt->execute();
$profile_result = $profile_stmt->get_result();
$profile = $profile_result->fetch_assoc();

// Fetch creator's tournaments
$tournaments_stmt = $conn->prepare("
    SELECT 
        id,
        title,
        entry_fee,
        prize_pool,
        max_players,
        date,
        time,
        status,
        created_at
    FROM tournaments
    WHERE created_by = ?
    ORDER BY created_at DESC
    LIMIT 20
");
$tournaments_stmt->bind_param('i', $creator_user_id);
$tournaments_stmt->execute();
$tournaments = $tournaments_stmt->get_result();

// Count tournaments by status
$stats_stmt = $conn->prepare("
    SELECT 
        status,
        COUNT(*) as count
    FROM tournaments
    WHERE created_by = ?
    GROUP BY status
");
$stats_stmt->bind_param('i', $creator_user_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();

$stats = ['upcoming' => 0, 'ongoing' => 0, 'completed' => 0, 'cancelled' => 0];
while ($stat = $stats_result->fetch_assoc()) {
    $status = strtolower($stat['status']);
    $stats[$status] = (int)$stat['count'];
}

// Get initials for avatar fallback
$name_parts = explode(' ', $user['name'] ?? 'Creator');
$initials = strtoupper(substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : ''));

// Total tournaments
$total_tournaments = array_sum($stats);

// Fetch creator wallet balance
$wallet_balance = 0.0;
if ($wb = $conn->prepare("SELECT COALESCE(wallet_balance,0) AS bal FROM users WHERE id = ? LIMIT 1")) {
    $wb->bind_param('i', $creator_user_id);
    $wb->execute();
    $rwb = $wb->get_result();
    if ($row = $rwb->fetch_assoc()) { $wallet_balance = (float)$row['bal']; }
    $wb->close();
}

// Fetch recent wallet transactions with context
$transactions = [];
if ($tx = $conn->prepare("SELECT wt.id, wt.type, wt.amount, wt.status, wt.description, wt.tournament_id, wt.created_at,
                                 t.title AS tournament_title,
                                 u2.name AS related_user_name
                          FROM wallet_transactions wt
                          LEFT JOIN tournaments t ON wt.tournament_id = t.id
                          LEFT JOIN users u2 ON wt.related_user_id = u2.id
                          WHERE wt.user_id = ?
                          ORDER BY wt.created_at DESC, wt.id DESC
                          LIMIT 50")) {
    $tx->bind_param('i', $creator_user_id);
    $tx->execute();
    $resTx = $tx->get_result();
    $transactions = $resTx ? $resTx->fetch_all(MYSQLI_ASSOC) : [];
    $tx->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/gaming-theme.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Creator Profile - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <a href="../../src/index.php">
                        <img src="../../assets/images/logo.svg" alt="SKYNOXX FF Logo" class="header-logo">
                    </a>
                    <a href="admin_dashboard.php" class="back-btn">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="dropdown">
                        <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" id="adminMenu" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-2"></i>Admin Menu
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="adminMenu">
                            <li><a class="dropdown-item" href="admin_dashboard.php"><i class="fas fa-home me-2"></i>Dashboard</a></li>
                            <li><a class="dropdown-item" href="analytics_dashboard.php"><i class="fas fa-chart-line me-2"></i>Analytics</a></li>
                            <li><a class="dropdown-item" href="payment_management.php"><i class="fas fa-credit-card me-2"></i>Payments</a></li>
                            <li><a class="dropdown-item" href="admin_withdrawals.php"><i class="fas fa-money-check-alt me-2"></i>Withdrawals</a></li>
                            <li><a class="dropdown-item" href="wallet_deposits.php"><i class="fas fa-wallet me-2"></i>Wallet Deposits</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../../src/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                    <span class="badge badge-status badge-creator">
                        <i class="fas fa-shield-alt me-1"></i>ADMIN VIEW
                    </span>
                    <span class="text-muted">Viewing Creator Profile</span>
                </div>
            </div>
        </div>
    </header>

    <div class="profile-container">
        <!-- Profile Header Section -->
        <div class="profile-header">
            <div class="row align-items-center">
                <div class="col-md-auto mb-3 mb-md-0 text-center text-md-start">
                    <div class="avatar-display">
                        <?php if (!empty($profile['profile_pic']) && file_exists('../' . $profile['profile_pic'])): ?>
                            <img src="../<?php echo htmlspecialchars($profile['profile_pic']); ?>" 
                                 alt="Creator Avatar" 
                                 class="creator-avatar-large">
                        <?php else: ?>
                            <div class="avatar-placeholder"><?php echo $initials; ?></div>
                        <?php endif; ?>
                        <div class="verified-badge">
                            <i class="fas fa-gamepad"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md">
                    <h1 class="creator-name"><?php echo htmlspecialchars($profile['name'] ?? $user['name']); ?></h1>
                    <p class="creator-subtitle">
                        <i class="fas fa-youtube me-2"></i>
                        <?php echo htmlspecialchars($profile['yt_channel_name'] ?? 'No YouTube channel set'); ?>
                    </p>
                    <div class="mt-3">
                        <span class="badge badge-status badge-creator me-2">
                            <i class="fas fa-user-tie me-1"></i>CREATOR
                        </span>
                        <?php if (isset($user['is_active']) && $user['is_active'] == 0): ?>
                        <span class="badge badge-status me-2" style="background: rgba(220, 53, 69, 0.2); color: #ff6b6b; border: 1px solid rgba(220, 53, 69, 0.4);">
                            <i class="fas fa-ban me-1"></i>LOGIN BLOCKED
                        </span>
                        <?php endif; ?>
                        <span class="badge badge-status" style="background: rgba(0, 245, 255, 0.2); color: var(--accent-gaming); border: 1px solid rgba(0, 245, 255, 0.4);">
                            <i class="fas fa-calendar-alt me-1"></i>Joined <?php echo date('M Y', strtotime($user['joined_at'])); ?>
                        </span>
                    </div>
                </div>
                <div class="col-md-auto text-md-end">
                    <a href="creator_wallet.php?user_id=<?php echo (int)$creator_user_id; ?>" class="btn btn-sm btn-outline-info me-2" style="font-weight:700; letter-spacing:.3px;">
                        <i class="fas fa-wallet me-1"></i> Wallet
                    </a>
                    <?php if (isset($user['is_active'])): ?>
                        <?php if ($user['is_active'] == 1): ?>
                        <a href="toggle_user_status.php?user_id=<?php echo (int)$creator_user_id; ?>&action=block&redirect=<?php echo urlencode('view_creator_profile.php?creator_id=' . $creator_user_id); ?>" 
                           class="btn btn-sm btn-secondary me-2" 
                           style="font-weight:700; letter-spacing:.3px;"
                           onclick="return confirm('Are you sure you want to BLOCK this creator? They will not be able to login.');">
                            <i class="fas fa-ban me-1"></i> Block Login
                        </a>
                        <?php else: ?>
                        <a href="toggle_user_status.php?user_id=<?php echo (int)$creator_user_id; ?>&action=unblock&redirect=<?php echo urlencode('view_creator_profile.php?creator_id=' . $creator_user_id); ?>" 
                           class="btn btn-sm btn-success me-2" 
                           style="font-weight:700; letter-spacing:.3px;"
                           onclick="return confirm('Are you sure you want to UNBLOCK this creator? They will be able to login again.');">
                            <i class="fas fa-check-circle me-1"></i> Unblock Login
                        </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <a href="edit_creator_profile.php?creator_id=<?php echo (int)$creator_user_id; ?>" class="btn btn-sm btn-warning me-2" style="font-weight:700; letter-spacing:.3px;">
                        <i class="fas fa-edit me-1"></i> Edit
                    </a>
                    <button type="button" class="btn btn-sm btn-danger" style="font-weight:700; letter-spacing:.3px;" onclick="confirmDeleteCreator(<?php echo (int)$creator_user_id; ?>)">
                        <i class="fas fa-trash me-1"></i> Delete
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="stat-value"><?php echo $total_tournaments; ?></div>
                <div class="stat-label">Total Tournaments</div>
            </div>
            <div class="stat-box">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?php echo $stats['upcoming']; ?></div>
                <div class="stat-label">Upcoming</div>
            </div>
            <div class="stat-box">
                <div class="stat-icon">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="stat-value"><?php echo $stats['ongoing']; ?></div>
                <div class="stat-label">Ongoing</div>
            </div>
            <div class="stat-box">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo $stats['completed']; ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>

        <!-- Wallet moved to dedicated page; see creator_wallet.php -->

        <div class="row">
            <!-- Basic Information -->
            <div class="col-lg-6">
                <div class="info-card">
                    <h3 class="card-title-custom">
                        <i class="fas fa-user-circle"></i>
                        Basic Information
                    </h3>
                    <div class="info-row">
                        <span class="info-label">User ID</span>
                        <span class="info-value">#<?php echo $user['id']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Full Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($profile['name'] ?? $user['name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email Address</span>
                        <span class="info-value"><?php echo htmlspecialchars($profile['email'] ?? $user['email']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Mobile Number</span>
                        <span class="info-value"><?php echo htmlspecialchars($profile['mobile_no'] ?? 'Not provided'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Joined Date</span>
                        <span class="info-value"><?php echo date('d M Y, h:i A', strtotime($user['joined_at'])); ?></span>
                    </div>
                </div>
            </div>

            <!-- Creator Information -->
            <div class="col-lg-6">
                <div class="info-card">
                    <h3 class="card-title-custom">
                        <i class="fas fa-gamepad"></i>
                        Creator Information
                    </h3>
                    <div class="info-row">
                        <span class="info-label">Game UID</span>
                        <span class="info-value"><?php echo htmlspecialchars($profile['game_uid'] ?? 'Not set'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">YouTube Channel</span>
                        <span class="info-value"><?php echo htmlspecialchars($profile['yt_channel_name'] ?? 'Not set'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">YouTube Link</span>
                        <span class="info-value">
                            <?php if (!empty($profile['yt_channel_link'])): ?>
                                <a href="<?php echo htmlspecialchars($profile['yt_channel_link']); ?>" 
                                   target="_blank" 
                                   class="btn btn-sm btn-youtube">
                                    <i class="fab fa-youtube me-1"></i>Visit Channel
                                </a>
                            <?php else: ?>
                                <span class="text-muted">Not set</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Profile Created</span>
                        <span class="info-value"><?php echo $profile ? date('d M Y', strtotime($profile['created_at'])) : 'Not created'; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Last Updated</span>
                        <span class="info-value"><?php echo $profile ? date('d M Y, h:i A', strtotime($profile['updated_at'])) : 'Never'; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Total Tournaments</span>
                        <span class="info-value"><?php echo $total_tournaments; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Game Profile Picture -->
        <?php if (!empty($profile['game_profile_pic']) && file_exists('../' . $profile['game_profile_pic'])): ?>
            <div class="info-card">
                <h3 class="card-title-custom">
                    <i class="fas fa-image"></i>
                    Game Profile Picture
                </h3>
                <div class="text-center">
                    <img src="../<?php echo htmlspecialchars($profile['game_profile_pic']); ?>" 
                         alt="Game Profile" 
                         class="game-profile-preview">
                </div>
            </div>
        <?php endif; ?>

        <!-- Created Tournaments -->
        <div class="info-card">
            <h3 class="card-title-custom">
                <i class="fas fa-trophy"></i>
                Created Tournaments
            </h3>
            <?php if ($tournaments->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-dark-custom table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Entry Fee</th>
                                <th>Prize Pool</th>
                                <th>Max Players</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($tournament = $tournaments->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?php echo $tournament['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($tournament['title']); ?></td>
                                    <td>₹<?php echo number_format($tournament['entry_fee'], 2); ?></td>
                                    <td>₹<?php echo number_format($tournament['prize_pool'], 2); ?></td>
                                    <td><?php echo $tournament['max_players']; ?></td>
                                    <td><?php echo date('d M Y, h:i A', strtotime($tournament['date'] . ' ' . $tournament['time'])); ?></td>
                                    <td>
                                        <?php
                                        $status = strtolower($tournament['status']);
                                        $badge_class = 'status-upcoming';
                                        if ($status === 'ongoing') $badge_class = 'status-ongoing';
                                        elseif ($status === 'completed') $badge_class = 'status-completed';
                                        elseif ($status === 'cancelled') $badge_class = 'status-cancelled';
                                        ?>
                                        <span class="status-badge <?php echo $badge_class; ?>">
                                            <?php echo ucfirst($tournament['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($tournament['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox fa-3x mb-3" style="opacity: 0.3;"></i>
                    <p>This creator has not created any tournaments yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function confirmDeleteCreator(creatorId) {
        if (confirm('⚠️ WARNING: This will permanently delete the creator account and all related data!\n\n' +
                    'This includes:\n' +
                    '- Creator profile\n' +
                    '- All created tournaments\n' +
                    '- Wallet transactions\n' +
                    '- Payment history\n\n' +
                    'Are you absolutely sure?')) {
            window.location.href = 'delete_creator.php?creator_id=' + creatorId;
        }
    }
    </script>
</body>
</html>
