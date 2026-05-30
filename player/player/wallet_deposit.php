<?php
session_start();
include '../src/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../src/login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/gaming-theme.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Money to Wallet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
</head>
<body>
    <div class="wallet-container">
        <div class="wallet-card animate__animated animate__fadeInUp">
            <h2 class="wallet-header"><i class="fas fa-wallet me-2"></i>Add Money to Wallet</h2>
            <form method="post" autocomplete="off">
                <div class="mb-3">
                    <label for="amount" class="form-label">Amount (₹)</label>
                    <input type="number" step="0.01" min="1" class="form-control" name="amount" id="amount" 
                           required placeholder="Enter amount to add">
                </div>
                <div class="mb-3">
                    <div class="alert alert-warning" role="alert" style="border-radius: 12px;">
                        <i class="fas fa-info-circle me-2"></i>
                        Online wallet deposits are currently unavailable. You can submit a manual UPI top-up request instead.
                        <div class="mt-2">
                            <a href="wallet_topup_manual.php" class="btn btn-sm btn-light">
                                <i class="fas fa-qrcode me-1"></i> Manual UPI Top-up
                            </a>
                        </div>
                    </div>
                </div>
                <?php if (!empty($error)): ?>
                    <div class="alert animate__animated animate__shakeX"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <a href="wallet_dashboard.php" id="payBtn" class="btn btn-wallet mt-2">
                    <i class="fas fa-arrow-left me-2"></i>Back to Wallet
                </a>
            </form>
            <a href="wallet_dashboard.php" class="btn btn-secondary mt-3">
                <i class="fas fa-arrow-left me-2"></i>Back to Wallet
            </a>
        </div>
    </div>
    <script src="https://kit.fontawesome.com/2c7fc25c36.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Online deposits disabled -->
</body>
</html>