<?php
session_start();
include '../src/db.php';

// Check if the user is logged in and is a creator
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'creator') {
    header('Location: ../../src/login.php');
    exit();
}

// CSRF token for actions on this page
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Current creator id
$creator_id = (int)($_SESSION['user_id'] ?? 0);

// Handle update room (server-side, allowed only before scheduled time and if status not started)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_room'], $_POST['tournament_id'])) {
    // CSRF check
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $_SESSION['flash_error'] = 'Invalid request. Please try again.';
        header('Location: creator_dashboard.php');
        exit();
    }

    $tid = (int)$_POST['tournament_id'];
    $roomId = trim($_POST['room_id'] ?? '');
    $roomPwd = trim($_POST['room_password'] ?? '');

    if ($roomId === '' || $roomPwd === '') {
        $_SESSION['flash_error'] = 'Room ID and Password are required.';
        header('Location: creator_dashboard.php');
        exit();
    }

    // Verify ownership and check time/status
    $q = "SELECT id, date, time, status, created_by FROM tournaments WHERE id = ? LIMIT 1";
    $s = $conn->prepare($q);
    $s->bind_param('i', $tid);
    $s->execute();
    $res = $s->get_result();
    $t = $res->fetch_assoc();
    $s->close();

    if (!$t || (int)$t['created_by'] !== (int)$creator_id) {
        $_SESSION['flash_error'] = 'Tournament not found.';
        header('Location: creator_dashboard.php');
        exit();
    }

    $status = strtolower($t['status'] ?? '');
    if ($status === 'ongoing' || $status === 'completed') {
        $_SESSION['flash_error'] = 'Cannot update room after the tournament has started.';
        header('Location: creator_dashboard.php');
        exit();
    }

    $scheduledStr = ($t['date'] ?? '') . ' ' . ($t['time'] ?? '00:00:00');
    try {
        $scheduledAt = new DateTime($scheduledStr);
        $now = new DateTime('now');
    } catch (Exception $e) {
        $_SESSION['flash_error'] = 'Invalid tournament schedule data.';
        header('Location: creator_dashboard.php');
        exit();
    }

    if ($now >= $scheduledAt) {
        $_SESSION['flash_error'] = 'Room credentials can be updated only before the scheduled start time.';
        header('Location: creator_dashboard.php');
        exit();
    }

    // Proceed with update: set room, and immediately move tournament to 'ongoing' to stop further registrations
    // Note: DB uses 'ongoing' as the running status
    $uq = "UPDATE tournaments SET room_id = ?, room_password = ?, status = 'ongoing' WHERE id = ? AND created_by = ? AND status = 'upcoming'";
    $us = $conn->prepare($uq);
    $us->bind_param('ssii', $roomId, $roomPwd, $tid, $creator_id);
    if ($us->execute()) {
        if ($us->affected_rows > 0) {
            // Notify all registered players (including team members) about updated room credentials
            // Ensure notifications table exists (compatible schema)
            @$conn->query("CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                type VARCHAR(50) NOT NULL DEFAULT 'tournament_created',
                title VARCHAR(255) NOT NULL,
                message TEXT,
                tournament_id INT NULL,
                audience ENUM('all','players','creators','user') NOT NULL DEFAULT 'players',
                audience_user_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_created_at (created_at),
                INDEX idx_audience (audience)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            // Collect registered users: captains
            $userIds = [];
            if ($rs = $conn->prepare("SELECT DISTINCT player_id AS uid FROM registrations WHERE tournament_id = ? AND payment_status IN ('success','paid')")) {
                $rs->bind_param('i', $tid);
                $rs->execute();
                $rr = $rs->get_result();
                while ($row = $rr->fetch_assoc()) { $userIds[] = (int)$row['uid']; }
                $rs->close();
            }
            // Add team members
            if ($trs = $conn->prepare("SELECT DISTINCT tr.user_id AS uid FROM team_registrations tr JOIN registrations r ON tr.registration_id = r.id WHERE r.tournament_id = ? AND r.payment_status IN ('success','paid') AND tr.invitation_status IN ('accepted','pending')")) {
                $trs->bind_param('i', $tid);
                $trs->execute();
                $trr = $trs->get_result();
                while ($row = $trr->fetch_assoc()) { $userIds[] = (int)$row['uid']; }
                $trs->close();
            }
            // Unique list
            $userIds = array_values(array_unique($userIds));

            if (!empty($userIds)) {
                $title = 'Room Details Updated';
                $message = 'The room has been created. Use the following details to join: Room ID: ' . $roomId . ' | Password: ' . $roomPwd;
                if ($insn = $conn->prepare("INSERT INTO notifications (type, title, message, tournament_id, audience, audience_user_id) VALUES ('system_announcement', ?, ?, ?, 'user', ?)")) {
                    foreach ($userIds as $uid) {
                        $insn->bind_param('ssii', $title, $message, $tid, $uid);
                        $insn->execute();
                    }
                    $insn->close();
                }
            }

            $_SESSION['flash_success'] = 'Room credentials updated, players notified, and tournament status set to Running.';
        } else {
            // If no rows affected (e.g., status not upcoming), try updating only room credentials without changing status
            $fallback = $conn->prepare("UPDATE tournaments SET room_id = ?, room_password = ? WHERE id = ? AND created_by = ?");
            $fallback->bind_param('ssii', $roomId, $roomPwd, $tid, $creator_id);
            if ($fallback->execute()) {
                // Notify even if status unchanged
                @$conn->query("CREATE TABLE IF NOT EXISTS notifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    type VARCHAR(50) NOT NULL DEFAULT 'tournament_created',
                    title VARCHAR(255) NOT NULL,
                    message TEXT,
                    tournament_id INT NULL,
                    audience ENUM('all','players','creators','user') NOT NULL DEFAULT 'players',
                    audience_user_id INT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_created_at (created_at),
                    INDEX idx_audience (audience)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                $userIds = [];
                if ($rs = $conn->prepare("SELECT DISTINCT player_id AS uid FROM registrations WHERE tournament_id = ? AND payment_status IN ('success','paid')")) {
                    $rs->bind_param('i', $tid);
                    $rs->execute();
                    $rr = $rs->get_result();
                    while ($row = $rr->fetch_assoc()) { $userIds[] = (int)$row['uid']; }
                    $rs->close();
                }
                if ($trs = $conn->prepare("SELECT DISTINCT tr.user_id AS uid FROM team_registrations tr JOIN registrations r ON tr.registration_id = r.id WHERE r.tournament_id = ? AND r.payment_status IN ('success','paid') AND tr.invitation_status IN ('accepted','pending')")) {
                    $trs->bind_param('i', $tid);
                    $trs->execute();
                    $trr = $trs->get_result();
                    while ($row = $trr->fetch_assoc()) { $userIds[] = (int)$row['uid']; }
                    $trs->close();
                }
                $userIds = array_values(array_unique($userIds));
                if (!empty($userIds)) {
                    $title = 'Room Details Updated';
                    $message = 'Use the updated details: Room ID: ' . $roomId . ' | Password: ' . $roomPwd;
                    if ($insn = $conn->prepare("INSERT INTO notifications (type, title, message, tournament_id, audience, audience_user_id) VALUES ('system_announcement', ?, ?, ?, 'user', ?)")) {
                        foreach ($userIds as $uid) {
                            $insn->bind_param('ssii', $title, $message, $tid, $uid);
                            $insn->execute();
                        }
                        $insn->close();
                    }
                }
                $_SESSION['flash_success'] = 'Room credentials updated and players notified.';
            } else {
                $_SESSION['flash_error'] = 'Failed to update room credentials.';
            }
            $fallback->close();
        }
    } else {
        $_SESSION['flash_error'] = 'Failed to update room credentials.';
    }
    $us->close();

    header('Location: creator_dashboard.php');
    exit();
}

// Handle deletion (safe: cancel if there are registrations/transactions)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_tournament'], $_POST['delete_id'])) {
    // CSRF check for delete as well
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $_SESSION['flash_error'] = 'Invalid request. Please try again.';
        header('Location: creator_dashboard.php');
        exit();
    }

    $del_id = (int)$_POST['delete_id'];

    // Verify the tournament belongs to this creator
    $ownQ = $conn->prepare("SELECT id FROM tournaments WHERE id = ? AND created_by = ? LIMIT 1");
    $ownQ->bind_param('ii', $del_id, $creator_id);
    $ownQ->execute();
    $ownRes = $ownQ->get_result();
    $owns = (bool)$ownRes->fetch_assoc();
    $ownQ->close();
    if (!$owns) {
        $_SESSION['flash_error'] = 'Tournament not found.';
        header('Location: creator_dashboard.php');
        exit();
    }

    // Check if there are any registrations or wallet transactions tied to this tournament
    $regCount = 0; $txCount = 0;
    if ($st1 = $conn->prepare("SELECT COUNT(*) AS c FROM registrations WHERE tournament_id = ?")) {
        $st1->bind_param('i', $del_id);
        $st1->execute();
        $r1 = $st1->get_result()->fetch_assoc();
        $regCount = (int)($r1['c'] ?? 0);
        $st1->close();
    }
    if ($st2 = $conn->prepare("SELECT COUNT(*) AS c FROM wallet_transactions WHERE tournament_id = ?")) {
        $st2->bind_param('i', $del_id);
        $st2->execute();
        $r2 = $st2->get_result()->fetch_assoc();
        $txCount = (int)($r2['c'] ?? 0);
        $st2->close();
    }

    if ($regCount > 0 || $txCount > 0) {
        // Refund all paid entries, then cancel the tournament
        // Get entry_fee and creator (self) confirmation
        $entry_fee = 0.0; $created_by = 0;
        if ($tq = $conn->prepare("SELECT entry_fee, created_by FROM tournaments WHERE id = ? LIMIT 1")) {
            $tq->bind_param('i', $del_id);
            $tq->execute();
            $tr = $tq->get_result();
            if ($row = $tr->fetch_assoc()) { $entry_fee = (float)($row['entry_fee'] ?? 0); $created_by = (int)($row['created_by'] ?? 0); }
            $tq->close();
        }
        // Collect paid registrations
        $playerIds = [];
        if ($rg = $conn->prepare("SELECT player_id FROM registrations WHERE tournament_id = ? AND (payment_status = 'success' OR payment_status = 'paid')")) {
            $rg->bind_param('i', $del_id);
            $rg->execute();
            $rr = $rg->get_result();
            while ($r = $rr->fetch_assoc()) { $playerIds[] = (int)$r['player_id']; }
            $rg->close();
        }
        // If there is nothing to refund or entry_fee is zero, just cancel
        if (empty($playerIds) || $entry_fee <= 0) {
            if ($up = $conn->prepare("UPDATE tournaments SET status = 'cancelled' WHERE id = ? AND created_by = ?")) {
                $up->bind_param('ii', $del_id, $creator_id);
                if ($up->execute()) {
                    $_SESSION['flash_success'] = 'Tournament cancelled.';
                } else {
                    $_SESSION['flash_error'] = 'Unable to cancel tournament.';
                }
                $up->close();
            } else {
                $_SESSION['flash_error'] = 'Server error. Try again later.';
            }
            header('Location: creator_dashboard.php');
            exit();
        }

        // Refunds are paid from the tournament wallet balance
        $totalRefund = $entry_fee * count($playerIds);
        $twBalance = 0.0;
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
        if ($twq = $conn->prepare("SELECT balance FROM tournament_wallets WHERE tournament_id = ? AND status = 'open' LIMIT 1")) {
            $twq->bind_param('i', $del_id);
            $twq->execute();
            $twr = $twq->get_result();
            if ($twrow = $twr->fetch_assoc()) { $twBalance = (float)$twrow['balance']; }
            $twq->close();
        }
        if ($twBalance < $totalRefund) {
            $_SESSION['flash_error'] = 'Cannot cancel: tournament wallet has insufficient balance to refund all players (needed ₹' . number_format($totalRefund, 2) . ').';
            header('Location: creator_dashboard.php');
            exit();
        }

        // Process refunds within a transaction
        $conn->begin_transaction();
        try {
            foreach ($playerIds as $pid) {
                // Deduct from tournament wallet
                if ($ded = $conn->prepare("UPDATE tournament_wallets SET balance = balance - ? WHERE tournament_id = ? AND status = 'open' AND balance >= ?")) {
                    $ded->bind_param('dii', $entry_fee, $del_id, $entry_fee);
                    $ded->execute();
                    $affected = $ded->affected_rows;
                    $ded->close();
                    if ($affected !== 1) { throw new Exception('Insufficient tournament wallet balance during refund.'); }
                } else { throw new Exception('Failed to prepare tournament wallet debit.'); }

                // Credit player
                if ($cr = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?")) {
                    $cr->bind_param('di', $entry_fee, $pid);
                    $cr->execute();
                    $cr->close();
                } else { throw new Exception('Failed to prepare player credit.'); }

                // Log transactions
                if ($wtp = $conn->prepare("INSERT INTO wallet_transactions (user_id, type, amount, related_user_id, tournament_id, description, status) VALUES (?, 'credit', ?, ?, ?, ?, 'completed')")) {
                    $descP = 'Refund for cancelled tournament #' . $del_id;
                    $wtp->bind_param('idiis', $pid, $entry_fee, $creator_id, $del_id, $descP);
                    $wtp->execute();
                    $wtp->close();
                }
                // Optionally log a tournament wallet ledger here (skipped; using player credit log only)

                // Mark registration refunded
                if ($rup = $conn->prepare("UPDATE registrations SET payment_status = 'refunded' WHERE tournament_id = ? AND player_id = ?")) {
                    $rup->bind_param('ii', $del_id, $pid);
                    $rup->execute();
                    $rup->close();
                }
            }

            // Finally cancel the tournament
            if ($up = $conn->prepare("UPDATE tournaments SET status = 'cancelled' WHERE id = ? AND created_by = ?")) {
                $up->bind_param('ii', $del_id, $creator_id);
                if (!$up->execute()) { throw new Exception('Failed to mark tournament cancelled.'); }
                $up->close();
            } else { throw new Exception('Failed to prepare cancel update.'); }

            $conn->commit();
            $_SESSION['flash_success'] = 'Tournament cancelled and all players refunded.';
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['flash_error'] = 'Refund failed: ' . $e->getMessage();
        }
        header('Location: creator_dashboard.php');
        exit();
    }

    // No references: safe to delete
    if ($s = $conn->prepare("DELETE FROM tournaments WHERE id = ? AND created_by = ?")) {
        $s->bind_param('ii', $del_id, $creator_id);
        if ($s->execute()) {
            $_SESSION['flash_success'] = 'Tournament deleted.';
            $s->close();
            header('Location: creator_dashboard.php');
            exit();
        }
        $s->close();
    }
    $_SESSION['flash_error'] = 'Failed to delete tournament.';
    header('Location: creator_dashboard.php');
    exit();
}

// Inlined header is added in HTML below

// Fetch creator's tournaments from the database with entries count
// Show latest created tournaments first
$creator_id = $_SESSION['user_id'];
$query = "SELECT t.*, (
                        SELECT COUNT(*) FROM registrations r 
                        WHERE r.tournament_id = t.id 
                            AND (r.payment_status = 'success' OR r.payment_status = 'paid')
                ) AS entries
                FROM tournaments t
                WHERE t.created_by = ?
                ORDER BY t.id DESC, t.date DESC, t.time DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $creator_id);
$stmt->execute();
$result = $stmt->get_result();
$tournaments = $result->fetch_all(MYSQLI_ASSOC);

// Compute summary stats and organize tournaments by status
$total = count($tournaments);
$upcoming = 0;
$ongoing = 0;
$completed = 0;
$cancelled = 0;

$upcomingTournaments = [];
$ongoingTournaments = [];
$completedTournaments = [];
$cancelledTournaments = [];

foreach ($tournaments as $t) {
    $status = strtolower($t['status']);
    if (strpos($status, 'upcoming') !== false) {
        $upcoming++;
        $upcomingTournaments[] = $t;
    } else if (strpos($status, 'ongoing') !== false) {
        $ongoing++;
        $ongoingTournaments[] = $t;
    } else if (strpos($status, 'complete') !== false || strpos($status, 'finished') !== false) {
        $completed++;
        $completedTournaments[] = $t;
    } else if (strpos($status, 'cancel') !== false) {
        $cancelled++;
        $cancelledTournaments[] = $t;
    }
}

// Function to render tournament card HTML
function renderTournamentCard($tournament, $csrf_token) {
    $status = strtolower($tournament['status']);
    $statusClass = 'status-upcoming';
    if (strpos($status, 'upcoming') !== false) $statusClass = 'status-upcoming';
    else if (strpos($status, 'ongoing') !== false) $statusClass = 'status-ongoing';
    else if (strpos($status, 'complete') !== false || strpos($status, 'finished') !== false) $statusClass = 'status-completed';
    else if (strpos($status, 'cancel') !== false) $statusClass = 'status-cancelled';
    
    ob_start();
    ?>
    <div class="tournament-card" data-title="<?php echo htmlspecialchars(strtolower($tournament['title'])); ?>" data-status="<?php echo htmlspecialchars($status); ?>">
        <div class="tournament-header">
            <div>
                <h3 class="tournament-title"><?php echo htmlspecialchars($tournament['title']); ?></h3>
                <div class="tournament-id">ID: <?php echo $tournament['id']; ?></div>
            </div>
            <span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($tournament['status']); ?></span>
        </div>
        
        <div class="tournament-details">
            <div class="detail-item">
                <span class="detail-label">Date & Time</span>
                <span class="detail-value"><?php echo htmlspecialchars(date('M d, Y H:i', strtotime($tournament['date']))); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Entries</span>
                <span class="detail-value"><?php echo (int)($tournament['entries'] ?? 0); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Prize Pool</span>
                <span class="detail-value">₹<?php echo htmlspecialchars(number_format((float)($tournament['prize_pool'] ?? 0), 2)); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Room ID</span>
                <span class="detail-value"><?php echo isset($tournament['room_id']) && !empty($tournament['room_id']) ? htmlspecialchars($tournament['room_id']) : 'Not Set'; ?></span>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="view_tournament.php?id=<?php echo $tournament['id']; ?>" class="btn btn-primary-action">
                <i class="bi bi-eye"></i> View
            </a>
            <a href="create_tournament.php?edit=<?php echo $tournament['id']; ?>" class="btn btn-secondary-action">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <?php if ($tournament['status'] !== 'completed'): ?>
                <a href="update_match_stats.php?tournament_id=<?php echo $tournament['id']; ?>" class="btn btn-success-action">
                    <i class="bi bi-bar-chart-fill"></i> Match Stats
                </a>
            <?php endif; ?>
            <a href="tournament_leaderboard.php?tournament_id=<?php echo $tournament['id']; ?>" class="btn btn-info-action">
                <i class="bi bi-trophy-fill"></i> Leaderboard
            </a>
            <button type="button" class="btn btn-warning-action" data-update-room-btn data-target="#update-room-<?php echo $tournament['id']; ?>">
                <i class="bi bi-pencil-square"></i> Update Room
            </button>
            
            <form method="post" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this tournament? This action cannot be undone.');">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="delete_id" value="<?php echo $tournament['id']; ?>">
                <button type="submit" name="delete_tournament" class="btn btn-danger-action">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </form>
        </div>
        
        <!-- Update Room Form -->
        <div class="update-room-form d-none" id="update-room-<?php echo $tournament['id']; ?>">
            <form method="post" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="tournament_id" value="<?php echo (int)$tournament['id']; ?>">
                <div class="col-md-5">
                    <label class="form-label" for="room_id_<?php echo $tournament['id']; ?>">Room ID</label>
                    <input class="form-control-custom" type="text" id="room_id_<?php echo $tournament['id']; ?>" name="room_id" value="<?php echo htmlspecialchars($tournament['room_id'] ?? ''); ?>" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="room_password_<?php echo $tournament['id']; ?>">Room Password</label>
                    <input class="form-control-custom" type="text" id="room_password_<?php echo $tournament['id']; ?>" name="room_password" value="<?php echo htmlspecialchars($tournament['room_password'] ?? ''); ?>" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="d-flex gap-2 w-100">
                        <button type="submit" name="update_room" class="btn btn-primary-action flex-fill">
                            <i class="bi bi-check-lg"></i> Save
                        </button>
                        <button type="button" class="btn btn-secondary-action flex-fill" data-cancel-update data-target="#update-room-<?php echo $tournament['id']; ?>">
                            <i class="bi bi-x-lg"></i> Cancel
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creator Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- Header navbar styles -->
    <link rel="stylesheet" href="../../assets/css/gaming-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg gaming-navbar">
        <div class="container">
            <a class="navbar-brand" href="../../src/index.php">
                <img src="../../assets/images/logo.svg" alt="SKYNOXX FF Logo" class="brand-logo-img">
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
                    <div class="d-flex align-items-center">
                        <button id="profileBtn" class="profile-btn" type="button" aria-haspopup="true" aria-expanded="false">
                            <span class="profile-avatar" role="img" aria-label="User avatar"><?php echo htmlspecialchars($initials ?: 'U'); ?></span>
                        </button>
                        <a href="../creator/wallet_dashboard.php" class="btn btn-wallet ms-2" style="background:#00f5ff;color:#101a24;font-weight:600;border-radius:8px;">Wallet</a>
                    </div>
                    <div class="position-relative">
                        <div id="profileMenu" class="profile-menu">
                            <div class="profile-header">
                                <div class="d-flex align-items-center gap-2 px-2">
                                    <div class="profile-avatar" style="width:48px;height:48px;font-size:16px; "><?php echo htmlspecialchars($initials ?: 'U'); ?></div>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($user_name ?: 'User'); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($role); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group list-group-flush mt-2">
                                <a href="creator_profile_details.php" class="list-group-item list-group-item-action">View all details</a>
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
    
    <div class="dashboard-container">
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
        <?php endif; ?>
        
        <div class="dashboard-header">
            <h1 class="dashboard-title">Creator Dashboard</h1>
            <a href="create_tournament.php" class="btn btn-primary-action">
                <i class="bi bi-plus-circle"></i> Create New Tournament
            </a>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value total-tournaments"><?php echo $total; ?></div>
                <div class="stat-label">Total Tournaments</div>
            </div>
            <div class="stat-card">
                <div class="stat-value upcoming-tournaments"><?php echo $upcoming; ?></div>
                <div class="stat-label">Upcoming</div>
            </div>
            <div class="stat-card">
                <div class="stat-value ongoing-tournaments"><?php echo $ongoing; ?></div>
                <div class="stat-label">Ongoing</div>
            </div>
            <div class="stat-card">
                <div class="stat-value completed-tournaments"><?php echo $completed; ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #dc3545;"><?php echo $cancelled; ?></div>
                <div class="stat-label">Cancelled</div>
            </div>
        </div>
        
        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <div class="filter-tab active" data-tab="upcoming">
                <span class="tab-count"><?php echo $upcoming; ?></span>
                <span class="tab-label">Upcoming</span>
            </div>
            <div class="filter-tab" data-tab="ongoing">
                <span class="tab-count"><?php echo $ongoing; ?></span>
                <span class="tab-label">Ongoing</span>
            </div>
            <div class="filter-tab" data-tab="completed">
                <span class="tab-count"><?php echo $completed; ?></span>
                <span class="tab-label">Completed</span>
            </div>
            <div class="filter-tab" data-tab="cancelled">
                <span class="tab-count"><?php echo $cancelled; ?></span>
                <span class="tab-label">Cancelled</span>
            </div>
        </div>
        
        <div class="controls-card">
            <div class="row g-3">
                <div class="col-md-6">
                    <input id="searchInput" type="search" class="form-control-custom" placeholder="Search tournaments by title or ID...">
                </div>
                <div class="col-md-4">
                    <select id="statusFilter" class="form-control-custom">
                        <option value="">All Statuses</option>
                        <option value="upcoming">Upcoming</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="create_tournament.php" class="btn btn-primary-action w-100">
                        <i class="bi bi-plus-circle"></i> New
                    </a>
                </div>
            </div>
        </div>
        
        <?php if (empty($tournaments)): ?>
            <div class="no-tournaments">
                <div class="no-tournaments-icon">
                    <i class="bi bi-trophy"></i>
                </div>
                <h3>No Tournaments Yet</h3>
                <p>Create your first tournament to get started!</p>
                <a href="create_tournament.php" class="btn btn-primary-action mt-3">
                    <i class="bi bi-plus-circle"></i> Create Tournament
                </a>
            </div>
        <?php else: ?>
            <!-- Upcoming Tournaments Tab -->
            <div id="upcoming-tab" class="tab-content-section active">
                <?php if (empty($upcomingTournaments)): ?>
                    <div class="no-tournaments">
                        <div class="no-tournaments-icon">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <h3>No Upcoming Tournaments</h3>
                        <p>Create a new tournament to get started!</p>
                        <a href="create_tournament.php" class="btn btn-primary-action mt-3">
                            <i class="bi bi-plus-circle"></i> Create Tournament
                        </a>
                    </div>
                <?php else: ?>
                    <div id="tournamentsList">
                        <?php foreach ($upcomingTournaments as $tournament): 
                            echo renderTournamentCard($tournament, $_SESSION['csrf_token']);
                        endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Ongoing Tournaments Tab -->
            <div id="ongoing-tab" class="tab-content-section">
                <?php if (empty($ongoingTournaments)): ?>
                    <div class="no-tournaments">
                        <div class="no-tournaments-icon">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <h3>No Ongoing Tournaments</h3>
                        <p>Ongoing tournaments will appear here once started.</p>
                    </div>
                <?php else: ?>
                    <div>
                        <?php foreach ($ongoingTournaments as $tournament): 
                            echo renderTournamentCard($tournament, $_SESSION['csrf_token']);
                        endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Completed Tournaments Tab -->
            <div id="completed-tab" class="tab-content-section">
                <?php if (empty($completedTournaments)): ?>
                    <div class="no-tournaments">
                        <div class="no-tournaments-icon">
                            <i class="bi bi-trophy-fill"></i>
                        </div>
                        <h3>No Completed Tournaments</h3>
                        <p>Completed tournaments will appear here.</p>
                    </div>
                <?php else: ?>
                    <div>
                        <?php foreach ($completedTournaments as $tournament): 
                            echo renderTournamentCard($tournament, $_SESSION['csrf_token']);
                        endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Cancelled Tournaments Tab -->
            <div id="cancelled-tab" class="tab-content-section">
                <?php if (empty($cancelledTournaments)): ?>
                    <div class="no-tournaments">
                        <div class="no-tournaments-icon">
                            <i class="bi bi-x-circle"></i>
                        </div>
                        <h3>No Cancelled Tournaments</h3>
                        <p>Cancelled or deleted tournaments will appear here.</p>
                    </div>
                <?php else: ?>
                    <div>
                        <?php foreach ($cancelledTournaments as $tournament): 
                            echo renderTournamentCard($tournament, $_SESSION['csrf_token']);
                        endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Client-side quick search & filter
        document.addEventListener('DOMContentLoaded', function(){
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            
            // Tab switching functionality
            const filterTabs = document.querySelectorAll('.filter-tab');
            const tabContents = document.querySelectorAll('.tab-content-section');
            
            filterTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remove active class from all tabs
                    filterTabs.forEach(t => t.classList.remove('active'));
                    // Add active class to clicked tab
                    this.classList.add('active');
                    
                    // Hide all tab contents
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Show corresponding tab content
                    const tabName = this.getAttribute('data-tab');
                    const targetContent = document.getElementById(tabName + '-tab');
                    if (targetContent) {
                        targetContent.classList.add('active');
                    }
                });
            });

            // Search and filter functionality
            function applyFilters(){
                const q = searchInput.value.trim().toLowerCase();
                const status = statusFilter.value;
                
                // Get all tournament cards across all tabs
                const tournamentCards = document.querySelectorAll('.tournament-card');
                
                tournamentCards.forEach(card => {
                    const title = card.getAttribute('data-title') || '';
                    const s = card.getAttribute('data-status') || '';
                    const id = card.querySelector('.tournament-id')?.textContent.toLowerCase() || '';
                    
                    const matchesQ = !q || title.includes(q) || id.includes(q);
                    const matchesStatus = !status || s.includes(status);
                    
                    card.style.display = (matchesQ && matchesStatus) ? 'block' : 'none';
                });
            }

            if (searchInput) searchInput.addEventListener('input', applyFilters);
            if (statusFilter) statusFilter.addEventListener('change', applyFilters);

            // Toggle inline Update Room forms
            document.querySelectorAll('[data-update-room-btn]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const targetSel = btn.getAttribute('data-target');
                    const form = document.querySelector(targetSel);
                    if (form) form.classList.toggle('d-none');
                });
            });
            
            document.querySelectorAll('[data-cancel-update]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const targetSel = btn.getAttribute('data-target');
                    const form = document.querySelector(targetSel);
                    if (form) form.classList.add('d-none');
                });
            });
        });
    </script>

    <?php include '../../src/includes/footer.php'; ?>
</body>

</html>