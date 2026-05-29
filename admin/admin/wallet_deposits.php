<?php
session_start();
include '../src/db.php';

// Ensure only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../src/login.php');
    exit();
}

// Filters
$status = isset($_GET['status']) ? trim($_GET['status']) : 'all'; // all | completed | pending | failed
$user_type = isset($_GET['user_type']) ? trim($_GET['user_type']) : 'all'; // all | player | creator | admin
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to   = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$where = ["wt.type = 'deposit'"];
$params = [];
$types = '';

if ($status !== 'all') {
    $where[] = 'wt.status = ?';
    $params[] = $status;
    $types .= 's';
}
if ($user_type !== 'all') {
    $where[] = 'u.role = ?';
    $params[] = $user_type;
    $types .= 's';
}
if ($search !== '') {
    $where[] = '(u.name LIKE ? OR u.email LIKE ? OR wt.description LIKE ?)';
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}
if ($date_from !== '') {
    $where[] = 'DATE(wt.created_at) >= ?';
    $params[] = $date_from;
    $types .= 's';
}
if ($date_to !== '') {
    $where[] = 'DATE(wt.created_at) <= ?';
    $params[] = $date_to;
    $types .= 's';
}
$where_clause = 'WHERE ' . implode(' AND ', $where);

// Count
$count_sql = "SELECT COUNT(*) AS total FROM wallet_transactions wt JOIN users u ON wt.user_id = u.id $where_clause";
$count_stmt = $conn->prepare($count_sql);
if ($types) { $count_stmt->bind_param($types, ...$params); }
$count_stmt->execute();
$count_res = $count_stmt->get_result();
$total_records = $count_res ? (int)$count_res->fetch_assoc()['total'] : 0;
$count_stmt->close();
$total_pages = $total_records > 0 ? (int)ceil($total_records / $limit) : 1;

// Stats
$stats_sql = "SELECT 
    COUNT(*) AS total_count,
    SUM(CASE WHEN wt.status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
    SUM(CASE WHEN wt.status = 'failed' THEN 1 ELSE 0 END) AS failed_count,
    SUM(CASE WHEN wt.status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
    COALESCE(SUM(CASE WHEN wt.status = 'completed' THEN wt.amount ELSE 0 END), 0) AS total_completed_amount,
    COALESCE(SUM(CASE WHEN wt.status = 'pending' THEN wt.amount ELSE 0 END), 0) AS total_pending_amount,
    COALESCE(SUM(CASE WHEN wt.status = 'failed' THEN wt.amount ELSE 0 END), 0) AS total_failed_amount
    FROM wallet_transactions wt 
    JOIN users u ON wt.user_id = u.id 
    $where_clause";
$stats_stmt = $conn->prepare($stats_sql);
if ($types) { $stats_stmt->bind_param($types, ...$params); }
$stats_stmt->execute();
$stats_res = $stats_stmt->get_result();
$stats = $stats_res ? $stats_res->fetch_assoc() : [
    'total_count' => 0,
    'completed_count' => 0,
    'failed_count' => 0,
    'pending_count' => 0,
    'total_completed_amount' => 0,
    'total_pending_amount' => 0,
    'total_failed_amount' => 0,
];
$stats_stmt->close();

// Data query
$data_sql = "SELECT wt.*, u.name, u.email, u.role, u.wallet_balance 
             FROM wallet_transactions wt 
             JOIN users u ON wt.user_id = u.id 
             $where_clause 
             ORDER BY wt.created_at DESC 
             LIMIT ? OFFSET ?";
$params_with_pagination = $params;
$types_with_pagination = $types . 'ii';
$params_with_pagination[] = $limit;
$params_with_pagination[] = $offset;

$data_stmt = $conn->prepare($data_sql);
$data_stmt->bind_param($types_with_pagination, ...$params_with_pagination);
$data_stmt->execute();
$data_res = $data_stmt->get_result();
$deposits = $data_res ? $data_res->fetch_all(MYSQLI_ASSOC) : [];
$data_stmt->close();

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Wallet Deposits - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Inter', sans-serif; background: #f3f6fb; }
        .main-container { background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); padding: 28px; }
        .stats-card { border-radius: 12px; color: #fff; padding: 18px; }
        .bg-total { background: linear-gradient(135deg, #36d1dc 0%, #5b86e5 100%); }
        .bg-completed { background: linear-gradient(135deg, #0ba360 0%, #3cba92 100%); }
        .bg-pending { background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); }
        .bg-failed { background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); }
        .filter-card { background: #f8fafc; border-radius: 12px; padding: 16px; border: 1px solid #eef2f7; }
        .table thead { background: #0d6efd; color: #fff; }
        .status-badge { padding: 6px 12px; border-radius: 999px; font-weight: 600; font-size: .85rem; }
        .truncate { max-width: 360px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        code.small { background: #f1f5f9; padding: 2px 6px; border-radius: 6px; font-size: .85rem; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="main-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1"><i class="bi bi-wallet2"></i> Wallet Deposits</h2>
                <p class="text-muted mb-0">Track all wallet deposits with filters and export. For manual requests, see <a href="wallet_topup_requests.php">Manual Top-up Requests</a>.</p>
            </div>
            <div>
                <a href="admin_dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-house"></i> Dashboard</a>
                <a href="withdrawal_history.php" class="btn btn-outline-primary ms-2"><i class="bi bi-clock-history"></i> Withdrawals</a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="stats-card bg-total h-100">
                    <div class="small">Total Deposits</div>
                    <div class="fs-3 fw-bold"><?php echo number_format((int)$stats['total_count']); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card bg-completed h-100">
                    <div class="small">Completed</div>
                    <div class="fs-3 fw-bold"><?php echo number_format((int)$stats['completed_count']); ?></div>
                    <div class="small">₹<?php echo number_format((float)$stats['total_completed_amount'], 2); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card bg-pending h-100">
                    <div class="small">Pending</div>
                    <div class="fs-3 fw-bold"><?php echo number_format((int)$stats['pending_count']); ?></div>
                    <div class="small">₹<?php echo number_format((float)$stats['total_pending_amount'], 2); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card bg-failed h-100">
                    <div class="small">Failed</div>
                    <div class="fs-3 fw-bold"><?php echo number_format((int)$stats['failed_count']); ?></div>
                    <div class="small">₹<?php echo number_format((float)$stats['total_failed_amount'], 2); ?></div>
                </div>
            </div>
        </div>

        <div class="filter-card mb-3">
            <form method="GET" action="wallet_deposits.php" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status==='all'?'selected':''; ?>>All</option>
                        <option value="completed" <?php echo $status==='completed'?'selected':''; ?>>Completed</option>
                        <option value="pending" <?php echo $status==='pending'?'selected':''; ?>>Pending</option>
                        <option value="failed" <?php echo $status==='failed'?'selected':''; ?>>Failed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">User Type</label>
                    <select name="user_type" class="form-select">
                        <option value="all" <?php echo $user_type==='all'?'selected':''; ?>>All</option>
                        <option value="player" <?php echo $user_type==='player'?'selected':''; ?>>Player</option>
                        <option value="creator" <?php echo $user_type==='creator'?'selected':''; ?>>Creator</option>
                        <option value="admin" <?php echo $user_type==='admin'?'selected':''; ?>>Admin</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo h($date_from); ?>" />
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo h($date_to); ?>" />
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Per Page</label>
                    <select name="limit" class="form-select">
                        <option value="25" <?php echo $limit===25?'selected':''; ?>>25</option>
                        <option value="50" <?php echo $limit===50?'selected':''; ?>>50</option>
                        <option value="100" <?php echo $limit===100?'selected':''; ?>>100</option>
                        <option value="200" <?php echo $limit===200?'selected':''; ?>>200</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Name, Email, Payment ID or Description" value="<?php echo h($search); ?>" />
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 me-2"><i class="bi bi-search"></i> Apply</button>
                    <a href="wallet_deposits.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Reset</a>
                </div>
            </form>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="text-muted">Showing <?php echo number_format(min($offset+1, max(1,$total_records))); ?> to <?php echo number_format(min($offset+$limit, $total_records)); ?> of <?php echo number_format($total_records); ?> records</div>
            <div>
                <button class="btn btn-sm btn-outline-success" onclick="exportToCSV()"><i class="bi bi-filetype-csv"></i> Export CSV</button>
            </div>
        </div>

        <?php if (empty($deposits)): ?>
            <div class="alert alert-info"><i class="bi bi-info-circle"></i> No wallet deposit records found for the selected filters.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover" id="depositsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment ID</th>
                        <th>Description</th>
                        <th>Current Wallet</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($deposits as $d): ?>
                    <?php
                    // Try to extract payment id from description pattern: "Wallet Deposit via Razorpay: <payment_id>"
                    $paymentId = '';
                    if (!empty($d['description']) && strpos($d['description'], 'Razorpay:') !== false) {
                        $parts = explode('Razorpay:', $d['description'], 2);
                        $paymentId = trim($parts[1] ?? '');
                    }
                    $status_color = 'secondary';
                    if ($d['status'] === 'completed') $status_color = 'success';
                    elseif ($d['status'] === 'pending') $status_color = 'warning';
                    elseif ($d['status'] === 'failed') $status_color = 'danger';
                    ?>
                    <tr>
                        <td class="fw-semibold">#<?php echo (int)$d['id']; ?></td>
                        <td>
                            <div><?php echo date('d M Y', strtotime($d['created_at'])); ?></div>
                            <small class="text-muted"><?php echo date('h:i A', strtotime($d['created_at'])); ?></small>
                        </td>
                        <td>
                            <div class="fw-semibold"><?php echo h($d['name']); ?></div>
                            <small class="text-muted"><?php echo h($d['email']); ?></small>
                            <div><span class="badge bg-<?php echo $d['role']==='creator'?'success':($d['role']==='admin'?'dark':'primary'); ?> mt-1"><?php echo ucfirst(h($d['role'])); ?></span></div>
                        </td>
                        <td class="text-success fw-bold">₹<?php echo number_format((float)$d['amount'], 2); ?></td>
                        <td><span class="status-badge bg-<?php echo $status_color; ?> text-white"><?php echo strtoupper(h($d['status'])); ?></span></td>
                        <td><?php echo $paymentId ? '<code class="small">'.h($paymentId).'</code>' : '<span class="text-muted">-</span>'; ?></td>
                        <td class="truncate" title="<?php echo h($d['description']); ?>"><?php echo h($d['description']); ?></td>
                        <td>₹<?php echo number_format((float)$d['wallet_balance'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-3">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $page-1; ?>&status=<?php echo h($status); ?>&user_type=<?php echo h($user_type); ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo h($date_from); ?>&date_to=<?php echo h($date_to); ?>&limit=<?php echo (int)$limit; ?>">&laquo; Prev</a></li>
                <?php endif; ?>
                <?php
                $start = max(1, $page-2);
                $end = min($total_pages, $page+2);
                for ($i=$start; $i<=$end; $i++): ?>
                    <li class="page-item <?php echo $i===$page?'active':''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo h($status); ?>&user_type=<?php echo h($user_type); ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo h($date_from); ?>&date_to=<?php echo h($date_to); ?>&limit=<?php echo (int)$limit; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $page+1; ?>&status=<?php echo h($status); ?>&user_type=<?php echo h($user_type); ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo h($date_from); ?>&date_to=<?php echo h($date_to); ?>&limit=<?php echo (int)$limit; ?>">Next &raquo;</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function exportToCSV() {
    const table = document.getElementById('depositsTable');
    const rows = Array.from(table.querySelectorAll('tr'));
    const csv = [];
    rows.forEach((row, rIdx) => {
        const cols = Array.from(row.querySelectorAll('th,td'));
        // Include all columns
        const out = cols.map((c) => '"' + (c.innerText || '').replace(/\n/g, ' ').replace(/"/g, '""') + '"');
        csv.push(out.join(','));
    });
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'wallet_deposits_' + new Date().toISOString().slice(0,10) + '.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}
</script>
</body>
</html>
