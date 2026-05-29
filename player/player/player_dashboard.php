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
    WHERE tr.user_id = ? AND tr.status = 'accepted'
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
    <style>
        :root { 
            --primary:#ff4655; 
            --primary-dark:#e03e4c; 
            --primary-gaming: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --accent-gaming: #00f5ff; 
            --accent-gaming-dark: #00c4cc;
            --dark-bg: #0f0f23; 
            --card-bg: #1a1a2e; 
            --card-border: #16213e; 
            --text-primary: #e0e0e0; 
            --text-secondary: #9fb6c3;
            --secondary:#0f1923; 
            --text:#ece8e1; 
            --text-muted:#b8b3ad; 
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
        }
        body { 
            background: var(--dark-bg); 
            color: var(--text-primary); 
            font-family: 'Montserrat', sans-serif; 
            min-height:100vh; 
            overflow-x: hidden;
        }
        .container { max-width: 1400px; }
        .gaming-navbar { 
            background:rgba(15,25,35,0.95); 
            backdrop-filter:blur(10px); 
            border-bottom: 1px solid rgba(255,70,85,0.2); 
            padding:.5rem 0; 
            position:sticky; 
            top:0; 
            z-index:1000; 
        }
        .gaming-navbar .container {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            flex-wrap: nowrap !important;
        }
        .navbar-brand { 
            font-family:'Orbitron',sans-serif; 
            font-weight:800; 
            font-size:1.3rem; 
            color:var(--text); 
            text-transform:uppercase; 
            letter-spacing:1px; 
            display:flex; 
            align-items:center;
            text-decoration: none;
            flex-shrink: 0;
            margin-right: auto;
        }
        .navbar-brand span { color:var(--primary); }
        .brand-logo-img { width:150px; height:48px; margin-right:10px; border-radius:6px; object-fit:contain; }
        .ms-auto {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .profile-btn {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
        }
        .profile-avatar { 
            width:44px; 
            height:44px; 
            border-radius:50%!important; 
            display:inline-flex; 
            align-items:center; 
            justify-content:center; 
            font-weight:700; 
            color:#fff; 
            background:linear-gradient(135deg,#ff4655,#e03e4c);
            cursor: pointer;
            transition: transform 0.2s ease;
            object-fit: cover;
            border: 2px solid rgba(255, 70, 85, 0.5);
        }
        .profile-avatar:hover {
            transform: scale(1.1);
        }
        img.profile-avatar {
            object-fit: cover;
            border: 2px solid rgba(255, 70, 85, 0.5);
        }
        .profile-menu { 
            position: absolute; 
            right: 0; 
            top: 60px; 
            min-width: 280px; 
            display: none; 
            flex-direction: column; 
            padding: 0; 
            border-radius: 16px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.6), 0 0 40px rgba(102,126,234,0.15); 
            background: linear-gradient(180deg, rgba(26,43,60,.98), rgba(15,25,35,.98)); 
            border: 1px solid rgba(102,126,234,0.3); 
            transform-origin: top right; 
            opacity: 0; 
            transform: translateY(-10px) scale(.95); 
            transition: opacity 250ms cubic-bezier(0.4, 0, 0.2, 1), transform 250ms cubic-bezier(0.4, 0, 0.2, 1); 
            z-index: 1100; 
            pointer-events: none;
            backdrop-filter: blur(20px);
            overflow: hidden;
        }
        .profile-menu.show { 
            display: flex; 
            opacity: 1; 
            transform: translateY(0) scale(1); 
            pointer-events: auto; 
        }
        .profile-menu::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #00f5ff 100%);
        }
        .profile-header {
            padding: 1.25rem 1rem;
            background: rgba(102,126,234,0.08);
            border-bottom: 1px solid rgba(102,126,234,0.15);
        }
        .profile-header .profile-avatar {
            width: 52px;
            height: 52px;
            font-size: 1.3rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 15px rgba(102,126,234,0.4);
        }
        .profile-header .fw-bold {
            font-size: 1.05rem;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        .profile-header .text-muted {
            color: #00f5ff !important;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        .list-group-flush {
            margin: 0;
        }
        .profile-menu .list-group-item {
            background: transparent;
            border: none;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            color: var(--text-primary);
            padding: 0.85rem 1.25rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .profile-menu .list-group-item:last-child {
            border-bottom: none;
        }
        .profile-menu .list-group-item::before {
            content: '→';
            font-size: 1.1rem;
            color: #00f5ff;
            transition: transform 0.2s ease;
        }
        .profile-menu .list-group-item:hover {
            background: rgba(102,126,234,0.15);
            color: #fff;
            padding-left: 1.5rem;
        }
        .profile-menu .list-group-item:hover::before {
            transform: translateX(4px);
        }
        .profile-menu .list-group-item.text-danger::before {
            color: #ff4655;
        }
        .profile-menu .list-group-item.text-danger:hover {
            background: rgba(255,70,85,0.15);
            color: #ff4655;
        }
        
        /* Dashboard Layout */
        .dashboard-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            padding: 1.5rem 0;
        }
        
        .dashboard-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .dashboard-header h1 {
            font-family: 'Orbitron', sans-serif;
            font-weight: 800;
            color: var(--text-primary);
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .dashboard-header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }
        
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid var(--card-border);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #00f5ff 100%);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.4);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--accent-gaming);
        }
        
        .stat-number {
            font-family: 'Orbitron', sans-serif;
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .tournaments-section {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        
        .section-title {
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            color: var(--accent-gaming);
            font-size: 1.5rem;
            margin-bottom: 1rem;
            position: relative;
            display: inline-block;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-gaming), transparent);
            border-radius: 3px;
        }
        
        .tournament-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .tournament-card {
            background: var(--card-bg);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--card-border);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }
        
        .tournament-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.4);
        }
        
        .tournament-header {
            padding: 1.5rem;
            background: rgba(102,126,234,0.1);
            border-bottom: 1px solid rgba(102,126,234,0.2);
        }
        
        .tournament-title {
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            color: var(--text-primary);
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }
        
        .tournament-meta {
            display: flex;
            justify-content: space-between;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .tournament-body {
            padding: 1.5rem;
        }
        
        .tournament-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            color: var(--text-secondary);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.25rem;
        }
        
        .detail-value {
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .tournament-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            margin-top: auto;
        }
        
        .btn-gaming { 
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); 
            border: none; 
            border-radius: 12px; 
            padding: 0.6rem 1.25rem; 
            font-weight: 600;
            color: #fff;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(255, 70, 85, 0.3);
            flex: 1;
            text-align: center;
        }
        
        .btn-gaming::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-gaming:hover { 
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 70, 85, 0.4);
        }
        
        .btn-gaming:hover::before {
            left: 100%;
        }
        
        .btn-gaming-outline { 
            background: transparent; 
            border: 2px solid var(--accent-gaming); 
            color: var(--accent-gaming); 
            border-radius: 12px; 
            padding: 0.55rem 1.15rem; 
            font-weight: 600; 
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            flex: 1;
            text-align: center;
        }
        
        .btn-gaming-outline::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: var(--accent-gaming);
            transition: width 0.3s ease;
            z-index: -1;
        }
        
        .btn-gaming-outline:hover { 
            color: var(--dark-bg); 
        }
        
        .btn-gaming-outline:hover::before {
            width: 100%;
        }
        
        .badge-gaming { 
            font-weight: 600; 
            padding: 0.4rem 0.8rem; 
            border-radius: 8px; 
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-completed {
            background: linear-gradient(135deg, var(--success), #20c997);
            color: white;
        }
        
        .badge-pending {
            background: linear-gradient(135deg, var(--warning), #fd7e14);
            color: #212529;
        }
        
        .badge-not-paid {
            background: linear-gradient(135deg, var(--danger), #e83e8c);
            color: white;
        }
        
        .badge-upcoming {
            background: linear-gradient(135deg, var(--info), #6f42c1);
            color: white;
        }
        
        /* Footer */
        footer {
            background: rgba(15,25,35,0.95); 
            border-top: 1px solid rgba(255,70,85,0.2);
            padding: 1.5rem 0;
            margin-top: 3rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .stats-section {
                grid-template-columns: 1fr;
            }
            
            .tournament-grid {
                grid-template-columns: 1fr;
            }
            
            .tournament-actions {
                flex-direction: column;
                align-items: stretch;
                justify-content: flex-end;
                margin-top: auto;
            }
            
            .dashboard-header h1 {
                font-size: 2rem;
            }
        }
    </style>
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
                    <style>
                        .notif-btn{position:relative;background:transparent;border:none;color:#fff;margin-right:12px;cursor:pointer}
                        .notif-btn .bi{font-size:1.4rem}
                        .notif-count{position:absolute;top:-6px;right:-6px;background:#ff4655;color:#fff;border-radius:999px;padding:0 6px;height:18px;min-width:18px;display:none;align-items:center;justify-content:center;font-size:11px;line-height:18px}
                        .notif-menu{position:absolute;right:70px;top:54px;min-width:320px;display:none;flex-direction:column;background:linear-gradient(180deg, rgba(26,43,60,0.98), rgba(15,25,35,0.98));border:1px solid rgba(255,255,255,0.08);border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,0.4);z-index:1100;pointer-events:none;opacity:0;transform:translateY(-6px);transition:opacity .18s ease, transform .18s ease}
                        .notif-menu.show{display:flex;pointer-events:auto;opacity:1;transform:translateY(0)}
                        .notif-header{padding:.6rem .8rem;border-bottom:1px solid rgba(255,255,255,0.06);display:flex;justify-content:space-between;align-items:center}
                        .notif-list{max-height:360px;overflow:auto;display:flex;flex-direction:column}
                        .notif-item{display:block;padding:.6rem .8rem;border-bottom:1px solid rgba(255,255,255,0.04);text-decoration:none;color:#eaeaea}
                        .notif-item:hover{background:rgba(255,70,85,0.06)}
                        .notif-title{font-weight:700;font-size:.95rem}
                        .notif-message{font-size:.82rem;color:#c9c9c9}
                        .notif-meta{font-size:.75rem;color:#999;margin-top:2px}
                        .notif-item.unread .notif-title{color:#fff}
                        .notif-empty{padding:1rem;color:#bbb;text-align:center}
                    </style>
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