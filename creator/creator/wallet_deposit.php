<?php
session_start();
include '../src/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'creator') {
    header('Location: ../../src/login.php');
    exit();
}

// Online deposits are disabled
header('Location: wallet_dashboard.php?deposit=disabled');
exit();

$creator_id = (int)$_SESSION['user_id'];

// Handle Razorpay payment callback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['razorpay_payment_id'], $_POST['amount'])) {
    $amount = (float)$_POST['amount'];
    $razorpay_payment_id = $_POST['razorpay_payment_id'];
    // You should verify payment with Razorpay API here (for demo, we trust it)
    $conn->query("UPDATE users SET wallet_balance = wallet_balance + $amount WHERE id = $creator_id");
    $conn->query("INSERT INTO wallet_transactions (user_id, type, amount, description) VALUES ($creator_id, 'credit', $amount, 'Wallet Deposit via Razorpay: $razorpay_payment_id')");
    $_SESSION['success_message'] = 'Amount added to wallet!';
    header('Location: wallet_dashboard.php');
    exit();
}

// Fetch wallet balance
$res = $conn->query("SELECT wallet_balance FROM users WHERE id = $creator_id");
$balance = $res ? $res->fetch_assoc()['wallet_balance'] : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/gaming-theme.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Money - Creator Wallet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</head>
<body>
    <div class="container my-4">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 col-12">
                <div class="wallet-card p-4 mb-4 animate-card">
                    <i class="fas fa-plus-circle deposit-icon"></i>
                    <h2 class="wallet-header">Add Money to Wallet</h2>
                    
                    <!-- Balance Display -->
                    <div class="balance-card">
                        <p class="mb-1">Current Balance</p>
                        <div class="balance-amount">₹<?php echo number_format($balance, 2); ?></div>
                        <small class="text-muted">Add funds instantly with Razorpay</small>
                    </div>
                    
                    <form id="razorpayForm">
                        <div class="mb-4">
                            <label for="amount" class="form-label">Deposit Amount (₹)</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" min="1" step="0.01" class="form-control" id="amount" name="amount" required placeholder="Enter amount to deposit">
                            </div>
                            
                            <!-- Quick Amount Buttons -->
                            <div class="quick-amounts mt-3">
                                <button type="button" class="quick-amount-btn" data-amount="100">₹100</button>
                                <button type="button" class="quick-amount-btn" data-amount="500">₹500</button>
                                <button type="button" class="quick-amount-btn" data-amount="1000">₹1,000</button>
                                <button type="button" class="quick-amount-btn" data-amount="2000">₹2,000</button>
                            </div>
                        </div>
                        
                        <!-- Payment Method -->
                        <div class="mb-4">
                            <label class="form-label">Payment Method</label>
                            <div class="payment-methods">
                                <div class="payment-method active">
                                    <i class="fas fa-credit-card text-primary"></i>
                                    <span>Razorpay</span>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" id="payBtn" class="btn btn-success w-100 py-2 mt-2">
                            <i class="fas fa-lock me-2"></i>Add Money Securely
                        </button>
                    </form>
                    
                    <p class="info-text">
                        <i class="fas fa-shield-alt me-1"></i>
                        Your payment is secured with Razorpay. Funds will be added instantly.
                    </p>
                </div>
                
                <a href="wallet_dashboard.php" class="btn btn-secondary w-100 py-2">
                    <i class="fas fa-arrow-left me-2"></i>Back to Wallet
                </a>
            </div>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/2c7fc25c36.js" crossorigin="anonymous"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const amountInput = document.getElementById('amount');
            const payBtn = document.getElementById('payBtn');
            const quickAmountBtns = document.querySelectorAll('.quick-amount-btn');
            
            // Quick amount buttons
            quickAmountBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const amount = this.getAttribute('data-amount');
                    amountInput.value = amount;
                    
                    // Update active state
                    quickAmountBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                });
            });
            
            // Razorpay payment handler (create order -> open checkout -> verify)
            payBtn.addEventListener('click', async function(e) {
                e.preventDefault();
                const amount = parseFloat(amountInput.value || '0');
                if (!amount || amount <= 0) {
                    showError('Please enter a valid amount');
                    return;
                }

                // Show loading state
                payBtn.classList.add('btn-loading');
                payBtn.disabled = true;
                payBtn.innerHTML = 'Processing...';

                try {
                    // 1) Create Razorpay order on server
                    const orderRes = await fetch('../src/wallet_deposit_create_order.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ amount })
                    });
                    const orderData = await orderRes.json();
                    if (!orderRes.ok || !orderData.success) {
                        throw new Error(orderData.message || 'Failed to create order');
                    }

                    // 2) Open Razorpay Checkout with order
                    const options = {
                        key: orderData.key,
                        amount: Math.round(amount * 100),
                        currency: orderData.currency || 'INR',
                        name: 'SKYNOXX',
                        description: 'Wallet Deposit',
                        order_id: orderData.order.id,
                        handler: async function (response) {
                            try {
                                // 3) Verify on server and credit wallet
                                const verifyRes = await fetch('../src/wallet_deposit_verify.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({
                                        razorpay_payment_id: response.razorpay_payment_id,
                                        razorpay_order_id: response.razorpay_order_id,
                                        razorpay_signature: response.razorpay_signature,
                                        amount: amount
                                    })
                                });
                                const verifyData = await verifyRes.json();
                                if (!verifyRes.ok || !verifyData.success) {
                                    throw new Error(verifyData.message || 'Verification failed');
                                }
                                // Success -> redirect to wallet dashboard
                                window.location.href = 'wallet_dashboard.php?deposit=success';
                            } catch (err) {
                                showError(err.message);
                                payBtn.classList.remove('btn-loading');
                                payBtn.disabled = false;
                                payBtn.innerHTML = '<i class="fas fa-lock me-2"></i>Add Money Securely';
                            }
                        },
                        prefill: {
                            contact: '<?php echo $_SESSION['phone'] ?? ''; ?>',
                            email: '<?php echo $_SESSION['email'] ?? ''; ?>'
                        },
                        config: {
                            display: {
                                blocks: {
                                    banks: {
                                        name: 'Pay using UPI',
                                        instruments: [
                                            {
                                                method: 'upi',
                                                flows: ['qr', 'intent', 'collect']
                                            }
                                        ]
                                    }
                                },
                                sequence: ['block.banks'],
                                preferences: {
                                    show_default_blocks: true
                                }
                            }
                        },
                        theme: { color: '#10b981' }
                    };
                    const rzp = new Razorpay(options);
                    rzp.on('payment.failed', function() {
                        payBtn.classList.remove('btn-loading');
                        payBtn.disabled = false;
                        payBtn.innerHTML = '<i class="fas fa-lock me-2"></i>Add Money Securely';
                        showError('Payment failed. Please try again.');
                    });
                    rzp.open();
                } catch (err) {
                    showError(err.message);
                    payBtn.classList.remove('btn-loading');
                    payBtn.disabled = false;
                    payBtn.innerHTML = '<i class=\"fas fa-lock me-2\"></i>Add Money Securely';
                }
            });
            
            function showError(message) {
                // Remove any existing error alerts
                const existingAlert = document.querySelector('.alert-danger');
                if (existingAlert) {
                    existingAlert.remove();
                }
                
                // Create new error alert
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger mb-3 animate__animated animate__shakeX';
                alertDiv.innerHTML = `<i class="fas fa-exclamation-circle me-2"></i>${message}`;
                
                // Insert before the submit button
                const form = document.getElementById('razorpayForm');
                form.insertBefore(alertDiv, payBtn);
                
                // Scroll to error
                alertDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            
            // Input validation
            amountInput.addEventListener('input', function() {
                const value = parseFloat(this.value) || 0;
                
                if (value < 1) {
                    this.setCustomValidity('Minimum deposit is ₹1');
                } else {
                    this.setCustomValidity('');
                }
            });
        });
    </script>
</body>
</html>