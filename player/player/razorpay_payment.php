<?php
session_start();
// Razorpay integration removed; redirect users
header('Location: wallet_dashboard.php?deposit=disabled');
exit;

// Check if user is logged in and is a player
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'player') {
    header('Location: ../../src/login.php');
    exit;
}

$player_id = $_SESSION['user_id'];
$registration_id = isset($_GET['reg_id']) ? (int)$_GET['reg_id'] : 0;

$stmt = $conn->prepare("
    SELECT r.*, t.title as tournament_name, t.entry_fee, t.date as tournament_date, u.name as username, u.email
    FROM registrations r
    JOIN tournaments t ON r.tournament_id = t.id
    JOIN users u ON r.player_id = u.id
    WHERE r.id = ? AND r.player_id = ?
");
$stmt->bind_param('ii', $registration_id, $player_id);
$stmt->execute();
$registration = $stmt->get_result()->fetch_assoc();

if (!$registration) {
    die('Registration not found');
}

// Check if payment is already completed
if ($registration['payment_status'] === 'Completed') {
    header('Location: player_dashboard.php?msg=already_paid');
    exit;
}

// Generate a short-lived payment token (5 minutes) and persist it
$payment_token = bin2hex(random_bytes(16));
$token_expires_at = date('Y-m-d H:i:s', time() + 300); // 5 minutes from now

// Ensure payment_tokens table exists (safe if run multiple times)
$create_table_sql = "CREATE TABLE IF NOT EXISTS payment_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_id INT NOT NULL,
    player_id INT NOT NULL,
    token VARCHAR(128) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (registration_id),
    INDEX (token)
)
ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$conn->query($create_table_sql);

// Insert token record
$ins_tok = $conn->prepare("INSERT INTO payment_tokens (registration_id, player_id, token, amount, expires_at) VALUES (?, ?, ?, ?, ?)");
if ($ins_tok) {
    $ins_tok->bind_param('iidss', $registration_id, $player_id, $payment_token, $registration['entry_fee'], $token_expires_at);
    $ins_tok->execute();
    $ins_tok->close();
}

// Generate unique order ID
$order_id = 'ORDER_' . $registration_id . '_' . time();
$amount_in_paise = $registration['entry_fee'] * 100; // Convert to paise

// Create Razorpay order via API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, RAZORPAY_API_URL . 'orders');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_USERPWD, RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'amount' => $amount_in_paise,
    'currency' => RAZORPAY_CURRENCY,
    'receipt' => $order_id,
    'notes' => [
        'registration_id' => $registration_id,
        'player_id' => $player_id,
        'tournament' => $registration['tournament_name'],
        'payment_token' => $payment_token
    ]
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    $error_message = 'Unable to create payment order. Please try again later.';
    $razorpay_order = null;
} else {
    $razorpay_order = json_decode($response, true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay with Razorpay - Free Fire Tournament</title>
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
        
        .razorpay-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .razorpay-logo img {
            height: 40px;
            filter: brightness(0) invert(1);
        }
        
        .tournament-info {
            background: rgba(255, 70, 85, 0.1);
            border-left: 4px solid var(--primary);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .amount-box {
            background: linear-gradient(135deg, rgba(255, 70, 85, 0.2), rgba(0, 245, 255, 0.2));
            border: 2px solid var(--accent-gaming);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            margin: 30px 0;
        }
        
        .amount-box .amount {
            font-size: 3rem;
            font-weight: bold;
            color: var(--accent-gaming);
            text-shadow: 0 0 20px rgba(0, 245, 255, 0.5);
        }
        
        .btn-razorpay {
            background: linear-gradient(135deg, #3395ff 0%, #0080ff 100%);
            border: none;
            padding: 15px 50px;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }
        
        .btn-razorpay:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(51, 149, 255, 0.5);
        }
        
        .payment-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .feature-card {
            background: rgba(0, 245, 255, 0.05);
            border: 1px solid rgba(0, 245, 255, 0.2);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .feature-card i {
            font-size: 2.5rem;
            color: var(--accent-gaming);
            margin-bottom: 10px;
        }
        
        .payment-methods {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
            margin: 20px 0;
        }
        
        .payment-method {
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            margin: 20px 0;
        }
        
        .loading-spinner.active {
            display: block;
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
        </div>
    </nav>

    <div class="payment-container">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= $error_message ?>
            </div>
        <?php endif; ?>

        <div class="payment-card">
            <div class="razorpay-logo">
                <h2 class="text-center mb-3">
                    <i class="bi bi-shield-check text-primary"></i> Secure Payment
                </h2>
                <p class="text-muted">Powered by Razorpay</p>
            </div>

            <!-- Tournament Information -->
            <div class="tournament-info">
                <h5><i class="bi bi-trophy-fill text-primary"></i> Tournament Details</h5>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <p><strong>Tournament:</strong> <?= htmlspecialchars($registration['tournament_name']) ?></p>
                        <p><strong>Player:</strong> <?= htmlspecialchars($registration['username']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Date:</strong> <?= date('d M Y', strtotime($registration['tournament_date'])) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($registration['email']) ?></p>
                    </div>
                </div>
            </div>

            <!-- Amount Box -->
            <div class="amount-box">
                <p class="mb-2">Entry Fee Amount</p>
                <div class="amount">₹<?= number_format($registration['entry_fee'], 2) ?></div>
            </div>

            <!-- Payment Features -->
            <div class="payment-features">
                <div class="feature-card">
                    <i class="bi bi-shield-lock-fill"></i>
                    <p class="mb-0">Secure & Encrypted</p>
                </div>
                <div class="feature-card">
                    <i class="bi bi-lightning-charge-fill"></i>
                    <p class="mb-0">Instant Processing</p>
                </div>
                <div class="feature-card">
                    <i class="bi bi-check-circle-fill"></i>
                    <p class="mb-0">Auto-Verification</p>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="text-center">
                <p class="text-muted mb-2">Accepted Payment Methods</p>
                <div class="payment-methods">
                    <span class="payment-method"><i class="bi bi-credit-card"></i> Cards</span>
                    <span class="payment-method"><i class="bi bi-bank"></i> Net Banking</span>
                    <span class="payment-method"><i class="bi bi-phone"></i> UPI</span>
                    <span class="payment-method"><i class="bi bi-wallet2"></i> Wallets</span>
                </div>
            </div>

            <?php if ($razorpay_order): ?>
                <!-- Pay Now Button -->
                <button id="rzp-button" class="btn btn-razorpay btn-primary">
                    <i class="bi bi-credit-card me-2"></i>Pay ₹<?= number_format($registration['entry_fee'], 2) ?> Now
                </button>

                <div class="loading-spinner" id="loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Processing...</span>
                    </div>
                    <p class="mt-2">Processing payment...</p>
                </div>

                <div class="text-center mt-3">
                    <small class="text-muted">
                        <i class="bi bi-lock-fill"></i> Your payment information is secure and encrypted
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    
    <?php if ($razorpay_order): ?>
    <script>
        const options = {
            "key": "<?= RAZORPAY_KEY_ID ?>",
            "amount": "<?= $razorpay_order['amount'] ?>",
            "currency": "<?= $razorpay_order['currency'] ?>",
            "name": "<?= RAZORPAY_BUSINESS_NAME ?>",
            "description": "Tournament Entry Fee - <?= htmlspecialchars($registration['tournament_name']) ?>",
            "image": "<?= getRazorpayBaseUrl() . RAZORPAY_BUSINESS_LOGO ?>",
            "order_id": "<?= $razorpay_order['id'] ?>",
            "handler": function (response) {
                document.getElementById('loading').classList.add('active');
                document.getElementById('rzp-button').disabled = true;
                
                // Send payment details to server for verification
                fetch('razorpay_verify.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        razorpay_payment_id: response.razorpay_payment_id,
                        razorpay_order_id: response.razorpay_order_id,
                        razorpay_signature: response.razorpay_signature,
                        registration_id: <?= $registration_id ?>,
                        amount: <?= $registration['entry_fee'] ?>,
                        payment_token: '<?= $payment_token ?>'
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'player_dashboard.php?payment=success&txn=' + response.razorpay_payment_id;
                    } else {
                        alert('Payment verification failed: ' + data.message);
                        window.location.href = 'player_dashboard.php?payment=failed';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please contact support.');
                });
            },
            "prefill": {
                "name": "<?= htmlspecialchars($registration['username']) ?>",
                "email": "<?= htmlspecialchars($registration['email']) ?>",
                "contact": ""
            },
            "notes": {
                "registration_id": "<?= $registration_id ?>",
                "tournament": "<?= htmlspecialchars($registration['tournament_name']) ?>"
            },
            "theme": {
                "color": "<?= RAZORPAY_THEME_COLOR ?>"
            },
            "modal": {
                "ondismiss": function() {
                    console.log('Payment cancelled by user');
                }
            }
        };

        const rzp = new Razorpay(options);

        document.getElementById('rzp-button').onclick = function(e) {
            e.preventDefault();
            rzp.open();
        };

        // Handle payment failures
        rzp.on('payment.failed', function (response) {
            alert('Payment failed: ' + response.error.description);
            window.location.href = 'player_dashboard.php?payment=failed';
        });
    </script>
    <?php endif; ?>
</body>
</html>
