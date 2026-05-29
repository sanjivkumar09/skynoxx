<?php
session_start();
include '../src/db.php';
require_once '../src/creator_functions.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../src/login.php');
    exit();
}

// Handle Create Creator submission (process before any HTML output)
$creator_message = null;
$creator_message_type = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_creator'])) {
    $c_name = trim($_POST['c_name'] ?? '');
    $c_email = trim($_POST['c_email'] ?? '');
    $c_mobile = trim($_POST['c_mobile'] ?? '');
    $c_game_uid = trim($_POST['c_game_uid'] ?? '');
    $c_yt = trim($_POST['c_yt'] ?? '');
    $c_yt_link = trim($_POST['c_yt_link'] ?? '');

    if ($c_name === '' || $c_email === '' || $c_mobile === '') {
        $creator_message = 'Name, Email, and Mobile No are required.';
        $creator_message_type = 'danger';
    } elseif (!filter_var($c_email, FILTER_VALIDATE_EMAIL)) {
        $creator_message = 'Please provide a valid email address.';
        $creator_message_type = 'danger';
    } else {
        // Start transaction
        mysqli_begin_transaction($conn);
        try {
            // Check if a user already exists with this email
            $stmt = mysqli_prepare($conn, 'SELECT id, role FROM users WHERE email = ?');
            mysqli_stmt_bind_param($stmt, 's', $c_email);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $existing_user = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);

            if ($existing_user && $existing_user['role'] !== 'creator') {
                // If user exists but not a creator yet, upgrade role to creator
                $user_id = (int)$existing_user['id'];
                $upd = mysqli_prepare($conn, "UPDATE users SET role = 'creator' WHERE id = ?");
                mysqli_stmt_bind_param($upd, 'i', $user_id);
                mysqli_stmt_execute($upd);
                mysqli_stmt_close($upd);
            } elseif ($existing_user && $existing_user['role'] === 'creator') {
                // Check if already present in creators table
                $check = mysqli_prepare($conn, 'SELECT id FROM creators WHERE user_id = ?');
                mysqli_stmt_bind_param($check, 'i', $existing_user['id']);
                mysqli_stmt_execute($check);
                $cres = mysqli_stmt_get_result($check);
                $already_creator = mysqli_fetch_assoc($cres);
                mysqli_stmt_close($check);
                if ($already_creator) {
                    throw new Exception('A creator with this email already exists.');
                }
                $user_id = (int)$existing_user['id'];
            } else {
                // Create new user with role creator and a random password (not used for creator login)
                $randPassword = bin2hex(random_bytes(8));
                $hash = password_hash($randPassword, PASSWORD_BCRYPT);
                $stmt = mysqli_prepare($conn, 'INSERT INTO users (name, email, phone, role, password) VALUES (?, ?, ?, "creator", ?)');
                mysqli_stmt_bind_param($stmt, 'ssss', $c_name, $c_email, $c_mobile, $hash);
                mysqli_stmt_execute($stmt);
                $user_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);
            }

            // Save creator profile (insert or update)
            $ok = saveCreatorProfile($user_id, $c_name, $c_mobile, $c_email, $c_game_uid, $c_yt, $c_yt_link);
            if (!$ok) {
                throw new Exception('Failed to save creator profile.');
            }

            mysqli_commit($conn);
            $creator_message = 'Creator created successfully.';
            $creator_message_type = 'success';
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $creator_message = 'Error: ' . $e->getMessage();
            $creator_message_type = 'danger';
        }
    }
}

// Fetch statistics from database
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"))['count'] ?? 0;
$total_tournaments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM tournaments"))['count'] ?? 0;
$active_creators = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM creators"))['count'] ?? 0;

// Pending manual top-up requests
$pending_topups = 0;
$res_pending = mysqli_query($conn, "SELECT COUNT(*) AS c FROM wallet_topup_requests WHERE status='pending'");
if ($res_pending) { $rowp = mysqli_fetch_assoc($res_pending); $pending_topups = (int)($rowp['c'] ?? 0); }

// Admin profit total from settlements (80/20 admin share credits)
$admin_id = (int)($_SESSION['user_id'] ?? 0);
$admin_profit_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) AS total FROM wallet_transactions WHERE user_id = $admin_id AND description LIKE 'Admin profit share%'"));
$admin_profit_total = $admin_profit_row['total'] ?? 0;

// Fetch announcements
$announcements = mysqli_query($conn, "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5");

// Fetch creators for the Creators tab
$creators = getAllCreators();

// Fetch players for the Players tab
$players = mysqli_query($conn, "SELECT id, name, email, phone, joined_at FROM users WHERE role='player' ORDER BY joined_at DESC LIMIT 200");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Free Fire Tournament Platform - Admin Dashboard</title>
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
        }
        
        body {
            background: linear-gradient(135deg, #0f1923 0%, #1a2836 100%);
            color: var(--text);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .admin-header {
            background: rgba(15, 25, 35, 0.9);
            border-bottom: 1px solid var(--border);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .logo {
            font-weight: 800;
            font-size: 1.8rem;
            letter-spacing: 1px;
            color: var(--text);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .logo span {
            color: var(--primary);
        }
        
        .logo-img {
            height: 45px;
            width: auto;
            object-fit: contain;
        }
        
        .brand-logo-img {
            height: 40px;
            width: auto;
            object-fit: contain;
        }
        
        .admin-tabs {
            border-bottom: 1px solid var(--border);
        }
        
        .admin-tabs .nav-link {
            color: var(--text);
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: .5px;
            border: none;
            background: transparent !important;
            padding: .75rem 1.5rem;
            opacity: .85;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .admin-tabs .nav-link:hover { 
            opacity: 1; 
            color: var(--primary);
            background: transparent !important;
        }
        
        .admin-tabs .nav-link.active {
            color: var(--primary);
            background: transparent !important;
            border-radius: 0;
            opacity: 1;
        }
        
        .admin-tabs .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--primary);
        }
        
        .admin-card {
            background: rgba(31, 45, 61, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .admin-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
        }
        
        .admin-card .card-title {
            color: #6eb4ff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: .9rem;
            letter-spacing: 0.5px;
        }
        
        .admin-card .card-text {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text);
        }
        
        .form-label {
            font-weight: 600;
            color: #c9d6e2;
            font-size: 0.95rem;
        }
        
        .form-control {
            background: rgba(31, 45, 61, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: var(--text);
            padding: 0.75rem 1rem;
        }
        
        .form-control::placeholder {
            color: rgba(236, 232, 225, 0.5);
        }
        
        .form-control:focus {
            background: rgba(31, 45, 61, 0.95);
            border-color: var(--primary);
            color: var(--text);
            box-shadow: 0 0 0 0.2rem rgba(255, 70, 85, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            font-weight: 700;
            letter-spacing: 0.5px;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 70, 85, 0.3);
        }
        
        .table-dark {
            --bs-table-bg: transparent;
            --bs-table-border-color: var(--border);
        }
        
        .table-dark th, .table-dark td {
            vertical-align: middle;
            border-color: var(--border);
            padding: 1rem 0.75rem;
        }
        
        .table-dark thead th {
            color: var(--text-muted);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border);
        }
        
        .table-dark tbody tr {
            transition: background-color 0.3s ease;
        }
        
        .table-dark tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .stats-card {
            height: 100%;
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
        }
        
        .announcement-item {
            background: rgba(15, 25, 35, 0.6);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .announcement-item:hover {
            background: rgba(15, 25, 35, 0.8);
            transform: translateX(5px);
        }
        
        .badge-custom {
            background: var(--primary);
            color: white;
            font-weight: 700;
            padding: 0.5rem 0.75rem;
        }
        
        .alert {
            border: none;
            border-radius: 8px;
            padding: 1rem 1.5rem;
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            color: #75b798;
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            color: #e6a2a9;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }
        
        .search-box input {
            padding-left: 40px;
        }
        
        /* Override Bootstrap dropdown link styles */
        .dropdown-menu-dark .dropdown-item {
            color: var(--text);
        }
        
        .dropdown-menu-dark .dropdown-item:hover,
        .dropdown-menu-dark .dropdown-item:focus {
            background-color: rgba(255, 70, 85, 0.2);
            color: var(--primary);
        }
        
        @media (max-width: 768px) {
            .admin-tabs .nav-link {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
            
            .admin-card .card-text {
                font-size: 1.4rem;
            }
            
            .table-responsive {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a href="../../src/index.php" class="logo">
                    <img src="../../assets/images/logo.svg" alt="SKYNOXX FF Logo" class="brand-logo-img">
                </a>
                <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" id="adminMenu" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-2"></i>Admin
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="adminMenu">
                            <li><a class="dropdown-item" href="../admin/analytics_dashboard.php"><i class="fas fa-chart-line me-2"></i>Analytics Dashboard</a></li>
                            <li><a class="dropdown-item" href="../admin/payment_management.php"><i class="fas fa-credit-card me-2"></i>Payment Management</a></li>
                            <li><a class="dropdown-item" href="../admin/admin_withdrawals.php"><i class="fas fa-money-check-alt me-2"></i>Withdrawal Management</a></li>
                            <li><a class="dropdown-item" href="../admin/wallet_deposits.php"><i class="fas fa-wallet me-2"></i>Wallet Deposits</a></li>
                            <li>
                                <a class="dropdown-item d-flex justify-content-between align-items-center" href="../admin/wallet_topup_requests.php">
                                    <span><i class="fas fa-receipt me-2"></i>Manual Top-ups</span>
                                    <?php if ($pending_topups > 0): ?>
                                        <span class="badge bg-danger ms-2"><?php echo $pending_topups; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li><a class="dropdown-item" href="../admin/admin_wallet.php"><i class="fas fa-piggy-bank me-2"></i>Admin Wallet</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><a class="dropdown-item" href="../../src/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container py-4">
        <h1 class="mb-4" style="font-weight:800; letter-spacing:0.5px;">Admin Dashboard</h1>

        <?php if ($creator_message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($creator_message_type); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($creator_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Top tabs matching the provided design -->
        <ul class="nav admin-tabs mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-create" data-bs-toggle="tab" data-bs-target="#pane-create" type="button" role="tab" aria-controls="pane-create" aria-selected="true">Create Creator</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-creators" data-bs-toggle="tab" data-bs-target="#pane-creators" type="button" role="tab" aria-controls="pane-creators" aria-selected="false">Creators</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-players" data-bs-toggle="tab" data-bs-target="#pane-players" type="button" role="tab" aria-controls="pane-players" aria-selected="false">Players</button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Create Creator Pane -->
            <div class="tab-pane fade show active" id="pane-create" role="tabpanel" aria-labelledby="tab-create">
                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="card admin-card shadow-sm">
                            <div class="card-header bg-transparent border-0 pb-0">
                                <h5 class="card-title">Create New Creator</h5>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <input type="hidden" name="create_creator" value="1">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Name</label>
                                            <input type="text" name="c_name" class="form-control" placeholder="Creator full name" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="c_email" class="form-control" placeholder="creator@example.com" required>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Mobile No</label>
                                            <input type="text" name="c_mobile" class="form-control" placeholder="10-digit mobile no" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Game UID</label>
                                            <input type="text" name="c_game_uid" class="form-control" placeholder="FF Game UID (for login)">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">YouTube Channel Name</label>
                                            <input type="text" name="c_yt" class="form-control" placeholder="Channel name (optional)">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">YouTube Channel Link</label>
                                            <input type="url" name="c_yt_link" class="form-control" placeholder="https://youtube.com/@channel (optional)">
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary px-4">
                                            <i class="fas fa-plus-circle me-2"></i>Create Creator
                                        </button>
                                    </div>
                                </form>
                                <small class="text-muted d-block mt-3">
                                    <i class="fas fa-info-circle me-1"></i> 
                                    Note: Creators log in using Email + Game UID. A secure random password is stored but not used.
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Quick stats tiles to the right -->
                    <div class="col-lg-5">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="card admin-card stats-card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-2">
                                            <i class="fas fa-users fa-2x text-primary"></i>
                                        </div>
                                        <h6 class="card-title">Total Users</h6>
                                        <div class="card-text"><?php echo number_format($total_users); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="card admin-card stats-card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-2">
                                            <i class="fas fa-trophy fa-2x text-primary"></i>
                                        </div>
                                        <h6 class="card-title">Tournaments</h6>
                                        <div class="card-text"><?php echo number_format($total_tournaments); ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-sm-6">
                                <div class="card admin-card stats-card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-2">
                                            <i class="fas fa-gamepad fa-2x text-primary"></i>
                                        </div>
                                        <h6 class="card-title">Active Creators</h6>
                                        <div class="card-text"><?php echo number_format($active_creators); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="card admin-card stats-card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-2">
                                            <i class="fas fa-piggy-bank fa-2x text-primary"></i>
                                        </div>
                                        <h6 class="card-title">Admin Profit (Total)</h6>
                                        <div class="card-text">₹<?php echo number_format((float)$admin_profit_total, 2); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h5 mb-0" style="font-weight:700;">Recent Announcements</h3>
                        <button class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-plus me-1"></i> New Announcement
                        </button>
                    </div>
                    <?php if ($announcements && mysqli_num_rows($announcements) > 0): ?>
                        <?php while ($announcement = mysqli_fetch_assoc($announcements)): ?>
                            <div class="announcement-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong><?php echo htmlspecialchars($announcement['title']); ?></strong>
                                        <p class="mb-1 mt-2"><?php echo htmlspecialchars($announcement['message']); ?></p>
                                        <small class="text-muted">Posted on <?php echo date('Y-m-d H:i', strtotime($announcement['created_at'])); ?></small>
                                    </div>
                                    <span class="badge badge-custom">New</span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="announcement-item">
                            <p class="text-muted mb-0">No announcements yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Creators Pane -->
            <div class="tab-pane fade" id="pane-creators" role="tabpanel" aria-labelledby="tab-creators">
                <div class="card admin-card mb-4">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            <h5 class="card-title mb-0">Creators Management</h5>
                            <div class="search-box w-100" style="max-width: 400px;">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchCreators" class="form-control" placeholder="Search creators (name, email, mobile, UID, channel)">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card admin-card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-dark table-striped table-hover align-middle mb-0" id="creatorsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Mobile</th>
                                        <th>Game UID</th>
                                        <th>YouTube</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($creators)): ?>
                                        <?php foreach ($creators as $c): ?>
                                            <tr>
                                                <td><?php echo (int)$c['id']; ?></td>
                                                <td><?php echo htmlspecialchars($c['name']); ?></td>
                                                <td><?php echo htmlspecialchars($c['email']); ?></td>
                                                <td><?php echo htmlspecialchars($c['mobile_no']); ?></td>
                                                <td><?php echo htmlspecialchars($c['game_uid'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($c['yt_channel_name'] ?? '-'); ?></td>
                                                <td><?php echo isset($c['created_at']) ? date('Y-m-d', strtotime($c['created_at'])) : '-'; ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="view_creator_profile.php?user_id=<?php echo (int)$c['user_id']; ?>" 
                                                           class="btn btn-outline-primary" 
                                                           title="View Creator Profile">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <button class="btn btn-outline-secondary" title="Edit"><i class="fas fa-edit"></i></button>
                                                        <button class="btn btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">No creators found. Create one using the "Create Creator" tab.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Players Pane -->
            <div class="tab-pane fade" id="pane-players" role="tabpanel" aria-labelledby="tab-players">
                <div class="card admin-card mb-4">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            <h5 class="card-title mb-0">Players Management</h5>
                            <div class="search-box w-100" style="max-width: 400px;">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchPlayers" class="form-control" placeholder="Search players">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card admin-card">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 border-bottom border-secondary">
                            <h6 class="mb-0">All Players</h6>
                            <span class="badge badge-custom">Latest 200</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-dark table-striped table-hover align-middle mb-0" id="playersTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($players && mysqli_num_rows($players) > 0): ?>
                                        <?php while ($p = mysqli_fetch_assoc($players)): ?>
                                            <tr>
                                                <td><?php echo (int)$p['id']; ?></td>
                                                <td><?php echo htmlspecialchars($p['name'] ?: '-'); ?></td>
                                                <td><?php echo htmlspecialchars($p['email']); ?></td>
                                                <td><?php echo htmlspecialchars($p['phone'] ?: '-'); ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($p['joined_at'])); ?></td>
                                                <td>
                                                    <a href="view_player_profile.php?user_id=<?php echo (int)$p['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       title="View Player Profile">
                                                        <i class="fas fa-user me-1"></i>View Details
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">No players found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple client-side filter for creators table
        (function(){
            const input = document.getElementById('searchCreators');
            if (!input) return;
            input.addEventListener('input', function(){
                const val = this.value.toLowerCase();
                const rows = document.querySelectorAll('#creatorsTable tbody tr');
                rows.forEach(r => {
                    const text = r.innerText.toLowerCase();
                    r.style.display = text.includes(val) ? '' : 'none';
                });
            });
        })();
        
        // Simple client-side filter for players table
        (function(){
            const input = document.getElementById('searchPlayers');
            if (!input) return;
            input.addEventListener('input', function(){
                const val = this.value.toLowerCase();
                const rows = document.querySelectorAll('#playersTable tbody tr');
                rows.forEach(r => {
                    const text = r.innerText.toLowerCase();
                    r.style.display = text.includes(val) ? '' : 'none';
                });
            });
        })();
        
        // Add active state to cards on click
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.admin-card');
            cards.forEach(card => {
                card.addEventListener('click', function() {
                    cards.forEach(c => c.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        });
    </script>
</body>
</html>