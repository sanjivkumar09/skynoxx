<?php
session_start();
include '../src/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'creator') {
    header('Location: ../../src/login.php');
    exit();
}

$creator_id = (int)$_SESSION['user_id'];

// Fetch wallet balance
$res = $conn->query("SELECT wallet_balance FROM users WHERE id = $creator_id");
$balance = $res ? $res->fetch_assoc()['wallet_balance'] : 0;


// Fetch wallet transactions
$res = $conn->query("SELECT * FROM wallet_transactions WHERE user_id = $creator_id ORDER BY created_at DESC LIMIT 20");
$transactions = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];




// Fetch all players who joined creator's tournaments, including game_uid and entry_fee
$player_query = "SELECT 
        r.*, 
        u.name AS player_name, 
        COALESCE(p.game_uid, '') AS game_uid,
        t.title AS tournament_title, 
        t.id AS tournament_id, 
        t.entry_fee
    FROM registrations r
    JOIN tournaments t ON r.tournament_id = t.id
    JOIN users u ON r.player_id = u.id
    LEFT JOIN players_profile p ON p.user_id = r.player_id
    WHERE t.created_by = $creator_id
    ORDER BY r.joined_at DESC";
$player_res = $conn->query($player_query);
$player_rows = $player_res ? $player_res->fetch_all(MYSQLI_ASSOC) : [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creator Wallet</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/mobile-responsive.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        /* Keep it clean and readable */
        body { background: #f7f9fc; color: #212529; }
        .wallet-card { background: #101a24; color: #fff; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; }
        .btn-wallet { background: #00c2ff; color: #101a24; font-weight: 600; border-radius: 8px; }
        .btn-wallet:hover { background: #00a2d6; color: #fff; }
        .badge-status { font-size: .85rem; }
    </style>
</head>
<body>
    <div class="container my-5">
        <h2 class="mb-4">Creator Wallet</h2>
        <div class="wallet-card mb-4">
            <h5 class="card-title">Wallet Balance</h5>
            <p class="display-5 fw-bold">₹<?php echo number_format($balance,2); ?></p>
            <button type="button" class="btn btn-secondary mt-3" disabled>Online deposits unavailable</button>
            <a href="wallet_withdraw.php" class="btn btn-danger mt-3 ms-2">Withdraw</a>
        </div>
        <h4 class="mb-3">Recent Transactions</h4>
        <table class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $txn): ?>
                <tr>
                    <td><?php echo htmlspecialchars($txn['created_at']); ?></td>
                    <td>
                        <?php 
                            $type = strtolower($txn['type']);
                            if (in_array($type, ['credit','deposit','prize_win','prize'])) {
                                echo '<span class="badge bg-success badge-status">Credit</span>';
                            } elseif (in_array($type, ['debit','withdrawal','entry_fee'])) {
                                echo '<span class="badge bg-danger badge-status">Debit</span>';
                            } else {
                                echo '<span class="badge bg-secondary badge-status">'.htmlspecialchars($txn['type']).'</span>';
                            }
                        ?>
                    </td>
                    <td>
                        <?php 
                            // prefix for clarity
                            $sign = in_array(strtolower($txn['type']), ['credit','deposit','prize_win','prize']) ? '+' : '-';
                            echo $sign.' ₹'.number_format($txn['amount'],2);
                        ?>
                    </td>
                    <td>
                        <?php
                        // If this is a withdrawal request, show real-time status
                        if (strpos($txn['description'], 'Withdrawal Request') !== false) {
                            // Find matching withdrawal record
                            $wres = $conn->query("SELECT status FROM withdrawals WHERE creator_id = $creator_id AND amount = {$txn['amount']} AND DATE(requested_at) = DATE('{$txn['created_at']}') ORDER BY requested_at DESC LIMIT 1");
                            $w = $wres ? $wres->fetch_assoc() : null;
                            if ($w) {
                                if ($w['status'] === 'pending') {
                                    echo 'Wallet Withdrawal Request (pending approval)';
                                } elseif ($w['status'] === 'completed') {
                                    echo 'Wallet Withdrawal Request (approved & paid)';
                                } elseif ($w['status'] === 'failed') {
                                    echo 'Wallet Withdrawal Request (rejected)';
                                } else {
                                    echo htmlspecialchars($txn['description']);
                                }
                            } else {
                                echo htmlspecialchars($txn['description']);
                            }
                        } else {
                            echo htmlspecialchars($txn['description']);
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h4 class="mb-3 mt-5">Players Joined Your Tournaments</h4>
        <table class="table table-striped table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Player Name</th>
                    <th>Game UID</th>
                    <th>Tournament</th>
                    <th>Slot No</th>
                    <th>Payment Amount</th>
                    <th>Payment Status</th>
                    <th>Joined At</th>
                    <th>Profile</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($player_rows as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['player_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['game_uid'] ?: 'Not Set'); ?></td>
                    <td><a href="../creator/view_tournament.php?id=<?php echo $row['tournament_id']; ?>" target="_blank"><?php echo htmlspecialchars($row['tournament_title']); ?></a></td>
                    <td><?php echo htmlspecialchars($row['slot_no']); ?></td>
                    <td>
                        <?php 
                            $paidStatuses = ['success','paid'];
                            $amt = (in_array(strtolower($row['payment_status']), $paidStatuses) && $row['entry_fee'] > 0) ? (float)$row['entry_fee'] : 0;
                            echo '₹'.number_format($amt, 2);
                        ?>
                    </td>
                    <td>
                        <?php 
                            $ps = strtolower($row['payment_status']);
                            if (in_array($ps, ['success','paid'])) {
                                echo '<span class="badge bg-success badge-status">Paid</span>';
                            } elseif (in_array($ps, ['pending','initiated'])) {
                                echo '<span class="badge bg-warning text-dark badge-status">Pending</span>';
                            } elseif (in_array($ps, ['failed','refunded','cancelled'])) {
                                echo '<span class="badge bg-danger badge-status">'.htmlspecialchars(ucfirst($ps)).'</span>';
                            } else {
                                echo '<span class="badge bg-secondary badge-status">'.htmlspecialchars(ucfirst($row['payment_status'] ?: 'N/A')).'</span>';
                            }
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['joined_at']); ?></td>
                    <td><a class="btn btn-sm btn-outline-primary" href="../admin/view_player_profile.php?user_id=<?php echo (int)$row['player_id']; ?>" target="_blank">View</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
