<?php
session_start();
include '../src/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../src/login.php');
    exit();
}

// Handle payment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment'])) {
    $registration_id = (int)$_POST['registration_id'];
    $payment_status = $_POST['payment_status'];
    $payment_method = $_POST['payment_method'] ?? '';
    $transaction_id = $_POST['transaction_id'] ?? '';
    
    $stmt = $conn->prepare("UPDATE registrations SET payment_status = ?, payment_method = ?, transaction_id = ? WHERE id = ?");
    $stmt->bind_param('sssi', $payment_status, $payment_method, $transaction_id, $registration_id);
    
    if ($stmt->execute()) {
        $message = "Payment status updated successfully!";
    } else {
        $error = "Error updating payment status.";
    }
    $stmt->close();
}

// Handle prize distribution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['distribute_prize'])) {
    $registration_id = (int)$_POST['registration_id'];
    $prize_amount = floatval($_POST['prize_amount']);
    $prize_status = 'Distributed';
    
    $stmt = $conn->prepare("UPDATE registrations SET prize_won = ?, prize_status = ? WHERE id = ?");
    $stmt->bind_param('dsi', $prize_amount, $prize_status, $registration_id);
    
    if ($stmt->execute()) {
        $message = "Prize distributed successfully!";
    } else {
        $error = "Error distributing prize.";
    }
    $stmt->close();
}

// Fetch all registrations with payment details
$query = "SELECT r.id, r.player_id, r.tournament_id, r.payment_status, r.payment_method, 
          r.transaction_id, r.prize_won, r.prize_status, r.joined_at,
          u.name as player_name, u.email as player_email,
          t.title as tournament_title, t.entry_fee, t.prize_pool,
          p.upi_id, p.game_uid
          FROM registrations r
          JOIN users u ON r.player_id = u.id
          JOIN tournaments t ON r.tournament_id = t.id
          LEFT JOIN players_profile p ON r.player_id = p.user_id
          ORDER BY r.joined_at DESC";
$result = $conn->query($query);
$registrations = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Calculate statistics
$total_revenue = 0;
$pending_payments = 0;
$completed_payments = 0;
$total_prizes_distributed = 0;

foreach ($registrations as $reg) {
    if ($reg['payment_status'] === 'Completed') {
        $total_revenue += floatval($reg['entry_fee']);
        $completed_payments++;
    } elseif ($reg['payment_status'] === 'Pending') {
        $pending_payments++;
    }
    if ($reg['prize_won']) {
        $total_prizes_distributed += floatval($reg['prize_won']);
    }
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
    <title>Payment Management - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="neon-text">Payment Management</h1>
        <div class="dropdown">
            <button class="btn btn-gaming dropdown-toggle" type="button" id="adminMenu" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle me-2"></i>Admin Menu
            </button>
            <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="adminMenu">
                <li><a class="dropdown-item" href="admin_dashboard.php"><i class="bi bi-house me-2"></i>Dashboard</a></li>
                <li><a class="dropdown-item" href="analytics_dashboard.php"><i class="bi bi-graph-up me-2"></i>Analytics</a></li>
                <li><a class="dropdown-item" href="payment_management.php"><i class="bi bi-credit-card me-2"></i>Payment Management</a></li>
                <li><a class="dropdown-item" href="admin_withdrawals.php"><i class="bi bi-cash-stack me-2"></i>Withdrawal Management</a></li>
                <li><a class="dropdown-item" href="wallet_deposits.php"><i class="bi bi-wallet2 me-2"></i>Wallet Deposits</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../../src/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>

    <?php if (isset($message)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card-modern stat-card">
                <div class="stat-value">₹<?php echo number_format($total_revenue, 2); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-modern stat-card">
                <div class="stat-value"><?php echo $completed_payments; ?></div>
                <div class="stat-label">Completed Payments</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-modern stat-card">
                <div class="stat-value"><?php echo $pending_payments; ?></div>
                <div class="stat-label">Pending Payments</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-modern stat-card">
                <div class="stat-value">₹<?php echo number_format($total_prizes_distributed, 2); ?></div>
                <div class="stat-label">Prizes Distributed</div>
            </div>
        </div>
    </div>

    <!-- Payment Records Table -->
    <div class="card-modern">
        <h3 class="mb-4">All Payments & Registrations</h3>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Player</th>
                        <th>Tournament</th>
                        <th>Entry Fee</th>
                        <th>Payment Status</th>
                        <th>Transaction ID</th>
                        <th>Prize Won</th>
                        <th>UPI ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registrations)): ?>
                        <tr><td colspan="9" class="text-center">No payment records found.</td></tr>
                    <?php else: foreach ($registrations as $reg): ?>
                        <tr>
                            <td><?php echo $reg['id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($reg['player_name']); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($reg['player_email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($reg['tournament_title']); ?></td>
                            <td>₹<?php echo number_format($reg['entry_fee'], 2); ?></td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($reg['payment_status']); ?>">
                                    <?php echo htmlspecialchars($reg['payment_status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($reg['transaction_id'] ?? 'N/A'); ?></td>
                            <td><?php echo $reg['prize_won'] ? '₹' . number_format($reg['prize_won'], 2) : 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($reg['upi_id'] ?? 'N/A'); ?></td>
                            <td>
                                <button class="btn btn-sm btn-gaming" data-bs-toggle="modal" data-bs-target="#paymentModal<?php echo $reg['id']; ?>">
                                    <i class="bi bi-pencil"></i> Update
                                </button>
                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#prizeModal<?php echo $reg['id']; ?>">
                                    <i class="bi bi-trophy"></i> Prize
                                </button>
                            </td>
                        </tr>

                        <!-- Payment Update Modal -->
                        <div class="modal fade" id="paymentModal<?php echo $reg['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Update Payment Status</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="post">
                                        <div class="modal-body">
                                            <input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Payment Status</label>
                                                <select name="payment_status" class="form-select" required>
                                                    <option value="Pending" <?php echo $reg['payment_status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="Completed" <?php echo $reg['payment_status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                                    <option value="Failed" <?php echo $reg['payment_status'] === 'Failed' ? 'selected' : ''; ?>>Failed</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Payment Method</label>
                                                <select name="payment_method" class="form-select">
                                                    <option value="">Select Method</option>
                                                    <option value="UPI" <?php echo $reg['payment_method'] === 'UPI' ? 'selected' : ''; ?>>UPI</option>
                                                    <option value="Card" <?php echo $reg['payment_method'] === 'Card' ? 'selected' : ''; ?>>Card</option>
                                                    <option value="Net Banking" <?php echo $reg['payment_method'] === 'Net Banking' ? 'selected' : ''; ?>>Net Banking</option>
                                                    <option value="Wallet" <?php echo $reg['payment_method'] === 'Wallet' ? 'selected' : ''; ?>>Wallet</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Transaction ID</label>
                                                <input type="text" name="transaction_id" class="form-control" value="<?php echo htmlspecialchars($reg['transaction_id'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" name="update_payment" class="btn btn-gaming">Update Payment</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Prize Distribution Modal -->
                        <div class="modal fade" id="prizeModal<?php echo $reg['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Distribute Prize</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="post">
                                        <div class="modal-body">
                                            <input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>">
                                            <div class="alert alert-info">
                                                <strong>Player UPI ID:</strong> <?php echo htmlspecialchars($reg['upi_id'] ?? 'Not provided'); ?>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Prize Amount (₹)</label>
                                                <input type="number" step="0.01" name="prize_amount" class="form-control" value="<?php echo $reg['prize_won'] ?? ''; ?>" required>
                                            </div>
                                            <div class="alert alert-warning">
                                                Make sure to transfer the prize to the player's UPI ID before marking as distributed.
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" name="distribute_prize" class="btn btn-success">Distribute Prize</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payment Instructions -->
    <div class="card-modern">
        <h3>Payment Management Guidelines</h3>
        <div class="row mt-3">
            <div class="col-md-6">
                <h5><i class="bi bi-credit-card text-success"></i> Accepting Payments</h5>
                <ul>
                    <li>Verify transaction IDs from players</li>
                    <li>Cross-check payment amounts with entry fees</li>
                    <li>Update payment status to "Completed" after verification</li>
                    <li>Mark as "Failed" if payment issues occur</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h5><i class="bi bi-trophy text-warning"></i> Distributing Prizes</h5>
                <ul>
                    <li>Ensure player has provided UPI ID</li>
                    <li>Transfer prize amount to player's UPI</li>
                    <li>Enter the prize amount in the system</li>
                    <li>Keep transaction records for reference</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
