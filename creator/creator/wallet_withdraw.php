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

// Handle withdrawal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount']) && $_POST['amount'] > 0) {
    $amount = (float)$_POST['amount'];
    $upi_id = trim($_POST['upi_id'] ?? '');
    
    if ($amount > $balance) {
        $error = 'You cannot withdraw more than your available balance.';
    } elseif (empty($upi_id)) {
        $error = 'UPI ID is required for withdrawal.';
    } else {
        // Handle QR code upload
        $qr_code_path = null;
        if (isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../src/uploads/withdrawal_qr/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileExtension = strtolower(pathinfo($_FILES['qr_code']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($fileExtension, $allowedExtensions)) {
                $fileName = 'qr_creator_' . $creator_id . '_' . time() . '.' . $fileExtension;
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['qr_code']['tmp_name'], $targetPath)) {
                    $qr_code_path = 'src/uploads/withdrawal_qr/' . $fileName;
                }
            }
        }
        
        // Log withdrawal request (for admin approval)
        $stmt = $conn->prepare("INSERT INTO withdrawals (creator_id, amount, upi_id, qr_code, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->bind_param('idss', $creator_id, $amount, $upi_id, $qr_code_path);
        $stmt->execute();
        $stmt->close();
        
        $conn->query("INSERT INTO wallet_transactions (user_id, type, amount, description) VALUES ($creator_id, 'debit', $amount, 'Wallet Withdrawal Request (pending approval)')");
        $_SESSION['success_message'] = 'Withdrawal request submitted! Awaiting admin approval.';
        header('Location: wallet_dashboard.php');
        exit();
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdraw Money - Creator Wallet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</head>
<body>
    <div class="container my-4">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 col-12">
                <div class="wallet-card p-4 mb-4 animate-card">
                    <i class="fas fa-money-bill-wave withdraw-icon"></i>
                    <h2 class="wallet-header">Withdraw Money</h2>
                    
                    <!-- Balance Display -->
                    <div class="balance-card">
                        <p class="mb-1">Available Balance</p>
                        <div class="balance-amount">₹<?php echo number_format($balance, 2); ?></div>
                        <small class="text-muted">Maximum withdrawal: ₹<?php echo number_format($balance, 2); ?></small>
                    </div>
                    
                    <form method="post" enctype="multipart/form-data" autocomplete="off" id="withdrawForm">
                        <div class="mb-4">
                            <label for="amount" class="form-label">Withdrawal Amount (₹)</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" min="1" max="<?php echo $balance; ?>" step="0.01" 
                                       class="form-control" id="amount" name="amount" required 
                                       placeholder="Enter amount to withdraw">
                            </div>
                            
                            <!-- Quick Amount Buttons -->
                            <div class="quick-amounts mt-3">
                                <?php
                                $quickAmounts = [100, 500, 1000, 2000];
                                foreach ($quickAmounts as $amt) {
                                    if ($amt <= $balance) {
                                        echo '<button type="button" class="quick-amount-btn" data-amount="' . $amt . '">₹' . number_format($amt) . '</button>';
                                    }
                                }
                                ?>
                                <button type="button" class="quick-amount-btn" data-amount="<?php echo $balance; ?>">Max</button>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="upi_id" class="form-label">UPI ID</label>
                            <input type="text" class="form-control" id="upi_id" name="upi_id" required 
                                   placeholder="yourname@upi">
                            <div class="form-text">We'll transfer funds to this UPI ID</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="qr_code" class="form-label">
                                <i class="fas fa-qrcode me-2"></i>Upload Payment QR Code (Optional)
                            </label>
                            <input type="file" class="form-control" id="qr_code" name="qr_code" 
                                   accept="image/png, image/jpeg, image/jpg, image/gif">
                            <div class="form-text">
                                Upload your UPI QR code for faster processing (JPG, PNG, GIF - Max 5MB)
                            </div>
                            <div id="qrPreview" class="mt-2" style="display: none;">
                                <img id="qrPreviewImg" src="" alt="QR Preview" style="max-width: 200px; border-radius: 8px; border: 2px solid var(--secondary);">
                            </div>
                        </div>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger mb-3 animate__animated animate__shakeX">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn btn-danger w-100 py-2 mt-2" id="submitBtn">
                            <i class="fas fa-arrow-circle-up me-2"></i>Withdraw Funds
                        </button>
                    </form>
                    
                    <!-- Withdrawal Information -->
                    <div class="withdrawal-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Important Information</h6>
                        <ul>
                            <li>Withdrawals are processed within 24 hours</li>
                            <li>Ensure your UPI ID is correct and active</li>
                            <li>You will receive a confirmation message</li>
                        </ul>
                    </div>
                </div>
                
                <a href="wallet_dashboard.php" class="btn btn-secondary w-100 py-2">
                    <i class="fas fa-arrow-left me-2"></i>Back to Wallet
                </a>
            </div>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/2c7fc25c36.js" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('withdrawForm');
            const submitBtn = document.getElementById('submitBtn');
            const amountInput = document.getElementById('amount');
            const upiInput = document.getElementById('upi_id');
            const qrInput = document.getElementById('qr_code');
            const qrPreview = document.getElementById('qrPreview');
            const qrPreviewImg = document.getElementById('qrPreviewImg');
            
            // QR code preview
            qrInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Validate file size (5MB max)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('File size must be less than 5MB');
                        this.value = '';
                        qrPreview.style.display = 'none';
                        return;
                    }
                    
                    // Validate file type
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    if (!allowedTypes.includes(file.type)) {
                        alert('Only JPG, PNG, and GIF files are allowed');
                        this.value = '';
                        qrPreview.style.display = 'none';
                        return;
                    }
                    
                    // Show preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        qrPreviewImg.src = e.target.result;
                        qrPreview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    qrPreview.style.display = 'none';
                }
            });
            const amountInput = document.getElementById('amount');
            const upiInput = document.getElementById('upi_id');
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
            
            // Real-time validation for amount
            amountInput.addEventListener('input', function() {
                const value = parseFloat(this.value) || 0;
                const max = parseFloat(this.max);
                
                if (value > max) {
                    this.setCustomValidity('Amount exceeds available balance!');
                } else if (value < 1) {
                    this.setCustomValidity('Minimum withdrawal is ₹1');
                } else {
                    this.setCustomValidity('');
                }
                
                // Update quick buttons active state
                quickAmountBtns.forEach(btn => {
                    if (btn.getAttribute('data-amount') == value) {
                        quickAmountBtns.forEach(b => b.classList.remove('active'));
                        btn.classList.add('active');
                    }
                });
            });
            
            // Form submission handler
            form.addEventListener('submit', function(e) {
                const amount = parseFloat(amountInput.value);
                const upi = upiInput.value.trim();
                
                if (isNaN(amount) || amount < 1) {
                    e.preventDefault();
                    showError('Please enter a valid amount');
                    return;
                }
                
                if (amount > parseFloat(amountInput.max)) {
                    e.preventDefault();
                    showError('Amount exceeds available balance');
                    return;
                }
                
                if (!upi) {
                    e.preventDefault();
                    showError('UPI ID is required');
                    return;
                }
                
                // Validate UPI ID format (basic validation)
                if (!isValidUPI(upi)) {
                    e.preventDefault();
                    showError('Please enter a valid UPI ID (e.g., name@bank)');
                    return;
                }
                
                // Show loading state
                submitBtn.classList.add('btn-loading');
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Processing Withdrawal...';
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
                form.insertBefore(alertDiv, submitBtn);
                
                // Scroll to error
                alertDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            
            function isValidUPI(upi) {
                // Basic UPI validation - should contain @ symbol
                return upi.includes('@') && upi.length > 5;
            }
        });
    </script>
</body>
</html>