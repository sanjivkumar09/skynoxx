<?php
session_start();
require_once '../src/db.php';

// Only admins allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../src/login.php');
    exit();
}

if (!isset($_GET['user_id']) && !isset($_GET['creator_id'])) {
    header('Location: admin_dashboard.php');
    exit();
}
$creator_user_id = isset($_GET['creator_id']) ? (int)$_GET['creator_id'] : (int)$_GET['user_id'];

// Fetch creator basic details
$u = null;
if ($st = $conn->prepare("SELECT id, name, email, joined_at FROM users WHERE id = ? AND role = 'creator' LIMIT 1")) {
    $st->bind_param('i', $creator_user_id);
    $st->execute();
    $res = $st->get_result();
    $u = $res->fetch_assoc();
    $st->close();
}
if (!$u) { header('Location: admin_dashboard.php'); exit(); }

// Handle manual wallet adjustment
$adjustment_message = '';
$adjustment_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_wallet'])) {
    $amount = (float)($_POST['amount'] ?? 0);
    $type = $_POST['adjustment_type'] ?? 'credit';
    $reason = trim($_POST['reason'] ?? 'Manual adjustment by admin');
    
    if ($amount > 0) {
        $conn->begin_transaction();
        try {
            if ($type === 'credit') {
                // Add to wallet
                if ($upd = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?")) {
                    $upd->bind_param('di', $amount, $creator_user_id);
                    $upd->execute();
                    $upd->close();
                }
                // Log transaction
                if ($log = $conn->prepare("INSERT INTO wallet_transactions (user_id, type, amount, status, description, created_at) VALUES (?, 'credit', ?, 'completed', ?, NOW())")) {
                    $log->bind_param('ids', $creator_user_id, $amount, $reason);
                    $log->execute();
                    $log->close();
                }
                $adjustment_message = 'Successfully added ₹'.number_format($amount, 2).' to creator wallet.';
                $adjustment_type = 'success';
            } else {
                // Deduct from wallet
                if ($upd = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ? AND wallet_balance >= ?")) {
                    $upd->bind_param('did', $amount, $creator_user_id, $amount);
                    $upd->execute();
                    $affected = $upd->affected_rows;
                    $upd->close();
                    
                    if ($affected === 1) {
                        // Log transaction
                        if ($log = $conn->prepare("INSERT INTO wallet_transactions (user_id, type, amount, status, description, created_at) VALUES (?, 'debit', ?, 'completed', ?, NOW())")) {
                            $log->bind_param('ids', $creator_user_id, $amount, $reason);
                            $log->execute();
                            $log->close();
                        }
                        $adjustment_message = 'Successfully deducted ₹'.number_format($amount, 2).' from creator wallet.';
                        $adjustment_type = 'success';
                    } else {
                        throw new Exception('Insufficient wallet balance');
                    }
                }
            }
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $adjustment_message = 'Error: ' . $e->getMessage();
            $adjustment_type = 'danger';
        }
    } else {
        $adjustment_message = 'Please enter a valid amount.';
        $adjustment_type = 'warning';
    }
}

// Wallet balance
$wallet_balance = 0.0;
if ($wb = $conn->prepare("SELECT COALESCE(wallet_balance,0) AS bal FROM users WHERE id = ? LIMIT 1")) {
    $wb->bind_param('i', $creator_user_id);
    $wb->execute();
    $rwb = $wb->get_result();
    if ($row = $rwb->fetch_assoc()) { $wallet_balance = (float)$row['bal']; }
    $wb->close();
}

// Transactions with context
$transactions = [];
if ($tx = $conn->prepare("SELECT wt.id, wt.type, wt.amount, wt.status, wt.description, wt.tournament_id, wt.created_at,
                                t.title AS tournament_title,
                                u2.name AS related_user_name
                         FROM wallet_transactions wt
                         LEFT JOIN tournaments t ON wt.tournament_id = t.id
                         LEFT JOIN users u2 ON wt.related_user_id = u2.id
                         WHERE wt.user_id = ?
                         ORDER BY wt.created_at DESC, wt.id DESC
                         LIMIT 100")) {
    $tx->bind_param('i', $creator_user_id);
    $tx->execute();
    $resTx = $tx->get_result();
    $transactions = $resTx ? $resTx->fetch_all(MYSQLI_ASSOC) : [];
    $tx->close();
}

// Name initials for display
$name_parts = explode(' ', $u['name'] ?? 'Creator');
$initials = strtoupper(substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creator Wallet - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary:#ff4655; --secondary:#0f1923; --text:#ece8e1; --border: rgba(255,255,255,0.12); }
        body { background: linear-gradient(135deg, #0f1923 0%, #1a2836 100%); color: var(--text); }
        .admin-header { background: rgba(15,25,35,0.95); border-bottom: 2px solid var(--primary); padding: 1rem 0; position: sticky; top:0; z-index:100; }
        .header-logo { height:48px; }
        .content { max-width: 1200px; margin: 0 auto; padding: 2rem 1rem; }
        .card-dark { background: rgba(31,45,61,0.6); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; }
        .wallet-balance { font-size: 2.25rem; font-weight: 800; color: #69f0ae; }
        .table-dark-custom { background: transparent; color: var(--text); }
        .table-dark-custom thead th { background: rgba(255,70,85,0.1); border-color: var(--border); color: #9fb3c8; text-transform: uppercase; font-size:.85rem; padding: .8rem; }
        .table-dark-custom tbody td { border-color: var(--border); padding: .8rem; vertical-align: middle; }
        .amount-credit { color:#69f0ae; font-weight:700; }
        .amount-debit { color:#ff6b6b; font-weight:700; }
        .txn-badge { font-size: .78rem; }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <a href="../../src/index.php"><img src="../../assets/images/logo.svg" class="header-logo" alt="Logo"></a>
                <a href="view_creator_profile.php?user_id=<?php echo (int)$creator_user_id; ?>" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Back to Profile
                </a>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-secondary">Admin</span>
            </div>
        </div>
    </header>

    <div class="content">
        <?php if ($adjustment_message): ?>
            <div class="alert alert-<?= htmlspecialchars($adjustment_type) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($adjustment_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="card-dark">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted">Creator</div>
                            <div class="h4 fw-bold mb-0"><?php echo htmlspecialchars($u['name']); ?></div>
                            <div class="small text-muted">Joined <?php echo date('M Y', strtotime($u['joined_at'])); ?></div>
                        </div>
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width:64px;height:64px;background: linear-gradient(135deg,#ff4655,#e23f4d); font-weight:800;">
                            <?php echo $initials; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card-dark">
                    <div class="text-muted">Wallet Balance</div>
                    <div class="wallet-balance">₹<?php echo number_format($wallet_balance, 2); ?></div>
                    <span class="badge bg-info">Live</span>
                </div>
            </div>
        </div>

        <div class="card-dark mb-3">
            <h5 class="mb-3"><i class="fas fa-edit me-2"></i>Manual Wallet Adjustment</h5>
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="adjustment_type" class="form-label">Adjustment Type</label>
                        <select name="adjustment_type" id="adjustment_type" class="form-select" required>
                            <option value="credit">Credit (Add Money)</option>
                            <option value="debit">Debit (Deduct Money)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="amount" class="form-label">Amount (₹)</label>
                        <input type="number" name="amount" id="amount" class="form-control" step="0.01" min="0.01" required>
                    </div>
                    <div class="col-md-4">
                        <label for="reason" class="form-label">Reason</label>
                        <input type="text" name="reason" id="reason" class="form-control" placeholder="e.g., Manual correction, Refund" required>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" name="adjust_wallet" class="btn btn-primary"><i class="fas fa-check me-1"></i> Apply Adjustment</button>
                </div>
            </form>
        </div>

        <div class="card-dark">
            <h5 class="mb-3"><i class="fas fa-list me-2"></i>Recent Wallet Transactions</h5>
            <?php if (!empty($transactions)): ?>
                <div class="table-responsive">
                    <table class="table table-dark-custom table-hover">
                        <thead>
                            <tr>
                                <th>#</th><th>Date</th><th>Type</th><th>Amount</th><th>Status</th><th>Description</th><th>Tournament</th><th>Related User</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $txn): $t = strtolower((string)$txn['type']); $isCredit = in_array($t, ['credit','deposit','prize','prize_win']); ?>
                                <tr>
                                    <td><?php echo (int)$txn['id']; ?></td>
                                    <td><?php echo htmlspecialchars($txn['created_at']); ?></td>
                                    <td>
                                        <?php if ($isCredit): ?>
                                            <span class="badge bg-success txn-badge">Credit</span>
                                        <?php elseif (in_array($t, ['debit','withdrawal','deduct','entry_fee','topup'])): ?>
                                            <span class="badge bg-danger txn-badge">Debit</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary txn-badge"><?php echo htmlspecialchars(ucfirst($t ?: 'N/A')); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="<?php echo $isCredit ? 'amount-credit' : 'amount-debit'; ?>"><?php echo ($isCredit?'+':'-').' ₹'.number_format((float)$txn['amount'],2); ?></span></td>
                                    <td>
                                        <?php $st = strtolower((string)$txn['status']);
                                        if ($st==='completed') echo '<span class="badge bg-success txn-badge">Completed</span>';
                                        elseif ($st==='pending') echo '<span class="badge bg-warning text-dark txn-badge">Pending</span>';
                                        elseif ($st==='failed') echo '<span class="badge bg-danger txn-badge">Failed</span>';
                                        else echo '<span class="badge bg-secondary txn-badge">'.htmlspecialchars(ucfirst($st ?: 'N/A')).'</span>'; ?>
                                    </td>
                                    <td style="max-width:320px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?php echo htmlspecialchars($txn['description'] ?: ''); ?>"><?php echo htmlspecialchars($txn['description'] ?: ''); ?></td>
                                    <td>
                                        <?php if (!empty($txn['tournament_id'])): ?>
                                            <a class="btn btn-sm btn-outline-info" href="../creator/view_tournament.php?id=<?php echo (int)$txn['tournament_id']; ?>" target="_blank">
                                                #<?php echo (int)$txn['tournament_id']; ?><?php echo $txn['tournament_title'] ? ' - '.htmlspecialchars($txn['tournament_title']) : ''; ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($txn['related_user_name'] ?: '—'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x mb-2" style="opacity:.5"></i>
                    <div>No transactions found for this creator.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
