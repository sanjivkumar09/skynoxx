<?php
session_start();
include '../src/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../src/login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
// Fetch tournaments available to join
$tournaments = [];
$res = $conn->query("SELECT t.id, t.title, t.entry_fee, t.created_by, t.status FROM tournaments t WHERE t.status = 'upcoming'");
while ($row = $res->fetch_assoc()) $tournaments[] = $row;
// Fetch wallet balance
$res = $conn->query("SELECT wallet_balance FROM users WHERE id = $user_id");
$balance = $res ? $res->fetch_assoc()['wallet_balance'] : 0;
// Handle join request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tournament_id'])) {
    $tid = (int)$_POST['tournament_id'];
    $tournament = null;
    foreach ($tournaments as $t) if ($t['id'] == $tid) $tournament = $t;
    if ($tournament && $balance >= $tournament['entry_fee']) {
        // Deduct from player wallet
        $conn->query("UPDATE users SET wallet_balance = wallet_balance - {$tournament['entry_fee']} WHERE id = $user_id");
        // Credit to creator wallet
        $conn->query("UPDATE users SET wallet_balance = wallet_balance + {$tournament['entry_fee']} WHERE id = {$tournament['created_by']}");
        // Record transactions
        $stmt = $conn->prepare("INSERT INTO wallet_transactions (user_id, type, amount, related_user_id, tournament_id, description, status) VALUES (?, 'deduct', ?, ?, ?, ?, 'completed')");
        $stmt->bind_param('idiss', $user_id, $tournament['entry_fee'], $tournament['created_by'], $tid, $tournament['title']);
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO wallet_transactions (user_id, type, amount, related_user_id, tournament_id, description, status) VALUES (?, 'transfer', ?, ?, ?, ?, 'completed')");
        $stmt->bind_param('idiss', $tournament['created_by'], $tournament['entry_fee'], $user_id, $tid, $tournament['title']);
        $stmt->execute();
        $stmt->close();
        // Register player in tournament
        $stmt = $conn->prepare("INSERT INTO registrations (tournament_id, player_id, payment_status) VALUES (?, ?, 'success')");
        $stmt->bind_param('ii', $tid, $user_id);
        $stmt->execute();
        $stmt->close();
        $success = true;
        $balance -= $tournament['entry_fee'];
    } else {
        $error = "Insufficient wallet balance or invalid tournament.";
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
    <title>Join Tournament (Wallet)</title>
</head>
<body>
<div class="container py-4">
    <h2 class="mb-4">Join Tournament (Wallet)</h2>
    <div class="card p-3 mb-3">
        <h5 class="card-title">Wallet Balance</h5>
        <div class="fs-3 fw-bold">₹<?php echo number_format($balance,2); ?></div>
        <a href="wallet_deposit.php" class="btn btn-wallet mt-3">Add Money</a>
    </div>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success">Successfully joined tournament and paid entry fee!</div>
    <?php elseif (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <div class="card p-3 mb-3">
        <h5 class="card-title">Available Tournaments</h5>
        <form method="post">
            <div class="mb-3">
                <label for="tournament_id" class="form-label">Select Tournament</label>
                <select class="form-select" name="tournament_id" id="tournament_id" required>
                    <option value="">Choose...</option>
                    <?php foreach ($tournaments as $t): ?>
                        <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['title']); ?> (Entry Fee: ₹<?php echo number_format($t['entry_fee'],2); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-wallet">Join & Pay</button>
        </form>
    </div>
</div>
</body>
</html>
