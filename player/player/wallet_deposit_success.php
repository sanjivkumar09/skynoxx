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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/gaming-theme.css">
    <meta charset="UTF-8">
    <title>Wallet Deposit Success</title>
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
