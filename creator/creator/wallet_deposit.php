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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Money - Creator Wallet</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="../../assets/css/mobile-responsive.css">
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #0d9668;
            --secondary: #3b82f6;
            --accent: #8b5cf6;
            --dark-bg: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.85);
            --text-light: #f1f5f9;
            --success: #10b981;
            --warning: #f59e0b;
        }
        
        body {
            background: linear-gradient(135deg, var(--dark-bg) 0%, #1e293b 100%);
            color: var(--text-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding-bottom: 2rem;
        }
        
        .wallet-card {
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
            position: relative;
        }
        
        .wallet-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
        }
        
        .wallet-card:hover {
            box-shadow: 0 15px 40px rgba(16, 185, 129, 0.2);
            transform: translateY(-8px);
        }
        
        .balance-card {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(59, 130, 246, 0.1) 100%);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(16, 185, 129, 0.2);
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .balance-card:hover {
            transform: scale(1.02);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.15);
        }
        
        .balance-amount {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--primary);
            text-shadow: 0 2px 10px rgba(16, 185, 129, 0.3);
            margin: 0.5rem 0;
        }
        
        .form-label {
            color: #fff;
            font-weight: 700;
            font-size: 1.08rem;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            background: rgba(30, 41, 59, 0.98);
            border: 1.5px solid #10b981;
            color: #fff !important;
            font-size: 1.13rem;
            border-radius: 12px;
            padding: 0.85rem 1.1rem;
            transition: all 0.3s ease;
        }
        .form-control::placeholder {
            color: #fff !important;
            opacity: 0.7;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(16, 185, 129, 0.2);
            background: rgba(30, 41, 59, 1);
        }
        
        .input-group-text {
            background: #10b981;
            border: 1.5px solid #10b981;
            color: #fff;
            border-radius: 12px 0 0 12px;
        }
        
        .btn-success {
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            font-weight: 700;
            letter-spacing: 0.5px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
            padding: 0.8rem 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-success:hover {
            background: linear-gradient(90deg, var(--primary-dark) 0%, var(--primary) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }
        
        .btn-secondary {
            border-radius: 12px;
            padding: 0.8rem 1.5rem;
            transition: all 0.3s ease;
            background: rgba(108, 117, 125, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .btn-secondary:hover {
            background: rgba(108, 117, 125, 0.9);
            transform: translateY(-2px);
        }
        
        .wallet-header {
            font-weight: 800;
            letter-spacing: 0.5px;
            color: var(--secondary);
            text-shadow: 0 2px 8px rgba(59, 130, 246, 0.15);
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .deposit-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
            display: block;
            text-align: center;
        }
        
        .info-text {
            color: #fff;
            font-size: 1rem;
            text-align: center;
            margin-top: 1rem;
        }
        
        .quick-amounts {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .quick-amount-btn {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: var(--text-light);
            border-radius: 10px;
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .quick-amount-btn:hover {
            background: rgba(59, 130, 246, 0.2);
            transform: translateY(-2px);
        }
        
        .quick-amount-btn.active {
            background: rgba(16, 185, 129, 0.3);
            border-color: var(--primary);
            color: var(--primary);
        }
        
        /* Animation for page load */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-card {
            animation: fadeInUp 0.7s cubic-bezier(0.68, -0.55, 0.27, 1.55) forwards;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }
            
            .wallet-card {
                padding: 1.5rem 1rem !important;
                margin: 0.5rem 0;
            }
            
            .balance-amount {
                font-size: 1.8rem;
            }
            
            .wallet-header {
                font-size: 1.5rem;
            }
            
            .btn-success, .btn-secondary {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            
            .deposit-icon {
                font-size: 2.5rem;
            }
            
            .quick-amounts {
                gap: 0.3rem;
            }
            
            .quick-amount-btn {
                padding: 0.4rem 0.8rem;
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 576px) {
            .wallet-card {
                border-radius: 16px;
            }
            
            .balance-card {
                padding: 1rem;
            }
            
            .balance-amount {
                font-size: 1.6rem;
            }
            
            .form-control {
                font-size: 1rem;
                padding: 0.6rem 0.8rem;
            }
        }
        
        /* Loading animation for button */
        .btn-loading {
            pointer-events: none;
            opacity: 0.8;
        }
        
        .btn-loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            margin: auto;
            border: 2px solid transparent;
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: button-loading-spinner 1s ease infinite;
        }
        
        @keyframes button-loading-spinner {
            from {
                transform: rotate(0turn);
            }
            to {
                transform: rotate(1turn);
            }
        }
        
        .payment-methods {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .payment-method {
            background: rgba(30, 41, 59, 0.7);
            border-radius: 10px;
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        
        .payment-method.active {
            border-color: var(--primary);
            background: rgba(16, 185, 129, 0.15);
        }
    </style>
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