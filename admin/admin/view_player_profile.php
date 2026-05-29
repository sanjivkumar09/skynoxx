<?php
session_start();
require_once '../src/db.php';

// Only admins and creators allowed
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'creator'])) {
    header('Location: ../../src/login.php');
    exit();
}

// Get player ID from URL (supports both user_id and player_id parameters)
if (!isset($_GET['user_id']) && !isset($_GET['player_id'])) {
    header('Location: admin_dashboard.php');
    exit();
}

$player_id = isset($_GET['player_id']) ? (int)$_GET['player_id'] : (int)$_GET['user_id'];

// Fetch user basic info (including is_active status)
$stmt = $conn->prepare("SELECT id, name, email, phone, joined_at, is_active FROM users WHERE id = ? AND role = 'player' LIMIT 1");
$stmt->bind_param('i', $player_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header('Location: admin_dashboard.php');
    exit();
}

// Fetch player profile details
$profile_stmt = $conn->prepare("SELECT in_game_name, avatar, screenshot, game_uid, upi_id, created_at, updated_at FROM players_profile WHERE user_id = ? LIMIT 1");
$profile_stmt->bind_param('i', $player_id);
$profile_stmt->execute();
$profile_result = $profile_stmt->get_result();
$profile = $profile_result->fetch_assoc();

// Fetch tournament registrations
$reg_stmt = $conn->prepare("
    SELECT 
        r.id, 
        r.tournament_id, 
        r.payment_status,
        r.joined_at,
        r.prize_won,
        t.title as tournament_name,
        t.entry_fee,
        t.date as tournament_date,
        t.time as tournament_time,
        p.method as payment_method,
        p.txn_id as transaction_id,
        p.created_at as payment_date
    FROM registrations r
    LEFT JOIN tournaments t ON r.tournament_id = t.id
    LEFT JOIN payments p ON p.user_id = r.player_id AND p.tournament_id = r.tournament_id
    WHERE r.player_id = ?
    ORDER BY r.joined_at DESC
    LIMIT 10
");
$reg_stmt->bind_param('i', $player_id);
$reg_stmt->execute();
$registrations = $reg_stmt->get_result();

// Get initials for avatar fallback
$name_parts = explode(' ', $user['name'] ?? 'User');
$initials = strtoupper(substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Player Profile - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #ff4655;
            --primary-dark: #e23f4d;
            --secondary: #0f1923;
            --accent: #1f2d3d;
            --text: #ece8e1;
            --text-muted: #9fb3c8;
            --card-bg: rgba(15, 25, 35, 0.7);
            --border: rgba(255, 255, 255, 0.1);
            --accent-gaming: #00f5ff;
        }
        
        body {
            background: linear-gradient(135deg, #0f1923 0%, #1a2836 100%);
            color: var(--text);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .admin-header {
            background: rgba(15, 25, 35, 0.95);
            border-bottom: 2px solid var(--primary);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        /* Dropdown Menu Styles */
        .dropdown-menu {
            z-index: 1050 !important;
            background: linear-gradient(180deg, rgba(26,43,60,.98), rgba(15,25,35,.98)) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 10px 30px rgba(0,0,0,.4) !important;
            min-width: 250px !important;
        }
        
        .dropdown-menu .dropdown-item {
            color: var(--text) !important;
            padding: 0.75rem 1.25rem !important;
            transition: all 0.2s ease !important;
        }
        
        .dropdown-menu .dropdown-item:hover {
            background: rgba(255, 70, 85, 0.2) !important;
            color: var(--primary) !important;
        }
        
        .dropdown-menu .dropdown-divider {
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
        
        .dropdown-toggle::after {
            margin-left: 0.5rem !important;
        }
        
        .header-logo {
            height: 48px;
            width: auto;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .header-logo:hover {
            transform: scale(1.05);
            filter: drop-shadow(0 0 10px rgba(255, 70, 85, 0.5));
        }
        
        .back-btn {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            padding: 0.5rem 1.5rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateX(-5px);
        }
        
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .profile-header {
            background: linear-gradient(135deg, rgba(31, 45, 61, 0.8) 0%, rgba(15, 25, 35, 0.9) 100%);
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        }
        
        .player-avatar-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid var(--primary);
            object-fit: cover;
            box-shadow: 0 4px 12px rgba(255, 70, 85, 0.4);
        }
        
        .avatar-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid var(--primary);
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
            box-shadow: 0 4px 12px rgba(255, 70, 85, 0.4);
        }
        
        .player-name {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 0.5rem;
            letter-spacing: 1px;
        }
        
        .player-subtitle {
            font-size: 1.1rem;
            color: var(--accent-gaming);
            font-weight: 600;
        }
        
        .badge-status {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .badge-verified {
            background: rgba(40, 167, 69, 0.2);
            color: #75b798;
            border: 1px solid rgba(40, 167, 69, 0.4);
        }
        
        .info-card {
            background: rgba(31, 45, 61, 0.6);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
            border-color: var(--primary);
        }
        
        .card-title-custom {
            color: var(--accent-gaming);
            font-weight: 800;
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .card-title-custom i {
            color: var(--primary);
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border);
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 700;
            color: var(--text-muted);
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            font-weight: 600;
            color: var(--text);
            font-size: 1.1rem;
        }
        
        .screenshot-preview {
            width: 100%;
            max-height: 400px;
            object-fit: contain;
            border-radius: 8px;
            border: 2px solid var(--border);
            background: rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }
        
        .screenshot-preview:hover {
            transform: scale(1.02);
            border-color: var(--primary);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        }
        
        .no-data {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
            font-style: italic;
        }
        
        .table-dark-custom {
            background: transparent;
            color: var(--text);
        }
        
        .table-dark-custom thead th {
            background: rgba(255, 70, 85, 0.1);
            border-color: var(--border);
            color: var(--text-muted);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 1rem 0.75rem;
        }
        
        .table-dark-custom tbody td {
            border-color: var(--border);
            padding: 1rem 0.75rem;
            vertical-align: middle;
        }
        
        .table-dark-custom tbody tr {
            transition: all 0.3s ease;
        }
        
        .table-dark-custom tbody tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .payment-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        
        .payment-completed {
            background: rgba(40, 167, 69, 0.2);
            color: #75b798;
        }
        
        .payment-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }
        
        .payment-failed {
            background: rgba(220, 53, 69, 0.2);
            color: #e6a2a9;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-box {
            background: rgba(31, 45, 61, 0.6);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-box:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
        }
        
        .stat-icon {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .avatar-display {
            position: relative;
            display: inline-block;
        }
        
        .verified-badge {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: var(--primary);
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid var(--secondary);
            font-size: 1rem;
        }
        
        @media (max-width: 768px) {
            .player-name {
                font-size: 1.5rem;
            }
            
            .player-subtitle {
                font-size: 1rem;
            }
            
            .player-avatar-large,
            .avatar-placeholder {
                width: 100px;
                height: 100px;
            }
            
            .info-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
    </style>
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
                    <span class="badge badge-verified">
                        <i class="fas fa-shield-alt me-1"></i>ADMIN VIEW
                    </span>
                    <span class="text-muted">Viewing Player Profile</span>
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
                        <?php if (!empty($profile['avatar']) && file_exists('../' . $profile['avatar'])): ?>
                            <img src="../<?php echo htmlspecialchars($profile['avatar']); ?>" 
                                 alt="Player Avatar" 
                                 class="player-avatar-large">
                        <?php else: ?>
                            <div class="avatar-placeholder"><?php echo $initials; ?></div>
                        <?php endif; ?>
                        <div class="verified-badge">
                            <i class="fas fa-check"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md">
                    <h1 class="player-name"><?php echo htmlspecialchars($user['name']); ?></h1>
                    <p class="player-subtitle">
                        <i class="fas fa-gamepad me-2"></i>
                        <?php echo htmlspecialchars($profile['in_game_name'] ?? 'No in-game name set'); ?>
                    </p>
                    <div class="mt-3">
                        <span class="badge badge-status badge-verified me-2">
                            <i class="fas fa-user-check me-1"></i>PLAYER
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
                    <a href="player_wallet.php?user_id=<?php echo (int)$player_id; ?>" class="btn btn-sm btn-outline-info me-2" style="font-weight:700; letter-spacing:.3px;">
                        <i class="fas fa-wallet me-1"></i> Wallet
                    </a>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <?php if (isset($user['is_active'])): ?>
                        <?php if ($user['is_active'] == 1): ?>
                        <a href="toggle_user_status.php?user_id=<?php echo (int)$player_id; ?>&action=block&redirect=<?php echo urlencode('view_player_profile.php?player_id=' . $player_id); ?>" 
                           class="btn btn-sm btn-secondary me-2" 
                           style="font-weight:700; letter-spacing:.3px;"
                           onclick="return confirm('Are you sure you want to BLOCK this player? They will not be able to login.');">
                            <i class="fas fa-ban me-1"></i> Block Login
                        </a>
                        <?php else: ?>
                        <a href="toggle_user_status.php?user_id=<?php echo (int)$player_id; ?>&action=unblock&redirect=<?php echo urlencode('view_player_profile.php?player_id=' . $player_id); ?>" 
                           class="btn btn-sm btn-success me-2" 
                           style="font-weight:700; letter-spacing:.3px;"
                           onclick="return confirm('Are you sure you want to UNBLOCK this player? They will be able to login again.');">
                            <i class="fas fa-check-circle me-1"></i> Unblock Login
                        </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <a href="edit_player_profile.php?player_id=<?php echo (int)$player_id; ?>" class="btn btn-sm btn-warning me-2" style="font-weight:700; letter-spacing:.3px;">
                        <i class="fas fa-edit me-1"></i> Edit
                    </a>
                    <button type="button" class="btn btn-sm btn-danger" style="font-weight:700; letter-spacing:.3px;" onclick="confirmDeletePlayer(<?php echo (int)$player_id; ?>)">
                        <i class="fas fa-trash me-1"></i> Delete
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="stat-value"><?php echo $registrations->num_rows; ?></div>
                <div class="stat-label">Tournaments Joined</div>
            </div>
            <div class="stat-box">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value">
                    <?php 
                    mysqli_data_seek($registrations, 0);
                    $completed = 0;
                    while ($reg = $registrations->fetch_assoc()) {
                        if ($reg['payment_status'] === 'success') $completed++;
                    }
                    mysqli_data_seek($registrations, 0);
                    echo $completed;
                    ?>
                </div>
                <div class="stat-label">Completed Payments</div>
            </div>
            <div class="stat-box">
                <div class="stat-icon">
                    <i class="fas fa-id-card"></i>
                </div>
                <div class="stat-value"><?php echo !empty($profile['game_uid']) ? 'Yes' : 'No'; ?></div>
                <div class="stat-label">Game UID Set</div>
            </div>
            <div class="stat-box">
                <div class="stat-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="stat-value"><?php echo !empty($profile['upi_id']) ? 'Yes' : 'No'; ?></div>
                <div class="stat-label">UPI ID Set</div>
            </div>
        </div>

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
                        <span class="info-value"><?php echo htmlspecialchars($user['name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email Address</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone Number</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Joined Date</span>
                        <span class="info-value"><?php echo date('d M Y, h:i A', strtotime($user['joined_at'])); ?></span>
                    </div>
                </div>
            </div>

            <!-- Gaming Information -->
            <div class="col-lg-6">
                <div class="info-card">
                    <h3 class="card-title-custom">
                        <i class="fas fa-gamepad"></i>
                        Gaming Information
                    </h3>
                    <div class="info-row">
                        <span class="info-label">In-Game Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($profile['in_game_name'] ?? 'Not set'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Game UID</span>
                        <span class="info-value"><?php echo htmlspecialchars($profile['game_uid'] ?? 'Not set'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">UPI ID</span>
                        <span class="info-value"><?php echo htmlspecialchars($profile['upi_id'] ?? 'Not set'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Profile Created</span>
                        <span class="info-value"><?php echo $profile ? date('d M Y', strtotime($profile['created_at'])) : 'Not created'; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Last Updated</span>
                        <span class="info-value"><?php echo $profile ? date('d M Y, h:i A', strtotime($profile['updated_at'])) : 'Never'; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Game Screenshot -->
        <?php if (!empty($profile['screenshot']) && file_exists('../' . $profile['screenshot'])): ?>
            <div class="info-card">
                <h3 class="card-title-custom">
                    <i class="fas fa-image"></i>
                    Game Profile Screenshot
                </h3>
                <div class="text-center">
                    <img src="../<?php echo htmlspecialchars($profile['screenshot']); ?>" 
                         alt="Game Screenshot" 
                         class="screenshot-preview">
                </div>
            </div>
        <?php endif; ?>

        <!-- Tournament Registrations -->
        <div class="info-card">
            <h3 class="card-title-custom">
                <i class="fas fa-trophy"></i>
                Tournament Registrations
            </h3>
            <?php if ($registrations->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-dark-custom table-hover">
                        <thead>
                            <tr>
                                <th>Tournament Name</th>
                                <th>Entry Fee</th>
                                <th>Tournament Date</th>
                                <th>Payment Status</th>
                                <th>Payment Method</th>
                                <th>Registered On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($reg = $registrations->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($reg['tournament_name']); ?></strong>
                                    </td>
                                    <td>₹<?php echo number_format($reg['entry_fee'], 2); ?></td>
                                    <td><?php echo date('d M Y', strtotime($reg['tournament_date'])); ?></td>
                                    <td>
                                        <?php
                                        $status = $reg['payment_status'];
                                        $badge_class = 'payment-pending';
                                        $display_status = ucfirst($status);
                                        if ($status === 'success') {
                                            $badge_class = 'payment-completed';
                                            $display_status = 'Completed';
                                        } elseif ($status === 'failed') {
                                            $badge_class = 'payment-failed';
                                            $display_status = 'Failed';
                                        } elseif ($status === 'pending') {
                                            $display_status = 'Pending';
                                        }
                                        ?>
                                        <span class="payment-badge <?php echo $badge_class; ?>">
                                            <?php echo htmlspecialchars($display_status); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($reg['payment_method'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('d M Y', strtotime($reg['joined_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox fa-3x mb-3" style="opacity: 0.3;"></i>
                    <p>This player has not registered for any tournaments yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function confirmDeletePlayer(playerId) {
        if (confirm('⚠️ WARNING: This will permanently delete the player account and all related data!\n\n' +
                    'This includes:\n' +
                    '- Player profile\n' +
                    '- Tournament registrations\n' +
                    '- Wallet transactions\n' +
                    '- Payment history\n\n' +
                    'Are you absolutely sure?')) {
            window.location.href = 'delete_player.php?player_id=' + playerId;
        }
    }
    </script>
</body>
</html>
