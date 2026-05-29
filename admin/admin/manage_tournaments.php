<?php
session_start();
include('../src/db.php');
include('../src/auth.php');

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../src/login.php');
    exit();
}

// Fetch tournaments from the database
$tournaments = [];
$query = "SELECT * FROM tournaments";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $tournaments[] = $row;
    }
}

// Handle delete tournament request (safe cancel if references exist)
if (isset($_POST['delete'])) {
    $tournament_id = (int)($_POST['tournament_id'] ?? 0);
    // Check for registrations and wallet transactions referencing this tournament
    $regCount = 0; $txCount = 0;
    if ($st1 = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM registrations WHERE tournament_id = ?")) {
        mysqli_stmt_bind_param($st1, 'i', $tournament_id);
        mysqli_stmt_execute($st1);
        $res1 = mysqli_stmt_get_result($st1);
        if ($row = mysqli_fetch_assoc($res1)) { $regCount = (int)$row['c']; }
        mysqli_stmt_close($st1);
    }
    if ($st2 = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM wallet_transactions WHERE tournament_id = ?")) {
        mysqli_stmt_bind_param($st2, 'i', $tournament_id);
        mysqli_stmt_execute($st2);
        $res2 = mysqli_stmt_get_result($st2);
        if ($row = mysqli_fetch_assoc($res2)) { $txCount = (int)$row['c']; }
        mysqli_stmt_close($st2);
    }

    if ($regCount > 0 || $txCount > 0) {
        // Refund all paid entries, then cancel the tournament
        // Get entry_fee and creator id
        $entry_fee = 0.0; $creator_id = 0;
        if ($tq = mysqli_prepare($conn, "SELECT entry_fee, created_by FROM tournaments WHERE id = ? LIMIT 1")) {
            mysqli_stmt_bind_param($tq, 'i', $tournament_id);
            mysqli_stmt_execute($tq);
            $tr = mysqli_stmt_get_result($tq);
            if ($row = mysqli_fetch_assoc($tr)) { $entry_fee = (float)$row['entry_fee']; $creator_id = (int)$row['created_by']; }
            mysqli_stmt_close($tq);
        }
        // Collect paid registrations
        $playerIds = [];
        if ($rg = mysqli_prepare($conn, "SELECT player_id FROM registrations WHERE tournament_id = ? AND (payment_status = 'success' OR payment_status = 'paid')")) {
            mysqli_stmt_bind_param($rg, 'i', $tournament_id);
            mysqli_stmt_execute($rg);
            $rr = mysqli_stmt_get_result($rg);
            while ($r = mysqli_fetch_assoc($rr)) { $playerIds[] = (int)$r['player_id']; }
            mysqli_stmt_close($rg);
        }
        if (!empty($playerIds) && $entry_fee > 0) {
            // Check tournament wallet has enough for refunds
            $needed = $entry_fee * count($playerIds);
            $twBal = 0.0;
            @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tournament_wallets (
                tournament_id INT PRIMARY KEY,
                balance DECIMAL(12,2) NOT NULL DEFAULT 0,
                required_prize_total DECIMAL(12,2) NOT NULL DEFAULT 0,
                prize_distributed_total DECIMAL(12,2) NOT NULL DEFAULT 0,
                status ENUM('open','settled','cancelled') NOT NULL DEFAULT 'open',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_tw_tournament FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            if ($twq = mysqli_prepare($conn, "SELECT balance FROM tournament_wallets WHERE tournament_id = ? AND status = 'open'")) {
                mysqli_stmt_bind_param($twq, 'i', $tournament_id);
                mysqli_stmt_execute($twq);
                $twr = mysqli_stmt_get_result($twq);
                if ($twrow = mysqli_fetch_assoc($twr)) { $twBal = (float)$twrow['balance']; }
                mysqli_stmt_close($twq);
            }
            if ($twBal < $needed) {
                // Not enough funds in tournament wallet; do not cancel
                header('Location: manage_tournaments.php');
                exit();
            }
            // Begin transaction
            mysqli_begin_transaction($conn);
            $ok = true;
            foreach ($playerIds as $pid) {
                // Deduct tournament wallet
                if ($ded = mysqli_prepare($conn, "UPDATE tournament_wallets SET balance = balance - ? WHERE tournament_id = ? AND status = 'open' AND balance >= ?")) {
                    mysqli_stmt_bind_param($ded, 'dii', $entry_fee, $tournament_id, $entry_fee);
                    mysqli_stmt_execute($ded);
                    $affected = mysqli_stmt_affected_rows($ded);
                    mysqli_stmt_close($ded);
                    if ($affected !== 1) { $ok = false; break; }
                } else { $ok = false; break; }
                // Credit player
                if ($cr = mysqli_prepare($conn, "UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?")) {
                    mysqli_stmt_bind_param($cr, 'di', $entry_fee, $pid);
                    mysqli_stmt_execute($cr);
                    mysqli_stmt_close($cr);
                } else { $ok = false; break; }
                // Log transactions (player credit only)
                if ($wtp = mysqli_prepare($conn, "INSERT INTO wallet_transactions (user_id, type, amount, related_user_id, tournament_id, description, status) VALUES (?, 'credit', ?, ?, ?, ?, 'completed')")) {
                    $descP = 'Refund for cancelled tournament #' . $tournament_id;
                    mysqli_stmt_bind_param($wtp, 'idiis', $pid, $entry_fee, $creator_id, $tournament_id, $descP);
                    mysqli_stmt_execute($wtp);
                    mysqli_stmt_close($wtp);
                }
                // Mark registration refunded
                if ($rup = mysqli_prepare($conn, "UPDATE registrations SET payment_status = 'refunded' WHERE tournament_id = ? AND player_id = ?")) {
                    mysqli_stmt_bind_param($rup, 'ii', $tournament_id, $pid);
                    mysqli_stmt_execute($rup);
                    mysqli_stmt_close($rup);
                }
            }
            if ($ok) {
                // Cancel tournament
                if ($up = mysqli_prepare($conn, "UPDATE tournaments SET status = 'cancelled' WHERE id = ?")) {
                    mysqli_stmt_bind_param($up, 'i', $tournament_id);
                    mysqli_stmt_execute($up);
                    mysqli_stmt_close($up);
                } else { $ok = false; }
            }
            if ($ok) { mysqli_commit($conn); } else { mysqli_rollback($conn); }
        } else {
            // No paid entries, just cancel
            if ($up = mysqli_prepare($conn, "UPDATE tournaments SET status = 'cancelled' WHERE id = ?")) {
                mysqli_stmt_bind_param($up, 'i', $tournament_id);
                mysqli_stmt_execute($up);
                mysqli_stmt_close($up);
            }
        }
    } else {
        // Safe to delete
        $delete_query = "DELETE FROM tournaments WHERE id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, 'i', $tournament_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    header('Location: manage_tournaments.php');
    exit();
}

// Handle edit tournament request
if (isset($_POST['edit'])) {
    $tournament_id = $_POST['tournament_id'];
    header("Location: edit_tournament.php?id=$tournament_id");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Free Fire Tournament Platform</title>
    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/mobile-responsive.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary:#ff4655; --primary-dark:#e03e4c; --secondary:#0f1923; --text:#ece8e1; --text-muted:#b8b3ad; }
        body { font-family:'Montserrat',sans-serif; background:linear-gradient(135deg,var(--secondary) 0%, #0a0f17 100%); color:var(--text); min-height:100vh; }
        .gaming-navbar { background:rgba(15,25,35,0.85); backdrop-filter:blur(10px); border-bottom:1px solid rgba(255,70,85,0.2); padding:.5rem 0; position:sticky; top:0; z-index:1000; }
        .navbar-brand { font-family:'Orbitron',sans-serif; font-weight:800; font-size:1.3rem; color:var(--text); text-transform:uppercase; letter-spacing:1px; display:flex; align-items:center; }
        .navbar-brand span { color:var(--primary); }
        .brand-logo-img { width:48px; height:48px; margin-right:10px; border-radius:6px; object-fit:contain; }
        .nav-link { color:var(--text-muted); font-weight:500; margin:0 .3rem; padding:.4rem .6rem!important; font-size:.9rem; position:relative; }
        .nav-link:hover, .nav-link.active { color:var(--text); }
        .btn-gaming { background:linear-gradient(135deg,var(--primary) 0%, var(--primary-dark) 100%); border:none; color:#fff; font-weight:600; padding:.4rem 1rem; border-radius:4px; }
        .btn-gaming-outline { background:transparent; border:2px solid var(--primary); color:var(--primary); font-weight:600; padding:.4rem 1rem; border-radius:4px; }
        .profile-avatar { width:44px; height:44px; border-radius:50%!important; display:inline-flex; align-items:center; justify-content:center; font-weight:700; color:#fff; background:linear-gradient(135deg,#ff4655,#e03e4c); }
        .profile-menu { position:absolute; right:0; top:54px; min-width:260px; display:none; flex-direction:column; gap:.25rem; padding:.5rem; border-radius:8px; box-shadow:0 10px 30px rgba(0,0,0,.4); background:linear-gradient(180deg, rgba(26,43,60,.98), rgba(15,25,35,.98)); border:1px solid rgba(255,255,255,.04); transform-origin:top right; opacity:0; transform:translateY(-6px) scale(.98); transition:opacity 180ms ease, transform 180ms ease; z-index:1100; pointer-events:none; }
        .profile-menu.show { display:flex; opacity:1; transform:translateY(0) scale(1); pointer-events:auto; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg gaming-navbar">
        <div class="container">
            <a class="navbar-brand" href="../login.php">
                <img src="../../assets/images/logo.svg" alt="SKYNOXX Logo" class="brand-logo-img">
                <img src="../../assets/images/logo.svg" alt="Free Fire" style="height:28px; margin-left:8px; object-fit:contain; display:inline-block;">
            </a>
            <div class="ms-auto">
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
                    <div class="position-relative">
                        <button id="profileBtn" class="profile-btn" type="button" aria-haspopup="true" aria-expanded="false">
                            <span class="profile-avatar" role="img" aria-label="User avatar"><?php echo htmlspecialchars($initials ?: 'U'); ?></span>
                        </button>
                        <div id="profileMenu" class="profile-menu">
                            <div class="profile-header">
                                <div class="d-flex align-items-center gap-2 px-2">
                                    <div class="profile-avatar" style="width:48px;height:48px;font-size:16px;"><?php echo htmlspecialchars($initials ?: 'U'); ?></div>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($user_name ?: 'User'); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($role); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group list-group-flush mt-2">
                                <a href="../admin/admin_dashboard.php" class="list-group-item list-group-item-action"><i class="fas fa-home me-2"></i>Dashboard</a>
                                <a href="../admin/analytics_dashboard.php" class="list-group-item list-group-item-action"><i class="fas fa-chart-line me-2"></i>Analytics</a>
                                <a href="../admin/payment_management.php" class="list-group-item list-group-item-action"><i class="fas fa-credit-card me-2"></i>Payments</a>
                                <a href="../admin/admin_withdrawals.php" class="list-group-item list-group-item-action"><i class="fas fa-money-check-alt me-2"></i>Withdrawals</a>
                                <a href="../admin/wallet_deposits.php" class="list-group-item list-group-item-action"><i class="fas fa-wallet me-2"></i>Wallet Deposits</a>
                                <a href="../player/profile_details.php" class="list-group-item list-group-item-action">View all details</a>
                                <a href="../../src/change_password.php" data-change-password="1" class="list-group-item list-group-item-action">Change password</a>
                                <a href="../../src/logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <script src="../../assets/js/header.js"></script>

<div class="container">
    <h1>Manage Tournaments</h1>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Description</th>
                <th>Entry Fee</th>
                <th>Prize Pool</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tournaments as $tournament): ?>
                <tr>
                    <td><?php echo $tournament['id']; ?></td>
                    <td><?php echo $tournament['title']; ?></td>
                    <td><?php echo $tournament['description']; ?></td>
                    <td><?php echo $tournament['entry_fee']; ?></td>
                    <td><?php echo $tournament['prize_pool']; ?></td>
                    <td><?php echo $tournament['status']; ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="tournament_id" value="<?php echo $tournament['id']; ?>">
                            <button type="submit" name="edit" class="btn btn-warning">Edit</button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="tournament_id" value="<?php echo $tournament['id']; ?>">
                            <button type="submit" name="delete" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include('../src/includes/footer.php'); ?>