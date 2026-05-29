<?php
session_start();
include '../src/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../src/login.php');
    exit();
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$user_type = isset($_GET['user_type']) ? $_GET['user_type'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build query with filters
$where_conditions = [];
$params = [];
$types = '';

if ($status_filter !== 'all') {
    $where_conditions[] = "w.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($user_type !== 'all') {
    $where_conditions[] = "u.role = ?";
    $params[] = $user_type;
    $types .= 's';
}

if (!empty($search)) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR w.upi_id LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'sss';
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(w.requested_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(w.requested_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM withdrawals w JOIN users u ON w.creator_id = u.id $where_clause";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_records = $count_result->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $count_result = $conn->query($count_query);
    $total_records = $count_result->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $limit);

// Fetch withdrawals with filters
$query = "SELECT w.*, u.name, u.email, u.role, u.wallet_balance 
          FROM withdrawals w 
          JOIN users u ON w.creator_id = u.id 
          $where_clause 
          ORDER BY w.requested_at DESC 
          LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();
$withdrawals = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_requests,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as total_approved_amount,
    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as total_pending_amount
    FROM withdrawals w
    JOIN users u ON w.creator_id = u.id
    $where_clause";

if (!empty($where_conditions)) {
    $stats_stmt = $conn->prepare($stats_query);
    // Remove last two params (limit and offset)
    $stats_types = substr($types, 0, -2);
    $stats_params = array_slice($params, 0, -2);
    if (!empty($stats_params)) {
        $stats_stmt->bind_param($stats_types, ...$stats_params);
    }
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    $stats = $stats_result->fetch_assoc();
    $stats_stmt->close();
} else {
    $stats_result = $conn->query($stats_query);
    $stats = $stats_result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal History - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .main-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .stats-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stats-card h6 {
            font-size: 0.85rem;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        .stats-card h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
        }
        .bg-total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .bg-approved { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .bg-rejected { background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); }
        .bg-pending { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .filter-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .table-container {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .pagination {
            margin-top: 20px;
        }
        .qr-thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
            cursor: pointer;
            border-radius: 5px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="main-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1">
                    <i class="bi bi-clock-history"></i> Withdrawal History
                </h2>
                <p class="text-muted mb-0">Complete transaction history with filters</p>
            </div>
            <div>
                <a href="admin_withdrawals.php" class="btn btn-outline-primary me-2">
                    <i class="bi bi-hourglass-split"></i> Pending Requests
                </a>
                <a href="wallet_deposits.php" class="btn btn-outline-success me-2">
                    <i class="bi bi-wallet2"></i> Wallet Deposits
                </a>
                <a href="admin_dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-house"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card bg-total">
                    <h6>TOTAL REQUESTS</h6>
                    <h3><?php echo number_format($stats['total_requests']); ?></h3>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card bg-approved">
                    <h6>APPROVED</h6>
                    <h3><?php echo number_format($stats['approved_count']); ?></h3>
                    <small>₹<?php echo number_format($stats['total_approved_amount'], 2); ?></small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card bg-rejected">
                    <h6>REJECTED</h6>
                    <h3><?php echo number_format($stats['rejected_count']); ?></h3>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card bg-pending">
                    <h6>PENDING</h6>
                    <h3><?php echo number_format($stats['pending_count']); ?></h3>
                    <small>₹<?php echo number_format($stats['total_pending_amount'], 2); ?></small>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-card">
            <form method="GET" action="withdrawal_history.php" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">User Type</label>
                    <select name="user_type" class="form-select">
                        <option value="all" <?php echo $user_type === 'all' ? 'selected' : ''; ?>>All Users</option>
                        <option value="player" <?php echo $user_type === 'player' ? 'selected' : ''; ?>>Players Only</option>
                        <option value="creator" <?php echo $user_type === 'creator' ? 'selected' : ''; ?>>Creators Only</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Name, Email, or UPI ID" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Records per page</label>
                    <select name="limit" class="form-select">
                        <option value="25" <?php echo $limit === 25 ? 'selected' : ''; ?>>25</option>
                        <option value="50" <?php echo $limit === 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $limit === 100 ? 'selected' : ''; ?>>100</option>
                        <option value="200" <?php echo $limit === 200 ? 'selected' : ''; ?>>200</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 me-2">
                        <i class="bi bi-search"></i> Apply Filters
                    </button>
                    <a href="withdrawal_history.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Results Info -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <p class="mb-0 text-muted">
                    Showing <?php echo number_format($offset + 1); ?> to <?php echo number_format(min($offset + $limit, $total_records)); ?> of <?php echo number_format($total_records); ?> records
                </p>
            </div>
            <div>
                <button class="btn btn-sm btn-outline-success" onclick="exportToCSV()">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
                </button>
            </div>
        </div>

        <!-- Withdrawals Table -->
        <?php if (empty($withdrawals)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No withdrawal records found matching your filters.
            </div>
        <?php else: ?>
        <div class="table-container">
            <table class="table table-hover mb-0" id="withdrawalsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date & Time</th>
                        <th>User Details</th>
                        <th>Amount</th>
                        <th>UPI ID</th>
                        <th>QR Code</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($withdrawals as $w): ?>
                    <tr>
                        <td class="fw-bold">#<?php echo $w['id']; ?></td>
                        <td>
                            <div><?php echo date('d M Y', strtotime($w['requested_at'])); ?></div>
                            <small class="text-muted"><?php echo date('h:i A', strtotime($w['requested_at'])); ?></small>
                        </td>
                        <td>
                            <div class="fw-bold"><?php echo htmlspecialchars($w['name']); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($w['email']); ?></small>
                            <div>
                                <span class="badge bg-<?php echo $w['role'] === 'player' ? 'primary' : 'success'; ?> mt-1">
                                    <?php echo ucfirst($w['role']); ?>
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="fw-bold text-success">₹<?php echo number_format($w['amount'], 2); ?></span>
                            <div><small class="text-muted">Balance: ₹<?php echo number_format($w['wallet_balance'], 2); ?></small></div>
                        </td>
                        <td>
                            <?php if (!empty($w['upi_id'])): ?>
                                <code class="bg-light p-1 rounded"><?php echo htmlspecialchars($w['upi_id']); ?></code>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if (!empty($w['qr_code']) && file_exists('../src/uploads/withdrawal_qr/' . basename($w['qr_code']))): ?>
                                <img src="../src/uploads/withdrawal_qr/<?php echo htmlspecialchars(basename($w['qr_code'])); ?>" 
                                     class="qr-thumbnail img-thumbnail" 
                                     alt="QR Code"
                                     onclick="showQRModal('<?php echo htmlspecialchars(basename($w['qr_code'])); ?>')">
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $status_colors = [
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger'
                            ];
                            $color = $status_colors[$w['status']] ?? 'secondary';
                            ?>
                            <span class="status-badge bg-<?php echo $color; ?> text-white">
                                <?php echo strtoupper($w['status']); ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-info" 
                                    onclick="showDetails(<?php echo htmlspecialchars(json_encode($w)); ?>)">
                                <i class="bi bi-eye"></i> View
                            </button>
                            <?php if ($w['role'] === 'player'): ?>
                                <a href="view_player_profile.php?player_id=<?php echo $w['creator_id']; ?>" 
                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="bi bi-person"></i> Profile
                                </a>
                            <?php else: ?>
                                <a href="view_creator_profile.php?creator_id=<?php echo $w['creator_id']; ?>" 
                                   class="btn btn-sm btn-outline-success" target="_blank">
                                    <i class="bi bi-person"></i> Profile
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo ($page-1); ?>&status=<?php echo $status_filter; ?>&user_type=<?php echo $user_type; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&limit=<?php echo $limit; ?>">
                        <i class="bi bi-chevron-left"></i> Previous
                    </a>
                </li>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&user_type=<?php echo $user_type; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&limit=<?php echo $limit; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo ($page+1); ?>&status=<?php echo $status_filter; ?>&user_type=<?php echo $user_type; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&limit=<?php echo $limit; ?>">
                        Next <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- QR Code Modal -->
<div class="modal fade" id="qrModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-qr-code"></i> UPI QR Code</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <img id="qrModalImage" src="" class="img-fluid" style="max-width: 400px; max-height: 400px; border: 2px solid #ddd; border-radius: 8px; padding: 10px;" alt="QR Code">
            </div>
            <div class="modal-footer justify-content-center">
                <a id="qrDownloadLink" href="" download class="btn btn-success">
                    <i class="bi bi-download"></i> Download QR Code
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-info-circle"></i> Withdrawal Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="card mb-3">
                    <div class="card-header bg-light"><strong>User Information</strong></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <span class="fw-bold text-muted">User ID:</span>
                                <div id="detail-user-id"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="fw-bold text-muted">Name:</span>
                                <div id="detail-name"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="fw-bold text-muted">Role:</span>
                                <div id="detail-role"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="fw-bold text-muted">Email:</span>
                                <div id="detail-email"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header bg-light"><strong>Payment Information</strong></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <span class="fw-bold text-muted">Amount:</span>
                                <div id="detail-amount" class="text-success fw-bold fs-5"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="fw-bold text-muted">Wallet Balance:</span>
                                <div id="detail-balance"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="fw-bold text-muted">UPI ID:</span>
                                <div id="detail-upi"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="fw-bold text-muted">Status:</span>
                                <div id="detail-status"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header bg-light"><strong>Request Information</strong></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <span class="fw-bold text-muted">Request ID:</span>
                                <div id="detail-id"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="fw-bold text-muted">Requested At:</span>
                                <div id="detail-requested"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="detail-qr-section" class="card">
                    <div class="card-header bg-light"><strong>UPI QR Code</strong></div>
                    <div class="card-body text-center">
                        <img id="detail-qr-img" src="" class="img-thumbnail" style="max-width: 250px; cursor: pointer;" alt="QR Code" onclick="enlargeQR(this.src)">
                        <div class="mt-2"><small class="text-muted">Click to enlarge</small></div>
                    </div>
                </div>
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
        new bootstrap.Modal(document.getElementById('qrModal')).show();
    }

    function enlargeQR(qrSrc) {
        document.getElementById('qrModalImage').src = qrSrc;
        document.getElementById('qrDownloadLink').href = qrSrc;
        new bootstrap.Modal(document.getElementById('qrModal')).show();
    }

    function showDetails(withdrawal) {
        document.getElementById('detail-user-id').textContent = withdrawal.creator_id;
        document.getElementById('detail-name').textContent = withdrawal.name;
        document.getElementById('detail-role').innerHTML = '<span class="badge bg-' + 
            (withdrawal.role === 'player' ? 'primary' : 'success') + '">' + 
            withdrawal.role.charAt(0).toUpperCase() + withdrawal.role.slice(1) + '</span>';
        document.getElementById('detail-email').textContent = withdrawal.email;
        document.getElementById('detail-amount').textContent = '₹' + parseFloat(withdrawal.amount).toFixed(2);
        document.getElementById('detail-balance').textContent = '₹' + parseFloat(withdrawal.wallet_balance).toFixed(2);
        document.getElementById('detail-upi').innerHTML = withdrawal.upi_id ? 
            '<code class="bg-light p-2 rounded">' + withdrawal.upi_id + '</code>' : 
            '<span class="text-muted">Not provided</span>';
        
        let statusColor = 'secondary';
        if (withdrawal.status === 'approved') statusColor = 'success';
        else if (withdrawal.status === 'rejected') statusColor = 'danger';
        else if (withdrawal.status === 'pending') statusColor = 'warning';
        
        document.getElementById('detail-status').innerHTML = '<span class="badge bg-' + statusColor + '">' + 
            withdrawal.status.toUpperCase() + '</span>';
        document.getElementById('detail-id').textContent = withdrawal.id;
        document.getElementById('detail-requested').textContent = new Date(withdrawal.requested_at).toLocaleString('en-IN', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
        
        const qrSection = document.getElementById('detail-qr-section');
        if (withdrawal.qr_code) {
            qrSection.style.display = 'block';
            const qrFileName = withdrawal.qr_code.split('/').pop();
            document.getElementById('detail-qr-img').src = '../src/uploads/withdrawal_qr/' + qrFileName;
        } else {
            qrSection.style.display = 'none';
        }
        
        new bootstrap.Modal(document.getElementById('detailsModal')).show();
    }

    function exportToCSV() {
        const table = document.getElementById('withdrawalsTable');
        let csv = [];
        const rows = table.querySelectorAll('tr');
        
        for (let i = 0; i < rows.length; i++) {
            const row = [], cols = rows[i].querySelectorAll('td, th');
            for (let j = 0; j < cols.length - 1; j++) { // Exclude last column (actions)
                let text = cols[j].innerText.replace(/\n/g, ' ').replace(/,/g, ';');
                row.push('"' + text + '"');
            }
            csv.push(row.join(','));
        }
        
        const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
        const downloadLink = document.createElement('a');
        downloadLink.download = 'withdrawal_history_' + new Date().toISOString().split('T')[0] + '.csv';
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = 'none';
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }
</script>
</body>
</html>
