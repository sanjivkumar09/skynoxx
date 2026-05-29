<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect logged-in users directly to their dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        header('Location: admin/admin/admin_dashboard.php');
        exit();
    } elseif ($role === 'creator') {
        header('Location: creator/creator/creator_dashboard.php');
        exit();
    } elseif ($role === 'player') {
        header('Location: player/player/player_dashboard.php');
        exit();
    }
}

// Redirect to public homepage if not logged in
header('Location: src/index.php');
exit;
?>
