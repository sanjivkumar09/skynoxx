<?php
session_start();
include 'db.php';
include 'auth.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$tournament_id = $_GET['tournament_id'] ?? $_GET['id'] ?? null;

if (!$tournament_id) {
    header("Location: tournaments.php");
    exit();
}

$query = "SELECT * FROM tournaments WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $tournament_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: tournaments.php");
    exit();
}

$tournament = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'];
    $entry_fee = $tournament['entry_fee'];

    // Process payment (this is a placeholder, integrate Razorpay or Paytm here)
    $payment_status = 'success'; // Assume payment is successful for this example

    if ($payment_status === 'success') {
        $insert_query = "INSERT INTO registrations (tournament_id, player_id, payment_status, joined_at) VALUES (?, ?, 'success', NOW())";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("ii", $tournament_id, $user_id);
        $insert_stmt->execute();

        header("Location: ../player/player/player_dashboard.php");
        exit();
    } else {
        $error_message = "Payment failed. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Tournament</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h1>Join Tournament: <?php echo htmlspecialchars($tournament['title']); ?></h1>
        <p><?php echo htmlspecialchars($tournament['description']); ?></p>
        <p>Entry Fee: ₹<?php echo htmlspecialchars($tournament['entry_fee']); ?></p>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="payment_method">Payment Method</label>
                <select name="payment_method" id="payment_method" required>
                    <option value="razorpay">Razorpay</option>
                    <option value="paytm">Paytm</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Join Tournament</button>
        </form>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>