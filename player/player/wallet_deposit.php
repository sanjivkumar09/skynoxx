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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Money to Wallet</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="../../assets/css/mobile-responsive.css">
    <style>
        :root {
            --primary-color: #6eb4ff;
            --accent-color: #ff4655;
            --bg-dark: #0f1923;
            --card-bg: rgba(31, 45, 61, 0.95);
            --text-light: #ece8e1;
        }

        body {
            background: linear-gradient(135deg, var(--bg-dark) 0%, #1a2836 100%);
            color: var(--text-light);
            font-family: 'Inter', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .wallet-container {
            max-width: 450px;
            width: 100%;
        }

        .wallet-card {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .wallet-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 10% 20%, rgba(255, 70, 85, 0.1) 0%, transparent 50%);
            opacity: 0.5;
            pointer-events: none; /* Ensure overlay does not block input clicks */
            z-index: 0;
        }

        .wallet-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(255, 70, 85, 0.2);
        }

        .wallet-header {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .form-label {
            color: var(--primary-color);
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            letter-spacing: 0.5px;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff !important; /* ensure white input text */
            caret-color: #ffffff;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        .form-control::placeholder { color: #ffffff !important; opacity: 0.7; }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(255, 70, 85, 0.2);
            background: rgba(255, 255, 255, 0.1);
        }

        .btn-wallet {
            background: linear-gradient(90deg, var(--accent-color) 0%, #e23f4d 100%);
            border: none;
            border-radius: 12px;
            padding: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-light);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
            width: 100%;
        }

        .btn-wallet::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
            z-index: -1;
        }

        .btn-wallet:hover::after {
            left: 100%;
        }

        .btn-wallet:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 70, 85, 0.3);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: var(--text-light);
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 12px;
            background: rgba(255, 70, 85, 0.2);
            border: 1px solid var(--accent-color);
            color: var(--text-light);
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        @media (max-width: 576px) {
            .wallet-card {
                padding: 1.5rem;
                border-radius: 16px;
            }
            .wallet-header {
                font-size: 1.3rem;
            }
            .form-control {
                font-size: 0.9rem;
                padding: 0.6rem 0.8rem;
            }
            .btn-wallet, .btn-secondary {
                font-size: 0.9rem;
                padding: 0.6rem;
            }
        }
    </style>
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