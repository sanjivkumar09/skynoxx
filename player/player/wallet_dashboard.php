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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/gaming-theme.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wallet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
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