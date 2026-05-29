<?php
session_start();
include '../src/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../src/login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
$amount = $_SESSION['deposit_amount'] ?? 0;
if ($amount > 0) {
    // Update wallet balance
    $conn->query("UPDATE users SET wallet_balance = wallet_balance + $amount WHERE id = $user_id");
    // Record transaction
    $stmt = $conn->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description, status) VALUES (?, 'deposit', ?, 'Wallet deposit', 'completed')");
    $stmt->bind_param('id', $user_id, $amount);
    $stmt->execute();
    $stmt->close();
    unset($_SESSION['deposit_amount']);
    $success = true;
} else {
    $success = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Wallet Deposit Success</title>
    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/mobile-responsive.css">
    <style>
        body { background: #101a24; color: #fff; }
        .card { background: #23243a; border-radius: 16px; border: none; }
        .btn-wallet { background: #00f5ff; color: #101a24; font-weight: 600; border-radius: 8px; }
        .btn-wallet:hover { background: #ffaa00; color: #23243a; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="card p-3 mb-3">
        <?php if ($success): ?>
            <h4 class="mb-3">Deposit Successful!</h4>
            <p>Your wallet has been credited with ₹<?php echo number_format($amount,2); ?>.</p>
            <a href="wallet_dashboard.php" class="btn btn-wallet">Go to Wallet</a>
        <?php else: ?>
            <h4 class="mb-3">Deposit Failed</h4>
            <p>No deposit amount found. Please try again.</p>
            <a href="wallet_deposit.php" class="btn btn-wallet">Try Again</a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
