<?php
session_start();
include '../src/db.php';
include '../src/auth.php';

// Check if the user is logged in and is a creator
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'creator') {
    header('Location: ../../src/login.php');
    exit();
}

$tournament_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($tournament_id <= 0) {
    header('Location: creator_dashboard.php');
    exit();
}

// Fetch tournament ensuring ownership by current creator

$creator_id = (int)$_SESSION['user_id'];
$tournament = null;
if ($stmt = $conn->prepare("SELECT id, title, description, entry_fee, prize_pool, max_players, match_type, map_name, date, time, status, banner FROM tournaments WHERE id = ? AND created_by = ? LIMIT 1")) {
    $stmt->bind_param('ii', $tournament_id, $creator_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $tournament = $res->fetch_assoc();
    $stmt->close();
}

if (!$tournament) {
    header('Location: creator_dashboard.php');
    exit();
}

// Fetch creator wallet balance (used for tournament top-ups)
$creator_wallet_balance = 0.0;
if ($cwb = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ? LIMIT 1")) {
    $cwb->bind_param('i', $creator_id);
    $cwb->execute();
    $cwbr = $cwb->get_result();
    if ($cwbr && ($row = $cwbr->fetch_assoc())) {
        $creator_wallet_balance = (float)$row['wallet_balance'];
    }
    $cwb->close();
}

// Fetch participants with player profile details and registration id
$participants = [];
if ($ps = $conn->prepare("SELECT 
    r.id as registration_id,
    r.team_name,
    u.id as player_id,
    u.name, 
    u.email, 
    u.phone,
    p.in_game_name,
    p.game_uid,
    u.joined_at as member_since,
    r.payment_status, 
    r.slot_no,
    r.prize_won,
    r.joined_at
FROM registrations r 
INNER JOIN users u ON u.id = r.player_id 
LEFT JOIN players_profile p ON p.user_id = u.id 
WHERE r.tournament_id = ? 
ORDER BY r.joined_at DESC")) {
    $ps->bind_param('i', $tournament_id);
    $ps->execute();
    $result = $ps->get_result();
    $participants = $result->fetch_all(MYSQLI_ASSOC);
    $ps->close();
}

// NEW: Load team members for each registration
$teamMembers = [];
$teamCheck = $conn->query("SHOW TABLES LIKE 'team_registrations'");
if ($teamCheck && $teamCheck->num_rows > 0) {
    // Get all team members for this tournament
    $teamQuery = "SELECT 
        tr.registration_id,
        tr.user_id,
        tr.role,
        tr.position_index,
        u.name,
        pp.in_game_name,
        pp.game_uid,
        pp.avatar
    FROM team_registrations tr
    JOIN users u ON tr.user_id = u.id
    LEFT JOIN players_profile pp ON pp.user_id = tr.user_id
    WHERE tr.registration_id IN (SELECT id FROM registrations WHERE tournament_id = ?)
    ORDER BY tr.registration_id, tr.role DESC, tr.position_index";
    
    if ($tm = $conn->prepare($teamQuery)) {
        $tm->bind_param('i', $tournament_id);
        $tm->execute();
        $tmResult = $tm->get_result();
        while ($tmRow = $tmResult->fetch_assoc()) {
            $regId = $tmRow['registration_id'];
            if (!isset($teamMembers[$regId])) {
                $teamMembers[$regId] = [];
            }
            $teamMembers[$regId][] = $tmRow;
        }
        $tm->close();
    }
}

// Legacy support: Old team members table (deprecated)
$teammateStmt = null;
$teamTableExists = false;
$res = $conn->query("SHOW TABLES LIKE 'registration_team_members'");
if ($res && $res->num_rows > 0) {
    $teamTableExists = true;
    $teammateStmt = $conn->prepare("SELECT member_name, member_uid, member_index FROM registration_team_members WHERE registration_id = ? ORDER BY member_index ASC");
}

// Ensure tournament wallet exists and load wallet info
@$conn->query("CREATE TABLE IF NOT EXISTS tournament_wallets (
    tournament_id INT PRIMARY KEY,
    balance DECIMAL(12,2) NOT NULL DEFAULT 0,
    required_prize_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    prize_distributed_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('open','settled','cancelled') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tw_tournament FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Create wallet row if missing
if ($wi = $conn->prepare("INSERT IGNORE INTO tournament_wallets (tournament_id, balance, required_prize_total, prize_distributed_total, status) VALUES (?, 0, ?, 0, 'open')")) {
    $reqPrize = (float)($tournament['prize_pool'] ?? 0);
    $wi->bind_param('id', $tournament_id, $reqPrize);
    $wi->execute();
    $wi->close();
}

// Sync required_prize_total with current tournament prize_pool when wallet is open
if ($su = $conn->prepare("UPDATE tournament_wallets SET required_prize_total = ? WHERE tournament_id = ? AND status = 'open'")) {
    $currentPrizePool = (float)($tournament['prize_pool'] ?? 0);
    $su->bind_param('di', $currentPrizePool, $tournament_id);
    $su->execute();
    $su->close();
}

$wallet = ['balance'=>0.0,'required_prize_total'=>0.0,'prize_distributed_total'=>0.0,'status'=>'open'];
if ($wq = $conn->prepare("SELECT balance, required_prize_total, prize_distributed_total, status FROM tournament_wallets WHERE tournament_id = ? LIMIT 1")) {
    $wq->bind_param('i', $tournament_id);
    $wq->execute();
    $wr = $wq->get_result();
    if ($row = $wr->fetch_assoc()) { $wallet = $row; }
    $wq->close();
}

// Fetch recent wallet transactions for this tournament (for history section)
$transactions = [];
if ($tx = $conn->prepare("SELECT wt.id, wt.user_id, u.name as user_name, wt.type, wt.amount, wt.status, wt.description, wt.created_at
    FROM wallet_transactions wt
    INNER JOIN users u ON u.id = wt.user_id
    WHERE wt.tournament_id = ?
    ORDER BY wt.created_at DESC, wt.id DESC
    LIMIT 25")) {
    $tx->bind_param('i', $tournament_id);
    $tx->execute();
    $tr = $tx->get_result();
    if ($tr) { $transactions = $tr->fetch_all(MYSQLI_ASSOC); }
    $tx->close();
}

// Handle profit settlement (80/20 split: creator/admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'settle_profit') {
    $conn->begin_transaction();
    try {
        // Lock wallet
        $lw = $conn->prepare("SELECT balance, required_prize_total, prize_distributed_total, status FROM tournament_wallets WHERE tournament_id = ? FOR UPDATE");
        $lw->bind_param('i', $tournament_id);
        $lw->execute();
        $res = $lw->get_result();
        $w = $res->fetch_assoc();
        $lw->close();

        if (!$w) { throw new Exception('Wallet not found'); }
        $balance = (float)$w['balance'];
        $required = (float)$w['required_prize_total'];
        $distributed = (float)$w['prize_distributed_total'];
        $status = $w['status'];

        if ($status !== 'open') { throw new Exception('Wallet is not open for settlement'); }
        if ($distributed + 0.0001 < $required) { // allow tiny float tolerance
            $short = max(0, $required - $distributed);
            throw new Exception('Prizes not fully distributed. Remaining: ₹' . number_format($short, 2));
        }
        if ($balance <= 0) { throw new Exception('No remaining balance to settle'); }

        // Get creator id (owner)
        $creator_id_for_settle = (int)$creator_id; // already validated as owner

        // Find admin id by ADMIN_EMAIL or first admin role
        $admin_id = 0;
        if (defined('ADMIN_EMAIL')) {
            if ($sa = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1")) {
                $email = ADMIN_EMAIL;
                $sa->bind_param('s', $email);
                $sa->execute();
                $r = $sa->get_result();
                if ($row = $r->fetch_assoc()) { $admin_id = (int)$row['id']; }
                $sa->close();
            }
        }
        if ($admin_id <= 0) {
            $qr = $conn->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
            if ($qr && ($row = $qr->fetch_assoc())) { $admin_id = (int)$row['id']; }
        }
        if ($admin_id <= 0) { throw new Exception('Admin account not found'); }

        // Compute 80/20 split (handle rounding)
        $creatorShare = floor($balance * 0.80 * 100) / 100.0;
        $adminShare = round($balance - $creatorShare, 2);

        // Credit creator and admin
        if ($uc = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?")) {
            $uc->bind_param('di', $creatorShare, $creator_id_for_settle);
            $uc->execute();
            $uc->close();
        }
        if ($ua = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?")) {
            $ua->bind_param('di', $adminShare, $admin_id);
            $ua->execute();
            $ua->close();
        }

        // Log transactions
        if ($lt = $conn->prepare("INSERT INTO wallet_transactions (user_id, type, amount, status, description, tournament_id, created_at) VALUES (?, 'credit', ?, 'completed', 'Tournament profit share', ?, NOW())")) {
            $lt->bind_param('idi', $creator_id_for_settle, $creatorShare, $tournament_id);
            $lt->execute();
            $lt->close();
        }
        if ($lt2 = $conn->prepare("INSERT INTO wallet_transactions (user_id, type, amount, status, description, tournament_id, created_at) VALUES (?, 'credit', ?, 'completed', 'Admin profit share', ?, NOW())")) {
            $lt2->bind_param('idi', $admin_id, $adminShare, $tournament_id);
            $lt2->execute();
            $lt2->close();
        }

        // Zero wallet and mark settled
        if ($zw = $conn->prepare("UPDATE tournament_wallets SET balance = 0, status = 'settled' WHERE tournament_id = ?")) {
            $zw->bind_param('i', $tournament_id);
            $zw->execute();
            $zw->close();
        }

        // Optionally mark tournament completed (no-op if already)
        $conn->query("UPDATE tournaments SET status = 'completed' WHERE id = " . (int)$tournament_id . " AND status <> 'completed'");

        $conn->commit();
        $_SESSION['success_message'] = 'Settlement done. Credited ₹' . number_format($creatorShare,2) . ' to you and ₹' . number_format($adminShare,2) . ' to admin.';
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = 'Settlement failed: ' . $e->getMessage();
    }
    header("Location: view_tournament.php?id=" . $tournament_id);
    exit();
}

// Handle tournament wallet top-up from creator wallet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'topup_tournament') {
    $topup_amount = isset($_POST['topup_amount']) ? (float)$_POST['topup_amount'] : 0;
    if ($topup_amount <= 0) {
        $_SESSION['error_message'] = 'Enter a valid top-up amount.';
        header('Location: view_tournament.php?id=' . $tournament_id);
        exit();
    }

    $conn->begin_transaction();
    try {
        // Lock creator wallet
        if ($lc = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE")) {
            $lc->bind_param('i', $creator_id);
            $lc->execute();
            $lcr = $lc->get_result();
            $creatorBal = 0.0;
            if ($lcr && ($row = $lcr->fetch_assoc())) { $creatorBal = (float)$row['wallet_balance']; }
            $lc->close();
            if ($creatorBal + 0.0001 < $topup_amount) { throw new Exception('Insufficient balance in your wallet.'); }
        } else { throw new Exception('Unable to lock creator wallet.'); }

        // Lock tournament wallet
        if ($ltw = $conn->prepare("SELECT balance FROM tournament_wallets WHERE tournament_id = ? FOR UPDATE")) {
            $ltw->bind_param('i', $tournament_id);
            $ltw->execute();
            $ltw->get_result();
            $ltw->close();
        } else { throw new Exception('Unable to lock tournament wallet.'); }

        // Deduct from creator wallet
        if ($ud = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?")) {
            $ud->bind_param('di', $topup_amount, $creator_id);
            $ud->execute();
            $ud->close();
        } else { throw new Exception('Failed to update creator wallet.'); }

        // Credit tournament wallet
        if ($ctw = $conn->prepare("UPDATE tournament_wallets SET balance = balance + ? WHERE tournament_id = ?")) {
            $ctw->bind_param('di', $topup_amount, $tournament_id);
            $ctw->execute();
            $ctw->close();
        } else { throw new Exception('Failed to credit tournament wallet.'); }

        // Log in wallet transactions (as a debit for the creator), tied to this tournament
        if ($wlog = $conn->prepare("INSERT INTO wallet_transactions (user_id, type, amount, status, description, tournament_id, created_at) VALUES (?, 'debit', ?, 'completed', 'Top-up tournament wallet', ?, NOW())")) {
            $wlog->bind_param('idi', $creator_id, $topup_amount, $tournament_id);
            $wlog->execute();
            $wlog->close();
        }

        $conn->commit();
        $_SESSION['success_message'] = 'Added ₹' . number_format($topup_amount, 2) . ' to the tournament wallet.';
    } catch (Exception $ex) {
        $conn->rollback();
        $_SESSION['error_message'] = 'Top-up failed: ' . $ex->getMessage();
    }
    header('Location: view_tournament.php?id=' . $tournament_id);
    exit();
}

// Handle prize payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pay_prize') {
    // Support selecting from dropdown if direct fields are empty
    $registration_id = isset($_POST['registration_id']) ? (int)$_POST['registration_id'] : 0;
    $registration_id_select = isset($_POST['registration_id_select']) ? (int)$_POST['registration_id_select'] : 0;
    if ($registration_id <= 0 && $registration_id_select > 0) { $registration_id = $registration_id_select; }
    $prize_amount = isset($_POST['prize_amount']) ? (float)$_POST['prize_amount'] : 0;
    $player_id = isset($_POST['player_id']) ? (int)$_POST['player_id'] : 0;

    // If player_id is not provided, look it up by registration
    if ($player_id <= 0 && $registration_id > 0) {
        if ($gp = $conn->prepare("SELECT player_id FROM registrations WHERE id = ? AND tournament_id = ? LIMIT 1")) {
            $gp->bind_param('ii', $registration_id, $tournament_id);
            $gp->execute();
            $gr = $gp->get_result();
            if ($row = $gr->fetch_assoc()) { $player_id = (int)$row['player_id']; }
            $gp->close();
        }
    }

    if ($registration_id > 0 && $prize_amount > 0 && $player_id > 0) {
        $conn->begin_transaction();
        try {
            // Deduct from tournament wallet if enough
            if ($wd = $conn->prepare("UPDATE tournament_wallets SET balance = balance - ? WHERE tournament_id = ? AND status = 'open' AND balance >= ?")) {
                $wd->bind_param('did', $prize_amount, $tournament_id, $prize_amount);
                $wd->execute();
                $affected = $wd->affected_rows;
                $wd->close();
            } else { throw new Exception('Failed to prepare wallet debit'); }
            if ($affected !== 1) {
                $conn->rollback();
                $_SESSION['error_message'] = 'Insufficient tournament wallet balance.';
            } else {
                // Credit player
                if ($cr = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?")) {
                    $cr->bind_param('di', $prize_amount, $player_id);
                    $cr->execute();
                    $cr->close();
                }
                // Update prize_won (accumulate)
                if ($up = $conn->prepare("UPDATE registrations SET prize_won = COALESCE(prize_won,0) + ? WHERE id = ? AND tournament_id = ?")) {
                    $up->bind_param('dii', $prize_amount, $registration_id, $tournament_id);
                    $up->execute();
                    $up->close();
                }
                // Log player credit
                if ($wt = $conn->prepare("INSERT INTO wallet_transactions (user_id, type, amount, status, description, tournament_id, created_at) VALUES (?, 'prize_win', ?, 'completed', 'Prize won in tournament', ?, NOW())")) {
                    $wt->bind_param('idi', $player_id, $prize_amount, $tournament_id);
                    $wt->execute();
                    $wt->close();
                }
                // Update distributed total
                if ($dt = $conn->prepare("UPDATE tournament_wallets SET prize_distributed_total = prize_distributed_total + ? WHERE tournament_id = ?")) {
                    $dt->bind_param('di', $prize_amount, $tournament_id);
                    $dt->execute();
                    $dt->close();
                }
                $conn->commit();
                $_SESSION['success_message'] = 'Prize of ₹' . number_format($prize_amount, 2) . ' paid successfully from tournament wallet!';
            }
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = 'Error processing payment: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = 'Invalid payment details.';
    }
    header("Location: view_tournament.php?id=" . $tournament_id);
    exit();
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
    <title>Tournament Details - <?php echo htmlspecialchars($tournament['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar gaming-navbar" style="padding:0;">
        <div class="container-fluid d-flex align-items-center justify-content-between" style="padding:0 24px;">
            <div class="d-flex align-items-center">
                <img src="../../assets/images/logo.svg" alt="SKYNOXX FF Logo" style="height:48px; width:auto; margin-right:10px;">
            </div>
           
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                
                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Tournament Header -->
                <div class="modern-card animate-card mb-4">
                    <?php if (!empty($tournament['banner'])): ?>
                        <div style="width:100%;height:220px;overflow:hidden;border-top-left-radius:20px;border-top-right-radius:20px;">
                           <img src="../<?php echo htmlspecialchars($tournament['banner']); ?>" alt="Tournament Banner" style="width:100%;height:100%;object-fit:cover;display:block;">
                        </div>
                    <?php endif; ?>
                    <div class="card-header-modern d-flex align-items-center justify-content-between flex-wrap">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-trophy-fill fs-1" style="color: var(--primary);"></i>
                            <div>
                                <h1 class="mb-1"><?php echo htmlspecialchars($tournament['title']); ?></h1>
                                <p class="mb-0 opacity-75">Tournament Management</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                            <span class="status-badge badge-<?php echo $tournament['status']; ?>">
                                <?php echo htmlspecialchars(ucfirst($tournament['status'])); ?>
                            </span>
                            <a href="creator_dashboard.php" class="btn-outline-modern btn-sm">
                                <i class="bi bi-arrow-left me-2"></i> Back
                            </a>
                        </div>
                    </div>
                    <div class="p-4">
                        <p class="mb-4 fs-5 text-light"><?php echo nl2br(htmlspecialchars($tournament['description'])); ?></p>
                        <!-- Tournament Stats -->
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-value">₹<?php echo htmlspecialchars($tournament['entry_fee']); ?></div>
                                <div class="stat-label">Entry Fee</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value">₹<?php echo htmlspecialchars($tournament['prize_pool']); ?></div>
                                <div class="stat-label">Prize Pool</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?php echo htmlspecialchars($tournament['max_players']); ?></div>
                                <div class="stat-label">Max Players</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?php echo count($participants); ?></div>
                                <div class="stat-label">Registered</div>
                            </div>
                        </div>
                        <!-- Tournament Wallet Summary -->
                        <div id="wallet" class="modern-card" style="background: rgba(255,255,255,0.03); border-radius: 16px; padding: 1.25rem; border: 1px solid rgba(255,255,255,0.08);">
                            <div class="d-flex align-items-center justify-content-between flex-wrap">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi bi-wallet2 fs-3" style="color: var(--accent);"></i>
                                    <div>
                                        <h4 class="mb-1">Tournament Wallet</h4>
                                        <div class="text-muted" style="font-size: 0.95rem;">Status: <span class="fw-semibold text-info"><?php echo htmlspecialchars(strtoupper($wallet['status'])); ?></span></div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                                    <button type="button" class="btn-outline-modern btn-sm" onclick="showTopUpModal()" <?php echo $wallet['status'] === 'open' ? '' : 'disabled'; ?>><i class="bi bi-wallet2 me-2"></i>Add Money</button>
                                    <a href="#wallet" class="btn-outline-modern btn-sm"><i class="bi bi-cash-stack me-2"></i>Wallet</a>
                                    <button type="button" class="btn-modern btn-sm" onclick="showPayPrizeModalSelect()"><i class="bi bi-cash-coin me-2"></i>Pay Prize</button>
                                </div>
                            </div>
                            <div class="row mt-3 g-3">
                                <div class="col-6 col-md-3">
                                    <div class="stat-card" style="margin:0;">
                                        <div class="stat-value">₹<?php echo number_format((float)$wallet['balance'], 2); ?></div>
                                        <div class="stat-label">Wallet Balance</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="stat-card" style="margin:0;">
                                        <div class="stat-value">₹<?php echo number_format((float)$wallet['required_prize_total'], 2); ?></div>
                                        <div class="stat-label">Required Prizes</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="stat-card" style="margin:0;">
                                        <div class="stat-value">₹<?php echo number_format((float)$wallet['prize_distributed_total'], 2); ?></div>
                                        <div class="stat-label">Distributed</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="stat-card" style="margin:0;">
                                        <?php $remaining = (float)$wallet['balance']; ?>
                                        <div class="stat-value">₹<?php echo number_format($remaining, 2); ?></div>
                                        <div class="stat-label">Remaining</div>
                                    </div>
                                </div>
                            </div>
                            <?php 
                                $readyToSettle = $wallet['status'] === 'open' && (float)$wallet['balance'] > 0 && (float)$wallet['prize_distributed_total'] + 0.0001 >= (float)$wallet['required_prize_total'];
                                $creatorSharePrev = floor(((float)$wallet['balance']) * 0.80 * 100) / 100.0;
                                $adminSharePrev = round(((float)$wallet['balance']) - $creatorSharePrev, 2);
                            ?>
                            <div class="d-flex align-items-center justify-content-between flex-wrap mt-3 p-3" style="background: rgba(255,255,255,0.04); border: 1px dashed rgba(255,255,255,0.15); border-radius: 12px;">
                                <div class="text-muted">
                                    <div><strong>Settlement Preview:</strong> Creator 80% = ₹<?php echo number_format(max(0,$creatorSharePrev),2); ?>, Admin 20% = ₹<?php echo number_format(max(0,$adminSharePrev),2); ?></div>
                                    <?php if (!$readyToSettle): ?>
                                        <small>Pay all prizes first to enable settlement.</small>
                                    <?php endif; ?>
                                </div>
                                <form method="POST" action="view_tournament.php?id=<?php echo $tournament_id; ?>" class="m-0">
                                    <input type="hidden" name="action" value="settle_profit">
                                    <button type="submit" class="btn btn-sm btn-success" <?php echo $readyToSettle ? '' : 'disabled'; ?> style="border-radius:10px; font-weight:700;">
                                        <i class="bi bi-bank me-1"></i> Settle Profit (80/20)
                                    </button>
                                </form>
                            </div>
                        </div>
                        <!-- Tournament Details -->
                        <div class="row g-4 mt-2">
                            <div class="col-md-6">
                                <div class="detail-item">
                                    <span class="detail-label">Match Type</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($tournament['match_type']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Map</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($tournament['map_name']); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item">
                                    <span class="detail-label">Date</span>
                                    <span class="detail-value"><?php echo htmlspecialchars(date('M d, Y', strtotime($tournament['date']))); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Time</span>
                                    <span class="detail-value"><?php echo htmlspecialchars(date('H:i', strtotime($tournament['time']))); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Participants Section -->
                <div class="modern-card animate-card">
                    <div class="card-header-modern">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-people-fill fs-2" style="color: var(--accent);"></i>
                                <div>
                                    <h3 class="mb-0">Registered Players</h3>
                                    <p class="mb-0 opacity-75">
                                        <?php echo count($participants); ?> out of <?php echo htmlspecialchars($tournament['max_players']); ?> slots filled
                                        (<?php echo round((count($participants) / $tournament['max_players']) * 100); ?>%)
                                    </p>
                                </div>
                            </div>
                            <span class="status-badge mt-2 mt-md-0" style="background: rgba(0, 245, 255, 0.2); color: var(--accent);">
                                <?php echo count($participants); ?> Players
                            </span>
                        </div>
                    </div>
                    
                    <div class="p-4">
                        <?php if (empty($participants)): ?>
                            <div class="empty-state">
                                <i class="bi bi-people"></i>
                                <h4>No Participants Yet</h4>
                                <p>Players haven't registered for this tournament yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="player-grid">
                                <?php foreach ($participants as $index => $participant): 
                                    $player_initials = strtoupper(substr($participant['name'], 0, 2));
                                    $registration_date = date('M d, Y', strtotime($participant['joined_at']));
                                    $in_game_name = !empty($participant['in_game_name']) ? $participant['in_game_name'] : 'Not Set';
                                    $game_uid = !empty($participant['game_uid']) ? $participant['game_uid'] : 'Not Set';
                                    $team_name = $participant['team_name'] ?? '';
                                    $registration_id = (int)$participant['registration_id'];
                                    
                                    // NEW: Get team members from team_registrations
                                    $currentTeam = isset($teamMembers[$registration_id]) ? $teamMembers[$registration_id] : [];
                                    
                                    // LEGACY: Fallback to old team members table
                                    $legacyTeam = [];
                                    if (empty($currentTeam) && $teamTableExists && $teammateStmt) {
                                        $teammateStmt->bind_param('i', $registration_id);
                                        $teammateStmt->execute();
                                        $tmRes = $teammateStmt->get_result();
                                        if ($tmRes) { $legacyTeam = $tmRes->fetch_all(MYSQLI_ASSOC); }
                                    }
                                    
                                    $hasTeam = !empty($currentTeam) || !empty($legacyTeam);
                                ?>
                                    <div class="player-card">
                                        <div class="d-flex align-items-start justify-content-between mb-2">
                                            <div class="d-flex align-items-start">
                                                <div class="player-avatar">
                                                    <?php echo htmlspecialchars($player_initials); ?>
                                                </div>
                                                <div class="player-info">
                                                    <div class="player-name">
                                                        <?php echo htmlspecialchars($participant['name']); ?>
                                                        <?php if (!empty($team_name)): ?>
                                                            <span class="badge bg-info ms-2" style="font-size: 0.7rem;">
                                                                <i class="bi bi-flag-fill"></i> <?php echo htmlspecialchars($team_name); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="player-email">
                                                        <i class="bi bi-envelope me-1"></i>
                                                        <?php echo htmlspecialchars($participant['email']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Pay Prize Button (Top Right) -->
                                            <?php if ($tournament['status'] === 'completed' || $tournament['status'] === 'ongoing'): ?>
                                                <?php if (!empty($participant['prize_won']) && $participant['prize_won'] > 0): ?>
                                                    <button class="btn btn-sm" style="background: rgba(0, 255, 136, 0.2); color: var(--accent-green); border: 2px solid rgba(0, 255, 136, 0.4); font-weight: 600; border-radius: 10px; white-space: nowrap;" disabled>
                                                        <i class="bi bi-check-circle-fill me-1"></i> Paid ₹<?php echo number_format($participant['prize_won'], 2); ?>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm" 
                                                            style="background: var(--gradient-primary); color: white; border: none; font-weight: 700; border-radius: 10px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(255, 70, 85, 0.3); white-space: nowrap;"
                                                            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(255, 70, 85, 0.5)';"
                                                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(255, 70, 85, 0.3)';"
                                                            onclick="showPayPrizeModal(<?php echo $participant['registration_id']; ?>, <?php echo $participant['player_id']; ?>, '<?php echo htmlspecialchars($participant['name'], ENT_QUOTES); ?>')">
                                                        <i class="bi bi-cash-coin me-1"></i> Pay
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="player-details">
                                            <?php if (!empty($currentTeam)): ?>
                                                <div class="detail-item" style="align-items:flex-start; grid-column: 1 / -1;">
                                                    <span class="detail-label"><i class="bi bi-people-fill me-1"></i> Team Roster</span>
                                                    <div class="flex-grow-1">
                                                        <div class="team-roster-list" style="display: grid; gap: 0.5rem;">
                                                            <?php foreach ($currentTeam as $tm): ?>
                                                                <div class="team-member-item" style="background: rgba(0, 245, 255, 0.05); padding: 0.5rem 0.75rem; border-radius: 8px; border-left: 3px solid <?php echo $tm['role'] === 'captain' ? '#ffc107' : '#00f5ff'; ?>;">
                                                                    <div class="d-flex align-items-center justify-content-between">
                                                                        <div>
                                                                            <strong class="text-light"><?php echo htmlspecialchars($tm['name']); ?></strong>
                                                                            <?php if ($tm['role'] === 'captain'): ?>
                                                                                <span class="badge bg-warning text-dark ms-1" style="font-size: 0.65rem;">
                                                                                    <i class="bi bi-star-fill"></i> Captain
                                                                                </span>
                                                                            <?php endif; ?>
                                                                            <div class="small text-muted">
                                                                                IGN: <span class="text-info"><?php echo htmlspecialchars($tm['in_game_name'] ?: 'N/A'); ?></span>
                                                                            </div>
                                                                        </div>
                                                                        <div class="text-end">
                                                                            <div class="small text-muted">UID</div>
                                                                            <strong class="text-accent"><?php echo htmlspecialchars($tm['game_uid'] ?: 'N/A'); ?></strong>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php elseif (!empty($legacyTeam)): ?>
                                                <div class="detail-item" style="align-items:flex-start; gap:8px;">
                                                    <span class="detail-label">Team</span>
                                                    <span class="detail-value" style="flex:1;">
                                                        <ul class="mb-0" style="list-style: none; padding-left: 0;">
                                                            <?php foreach ($legacyTeam as $tm): ?>
                                                                <li>
                                                                    <span class="text-info fw-semibold"><?php echo htmlspecialchars($tm['member_name']); ?></span>
                                                                    <span class="text-muted">(UID: <?php echo htmlspecialchars($tm['member_uid']); ?>)</span>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <div class="detail-item">
                                                    <span class="detail-label">In-Game Name</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars($in_game_name); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Game UID</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars($game_uid); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="detail-item">
                                                <span class="detail-label">Payment</span>
                                                <span class="payment-status payment-paid">
                                                    Paid
                                                </span>
                                            </div>
                                            <div class="detail-item">
                                                <span class="detail-label">Slot</span>
                                                <span class="detail-value">#<?php echo htmlspecialchars($participant['slot_no'] ?? 0); ?></span>
                                            </div>
                                            <?php if (!empty($participant['prize_won']) && $participant['prize_won'] > 0): ?>
                                                <div class="detail-item">
                                                    <span class="detail-label">Prize Won</span>
                                                    <span class="detail-value text-success">₹<?php echo htmlspecialchars($participant['prize_won']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="detail-item">
                                                <span class="detail-label">Registered</span>
                                                <span class="detail-value"><?php echo htmlspecialchars($registration_date); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Transactions Section -->
                <div class="modern-card animate-card mt-4">
                    <div class="card-header-modern d-flex align-items-center justify-content-between flex-wrap">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-receipt fs-2" style="color: var(--primary);"></i>
                            <div>
                                <h3 class="mb-0">Recent Tournament Transactions</h3>
                                <p class="mb-0 opacity-75">Last <?php echo count($transactions); ?> entries</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-0">
                        <?php if (empty($transactions)): ?>
                            <div class="empty-state">
                                <i class="bi bi-receipt"></i>
                                <h4>No Transactions Yet</h4>
                                <p>Prize payouts and settlement transfers will appear here.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-dark table-borderless align-middle mb-0" style="--bs-table-bg: transparent;">
                                    <thead>
                                        <tr style="color: var(--text-secondary);">
                                            <th style="white-space:nowrap;">Date</th>
                                            <th>User</th>
                                            <th>Type</th>
                                            <th class="text-end" style="white-space:nowrap;">Amount (₹)</th>
                                            <th>Description</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($transactions as $t): 
                                            $type = strtolower($t['type']);
                                            $amt = (float)$t['amount'];
                                            $isCredit = in_array($type, ['prize','credit']);
                                            $amountHtml = ($isCredit ? '<span class="text-success fw-bold">+ ' : '<span class="text-danger fw-bold">- ') . number_format($amt, 2) . '</span>';
                                            $badgeClass = ($t['status'] === 'completed') ? 'badge bg-success' : (($t['status'] === 'failed') ? 'badge bg-danger' : 'badge bg-secondary');
                                        ?>
                                            <tr>
                                                <td style="white-space:nowrap;"><?php echo htmlspecialchars(date('M d, Y H:i', strtotime($t['created_at']))); ?></td>
                                                <td><?php echo htmlspecialchars($t['user_name']); ?></td>
                                                <td style="text-transform:capitalize;">
                                                    <?php echo htmlspecialchars($type); ?>
                                                </td>
                                                <td class="text-end"><?php echo $amountHtml; ?></td>
                                                <td><?php echo htmlspecialchars($t['description']); ?></td>
                                                <td><span class="<?php echo $badgeClass; ?>"><?php echo htmlspecialchars($t['status']); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Money to Tournament Wallet Modal -->
    <div class="modal fade" id="topUpModal" tabindex="-1" aria-labelledby="topUpModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: linear-gradient(135deg, rgba(26, 26, 46, 0.98), rgba(34, 34, 58, 0.98)); border: 2px solid rgba(0, 245, 255, 0.3); border-radius: 20px; box-shadow: 0 20px 60px rgba(0, 245, 255, 0.4);">
                <div class="modal-header" style="border-bottom: 2px solid rgba(0, 245, 255, 0.2); padding: 1.5rem;">
                    <h5 class="modal-title fw-bold" id="topUpModalLabel" style="font-size: 1.3rem;">
                        <i class="bi bi-wallet2 me-2" style="color: var(--accent); font-size: 1.5rem;"></i> 
                        <span style="background: var(--gradient-accent); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                            Add Money to Tournament Wallet
                        </span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="view_tournament.php?id=<?php echo $tournament_id; ?>" id="topUpForm">
                    <input type="hidden" name="action" value="topup_tournament">
                    <div class="modal-body" style="padding: 2rem;">
                        <!-- Wallet Balance Info -->
                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <div class="p-3" style="background: rgba(0, 245, 255, 0.1); border: 1px solid rgba(0, 245, 255, 0.3); border-radius: 12px;">
                                    <div class="text-muted small mb-1">Your Wallet</div>
                                    <div class="fw-bold fs-5" style="color: var(--accent);">₹<?php echo number_format($creator_wallet_balance, 2); ?></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3" style="background: rgba(255, 70, 85, 0.1); border: 1px solid rgba(255, 70, 85, 0.3); border-radius: 12px;">
                                    <div class="text-muted small mb-1">Tournament Wallet</div>
                                    <div class="fw-bold fs-5" style="color: var(--primary);">₹<?php echo number_format((float)$wallet['balance'], 2); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Amount Input -->
                        <div class="mb-3">
                            <label for="topup_amount_modal" class="form-label fw-semibold">
                                <i class="bi bi-currency-rupee me-1"></i> Amount to Add
                            </label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text" style="background: rgba(255, 255, 255, 0.05); border: 2px solid rgba(255, 255, 255, 0.1); color: var(--text-primary); font-weight: 700;">₹</span>
                                <input type="number" class="form-control form-control-lg" id="topup_amount_modal" name="topup_amount" 
                                       step="0.01" min="0.01" max="<?php echo $creator_wallet_balance; ?>" 
                                       placeholder="Enter amount" required
                                       style="background: rgba(255, 255, 255, 0.05); border: 2px solid rgba(255, 255, 255, 0.1); color: var(--text-primary); font-size: 1.5rem; font-weight: 700; border-left: none;">
                            </div>
                            <small class="text-muted d-block mt-2">
                                <i class="bi bi-info-circle me-1"></i> Maximum: ₹<?php echo number_format($creator_wallet_balance, 2); ?>
                            </small>
                        </div>

                        <!-- Quick Amount Buttons -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold mb-2">Quick Select</label>
                            <div class="d-flex flex-wrap gap-2">
                                <?php 
                                $quickAmounts = [100, 500, 1000, 2000, 5000];
                                foreach ($quickAmounts as $qa) {
                                    if ($qa <= $creator_wallet_balance) {
                                        echo '<button type="button" class="btn btn-sm btn-outline-modern" onclick="document.getElementById(\'topup_amount_modal\').value = ' . $qa . ';">₹' . number_format($qa) . '</button>';
                                    }
                                }
                                ?>
                                <button type="button" class="btn btn-sm btn-outline-modern" onclick="document.getElementById('topup_amount_modal').value = <?php echo $creator_wallet_balance; ?>;">
                                    Max (₹<?php echo number_format($creator_wallet_balance, 2); ?>)
                                </button>
                            </div>
                        </div>

                        <!-- Info Alert -->
                        <div class="alert" style="background: rgba(0, 245, 255, 0.1); border: 2px solid rgba(0, 245, 255, 0.3); color: var(--accent); border-radius: 12px; padding: 1rem;">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-info-circle-fill me-3" style="font-size: 1.5rem;"></i>
                                <div>
                                    <strong style="display: block; margin-bottom: 0.5rem;">Transaction Details</strong>
                                    <ul class="mb-0" style="padding-left: 1.2rem; font-size: 0.9rem;">
                                        <li>Funds will be transferred from your wallet to tournament wallet</li>
                                        <li>Use these funds to pay prizes to winners</li>
                                        <li>Remaining balance will be split 80/20 (creator/admin) on settlement</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 2px solid rgba(255, 255, 255, 0.1); padding: 1.5rem; justify-content: space-between;">
                        <button type="button" class="btn btn-lg" data-bs-dismiss="modal"
                                style="background: transparent; border: 2px solid rgba(255, 255, 255, 0.2); color: var(--text-primary); border-radius: 12px; padding: 0.75rem 2rem; font-weight: 600;">
                            <i class="bi bi-x-circle me-2"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-lg"
                                style="background: var(--gradient-accent); color: white; border: none; border-radius: 12px; padding: 0.75rem 2.5rem; font-weight: 700; box-shadow: 0 8px 25px rgba(0, 245, 255, 0.4);">
                            <i class="bi bi-wallet2 me-2"></i> Add Money
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Pay Prize Modal -->
    <div class="modal fade" id="payPrizeModal" tabindex="-1" aria-labelledby="payPrizeModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: linear-gradient(135deg, rgba(26, 26, 46, 0.98), rgba(34, 34, 58, 0.98)); border: 2px solid rgba(255, 70, 85, 0.3); border-radius: 20px; box-shadow: 0 20px 60px rgba(255, 70, 85, 0.4);">
                <div class="modal-header" style="border-bottom: 2px solid rgba(255, 70, 85, 0.2); padding: 1.5rem;">
                    <h5 class="modal-title fw-bold" id="payPrizeModalLabel" style="font-size: 1.3rem;">
                        <i class="bi bi-cash-coin me-2" style="color: var(--primary); font-size: 1.5rem;"></i> 
                        <span style="background: var(--gradient-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                            Pay Prize to Winner
                        </span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="view_tournament.php?id=<?php echo $tournament_id; ?>" id="payPrizeForm">
                    <div class="modal-body" style="padding: 2rem;">
                        <input type="hidden" name="action" value="pay_prize">
                        <input type="hidden" name="registration_id" id="prize_registration_id">
                        <input type="hidden" name="player_id" id="prize_player_id">
                        <input type="hidden" id="tw_balance_current" value="<?php echo number_format((float)$wallet['balance'], 2, '.', ''); ?>">
                        <input type="hidden" id="creator_balance_current" value="<?php echo number_format((float)$creator_wallet_balance, 2, '.', ''); ?>">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold mb-2" style="color: var(--text-primary); font-size: 1rem;">
                                <i class="bi bi-person-circle me-2"></i> Select Player
                            </label>
                            <select class="form-select form-select-lg" id="prize_player_select" name="registration_id_select" style="background: rgba(255, 255, 255, 0.08); color: var(--text-primary); border: 2px solid rgba(255, 255, 255, 0.1); border-radius: 12px; font-weight: 600; font-size: 1.05rem;">
                                <option value="">-- Choose a player --</option>
                                <?php foreach ($participants as $p): ?>
                                    <option value="<?php echo (int)$p['registration_id']; ?>" data-player-id="<?php echo (int)$p['player_id']; ?>" data-player-name="<?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?>">
                                        <?php echo htmlspecialchars($p['name']); ?> (UID: <?php echo htmlspecialchars($p['game_uid'] ?: 'N/A'); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" class="form-control form-control-lg mt-2" id="prize_player_name" readonly 
                                   placeholder="Player" style="display:none; background: rgba(255, 255, 255, 0.08); color: var(--text-primary); border: 2px solid rgba(255, 255, 255, 0.1); border-radius: 12px; font-weight: 600; font-size: 1.1rem;">
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold mb-2" style="color: var(--text-primary); font-size: 1rem;">
                                <i class="bi bi-currency-rupee me-2"></i> Prize Amount
                            </label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text" style="background: rgba(255, 70, 85, 0.2); border: 2px solid rgba(255, 70, 85, 0.3); border-right: none; color: var(--primary); font-weight: 700; font-size: 1.3rem; border-radius: 12px 0 0 12px;">
                                    ₹
                                </span>
                                <input type="number" name="prize_amount" id="prize_amount" class="form-control form-control-lg" 
                                       placeholder="Enter amount" required min="1" step="0.01" 
                                       style="background: rgba(255, 255, 255, 0.05); color: var(--text-primary); border: 2px solid rgba(255, 70, 85, 0.3); border-left: none; font-weight: 600; font-size: 1.3rem; border-radius: 0 12px 12px 0;"
                                       autofocus>
                            </div>
                            <small class="text-muted d-block mt-2" style="font-size: 0.9rem;">
                                <i class="bi bi-info-circle me-1"></i> Amount will be transferred from the tournament wallet to the player's wallet
                            </small>
                            <div id="balanceInfo" class="mt-2" style="font-size: 0.9rem; color: #ddd;">
                                <div>Current Tournament Wallet: <strong>₹<?php echo number_format((float)$wallet['balance'], 2); ?></strong></div>
                                <div>Your Wallet: <strong>₹<?php echo number_format((float)$creator_wallet_balance, 2); ?></strong></div>
                            </div>
                        </div>

                        <!-- Insufficient balance suggestion -->
                        <div id="topupSuggestion" class="alert d-none" style="background: rgba(0, 212, 255, 0.12); border: 2px solid rgba(0, 212, 255, 0.35); color: #7ee5ff; border-radius: 12px; padding: 1rem;">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div>
                                    <i class="bi bi-info-circle-fill me-2"></i>
                                    <span>Insufficient tournament wallet balance. Shortfall: <strong id="shortfallText">₹0.00</strong></span>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-sm" id="topupBtnInline" onclick="showTopUpModal()"
                                            style="background: var(--gradient-primary); color: white; border: none; border-radius: 10px; padding: 0.5rem 1rem; font-weight: 700;">
                                        Add money to tournament wallet
                                    </button>
                                </div>
                            </div>
                            <div id="topupCreatorWarn" class="mt-2 small text-warning d-none">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Not enough balance in your wallet to cover the shortfall.
                            </div>
                        </div>
                        
                        <div class="alert" style="background: rgba(255, 170, 0, 0.15); border: 2px solid rgba(255, 170, 0, 0.4); color: var(--accent-yellow); border-radius: 12px; padding: 1rem;">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-exclamation-triangle-fill me-3" style="font-size: 1.5rem;"></i>
                                <div>
                                    <strong style="display: block; margin-bottom: 0.5rem;">Important Notice</strong>
                                    <ul class="mb-0" style="padding-left: 1.2rem;">
                                        <li>Ensure you have sufficient balance in your wallet</li>
                                        <li>This transaction cannot be reversed</li>
                                        <li>Prize will be credited instantly to player's wallet</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 2px solid rgba(255, 255, 255, 0.1); padding: 1.5rem; justify-content: space-between;">
                        <button type="button" class="btn btn-lg" data-bs-dismiss="modal"
                                style="background: transparent; border: 2px solid rgba(255, 255, 255, 0.2); color: var(--text-primary); border-radius: 12px; padding: 0.75rem 2rem; font-weight: 600;">
                            <i class="bi bi-x-circle me-2"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-lg" id="payNowBtn"
                                style="background: var(--gradient-primary); color: white; border: none; border-radius: 12px; padding: 0.75rem 2.5rem; font-weight: 700; box-shadow: 0 8px 25px rgba(255, 70, 85, 0.4);">
                            <i class="bi bi-send-fill me-2"></i> Pay Now
                        </button>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        console.log('=== Tournament View JavaScript Loaded ===');
        console.log('Tournament ID:', <?php echo $tournament_id; ?>);
        
        // Check if all elements exist on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM Content Loaded');
            const form = document.getElementById('payPrizeForm');
            const btn = document.getElementById('payNowBtn');
            const amountInput = document.getElementById('prize_amount');
            const selectEl = document.getElementById('prize_player_select');
            
            console.log('Form exists:', !!form);
            console.log('Button exists:', !!btn);
            console.log('Amount input exists:', !!amountInput);
            console.log('Select exists:', !!selectEl);
            
            if (btn) {
                btn.addEventListener('click', function() {
                    console.log('PAY NOW BUTTON CLICKED!');
                });
            }
        });
        
        function showPayPrizeModal(registrationId, playerId, playerName) {
            document.getElementById('prize_registration_id').value = registrationId;
            document.getElementById('prize_player_id').value = playerId;
            const nameInput = document.getElementById('prize_player_name');
            const select = document.getElementById('prize_player_select');
            if (select) select.value = '';
            nameInput.style.display = 'block';
            nameInput.value = playerName;
            document.getElementById('prize_amount').value = '';
            
            const modal = new bootstrap.Modal(document.getElementById('payPrizeModal'));
            modal.show();
        }
        function showPayPrizeModalSelect() {
            // Clear hidden fields; use select
            document.getElementById('prize_registration_id').value = '';
            document.getElementById('prize_player_id').value = '';
            const nameInput = document.getElementById('prize_player_name');
            const select = document.getElementById('prize_player_select');
            if (nameInput) { nameInput.style.display = 'none'; nameInput.value = ''; }
            if (select) { select.value = ''; }
            document.getElementById('prize_amount').value = '';
            const modal = new bootstrap.Modal(document.getElementById('payPrizeModal'));
            modal.show();
        }
        
        function showTopUpModal() {
            const modal = new bootstrap.Modal(document.getElementById('topUpModal'));
            modal.show();
        }
        
        // Dynamically show top-up suggestion when amount exceeds tournament wallet balance
        (function(){
            const amountEl = document.getElementById('prize_amount');
            const twBal = parseFloat(document.getElementById('tw_balance_current').value || '0');
            const creatorBal = parseFloat(document.getElementById('creator_balance_current').value || '0');
            const sugg = document.getElementById('topupSuggestion');
            const shortfallText = document.getElementById('shortfallText');
            const topupAmount = document.getElementById('topup_amount');
            const warn = document.getElementById('topupCreatorWarn');
            function updateSuggestion(){
                const amt = parseFloat(amountEl.value || '0');
                if (!isNaN(amt) && amt > twBal + 0.0001) {
                    const shortfall = Math.max(0, amt - twBal);
                    shortfallText.textContent = '₹' + shortfall.toFixed(2);
                    topupAmount.value = shortfall.toFixed(2);
                    if (sugg) sugg.classList.remove('d-none');
                    if (creatorBal + 0.0001 < shortfall) { warn.classList.remove('d-none'); } else { warn.classList.add('d-none'); }
                } else {
                    if (sugg) sugg.classList.add('d-none');
                }
            }
            if (amountEl) {
                amountEl.addEventListener('input', updateSuggestion);
                amountEl.addEventListener('change', updateSuggestion);
            }
        })();
        // Keep hidden fields synced to select
        const prizeSelectEl = document.getElementById('prize_player_select');
        if (prizeSelectEl) {
            prizeSelectEl.addEventListener('change', function() {
                const opt = this.options[this.selectedIndex];
                const pid = opt.getAttribute('data-player-id');
                const pname = opt.getAttribute('data-player-name');
                document.getElementById('prize_registration_id').value = this.value || '';
                document.getElementById('prize_player_id').value = pid || '';
                const nameInput = document.getElementById('prize_player_name');
                if (nameInput) { nameInput.value = pname || ''; }
            });
        }
        
        // Form validation
        const payPrizeFormEl = document.getElementById('payPrizeForm');
        if (payPrizeFormEl) {
            payPrizeFormEl.addEventListener('submit', function(e) {
                try {
                    console.log('Form submit triggered');
                    
                    // Get the amount
                    const amountInput = document.getElementById('prize_amount');
                    const amount = parseFloat(amountInput.value);
                    console.log('Prize Amount:', amount);
                    
                    // Get registration ID from select or hidden field
                    const regIdHidden = document.getElementById('prize_registration_id').value;
                    const selectEl = document.getElementById('prize_player_select');
                    const regIdSelect = selectEl ? selectEl.value : '';
                    
                    console.log('RegId Hidden:', regIdHidden, 'RegId Select:', regIdSelect);
                    
                    // If select has value but hidden doesn't, copy it over
                    if (!regIdHidden && regIdSelect) {
                        const opt = selectEl.options[selectEl.selectedIndex];
                        document.getElementById('prize_registration_id').value = regIdSelect;
                        document.getElementById('prize_player_id').value = opt.getAttribute('data-player-id') || '';
                        console.log('Copied select values to hidden fields');
                    }
                    
                    // Final validation
                    const finalRegId = document.getElementById('prize_registration_id').value;
                    console.log('Final RegId for submission:', finalRegId);
                    
                    // Check amount
                    if (!amount || amount <= 0 || isNaN(amount)) {
                        e.preventDefault();
                        alert('Please enter a valid prize amount greater than 0');
                        console.log('Validation failed: Invalid amount');
                        return false;
                    }
                    
                    // Check player selection
                    if (!finalRegId || finalRegId === '' || finalRegId === '0') {
                        e.preventDefault();
                        alert('Please select a player to pay.');
                        console.log('Validation failed: No player selected');
                        return false;
                    }
                    
                    // Get player name for confirmation
                    let playerName = '';
                    if (selectEl && selectEl.value) {
                        const opt = selectEl.options[selectEl.selectedIndex];
                        playerName = opt.getAttribute('data-player-name') || opt.text;
                    } else {
                        const nameInput = document.getElementById('prize_player_name');
                        playerName = nameInput ? nameInput.value : '';
                    }
                    
                    // Confirmation
                    const confirmMsg = 'Are you sure you want to pay ₹' + amount.toFixed(2) + ' to ' + (playerName || 'selected player') + '?';
                    console.log('Showing confirmation:', confirmMsg);
                    
                    if (!confirm(confirmMsg)) {
                        e.preventDefault();
                        console.log('User cancelled payment');
                        return false;
                    }
                    
                    console.log('Validation passed! Form will submit.');
                    return true;
                    
                } catch (err) {
                    console.error('Error in form validation:', err);
                    alert('An error occurred. Please try again.');
                    e.preventDefault();
                    return false;
                }
            });
        } else {
            console.error('ERROR: payPrizeForm element not found!');
        }
        
        // Top-up form validation
        const topUpFormEl = document.getElementById('topUpForm');
        if (topUpFormEl) {
            topUpFormEl.addEventListener('submit', function(e) {
                const amount = parseFloat(document.getElementById('topup_amount_modal').value);
                const creatorBal = <?php echo $creator_wallet_balance; ?>;
                
                if (amount <= 0 || isNaN(amount)) {
                    e.preventDefault();
                    alert('Please enter a valid amount greater than 0');
                    return false;
                }
                
                if (amount > creatorBal + 0.01) {
                    e.preventDefault();
                    alert('Insufficient balance in your wallet. Available: ₹' + creatorBal.toFixed(2));
                    return false;
                }
                
                if (!confirm('Add ₹' + amount.toFixed(2) + ' to tournament wallet from your wallet?')) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    </script>

    <?php include '../../src/includes/footer.php'; ?>
</body>
</html>