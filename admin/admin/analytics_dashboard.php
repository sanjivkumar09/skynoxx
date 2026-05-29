<?php
session_start();
include '../src/db.php';
// Only allow admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../src/login.php');
    exit();
}
// Revenue summary from wallet transactions and tournament entries
$revenue = 0;
$payments = [];
$methods = [];

// Get total revenue from tournament entries
$sql = "SELECT SUM(t.entry_fee) as total_revenue 
        FROM registrations r 
        INNER JOIN tournaments t ON r.tournament_id = t.id 
        WHERE r.payment_status IN ('success', 'paid', 'completed')";
$res = $conn->query($sql);
if ($row = $res->fetch_assoc()) {
    $revenue = $row['total_revenue'] ?? 0;
}

// Get payment methods from registrations
$sql = "SELECT payment_method as method, COUNT(*) as count 
        FROM registrations 
        WHERE payment_status IN ('success', 'paid', 'completed') 
        AND payment_method IS NOT NULL 
        GROUP BY payment_method";
$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) {
    $methods[$row['method']] = $row['count'];
}

// Get all registrations with payment details
$sql = "SELECT r.id, r.player_id, r.tournament_id, r.payment_status, r.payment_method, 
          r.transaction_id, r.prize_won, r.joined_at, r.slot_no,
          u.name as player_name, u.email as player_email,
          t.title as tournament_title, t.entry_fee, t.prize_pool,
          p.in_game_name, p.game_uid
          FROM registrations r
          INNER JOIN users u ON r.player_id = u.id
          INNER JOIN tournaments t ON r.tournament_id = t.id
          LEFT JOIN players_profile p ON r.player_id = p.user_id
          ORDER BY r.joined_at DESC 
          LIMIT 200";
$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) {
    $payments[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Analytics Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #101a24; color: #fff; }
        .card { background: #23243a; border-radius: 16px; border: none; }
        .card-title { color: #00f5ff; }
        .table { color: #fff; }
        .btn-export { background: #00f5ff; color: #101a24; font-weight: 600; border-radius: 8px; }
        .btn-export:hover { background: #ffaa00; color: #23243a; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Financial Analytics Dashboard</h2>
        <div class="dropdown">
            <button class="btn btn-secondary dropdown-toggle" type="button" id="adminMenu" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-user-circle me-2"></i>Admin Menu
            </button>
            <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="adminMenu">
                <li><a class="dropdown-item" href="admin_dashboard.php"><i class="fas fa-home me-2"></i>Dashboard</a></li>
                <li><a class="dropdown-item" href="analytics_dashboard.php"><i class="fas fa-chart-line me-2"></i>Analytics</a></li>
                <li><a class="dropdown-item" href="payment_management.php"><i class="fas fa-credit-card me-2"></i>Payment Management</a></li>
                <li><a class="dropdown-item" href="admin_withdrawals.php"><i class="fas fa-money-check-alt me-2"></i>Withdrawal Management</a></li>
                <li><a class="dropdown-item" href="wallet_deposits.php"><i class="fas fa-wallet me-2"></i>Wallet Deposits</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../../src/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card p-3 mb-3">
                <h5 class="card-title">Total Revenue</h5>
                <div class="fs-3 fw-bold">₹<?php echo number_format($revenue,2); ?></div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card p-3 mb-3">
                <h5 class="card-title">Payment Methods</h5>
                <?php if (empty($methods)): ?>
                    <p class="text-muted mb-0">No payment data available yet.</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($methods as $method => $count): ?>
                        <li class="list-group-item bg-transparent text-white d-flex justify-content-between align-items-center">
                            <?php echo htmlspecialchars($method ?: 'Wallet/Direct'); ?>
                            <span class="badge bg-info text-dark"><?php echo $count; ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="card p-3 mb-3">
        <h5 class="card-title">Recent Registrations & Payments</h5>
        <div class="mb-2">
            <button class="btn btn-export me-2" onclick="exportTableToCSV('payments.csv')">Export CSV</button>
            <button class="btn btn-export" onclick="window.print()">Export PDF</button>
            <input type="text" id="searchInput" class="form-control d-inline-block w-auto ms-3" placeholder="Search by player, tournament, method..." onkeyup="filterTable()">
        </div>
        <?php if (empty($payments)): ?>
            <div class="text-center py-4">
                <p class="text-muted">No registration data available yet.</p>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-dark table-striped" id="paymentsTable">
                <thead>
                    <tr>
                        <th>Player</th>
                        <th>Tournament</th>
                        <th>Entry Fee</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Txn ID</th>
                        <th>Slot #</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p['player_name']); ?></td>
                        <td><?php echo htmlspecialchars($p['tournament_title']); ?></td>
                        <td>₹<?php echo number_format($p['entry_fee'], 2); ?></td>
                        <td><?php echo htmlspecialchars($p['payment_method'] ?? 'Wallet'); ?></td>
                        <td>
                            <?php 
                            $status = strtolower($p['payment_status'] ?? '');
                            $statusClass = in_array($status, ['success', 'paid', 'completed']) ? 'badge bg-success' : 'badge bg-warning';
                            echo '<span class="' . $statusClass . '">' . htmlspecialchars(ucfirst($status)) . '</span>';
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($p['transaction_id'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($p['slot_no'] ?? '0'); ?></td>
                        <td><?php echo date('M d, Y h:i A', strtotime($p['joined_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<script>
function exportTableToCSV(filename) {
    var csv = [];
    var rows = document.querySelectorAll("#paymentsTable tr");
    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll("td, th");
        for (var j = 0; j < cols.length; j++) row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
        csv.push(row.join(","));
    }
    var csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
    var downloadLink = document.createElement("a");
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
}
function filterTable() {
    var input = document.getElementById("searchInput");
    var filter = input.value.toLowerCase();
    var table = document.getElementById("paymentsTable");
    var trs = table.getElementsByTagName("tr");
    for (var i = 1; i < trs.length; i++) {
        var tds = trs[i].getElementsByTagName("td");
        var show = false;
        for (var j = 0; j < tds.length; j++) {
            if (tds[j].innerText.toLowerCase().indexOf(filter) > -1) {
                show = true;
                break;
            }
        }
        trs[i].style.display = show ? "" : "none";
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
