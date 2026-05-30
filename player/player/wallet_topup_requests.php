<?php
session_start();
require_once __DIR__ . '/../src/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'player') {
    header('Location: ../../src/login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Ensure table exists (runtime safety)
@$conn->query("CREATE TABLE IF NOT EXISTS wallet_topup_requests (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Filters
$status = $_GET['status'] ?? 'all';
$valid = ['all','pending','approved','rejected'];
if (!in_array($status, $valid, true)) { $status = 'all'; }

$where = 'WHERE r.user_id = ?';
$params = [$user_id];
$types = 'i';
if ($status !== 'all') {
    $where .= ' AND r.status = ?';
    $params[] = $status;
    $types .= 's';
}

// Fetch rows
$sql = "SELECT r.* FROM wallet_topup_requests r $where ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/gaming-theme.css">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Top-up Requests</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
</head>
<body>
  <div class="container container-narrow py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="mb-0">My Top-up Requests</h2>
      <div>
        <a href="wallet_topup_manual.php" class="btn btn-sm btn-primary me-2"><i class="bi bi-plus-circle"></i> New Top-up</a>
        <a href="wallet_dashboard.php" class="btn btn-sm btn-outline-light"><i class="bi bi-wallet2"></i> Wallet</a>
      </div>
    </div>

    <form class="card card-glass card-body mb-3" method="GET">
      <div class="row g-3 align-items-end">
        <div class="col-sm-4">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="all" <?php echo $status==='all'?'selected':''; ?>>All</option>
            <option value="pending" <?php echo $status==='pending'?'selected':''; ?>>Pending</option>
            <option value="approved" <?php echo $status==='approved'?'selected':''; ?>>Approved</option>
            <option value="rejected" <?php echo $status==='rejected'?'selected':''; ?>>Rejected</option>
          </select>
        </div>
        <div class="col-sm-2">
          <button class="btn btn-primary w-100" type="submit"><i class="bi bi-filter"></i> Apply</button>
        </div>
      </div>
    </form>

    <?php if (empty($rows)): ?>
      <div class="alert alert-info"><i class="bi bi-info-circle"></i> No top-up requests found.</div>
    <?php else: ?>
      <div class="table-responsive card card-glass card-body">
        <table class="table align-middle">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Date</th>
              <th>Amount</th>
              <th>UPI Ref</th>
              <th>Screenshot</th>
              <th>Status</th>
              <th>Remarks</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $r): ?>
            <?php $status_color = $r['status']==='approved'?'success':($r['status']==='rejected'?'danger':'warning'); ?>
            <tr>
              <td><?php echo (int)$r['id']; ?></td>
              <td>
                <div><?php echo date('d M Y', strtotime($r['created_at'])); ?></div>
                <small class="text-muted"><?php echo date('h:i A', strtotime($r['created_at'])); ?></small>
              </td>
              <td class="fw-bold text-success">₹<?php echo number_format((float)$r['amount'],2); ?></td>
              <td><code><?php echo $r['upi_reference']? h($r['upi_reference']) : '-'; ?></code></td>
              <td>
                <?php if (!empty($r['screenshot_path'])): ?>
                  <a href="<?php echo h($r['screenshot_path']); ?>" target="_blank">
                    <img src="<?php echo h($r['screenshot_path']); ?>" class="screenshot-thumb" alt="screenshot"
                      data-path="<?php echo h($r['screenshot_path']); ?>"
                      onerror="this.onerror=null;var p=this.getAttribute('data-path')||'';var alt='../'+p.replace(/^\/+/, '');this.src=alt;var a=this.closest('a');if(a){a.href=alt;}" />
                  </a>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td><span class="status-badge bg-<?php echo $status_color; ?> text-white"><?php echo strtoupper(h($r['status'])); ?></span></td>
              <td><small class="text-muted"><?php echo h($r['remarks'] ?: '—'); ?></small></td>
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
