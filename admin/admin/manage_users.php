<?php
session_start();
require_once '../src/db.php';
require_once '../src/auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../src/login.php');
    exit();
}

$db = new Database();
$users = $db->query("SELECT * FROM users");

if (isset($_POST['approve'])) {
    $userId = $_POST['user_id'];
    $db->query("UPDATE users SET role = 'creator' WHERE id = ?", [$userId]);
    header("Location: manage_users.php");
    exit();
}

if (isset($_POST['remove'])) {
    $userId = $_POST['user_id'];
    $db->query("DELETE FROM users WHERE id = ?", [$userId]);
    header("Location: manage_users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/mobile-responsive.css">
    <!-- Header styles for navbar -->
    <style>
        :root { --primary:#ff4655; --primary-dark:#e03e4c; --secondary:#0f1923; --text:#ece8e1; --text-muted:#b8b3ad; }
        .gaming-navbar { background: rgba(15,25,35,0.85); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(255,70,85,0.2); padding: .5rem 0; position: sticky; top: 0; z-index: 1000; }
        .navbar-brand { font-family:'Orbitron',sans-serif; font-weight:800; font-size:1.1rem; color:var(--text); display:flex; align-items:center; }
        .navbar-brand span { color: var(--primary); }
        .brand-logo-img { width:40px; height:40px; margin-right:10px; border-radius:6px; object-fit:contain; }
        .btn-gaming { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); border: none; color: #fff; font-weight: 600; padding: .35rem .9rem; border-radius: 4px; }
        .btn-gaming-outline { background: transparent; border: 2px solid var(--primary); color: var(--primary); font-weight: 600; padding: .35rem .9rem; border-radius: 4px; }
        .profile-avatar { width: 40px; height: 40px; border-radius: 50%; display:inline-flex; align-items:center; justify-content:center; background: linear-gradient(135deg,#ff4655,#e03e4c); color:#fff; font-weight:700; }
        .profile-menu { position:absolute; right:0; top:54px; min-width:260px; display:none; flex-direction:column; gap:.25rem; padding:.5rem; border-radius:8px; box-shadow:0 10px 30px rgba(0,0,0,.4); background:linear-gradient(180deg, rgba(26,43,60,.98), rgba(15,25,35,.98)); border:1px solid rgba(255,255,255,.04); transform-origin:top right; opacity:0; transform:translateY(-6px) scale(.98); transition:opacity 180ms ease, transform 180ms ease; z-index:1100; pointer-events:none; }
        .profile-menu.show { display:flex; opacity:1; transform:translateY(0) scale(1); pointer-events:auto; }
    </style>
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
                                <a href="../admin/admin_dashboard.php" class="list-group-item list-group-item-action"><i class="fas fa-home me-2"></i>Dashboard</a>
                                <a href="../admin/analytics_dashboard.php" class="list-group-item list-group-item-action"><i class="fas fa-chart-line me-2"></i>Analytics</a>
                                <a href="../admin/payment_management.php" class="list-group-item list-group-item-action"><i class="fas fa-credit-card me-2"></i>Payments</a>
                                <a href="../admin/admin_withdrawals.php" class="list-group-item list-group-item-action"><i class="fas fa-money-check-alt me-2"></i>Withdrawals</a>
                                <a href="../admin/wallet_deposits.php" class="list-group-item list-group-item-action"><i class="fas fa-wallet me-2"></i>Wallet Deposits</a>
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
        <h1>Manage Users</h1>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['role']); ?></td>
                    <td>
                        <?php if ($user['role'] === 'player'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" name="approve">Approve</button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" name="remove">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php include '../src/includes/footer.php'; ?>
</body>
</html>