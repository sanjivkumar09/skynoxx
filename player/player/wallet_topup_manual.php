<?php
session_start();
require_once __DIR__ . '/../src/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'player') {
    header('Location: ../../src/login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Ensure table exists (runtime safety)
$createSql = "CREATE TABLE IF NOT EXISTS wallet_topup_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    upi_reference VARCHAR(100) NULL,
    screenshot_path VARCHAR(255) NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    remarks VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME NULL,
    admin_id INT NULL,
    INDEX (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
@$conn->query($createSql);

// Fetch wallet balance
$res = $conn->query("SELECT wallet_balance FROM users WHERE id = $user_id");
$balance = $res ? (float)$res->fetch_assoc()['wallet_balance'] : 0.0;

// Success/error messaging
$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';

// QR image path (URL-encoded due to spaces)
// Use relative path so it works when the site runs from a subfolder (e.g., XAMPP project directory)
$qrUrl = '../../assets/images/WhatsApp%20Image%202025-11-02%20at%2023.40.12_0ed4576f.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Wallet Top-up</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        body { background: #0f172a; color: #e2e8f0; }
        .container-narrow { max-width: 900px; margin: 24px auto; }
        .card-glass { background: rgba(255,255,255,0.04); border: 1px solid rgba(255, 255, 255, 0.95); border-radius: 16px; box-shadow: 0 10px 30px rgba(222, 21, 21, 0.94); }
        .wallet-header { font-weight: 800; letter-spacing: .3px; }
        .qr-wrap { background: rgba(255,255,255,0.06); border: 1px solid rgba(255, 255, 255, 0.89); border-radius: 12px; padding: 16px; text-align: center; }
        .qr-wrap img { max-width: 100%; height: auto; border-radius: 10px; }
        .badge-balance { background: linear-gradient(90deg, #22c55e, #16a34a); }
        .btn-primary { background: linear-gradient(90deg, #3b82f6, #2563eb); border: 0; }
        .btn-primary:hover { filter: brightness(1.1); }
    .form-control, .form-control:focus { background: #0b1220; color: #e2e8f0; border-color: #334155; }
    /* Use pure white for any previously-muted helper text on this page */
    .small-muted { color: #ffffff !important; }
    </style>
 </head>
 <body>
 <div class="container container-narrow">
     <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="wallet-header">Manual Wallet Top-up</h2>
        <a href="wallet_dashboard.php" class="btn btn-outline-light">Back to Wallet</a>
     </div>

     <?php if ($msg): ?>
        <div class="alert alert-success animate__animated animate__fadeInDown"><i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($msg); ?></div>
     <?php endif; ?>
     <?php if ($err): ?>
        <div class="alert alert-danger animate__animated animate__fadeInDown"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($err); ?></div>
     <?php endif; ?>

     <div class="card card-glass p-4 mb-4">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="qr-wrap">
                    <img src="<?php echo $qrUrl; ?>" alt="UPI QR Code" onerror="this.style.display='none';document.getElementById('qr-missing').style.display='block';" />
                </div>
                <p id="qr-missing" class="small small-muted mt-2 mb-0" style="display:none;">
                    QR image not found. Please place your QR at <code>assets/images/WhatsApp Image 2025-11-02 at 23.40.12_0ed4576f.jpg</code> or update the path in <code>player/wallet_topup_manual.php</code>.
                </p>
                <p class="small small-muted mt-2 mb-0">Scan the QR with your UPI app and pay the desired amount. Then upload the payment screenshot below.</p>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <div class="small small-muted">Current Balance</div>
                    <div class="h4"><span class="badge badge-balance">₹<?php echo number_format($balance,2); ?></span></div>
                </div>
                <form method="POST" action="submit_wallet_topup.php" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Amount (₹)</label>
                        <input type="number" class="form-control" name="amount" min="1" step="0.01" required />
                        <div class="form-text small-muted">Minimum ₹1. Enter the exact amount you paid.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="color:#ffffff;">UPI Reference ID (optional)</label>
                        <input type="text" class="form-control" name="upi_reference" placeholder="e.g., 2345XXXXXX@upi txn ref" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="color:#ffffff;">Upload Payment Screenshot</label>
                        <input type="file" class="form-control" name="screenshot" accept="image/*" required />
                        <div class="form-text small-muted">Accepted: JPG, PNG, WEBP • Max 5 MB</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-upload me-2"></i>Submit Top-up Request</button>
                </form>
            </div>
        </div>
     </div>

     <div class="card card-glass p-4">
        <h5 class="mb-3">How it works</h5>
        <ol class="small small-muted">
            <li>Scan the QR and pay the amount via your UPI app.</li>
            <li>Take a clear screenshot of the successful payment confirmation.</li>
            <li>Submit your amount and upload the screenshot here.</li>
            <li>Admin reviews and approves. On approval, your wallet is credited.</li>
        </ol>
     </div>
 </div>

 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
 </body>
 </html>
