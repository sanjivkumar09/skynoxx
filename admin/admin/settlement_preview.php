<?php
session_start();
include('../src/db.php');
include('../src/auth.php');
include('../src/config.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','creator'], true)) {
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { echo json_encode(['ok'=>false,'error'=>'Invalid id']); exit; }

// Load tournament
if (!($st = $conn->prepare("SELECT id, title, prize_pool, created_by, status FROM tournaments WHERE id = ? LIMIT 1"))) {
    echo json_encode(['ok'=>false,'error'=>'DB error']); exit;
}
$st->bind_param('i', $id);
$st->execute();
$res = $st->get_result();
$t = $res->fetch_assoc();
$st->close();
if (!$t) { echo json_encode(['ok'=>false,'error'=>'Tournament not found']); exit; }
if ($role === 'creator' && (int)$t['created_by'] !== $user_id) {
    echo json_encode(['ok'=>false,'error'=>'Forbidden']); exit;
}

// Ensure wallet table exists
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
    $twq->bind_param('i', $id);
    $twq->execute();
    $r = $twq->get_result();
    $tw = $r->fetch_assoc();
    $twq->close();
}
if (!$tw) { echo json_encode(['ok'=>false,'error'=>'Tournament wallet not found']); exit; }

// Sum winners
$sumPrize = 0.0;
if ($pr = $conn->prepare("SELECT SUM(prize_won) as s FROM registrations WHERE tournament_id = ? AND prize_won > 0")) {
    $pr->bind_param('i', $id);
    $pr->execute();
    $prr = $pr->get_result();
    if ($row = $prr->fetch_assoc()) { $sumPrize = (float)($row['s'] ?? 0); }
    $pr->close();
}

$prizePool = (float)($t['prize_pool'] ?? 0);
$walletBalance = (float)($tw['balance'] ?? 0);
$profit = max(0, round($walletBalance - $sumPrize, 2));
$adminShare = round($profit * 0.20, 2);
$creatorShare = round($profit - $adminShare, 2);
$ready = (round($sumPrize,2) === round($prizePool,2)) && ($walletBalance + 1e-9 >= $sumPrize) && ($tw['status'] === 'open');

echo json_encode([
    'ok' => true,
    'id' => (int)$t['id'],
    'title' => (string)$t['title'],
    'status' => (string)$t['status'],
    'prize_pool' => $prizePool,
    'sum_prize' => $sumPrize,
    'wallet_balance' => $walletBalance,
    'profit' => $profit,
    'admin_share' => $adminShare,
    'creator_share' => $creatorShare,
    'ready' => $ready
]);
?>