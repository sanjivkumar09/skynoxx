<?php
session_start();
include('../src/db.php');
include('../src/auth.php');

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../src/login.php');
    exit();
}

// Fetch payment transactions from the database
$query = "SELECT p.id, u.name AS user_name, t.title AS tournament_title, p.amount, p.method, p.txn_id, p.status, p.created_at 
          FROM payments p 
          JOIN users u ON p.user_id = u.id 
          JOIN tournaments t ON p.tournament_id = t.id 
          ORDER BY p.created_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/gaming-theme.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments Management</title>
    <!-- Header styles for navbar -->
</head>
<body>
    <!-- Inlined navbar -->
    <nav class="navbar navbar-expand-lg gaming-navbar">
        <div class="container">
            <a class="navbar-brand" href="../login.php">
                <img src="../../assets/images/logo.svg" alt="SKYNOXX Logo" class="brand-logo-img">
                <img src="../../assets/images/logo.svg" alt="Free Fire" style="height:28px; margin-left:8px; object-fit:contain; display:inline-block;">
            </a>
            <div class="ms-auto">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="../signup.php" class="btn btn-gaming-outline me-2">Sign Up</a>
                    <a href="../login.php" class="btn btn-gaming">Login</a>
                <?php else:
                    $role = $_SESSION['role'] ?? '';
                    $user_name = $_SESSION['user_name'] ?? '';
                    $initials = '';
                    if ($user_name) {
                        $parts = explode(' ', trim($user_name));
                        $initials = strtoupper(substr($parts[0],0,1) . (isset($parts[1]) ? substr($parts[1],0,1) : ''));
                    }
                    if ($role === 'admin') {
                        $dash = '../admin/admin_dashboard.php';
                    } elseif ($role === 'creator') {
                        $dash = '../creator/creator_dashboard.php';
                    } else {
                        $dash = '../player/player_dashboard.php';
                    }
                ?>
                    <div class="position-relative">
                        <button id="profileBtn" class="profile-btn" type="button" aria-haspopup="true" aria-expanded="false">
                            <span class="profile-avatar" role="img" aria-label="User avatar"><?php echo htmlspecialchars($initials ?: 'U'); ?></span>
                        </button>
                        <div id="profileMenu" class="profile-menu">
                            <div class="profile-header">
                                <div class="d-flex align-items-center gap-2 px-2">
                                    <div class="profile-avatar" style="width:48px;height:48px;font-size:16px;"><?php echo htmlspecialchars($initials ?: 'U'); ?></div>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($user_name ?: 'User'); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($role); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group list-group-flush mt-2">
                                <a href="../player/profile_details.php" class="list-group-item list-group-item-action">View all details</a>
                                <a href="../../src/change_password.php" data-change-password="1" class="list-group-item list-group-item-action">Change password</a>
                                <a href="../../src/logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <script src="../../assets/js/header.js"></script>
    
    <div class="container">
        <h1>Payment Transactions</h1>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User Name</th>
                    <th>Tournament Title</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Transaction ID</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['tournament_title']); ?></td>
                        <td><?php echo htmlspecialchars($row['amount']); ?></td>
                        <td><?php echo htmlspecialchars($row['method']); ?></td>
                        <td><?php echo htmlspecialchars($row['txn_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php include('../src/includes/footer.php'); ?>
</body>
</html>