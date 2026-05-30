<?php
session_start();
include '../src/db.php';

// Admin auth
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../../src/login.php');
    exit();
}

$admin_id = (int)$_SESSION['user_id'];

// Fetch admin wallet balance
$balance = 0.0;
if ($st = $conn->prepare("SELECT wallet_balance, name, email FROM users WHERE id = ? LIMIT 1")) {
    $st->bind_param('i', $admin_id);
    $st->execute();
    $res = $st->get_result();
    if ($row = $res->fetch_assoc()) {
        $balance = (float)$row['wallet_balance'];
        $admin_name = $row['name'] ?? 'Admin';
        $admin_email = $row['email'] ?? '';
    }
    $st->close();
}

// Aggregate profit totals (admin profit share only)
$total_profit = 0.0; $settlement_count = 0;
if ($ag = $conn->prepare("SELECT COALESCE(SUM(amount),0) AS total, COUNT(*) AS cnt FROM wallet_transactions WHERE user_id = ? AND description LIKE 'Admin profit share%'")) {
    $ag->bind_param('i', $admin_id);
    $ag->execute();
    $r = $ag->get_result();
    if ($a = $r->fetch_assoc()) { $total_profit = (float)$a['total']; $settlement_count = (int)$a['cnt']; }
    $ag->close();
}

// Recent settlements list (joined with tournament)
$transactions = [];
if ($tx = $conn->prepare("SELECT wt.id, wt.amount, wt.created_at, wt.status, wt.description, t.title AS tournament_title, t.id AS tournament_id
    FROM wallet_transactions wt
    LEFT JOIN tournaments t ON t.id = wt.tournament_id
    WHERE wt.user_id = ? AND wt.description LIKE 'Admin profit share%'
    ORDER BY wt.created_at DESC, wt.id DESC
    LIMIT 100")) {
    $tx->bind_param('i', $admin_id);
    $tx->execute();
    $tr = $tx->get_result();
    if ($tr) { $transactions = $tr->fetch_all(MYSQLI_ASSOC); }
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
    <title>Admin Wallet - Profit Settlements</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar admin-header">
        <div class="container d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <img src="../../assets/images/logo.svg" class="brand-logo-img" alt="Logo">
                <span class="fw-bold">Admin Wallet</span>
            </div>
            <div>
                <a href="admin_dashboard.php" class="btn btn-sm btn-outline-light"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card-modern p-3 h-100">
                    <div class="text-muted">Wallet Balance</div>
                    <div class="stat-val">₹<?php echo number_format($balance, 2); ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-modern p-3 h-100">
                    <div class="text-muted">Total Profit (All-time)</div>
                    <div class="stat-val text-success">₹<?php echo number_format($total_profit, 2); ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-modern p-3 h-100">
                    <div class="text-muted">Settlements Received</div>
                    <div class="stat-val"><?php echo (int)$settlement_count; ?></div>
                </div>
            </div>
        </div>

        <div class="card-modern mt-4">
            <div class="p-3 border-bottom" style="border-color: var(--border)!important;">
                <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Settlement History</h5>
            </div>
            <?php if (empty($transactions)): ?>
                <div class="p-5 text-center text-muted">No profit settlements have been received yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-borderless align-middle mb-0">
                        <thead>
                            <tr class="text-muted">
                                <th>Date</th>
                                <th>Tournament</th>
                                <th class="text-end">Amount (₹)</th>
                                <th>Status</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $t): ?>
                                <tr>
                                    <td style="white-space:nowrap;"><?php echo htmlspecialchars(date('M d, Y H:i', strtotime($t['created_at']))); ?></td>
                                    <td><?php echo htmlspecialchars($t['tournament_title'] ?? ('#'.$t['tournament_id'])); ?></td>
                                    <td class="text-end text-success fw-bold">+ <?php echo number_format((float)$t['amount'], 2); ?></td>
                                    <td><span class="badge bg-success"><?php echo htmlspecialchars($t['status']); ?></span></td>
                                    <td><?php echo htmlspecialchars($t['description']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
