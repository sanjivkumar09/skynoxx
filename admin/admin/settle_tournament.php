<?php
session_start();
include('../src/db.php');
include('../src/auth.php');
include('../src/config.php');

// Admin or creator can settle their own tournament
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','creator'], true)) {
    header('Location: ../../src/login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];
$tournament_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($tournament_id <= 0) {
    die('Invalid tournament id');
}

// Fetch tournament
if (!($st = $conn->prepare("SELECT id, created_by, prize_pool, status FROM tournaments WHERE id = ? LIMIT 1"))) {
    die('DB error');
}
$st->bind_param('i', $tournament_id);
$st->execute();
$res = $st->get_result();
$tournament = $res->fetch_assoc();
$st->close();
if (!$tournament) { die('Tournament not found'); }
if ($role === 'creator' && (int)$tournament['created_by'] !== $user_id) { die('Unauthorized'); }

// Ensure tournament wallet exists
@ $conn->query("CREATE TABLE IF NOT EXISTS tournament_wallets (
    tournament_id INT PRIMARY KEY,
    balance DECIMAL(12,2) NOT NULL DEFAULT 0,
    required_prize_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    prize_distributed_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('open','settled','cancelled') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tw_tournament FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Load wallet row
$tw = null;
if ($twq = $conn->prepare("SELECT balance, required_prize_total, prize_distributed_total, status FROM tournament_wallets WHERE tournament_id = ? LIMIT 1")) {
    $twq->bind_param('i', $tournament_id);
    $twq->execute();
    $r = $twq->get_result();
    $tw = $r->fetch_assoc();
    $twq->close();
}
if (!$tw) { die('Tournament wallet not found.'); }
if ($tw['status'] !== 'open') { die('Tournament is already settled or not open.'); }

// Sum prizes from registrations
$sumPrize = 0.0; $winners = [];
if ($pr = $conn->prepare("SELECT player_id, prize_won FROM registrations WHERE tournament_id = ? AND prize_won > 0")) {
    $pr->bind_param('i', $tournament_id);
    $pr->execute();
    $prr = $pr->get_result();
    while ($row = $prr->fetch_assoc()) {
        $amt = (float)$row['prize_won'];
        $sumPrize += $amt;
        $winners[] = ['player_id' => (int)$row['player_id'], 'amount' => $amt];
    }
    $pr->close();
}

// Verify prize sum equals required prize pool
$requiredPrize = (float)($tournament['prize_pool'] ?? 0);
if (round($sumPrize,2) !== round($requiredPrize,2)) {
    die('Prize distribution total (₹' . number_format($sumPrize,2) . ') does not equal prize pool (₹' . number_format($requiredPrize,2) . ').');
}

// Check wallet has enough for prizes
if ((float)$tw['balance'] + 1e-9 < $sumPrize) {
    die('Tournament wallet does not have enough balance for prizes.');
}

// Find admin user id for 20% share
$admin_id = 0;
// Prefer ADMIN_EMAIL if configured
if (defined('ADMIN_EMAIL') && ADMIN_EMAIL) {
    if ($as = $conn->prepare("SELECT id FROM users WHERE email = ? AND role = 'admin' LIMIT 1")) {
        $email = ADMIN_EMAIL;
        $as->bind_param('s', $email);
        $as->execute();
        $ar = $as->get_result();
        if ($row = $ar->fetch_assoc()) { $admin_id = (int)$row['id']; }
        $as->close();
    }
}
if ($admin_id === 0) {
    $qr = $conn->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
    if ($qr && ($row = $qr->fetch_assoc())) { $admin_id = (int)$row['id']; }
}
if ($admin_id === 0) { die('No admin account found to receive profit share.'); }

// Begin settlement transaction
$conn->begin_transaction();
try {
    // Pay winners from tournament wallet
    foreach ($winners as $w) {
        $pid = $w['player_id']; $amt = (float)$w['amount'];
        if ($amt <= 0) { continue; }
        // Deduct from tournament wallet
        if (!($d = $conn->prepare("UPDATE tournament_wallets SET balance = balance - ? WHERE tournament_id = ? AND status = 'open' AND balance >= ?"))) {
            throw new Exception('Failed to prepare tournament wallet debit');
        }
        $d->bind_param('dii', $amt, $tournament_id, $amt);
        $d->execute();
        if ($d->affected_rows !== 1) { $d->close(); throw new Exception('Insufficient tournament wallet balance'); }
        $d->close();
        // Credit player wallet
        if (!($c = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?"))) {
            throw new Exception('Failed to prepare player credit');
        }
        $c->bind_param('di', $amt, $pid);
        $c->execute();
        $c->close();
        // Log transaction
        if ($wt = $conn->prepare("INSERT INTO wallet_transactions (user_id, type, amount, tournament_id, description, status) VALUES (?, 'credit', ?, ?, ?, 'completed')")) {
            $desc = 'Prize distribution - Tournament #' . $tournament_id;
            $wt->bind_param('idis', $pid, $amt, $tournament_id, $desc);
            $wt->execute();
            $wt->close();
        }
    }

    // Update distributed total
    if ($up = $conn->prepare("UPDATE tournament_wallets SET prize_distributed_total = prize_distributed_total + ? WHERE tournament_id = ?")) {
        $up->bind_param('di', $sumPrize, $tournament_id);
        $up->execute();
        $up->close();
    }

    // Compute profit remaining in tournament wallet
    $nwBal = 0.0;
    if ($q2 = $conn->prepare("SELECT balance FROM tournament_wallets WHERE tournament_id = ? LIMIT 1")) {
        $q2->bind_param('i', $tournament_id);
        $q2->execute();
        $rr = $q2->get_result();
        if ($row = $rr->fetch_assoc()) { $nwBal = (float)$row['balance']; }
        $q2->close();
    }
    $profit = $nwBal; // what's left after prizes
    if ($profit < 0) { throw new Exception('Tournament wallet underflow after prizes'); }

    // Split profit: 80% creator, 20% admin
    $creator_id = (int)$tournament['created_by'];
    $admin_share = round($profit * 0.20, 2);
    $creator_share = round($profit - $admin_share, 2);

    // Pay admin
    if ($admin_share > 0) {
        if (!($d2 = $conn->prepare("UPDATE tournament_wallets SET balance = balance - ? WHERE tournament_id = ? AND status = 'open' AND balance >= ?"))) {
            throw new Exception('Failed to prepare admin payout');
        }
        $d2->bind_param('dii', $admin_share, $tournament_id, $admin_share);
        $d2->execute();
        if ($d2->affected_rows !== 1) { $d2->close(); throw new Exception('Insufficient balance for admin payout'); }
        $d2->close();
        if ($ca = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?")) {
            $ca->bind_param('di', $admin_share, $admin_id);
            $ca->execute();
            $ca->close();
        }
        if ($wta = $conn->prepare("INSERT INTO wallet_transactions (user_id, type, amount, tournament_id, description, status) VALUES (?, 'credit', ?, ?, ?, 'completed')")) {
            $descA = '20% admin share - Tournament #' . $tournament_id;
            $wta->bind_param('idis', $admin_id, $admin_share, $tournament_id, $descA);
            $wta->execute();
            $wta->close();
        }
    }

    // Pay creator
    if ($creator_share > 0) {
        if (!($d3 = $conn->prepare("UPDATE tournament_wallets SET balance = balance - ? WHERE tournament_id = ? AND status = 'open' AND balance >= ?"))) {
            throw new Exception('Failed to prepare creator payout');
        }
        $d3->bind_param('dii', $creator_share, $tournament_id, $creator_share);
        $d3->execute();
        if ($d3->affected_rows !== 1) { $d3->close(); throw new Exception('Insufficient balance for creator payout'); }
        $d3->close();
        if ($cc = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?")) {
            $cc->bind_param('di', $creator_share, $creator_id);
            $cc->execute();
            $cc->close();
        }
        if ($wtc = $conn->prepare("INSERT INTO wallet_transactions (user_id, type, amount, tournament_id, description, status) VALUES (?, 'credit', ?, ?, ?, 'completed')")) {
            $descC = 'Creator profit (80%) - Tournament #' . $tournament_id;
            $wtc->bind_param('idis', $creator_id, $creator_share, $tournament_id, $descC);
            $wtc->execute();
            $wtc->close();
        }
    }

    // Mark tournament as settled and completed
    if ($mt = $conn->prepare("UPDATE tournament_wallets SET status = 'settled' WHERE tournament_id = ?")) {
        $mt->bind_param('i', $tournament_id);
        $mt->execute();
        $mt->close();
    }
    if ($ts = $conn->prepare("UPDATE tournaments SET status = 'completed' WHERE id = ?")) {
        $ts->bind_param('i', $tournament_id);
        $ts->execute();
        $ts->close();
    }

    $conn->commit();
    echo 'Settlement successful: prizes paid and profit split (80% creator, 20% admin).';
} catch (Exception $e) {
    $conn->rollback();
    echo 'Settlement failed: ' . $e->getMessage();
}

?>