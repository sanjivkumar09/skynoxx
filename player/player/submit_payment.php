<?php
session_start();
require_once '../src/db.php';

// Check if user is logged in and is a player
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'player') {
    header('Location: ../../src/login.php');
    exit;
}

$player_id = $_SESSION['user_id'];
$registration_id = isset($_GET['reg_id']) ? (int)$_GET['reg_id'] : 0;

// Fetch registration details
$stmt = $conn->prepare("
    SELECT r.*, t.name as tournament_name, t.entry_fee, t.tournament_date
    FROM registrations r
    JOIN tournaments t ON r.tournament_id = t.id
    WHERE r.id = ? AND r.player_id = ?
");
$stmt->bind_param('ii', $registration_id, $player_id);
$stmt->execute();
$registration = $stmt->get_result()->fetch_assoc();

if (!$registration) {
    die('Registration not found');
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    $payment_method = $_POST['payment_method'];
    $transaction_id = $_POST['transaction_id'];
    $payment_screenshot = '';
    
    // Handle screenshot upload
    if (isset($_FILES['payment_screenshot']) && $_FILES['payment_screenshot']['error'] === 0) {
        $upload_dir = '../src/uploads/payments/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = pathinfo($_FILES['payment_screenshot']['name'], PATHINFO_EXTENSION);
        $filename = 'payment_' . $registration_id . '_' . time() . '.' . $file_ext;
        $upload_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['payment_screenshot']['tmp_name'], $upload_path)) {
            $payment_screenshot = $filename;
        }
    }
    
    // Update registration with payment details
    $stmt = $conn->prepare("
        UPDATE registrations 
        SET payment_method = ?, 
            transaction_id = ?, 
            payment_screenshot = ?,
            payment_date = NOW(),
            payment_status = 'Pending'
        WHERE id = ? AND player_id = ?
    ");
    $stmt->bind_param('sssii', $payment_method, $transaction_id, $payment_screenshot, $registration_id, $player_id);
    
    if ($stmt->execute()) {
        $success_message = 'Payment details submitted successfully! Awaiting admin verification.';
    } else {
        $error_message = 'Failed to submit payment details.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Payment - Free Fire Tournament Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #ff4655;
            --secondary: #1a1a2e;
            --accent-gaming: #00f5ff;
            --dark-bg: #0f0f23;
            --card-bg: #1a1a2e;
            --text-light: #e0e0e0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--dark-bg) 0%, #16213e 100%);
            color: var(--text-light);
            min-height: 100vh;
        }
        
        .navbar {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 2px solid var(--primary);
            padding: 0.5rem 0;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary) !important;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .navbar-brand img {
            height: 45px;
            width: auto;
        }
        
        .payment-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 0 20px;
        }
        
        .payment-card {
            background: rgba(26, 26, 46, 0.9);
            border: 1px solid rgba(255, 70, 85, 0.3);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }
        
        .tournament-info {
            background: rgba(255, 70, 85, 0.1);
            border-left: 4px solid var(--primary);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .form-label {
            color: var(--accent-gaming);
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .form-control, .form-select {
            background: rgba(15, 15, 35, 0.8);
            border: 1px solid rgba(0, 245, 255, 0.3);
            color: var(--text-light);
            padding: 12px;
            border-radius: 8px;
        }
        
        .form-control:focus, .form-select:focus {
            background: rgba(15, 15, 35, 0.9);
            border-color: var(--accent-gaming);
            color: var(--text-light);
            box-shadow: 0 0 0 0.25rem rgba(0, 245, 255, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #ff6b7a 100%);
            border: none;
            padding: 12px 40px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 70, 85, 0.4);
        }
        
        .payment-instructions {
            background: rgba(0, 245, 255, 0.1);
            border: 1px solid rgba(0, 245, 255, 0.3);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .payment-instructions h5 {
            color: var(--accent-gaming);
            margin-bottom: 15px;
        }
        
        .payment-instructions ol {
            margin: 0;
            padding-left: 20px;
        }
        
        .payment-instructions li {
            margin-bottom: 10px;
            line-height: 1.6;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            border-left: 4px solid #28a745;
            color: #4cff4c;
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            border-left: 4px solid #dc3545;
            color: #ff6b6b;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid #ffc107;
        }
        
        .status-completed {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid #28a745;
        }
        
        .file-upload-label {
            cursor: pointer;
            background: rgba(0, 245, 255, 0.1);
            border: 2px dashed rgba(0, 245, 255, 0.3);
            padding: 30px;
            text-align: center;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .file-upload-label:hover {
            border-color: var(--accent-gaming);
            background: rgba(0, 245, 255, 0.15);
        }
        
        .file-upload-label i {
            font-size: 3rem;
            color: var(--accent-gaming);
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="player_dashboard.php">
                <img src="/assets/images/logo.png" alt="Logo">
                Free Fire Tournament Platform
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="player_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../../src/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="payment-container">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= $success_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= $error_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="payment-card">
            <h2 class="text-center mb-4">
                <i class="bi bi-credit-card text-primary"></i> Submit Payment Details
            </h2>

            <!-- Tournament Information -->
            <div class="tournament-info">
                <h5><i class="bi bi-trophy-fill text-primary"></i> Tournament Details</h5>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <p><strong>Tournament:</strong> <?= htmlspecialchars($registration['tournament_name']) ?></p>
                        <p><strong>Entry Fee:</strong> ₹<?= number_format($registration['entry_fee'], 2) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Date:</strong> <?= date('d M Y', strtotime($registration['tournament_date'])) ?></p>
                        <p><strong>Status:</strong> 
                            <span class="status-badge status-<?= strtolower($registration['payment_status']) ?>">
                                <?= htmlspecialchars($registration['payment_status']) ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Payment Instructions -->
            <div class="payment-instructions">
                <h5><i class="bi bi-info-circle-fill"></i> Payment Instructions</h5>
                <ol>
                    <li>Make payment of <strong>₹<?= number_format($registration['entry_fee'], 2) ?></strong> using any of the supported payment methods</li>
                    <li>Note down the transaction ID from your payment receipt</li>
                    <li>Take a screenshot of the payment confirmation</li>
                    <li>Fill in the details below and submit</li>
                    <li>Admin will verify your payment within 24 hours</li>
                </ol>
            </div>

            <?php if ($registration['payment_status'] === 'Completed'): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Your payment has been verified and confirmed! You're all set for the tournament.
                </div>
            <?php else: ?>
                <!-- Payment Form -->
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="form-label">Payment Method *</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="">Select Payment Method</option>
                            <option value="UPI">UPI (Google Pay, PhonePe, Paytm)</option>
                            <option value="Card">Debit/Credit Card</option>
                            <option value="Net Banking">Net Banking</option>
                            <option value="Wallet">Mobile Wallet</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Transaction ID / Reference Number *</label>
                        <input type="text" name="transaction_id" class="form-control" 
                               placeholder="Enter transaction ID from payment receipt" required>
                        <small class="text-muted">This is the unique ID you received after completing the payment</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Upload Payment Screenshot *</label>
                        <input type="file" name="payment_screenshot" id="payment_screenshot" 
                               class="form-control d-none" accept="image/*" required>
                        <label for="payment_screenshot" class="file-upload-label w-100">
                            <i class="bi bi-cloud-upload"></i>
                            <p class="mb-0">Click to upload screenshot</p>
                            <small class="text-muted">PNG, JPG, JPEG (Max 5MB)</small>
                        </label>
                        <div id="file-name" class="mt-2 text-center text-muted"></div>
                    </div>

                    <div class="text-center">
                        <button type="submit" name="submit_payment" class="btn btn-primary btn-lg">
                            <i class="bi bi-send-fill me-2"></i>Submit Payment Details
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File upload feedback
        document.getElementById('payment_screenshot').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            const fileNameDiv = document.getElementById('file-name');
            if (fileName) {
                fileNameDiv.innerHTML = `<i class="bi bi-file-earmark-image text-primary"></i> ${fileName}`;
            }
        });
    </script>
</body>
</html>
