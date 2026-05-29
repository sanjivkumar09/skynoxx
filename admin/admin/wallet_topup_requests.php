<?php
session_start();
require_once __DIR__ . '/../src/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../src/login.php');
    exit;
}

// Ensure table exists
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

// Ensure notifications table exists (compatible schema used across app)
@$conn->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL DEFAULT 'system_announcement',
    title VARCHAR(255) NOT NULL,
    message TEXT,
    tournament_id INT NULL,
    audience ENUM('all','players','creators','user') NOT NULL DEFAULT 'players',
    audience_user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_audience (audience)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Actions: approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $id = (int)$_POST['id'];
    $action = $_POST['action'];
    $remarks = trim($_POST['remarks'] ?? '');
    $admin_id = (int)$_SESSION['user_id'];

    // Load request
    $req = $conn->query("SELECT * FROM wallet_topup_requests WHERE id = $id")->fetch_assoc();
    if ($req && $req['status'] === 'pending') {
        if ($action === 'approve') {
            try {
                $conn->begin_transaction();
                $amount = (float)$req['amount'];
                $user_id = (int)$req['user_id'];
                // Update request
                $stmt1 = $conn->prepare("UPDATE wallet_topup_requests SET status='approved', approved_at=NOW(), admin_id=?, remarks=? WHERE id=?");
                $stmt1->bind_param('isi', $admin_id, $remarks, $id);
                if (!$stmt1->execute()) { throw new Exception('update request failed'); }
                // Credit wallet
                $stmt2 = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                $stmt2->bind_param('di', $amount, $user_id);
                if (!$stmt2->execute()) { throw new Exception('wallet credit failed'); }
                // Insert transaction
                $desc = 'Manual wallet top-up approved (Request #' . $id . ')';
                $stmt3 = $conn->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description, status) VALUES (?, 'deposit', ?, ?, 'completed')");
                $stmt3->bind_param('ids', $user_id, $amount, $desc);
                if (!$stmt3->execute()) { throw new Exception('txn insert failed'); }
                $conn->commit();
                // Notify player of approval
                if ($insn = $conn->prepare("INSERT INTO notifications (type, title, message, tournament_id, audience, audience_user_id) VALUES ('wallet_topup_approved', ?, ?, NULL, 'user', ?)") ) {
                    $nt_title = 'Wallet Top-up Approved';
                    $nt_msg = 'Your manual wallet top-up of ₹' . number_format($amount, 2) . ' has been approved and credited.' . ($remarks ? ' Remarks: ' . $remarks : '');
                    $insn->bind_param('ssi', $nt_title, $nt_msg, $user_id);
                    @$insn->execute();
                    $insn->close();
                }
                $_SESSION['flash_success'] = 'Request approved and wallet credited.';
            } catch (Exception $e) {
                @$conn->rollback();
                $_SESSION['flash_error'] = 'Approval failed. ' . $e->getMessage();
            }
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE wallet_topup_requests SET status='rejected', approved_at=NOW(), admin_id=?, remarks=? WHERE id=?");
            $stmt->bind_param('isi', $admin_id, $remarks, $id);
            if ($stmt->execute()) {
                // Notify player of rejection
                $user_id = (int)$req['user_id'];
                if ($insn = $conn->prepare("INSERT INTO notifications (type, title, message, tournament_id, audience, audience_user_id) VALUES ('wallet_topup_rejected', ?, ?, NULL, 'user', ?)") ) {
                    $nt_title = 'Wallet Top-up Rejected';
                    $nt_msg = 'Your manual wallet top-up request has been rejected.' . ($remarks ? ' Reason: ' . $remarks : '');
                    $insn->bind_param('ssi', $nt_title, $nt_msg, $user_id);
                    @$insn->execute();
                    $insn->close();
                }
                $_SESSION['flash_success'] = 'Request rejected.';
            } else {
                $_SESSION['flash_error'] = 'Failed to reject request.';
            }
        }
    } else {
        $_SESSION['flash_error'] = 'Invalid or already processed request.';
    }
    header('Location: wallet_topup_requests.php');
    exit;
}

// Filters
$status = $_GET['status'] ?? 'pending';
$search = trim($_GET['search'] ?? '');

$where = ['1=1'];
$params = [];
$types = '';
if (in_array($status, ['pending','approved','rejected','all'], true) && $status !== 'all') {
    $where[] = 'r.status = ?';
    $params[] = $status; $types .= 's';
}
if ($search !== '') {
    $where[] = '(u.name LIKE ? OR u.email LIKE ? OR r.upi_reference LIKE ?)';
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like; $types .= 'sss';
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

$sql = "SELECT r.*, u.name, u.email FROM wallet_topup_requests r JOIN users u ON r.user_id = u.id $whereSql ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql);
if ($types) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$res = $stmt->get_result();
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Top-up Requests</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
    <style>
        body { background: #f5f7fb; }
        .card { border-radius: 12px; }
        .status-badge { padding: 6px 12px; border-radius: 999px; font-weight: 600; font-size: .85rem; }
        .screenshot-thumb { max-width: 120px; border-radius: 8px; border: 1px solid #e2e8f0; }
    </style>
 </head>
 <body>
 <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Manual Wallet Top-up Requests</h3>
        <div>
            <a href="admin_dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-house"></i> Dashboard</a>
            <a href="wallet_deposits.php" class="btn btn-outline-primary ms-2"><i class="bi bi-wallet2"></i> Wallet Deposits</a>
        </div>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?php echo h($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?php echo h($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
    <?php endif; ?>

    <form class="card card-body mb-3" method="GET" action="">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="pending" <?php echo $status==='pending'?'selected':''; ?>>Pending</option>
                    <option value="approved" <?php echo $status==='approved'?'selected':''; ?>>Approved</option>
                    <option value="rejected" <?php echo $status==='rejected'?'selected':''; ?>>Rejected</option>
                    <option value="all" <?php echo $status==='all'?'selected':''; ?>>All</option>
                </select>
            </div>
            <div class="col-md-7">
                <label class="form-label">Search</label>
                <input type="text" name="search" value="<?php echo h($search); ?>" placeholder="Name, Email, or UPI ref" class="form-control" />
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search"></i> Apply</button>
            </div>
        </div>
    </form>

    <?php if (empty($rows)): ?>
        <div class="alert alert-info"><i class="bi bi-info-circle"></i> No records found.</div>
    <?php else: ?>
        <div class="table-responsive card card-body">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Player</th>
                        <th>Amount</th>
                        <th>UPI Ref</th>
                        <th>Screenshot</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <?php 
                        $status_color = $r['status']==='approved'?'success':($r['status']==='rejected'?'danger':'warning');
                    ?>
                    <tr>
                        <td><?php echo (int)$r['id']; ?></td>
                        <td>
                            <div><?php echo date('d M Y', strtotime($r['created_at'])); ?></div>
                            <small class="text-muted"><?php echo date('h:i A', strtotime($r['created_at'])); ?></small>
                        </td>
                        <td>
                            <div class="fw-semibold"><?php echo h($r['name']); ?></div>
                            <small class="text-muted"><?php echo h($r['email']); ?></small>
                        </td>
                        <td class="fw-bold text-success">₹<?php echo number_format((float)$r['amount'],2); ?></td>
                        <td><code><?php echo $r['upi_reference']? h($r['upi_reference']) : '-'; ?></code></td>
                        <td>
                            <?php if (!empty($r['screenshot_path'])): ?>
                                <a href="<?php echo h($r['screenshot_path']); ?>" target="_blank">
                                    <img 
                                        src="<?php echo h($r['screenshot_path']); ?>" 
                                        class="screenshot-thumb" 
                                        alt="screenshot"
                                        data-path="<?php echo h($r['screenshot_path']); ?>"
                                        onerror="
                                            this.onerror=null;
                                            var p=this.getAttribute('data-path')||'';
                                            var alt='../'+p.replace(/^\/+/, '');
                                            this.src=alt;
                                            var a=this.closest('a'); if(a){ a.href=alt; }
                                        "
                                    />
                                </a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="status-badge bg-<?php echo $status_color; ?> text-white"><?php echo strtoupper(h($r['status'])); ?></span></td>
                        <td>
                            <?php if ($r['status'] === 'pending'): ?>
                                <form method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>" />
                                    <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Optional remarks" />
                                    <button class="btn btn-sm btn-success" name="action" value="approve" onclick="return confirm('Approve this top-up?')"><i class="bi bi-check2-circle"></i></button>
                                    <button class="btn btn-sm btn-danger" name="action" value="reject" onclick="return confirm('Reject this top-up?')"><i class="bi bi-x-circle"></i></button>
                                </form>
                            <?php else: ?>
                                <small class="text-muted"><?php echo h($r['remarks'] ?: '—'); ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
 </div>

 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
 </body>
 </html>
