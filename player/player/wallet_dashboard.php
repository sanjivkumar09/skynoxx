<?php
session_start();
include '../src/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../src/login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
// Fetch wallet balance
$res = $conn->query("SELECT wallet_balance FROM users WHERE id = $user_id");
$balance = $res ? $res->fetch_assoc()['wallet_balance'] : 0;
// Fetch wallet transactions with more details
$txns = [];
// Always include tournament title if a tournament_id exists
$query = "SELECT wt.*, t.title AS tournament_title
FROM wallet_transactions wt
LEFT JOIN tournaments t ON wt.tournament_id = t.id
WHERE wt.user_id = $user_id
ORDER BY wt.created_at DESC
LIMIT 50";
$res = $conn->query($query);
while ($row = $res->fetch_assoc()) $txns[] = $row;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wallet</title>
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
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
        }

        body {
            background: linear-gradient(135deg, var(--bg-dark) 0%, #1a2836 100%);
            color: var(--text-light);
            font-family: 'Inter', system-ui, sans-serif;
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .wallet-container {
            max-width: 1000px;
            width: 100%;
            margin: 0 auto;
        }

        .wallet-card, .transaction-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }

        .wallet-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at center, rgba(110, 180, 255, 0.15) 0%, transparent 70%);
            opacity: 0.6;
            z-index: 0;
        }

        .transaction-card::before {
            content: '';
            position: absolute;
            bottom: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at center, rgba(255, 70, 85, 0.1) 0%, transparent 70%);
            opacity: 0.4;
            z-index: 0;
        }

        .wallet-card:hover, .transaction-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(110, 180, 255, 0.2), 0 0 0 1px rgba(110, 180, 255, 0.3);
        }

        .wallet-header {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .balance-amount {
            font-size: 3rem;
            font-weight: 900;
            font-weight: 900;
            color: #ffd700;
            text-shadow: 0 4px 20px rgba(255, 215, 0, 0.4);
            position: relative;
            z-index: 1;
            letter-spacing: 2px;
        }

        .balance-label {
            font-size: 0.9rem;
            color: var(--primary-color);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .btn-wallet {
            background: linear-gradient(135deg, var(--accent-color) 0%, #e23f4d 100%);
            border: none;
            border-radius: 12px;
            padding: 0.9rem 2rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-light);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
            margin: 0.5rem;
            box-shadow: 0 4px 15px rgba(255, 70, 85, 0.3);
        }

        .btn-wallet::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
            z-index: -1;
        }

        .btn-wallet:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-wallet:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 70, 85, 0.5);
        }

        .btn-wallet i {
            margin-right: 8px;
        }

        /* Transaction Table Styles */
        .transaction-item {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 1.2rem;
            margin-bottom: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .transaction-item:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(110, 180, 255, 0.3);
            transform: translateX(5px);
        }

        .transaction-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .transaction-icon.credit {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .transaction-icon.debit {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .transaction-details {
            flex-grow: 1;
        }

        .transaction-type {
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 0.25rem;
            text-transform: capitalize;
        }

        .transaction-desc {
            font-size: 0.85rem;
            color: var(--text-light);
            opacity: 0.7;
        }

        .transaction-amount {
            font-size: 1.4rem;
            font-weight: 800;
            text-align: right;
            letter-spacing: 1px;
        }

        .transaction-amount.credit {
            color: var(--success-color);
        }

        .transaction-amount.debit {
            color: var(--danger-color);
        }

        .transaction-meta {
            text-align: right;
        }

        .transaction-date {
            font-size: 0.8rem;
            color: var(--text-light);
            opacity: 0.6;
            margin-top: 0.25rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 0.5rem;
        }

        .status-badge.success {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
        }

        .status-badge.pending {
            background: linear-gradient(135deg, var(--warning-color), #d97706);
            color: white;
        }

        .status-badge.failed {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
            color: white;
        }

        .btn-details {
            background: rgba(110, 180, 255, 0.15);
            border: 1px solid rgba(110, 180, 255, 0.3);
            color: var(--primary-color);
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
        }

        .btn-details:hover {
            background: rgba(110, 180, 255, 0.25);
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-light);
            opacity: 0.6;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--primary-color);
            opacity: 0.3;
            margin-bottom: 1rem;
        }

        .table {
            color: var(--text-light);
            background: transparent;
            position: relative;
            z-index: 1;
        }

        .table-dark {
            --bs-table-bg: transparent;
            --bs-table-color: var(--text-light);
        }

        .table th {
            color: var(--primary-color);
            font-weight: 600;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .table td {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 0.75rem;
            font-size: 0.9rem;
        }

        .table-striped>tbody>tr:nth-of-type(odd)>* {
            --bs-table-accent-bg: rgba(255, 255, 255, 0.05);
        }

        .table-responsive {
            border-radius: 12px;
            overflow-x: auto;
        }

        @media (max-width: 768px) {
            .transaction-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .transaction-icon {
                margin-bottom: 0.75rem;
            }

            .transaction-amount, .transaction-meta {
                text-align: left;
                width: 100%;
                margin-top: 0.5rem;
            }
        }

        @media (max-width: 576px) {
            .wallet-container {
                padding: 0.5rem;
            }
            .wallet-card, .transaction-card {
                padding: 1.25rem;
                border-radius: 16px;
            }
            .wallet-header {
                font-size: 1.5rem;
            }
            .balance-amount {
                font-size: 2rem;
            }
            .btn-wallet {
                font-size: 0.85rem;
                padding: 0.7rem 1.5rem;
                display: block;
                width: 100%;
                margin: 0.5rem 0;
            }
            .table td, .table th {
                font-size: 0.8rem;
                padding: 0.5rem;
            }
            .table-responsive {
                margin: 0 -0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="wallet-container py-4">
        <?php if (!empty($_GET['deposit']) && $_GET['deposit'] === 'success'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>Wallet deposit successful. Your balance has been updated.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <h2 class="wallet-header animate__animated animate__fadeInDown">
            <i class="fas fa-wallet me-2"></i>My Wallet
        </h2>
        <div class="wallet-card animate__animated animate__fadeInUp">
            <div class="text-center balance-label">Current Balance</div>
            <div class="balance-amount text-center">₹<?php echo number_format($balance, 2); ?></div>
            <div class="d-flex justify-content-center mt-4 flex-wrap">
                    <a href="wallet_topup_manual.php" class="btn btn-wallet">
                        <i class="fas fa-plus-circle me-2"></i>Add Money (Manual)
                    </a>
                <a href="wallet_topup_requests.php" class="btn btn-wallet">
                    <i class="fas fa-receipt me-2"></i>Top-up Requests
                </a>
                <a href="wallet_withdraw.php" class="btn btn-wallet">
                    <i class="fas fa-arrow-circle-down"></i>Withdraw
                </a>
            </div>
        </div>
        <div class="transaction-card animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
            <h5 class="card-title mb-4" style="color: var(--primary-color); font-weight: 700; font-size: 1.4rem;">
                <i class="fas fa-history me-2"></i>Transaction History
            </h5>
            <?php if (empty($txns)): ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <p class="mt-3">No transactions yet</p>
                    <small>Start by adding money to your wallet!</small>
                </div>
            <?php else: ?>
            <div class="transactions-list">
                <?php foreach ($txns as $index => $txn): 
                    $isCreditType = in_array($txn['type'], ['credit','deposit','prize_win','prize'], true);
                    $typeClass = $isCreditType ? 'credit' : 'debit';
                    $typeIcon = $isCreditType ? 'fa-arrow-down' : 'fa-arrow-up';
                    
                    // Determine status class
                    $statusClass = 'pending';
                    if ($txn['status'] === 'completed' || $txn['status'] === 'success') {
                        $statusClass = 'success';
                    } elseif ($txn['status'] === 'failed' || $txn['status'] === 'cancelled') {
                        $statusClass = 'failed';
                    }
                ?>
                <div class="transaction-item animate__animated animate__fadeIn" style="animation-delay: <?php echo 0.3 + $index * 0.05; ?>s;">
                    <div class="d-flex align-items-center flex-grow-1">
                        <div class="transaction-icon <?php echo $typeClass; ?>">
                            <i class="fas <?php echo $typeIcon; ?>"></i>
                        </div>
                        <div class="transaction-details">
                            <div class="transaction-type"><?php echo htmlspecialchars(str_replace('_', ' ', $txn['type'])); ?></div>
                            <div class="transaction-desc">
                                <?php 
                                if (!empty($txn['tournament_title'])) {
                                    echo htmlspecialchars($txn['tournament_title']);
                                } elseif (!empty($txn['description'])) {
                                    echo htmlspecialchars($txn['description']);
                                } else {
                                    echo 'Wallet transaction';
                                }
                                ?>
                            </div>
                            <span class="status-badge <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars(ucfirst($txn['status'])); ?>
                            </span>
                        </div>
                    </div>
                    <div class="transaction-meta">
                        <div class="transaction-amount <?php echo $typeClass; ?>">
                            <?php echo $isCreditType ? '+' : '-'; ?>₹<?php echo number_format($txn['amount'], 2); ?>
                        </div>
                        <div class="transaction-date">
                            <?php echo date('M d, Y', strtotime($txn['created_at'])); ?>
                            <br><?php echo date('h:i A', strtotime($txn['created_at'])); ?>
                        </div>
                        <button class="btn-details" onclick="showDetails(<?php echo htmlspecialchars(json_encode($txn), ENT_QUOTES, 'UTF-8'); ?>)">
                            <i class="fas fa-info-circle me-1"></i>Details
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Transaction Details Modal -->
    <div class="modal fade" id="transactionModal" tabindex="-1" aria-labelledby="transactionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: var(--card-bg); color: var(--text-light); border: 1px solid rgba(255, 255, 255, 0.1);">
                <div class="modal-header" style="border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                    <h5 class="modal-title" id="transactionModalLabel" style="color: var(--primary-color);">
                        <i class="fas fa-info-circle me-2"></i>Transaction Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="detail-row mb-3">
                        <strong style="color: var(--primary-color);">Transaction ID:</strong>
                        <span id="txn-id" class="ms-2"></span>
                    </div>
                    <div class="detail-row mb-3">
                        <strong style="color: var(--primary-color);">Type:</strong>
                        <span id="txn-type" class="ms-2"></span>
                    </div>
                    <div class="detail-row mb-3">
                        <strong style="color: var(--primary-color);">Amount:</strong>
                        <span id="txn-amount" class="ms-2 fw-bold"></span>
                    </div>
                    <div class="detail-row mb-3">
                        <strong style="color: var(--primary-color);">Status:</strong>
                        <span id="txn-status" class="ms-2"></span>
                    </div>
                    <div class="detail-row mb-3">
                        <strong style="color: var(--primary-color);">Description:</strong>
                        <div id="txn-description" class="mt-1" style="padding-left: 1rem; opacity: 0.9;"></div>
                    </div>
                    <div class="detail-row mb-3" id="tournament-section" style="display: none;">
                        <strong style="color: var(--primary-color);">Tournament:</strong>
                        <span id="txn-tournament" class="ms-2"></span>
                    </div>
                    <div class="detail-row mb-3" id="reference-section" style="display: none;">
                        <strong style="color: var(--primary-color);">Reference ID:</strong>
                        <span id="txn-reference" class="ms-2 font-monospace"></span>
                    </div>
                    <div class="detail-row mb-3">
                        <strong style="color: var(--primary-color);">Date & Time:</strong>
                        <span id="txn-date" class="ms-2"></span>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid rgba(255, 255, 255, 0.1);">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://kit.fontawesome.com/2c7fc25c36.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showDetails(txn) {
            // Populate modal with transaction details
            document.getElementById('txn-id').textContent = '#' + txn.id;
            document.getElementById('txn-type').textContent = txn.type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            
            // Format amount with color
            const amountElement = document.getElementById('txn-amount');
            const isCredit = ['credit','deposit','prize_win','prize'].includes(txn.type);
            amountElement.textContent = (isCredit ? '+' : '-') + '₹' + parseFloat(txn.amount).toFixed(2);
            amountElement.style.color = isCredit ? '#28a745' : '#dc3545';
            
            // Status badge
            const statusElement = document.getElementById('txn-status');
            const statusClass = (txn.status === 'completed' || txn.status === 'success') ? 'badge bg-success' : 'badge bg-warning';
            statusElement.innerHTML = '<span class="' + statusClass + '">' + txn.status.charAt(0).toUpperCase() + txn.status.slice(1) + '</span>';
            
            // Description
            document.getElementById('txn-description').textContent = txn.description || 'No description available';
            
            // Tournament info (if applicable)
            if (txn.tournament_title) {
                document.getElementById('tournament-section').style.display = 'block';
                document.getElementById('txn-tournament').textContent = txn.tournament_title;
            } else {
                document.getElementById('tournament-section').style.display = 'none';
            }
            
            // Reference ID (if applicable)
            if (txn.reference_id) {
                document.getElementById('reference-section').style.display = 'block';
                document.getElementById('txn-reference').textContent = txn.reference_id;
            } else {
                document.getElementById('reference-section').style.display = 'none';
            }
            
            // Format date
            const date = new Date(txn.created_at);
            document.getElementById('txn-date').textContent = date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('transactionModal'));
            modal.show();
        }
    </script>
</body>
</html>