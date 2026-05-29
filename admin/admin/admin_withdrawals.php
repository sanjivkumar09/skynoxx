<?php
session_start();
include '../src/db.php';
require_once '../src/NotificationManager.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../src/login.php');
    exit();
}

$notificationManager = new NotificationManager($conn);

// Fetch all pending withdrawals with user details and role
$stmt = $conn->prepare("SELECT w.*, u.name, u.email, u.role, u.wallet_balance FROM withdrawals w JOIN users u ON w.creator_id = u.id WHERE w.status = 'pending' ORDER BY w.requested_at DESC");
$stmt->execute();
$res = $stmt->get_result();
$withdrawals = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// Handle approval or rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdrawal_id'], $_POST['action'])) {
    $id = (int)$_POST['withdrawal_id'];
    $action = $_POST['action'];
    
    // Debug: Set a session variable to confirm POST was received
    $_SESSION['debug_post'] = "Received POST - ID: $id, Action: $action at " . date('H:i:s');
    
    // Log the attempt
    error_log("Withdrawal action attempt - ID: $id, Action: $action");

    // Fetch withdrawal details
    $stmt = $conn->prepare("SELECT * FROM withdrawals WHERE id = ? AND status = 'pending' LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $wres = $stmt->get_result();
    $withdrawal = $wres ? $wres->fetch_assoc() : null;
    $stmt->close();
    
    // Log if withdrawal found
    error_log("Withdrawal found: " . ($withdrawal ? "YES - User: " . $withdrawal['creator_id'] : "NO"));

    if ($withdrawal) {
        if ($action === 'approve') {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Deduct amount from user's wallet
                $user_id = (int)$withdrawal['creator_id'];
                $amount = (float)$withdrawal['amount'];
                
                error_log("Processing approval - User: $user_id, Amount: $amount");
                
                // Update wallet balance
                $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
                $stmt->bind_param("di", $amount, $user_id);
                $stmt->execute();
                $affected = $stmt->affected_rows;
                $stmt->close();
                
                error_log("Wallet update affected rows: $affected");

                // Mark withdrawal as approved (not completed)
                $stmt = $conn->prepare("UPDATE withdrawals SET status = 'approved' WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $affected = $stmt->affected_rows;
                $stmt->close();
                
                error_log("Withdrawal status update affected rows: $affected");
                
                // Add transaction record (use 'withdraw' type not 'debit')
                $stmt = $conn->prepare("INSERT INTO wallet_transactions (user_id, type, amount, status, description, created_at) VALUES (?, 'withdraw', ?, 'completed', 'Withdrawal approved by admin', NOW())");
                $stmt->bind_param("id", $user_id, $amount);
                $stmt->execute();
                $insert_id = $conn->insert_id;
                $stmt->close();
                
                error_log("Transaction record inserted with ID: $insert_id");
                
                // Commit transaction
                $conn->commit();
                
                error_log("Transaction committed successfully");
                
                // Send notification to user
                $notificationManager->notifyWithdrawalStatus(
                    $user_id,
                    $id,
                    'approved',
                    $amount
                );
                
                $_SESSION['success_message'] = "Withdrawal request approved successfully!";
            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                error_log("Error during approval: " . $e->getMessage());
                $_SESSION['error_message'] = "Error approving withdrawal: " . $e->getMessage();
            }
        } elseif ($action === 'reject') {
            $rejection_reason = isset($_POST['rejection_reason']) ? trim($_POST['rejection_reason']) : 'No reason provided';
            
            $stmt = $conn->prepare("UPDATE withdrawals SET status = 'rejected' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            error_log("Withdrawal rejected - ID: $id");
            
            // Send notification to user
            $notificationManager->notifyWithdrawalStatus(
                $withdrawal['creator_id'],
                $id,
                'rejected',
                $withdrawal['amount'],
                $rejection_reason
            );
            
            $_SESSION['success_message'] = "Withdrawal request rejected.";
        }
    } else {
        error_log("Withdrawal not found or already processed");
        $_SESSION['error_message'] = "Invalid withdrawal request or already processed.";
    }
    header('Location: admin_withdrawals.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Withdrawals</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Inter', sans-serif;
            color: #374151;
        }
        .container {
            max-width: 1200px;
            padding: 1rem;
        }
        .table {
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .table thead {
            background-color: #4a90e2;
            color: #fff;
        }
        .table th, .table td {
            vertical-align: middle;
            padding: 12px;
        }
        .table-hover tbody tr:hover {
            background-color: #f1f5f9;
        }
        .qr-preview {
            max-width: 100px;
            max-height: 100px;
            transition: transform 0.2s;
        }
        .qr-preview:hover {
            transform: scale(1.1);
        }
        .modal-qr {
            max-width: 100%;
            max-height: 400px;
        }
        .detail-label {
            font-weight: 600;
            color: #374151;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.875rem;
        }
        .btn-action-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .btn-action-group .btn {
            width: 100%;
            transition: background-color 0.2s;
        }
        .badge {
            font-size: 0.75rem;
            padding: 6px 10px;
        }
        .alert {
            border-radius: 8px;
        }
        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }
        .modal-header {
            border-bottom: none;
        }
        .modal-footer {
            border-top: none;
        }
        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
            }
            .table th, .table td {
                font-size: 0.85rem;
                padding: 8px;
            }
            .btn-action-group {
                flex-direction: row;
                flex-wrap: wrap;
                gap: 6px;
            }
            .btn-action-group .btn {
                flex: 1;
                min-width: 100px;
            }
            .qr-preview {
                max-width: 80px;
                max-height: 80px;
            }
            .modal-qr {
                max-height: 300px;
            }
            .modal-dialog {
                margin: 1rem;
            }
        }
        @media (max-width: 576px) {
            .table th, .table td {
                font-size: 0.75rem;
            }
            .btn-sm {
                font-size: 0.75rem;
                padding: 4px 8px;
            }
            .badge {
                font-size: 0.65rem;
            }
            .modal-body {
                padding: 1rem;
            }
            h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark">Pending Withdrawals</h2>
        <div>
            <div class="dropdown d-inline-block me-2">
                <button class="btn btn-dark dropdown-toggle btn-sm" type="button" id="adminMenu" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle me-2"></i>Admin Menu
                </button>
                <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="adminMenu">
                    <li><a class="dropdown-item" href="admin_dashboard.php"><i class="bi bi-house me-2"></i>Dashboard</a></li>
                    <li><a class="dropdown-item" href="analytics_dashboard.php"><i class="bi bi-graph-up me-2"></i>Analytics</a></li>
                    <li><a class="dropdown-item" href="payment_management.php"><i class="bi bi-credit-card me-2"></i>Payment Management</a></li>
                    <li><a class="dropdown-item" href="admin_withdrawals.php"><i class="bi bi-cash-stack me-2"></i>Withdrawal Management</a></li>
                    <li><a class="dropdown-item" href="wallet_deposits.php"><i class="bi bi-wallet2 me-2"></i>Wallet Deposits</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../../src/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
            <a href="withdrawal_history.php" class="btn btn-primary btn-sm me-2">
                <i class="bi bi-clock-history"></i> Withdrawal History
            </a>
            <a href="admin_dashboard.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-house"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <?php if (isset($_SESSION['debug_post'])): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <strong>Debug:</strong> <?php echo htmlspecialchars($_SESSION['debug_post']); unset($_SESSION['debug_post']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (empty($withdrawals)): ?>
        <div class="alert alert-info">No pending withdrawal requests at this time.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th scope="col">User Details</th>
                        <th scope="col">Amount</th>
                        <th scope="col">UPI ID</th>
                        <th scope="col">QR Code</th>
                        <th scope="col">Requested At</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($withdrawals as $w): ?>
                    <tr>
                        <td>
                            <div class="mb-2">
                                <strong><?php echo htmlspecialchars($w['name']); ?></strong>
                                <span class="badge bg-<?php echo $w['role'] === 'player' ? 'primary' : 'success'; ?> ms-2">
                                    <?php echo ucfirst($w['role']); ?>
                                </span>
                            </div>
                            <div class="text-muted small">
                                <i class="bi bi-envelope me-1"></i> <?php echo htmlspecialchars($w['email']); ?>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">Wallet Balance: ₹<?php echo number_format($w['wallet_balance'], 2); ?></small>
                            </div>
                            <div class="mt-2">
                                <?php if ($w['role'] === 'player'): ?>
                                    <a href="view_player_profile.php?player_id=<?php echo $w['creator_id']; ?>" 
                                       class="btn btn-sm btn-outline-primary" target="_blank">
                                        View Player Profile
                                    </a>
                                <?php else: ?>
                                    <a href="view_creator_profile.php?creator_id=<?php echo $w['creator_id']; ?>" 
                                       class="btn btn-sm btn-outline-success" target="_blank">
                                        View Creator Profile
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <h5 class="text-success mb-0">₹<?php echo number_format($w['amount'], 2); ?></h5>
                        </td>
                        <td>
                            <?php if (!empty($w['upi_id'])): ?>
                                <code class="bg-light p-2 rounded"><?php echo htmlspecialchars($w['upi_id']); ?></code>
                            <?php else: ?>
                                <span class="text-muted">Not provided</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($w['qr_code']) && file_exists('../src/uploads/withdrawal_qr/' . basename($w['qr_code']))): ?>
                                <img src="../src/uploads/withdrawal_qr/<?php echo htmlspecialchars(basename($w['qr_code'])); ?>" 
                                     class="qr-preview img-thumbnail" 
                                     alt="UPI QR Code"
                                     loading="lazy"
                                     data-qr-file="<?php echo htmlspecialchars(basename($w['qr_code'])); ?>"
                                     onclick="showQRModal(this.getAttribute('data-qr-file'))">
                                <div class="mt-1">
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-info"
                                            onclick="showQRModal('<?php echo htmlspecialchars(basename($w['qr_code'])); ?>')">
                                        View QR
                                    </button>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">No QR uploaded</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo date('d M Y, h:i A', strtotime($w['requested_at'])); ?>
                        </td>
                        <td>
                            <div class="btn-action-group">
                                <button type="button" class="btn btn-info btn-sm" 
                                        onclick="showDetails(<?php echo htmlspecialchars(json_encode($w)); ?>)">
                                    View Full Details
                                </button>
                                <form method="POST" action="admin_withdrawals.php" style="display: inline-block; width: 100%;">
                                    <input type="hidden" name="withdrawal_id" value="<?php echo (int)$w['id']; ?>">
                                    <button type="submit" 
                                            name="action" 
                                            value="approve" 
                                            class="btn btn-success btn-sm"
                                            onclick="return confirm('Approve this withdrawal of ₹<?php echo number_format($w['amount'], 2); ?> for <?php echo htmlspecialchars($w['name']); ?>?');">
                                        Approve
                                    </button>
                                    <button type="submit" 
                                            name="action" 
                                            value="reject" 
                                            class="btn btn-danger btn-sm"
                                            onclick="return confirm('Reject this withdrawal request?');">
                                        Reject
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- QR Code Modal -->
<div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="qrModalLabel">
                    <i class="bi bi-qr-code me-2"></i> UPI QR Code
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <img id="qrModalImage" src="" class="modal-qr img-fluid" alt="UPI QR Code">
                <p class="text-muted mt-3">Scan this QR code to make the payment</p>
            </div>
            <div class="modal-footer justify-content-center">
                <a id="qrDownloadLink" href="" download class="btn btn-success">
                    <i class="bi bi-download me-2"></i> Download QR Code
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="detailsModalLabel">
                    <i class="bi bi-info-circle me-2"></i> Withdrawal Request Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <strong>User Information</strong>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <span class="detail-label">User ID:</span>
                                <div id="detail-user-id" class="text-muted"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="detail-label">User Name:</span>
                                <div id="detail-name"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="detail-label">User Role:</span>
                                <div id="detail-role"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="detail-label">Email:</span>
                                <div id="detail-email"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <strong>Payment Information</strong>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <span class="detail-label">Withdrawal Amount:</span>
                                <div id="detail-amount" class="text-success fw-bold fs-5"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="detail-label">Current Wallet Balance:</span>
                                <div id="detail-balance" class="text-primary fw-bold"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="detail-label">UPI ID:</span>
                                <div id="detail-upi"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="detail-label">Status:</span>
                                <div id="detail-status"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <strong>Request Information</strong>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <span class="detail-label">Request ID:</span>
                                <div id="detail-id" class="text-muted"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="detail-label">Requested At:</span>
                                <div id="detail-requested"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="detail-qr-section" class="card">
                    <div class="card-header bg-light">
                        <strong>UPI QR Code</strong>
                    </div>
                    <div class="card-body text-center">
                        <img id="detail-qr-img" src="" class="img-thumbnail" style="max-width: 250px; max-height: 250px; cursor: pointer;" alt="UPI QR Code" onclick="enlargeQR(this.src)">
                        <div class="mt-2">
                            <small class="text-muted">Click to enlarge</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function showQRModal(qrFileName) {
        const qrPath = '../src/uploads/withdrawal_qr/' + qrFileName;
        document.getElementById('qrModalImage').src = qrPath;
        document.getElementById('qrDownloadLink').href = qrPath;
        const qrModal = new bootstrap.Modal(document.getElementById('qrModal'));
        qrModal.show();
    }

    function enlargeQR(qrSrc) {
        document.getElementById('qrModalImage').src = qrSrc;
        document.getElementById('qrDownloadLink').href = qrSrc;
        const qrModal = new bootstrap.Modal(document.getElementById('qrModal'));
        qrModal.show();
    }

    function showDetails(withdrawal) {
        // User Information
        document.getElementById('detail-user-id').textContent = withdrawal.creator_id;
        document.getElementById('detail-name').textContent = withdrawal.name;
        document.getElementById('detail-role').innerHTML = '<span class="badge bg-' + 
            (withdrawal.role === 'player' ? 'primary' : 'success') + '">' + 
            withdrawal.role.charAt(0).toUpperCase() + withdrawal.role.slice(1) + '</span>';
        document.getElementById('detail-email').textContent = withdrawal.email;
        
        // Payment Information
        document.getElementById('detail-amount').textContent = '₹' + parseFloat(withdrawal.amount).toFixed(2);
        document.getElementById('detail-balance').textContent = '₹' + parseFloat(withdrawal.wallet_balance).toFixed(2);
        document.getElementById('detail-upi').innerHTML = withdrawal.upi_id ? 
            '<code class="bg-light p-2 rounded">' + withdrawal.upi_id + '</code>' : 
            '<span class="text-muted">Not provided</span>';
        document.getElementById('detail-status').innerHTML = '<span class="badge bg-warning text-dark">' + 
            withdrawal.status.toUpperCase() + '</span>';
        
        // Request Information
        document.getElementById('detail-id').textContent = withdrawal.id;
        document.getElementById('detail-requested').textContent = new Date(withdrawal.requested_at).toLocaleString('en-IN', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
        
        // Show/hide QR section
        const qrSection = document.getElementById('detail-qr-section');
        if (withdrawal.qr_code) {
            qrSection.style.display = 'block';
            const qrFileName = withdrawal.qr_code.split('/').pop();
            document.getElementById('detail-qr-img').src = '../src/uploads/withdrawal_qr/' + qrFileName;
        } else {
            qrSection.style.display = 'none';
        }
        
        const detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));
        detailsModal.show();
    }

    // Disable buttons during form submission to prevent double-click
    document.querySelectorAll('form[method="POST"]').forEach(form => {
        form.addEventListener('submit', function(e) {
            // Allow form to submit normally
            setTimeout(() => {
                form.querySelectorAll('button[type="submit"]').forEach(btn => {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
                });
            }, 10);
        });
    });
</script>
</body>
</html>