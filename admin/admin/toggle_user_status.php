<?php
session_start();
require_once '../src/db.php';

// Only admins allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../src/login.php');
    exit();
}

// Get user ID and action
if (!isset($_GET['user_id']) || !isset($_GET['action'])) {
    $_SESSION['error'] = "Invalid request.";
    header('Location: admin_dashboard.php');
    exit();
}

$user_id = (int)$_GET['user_id'];
$action = $_GET['action']; // 'block' or 'unblock'
$redirect = $_GET['redirect'] ?? 'admin_dashboard.php';

// Validate action
if (!in_array($action, ['block', 'unblock'])) {
    $_SESSION['error'] = "Invalid action.";
    header('Location: ' . $redirect);
    exit();
}

// Get user info
$stmt = $conn->prepare("SELECT id, name, email, role, is_active FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    $_SESSION['error'] = "User not found.";
    header('Location: ' . $redirect);
    exit();
}

// Don't allow blocking admins
if ($user['role'] === 'admin') {
    $_SESSION['error'] = "Cannot block admin accounts.";
    header('Location: ' . $redirect);
    exit();
}

// Don't allow blocking yourself
if ($user_id === $_SESSION['user_id']) {
    $_SESSION['error'] = "Cannot block your own account.";
    header('Location: ' . $redirect);
    exit();
}

try {
    // Update is_active status
    $new_status = ($action === 'block') ? 0 : 1;
    $update_stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    $update_stmt->bind_param('ii', $new_status, $user_id);
    $update_stmt->execute();
    
    $action_text = ($action === 'block') ? 'blocked' : 'unblocked';
    $_SESSION['success'] = "User '" . htmlspecialchars($user['name']) . "' has been " . $action_text . " successfully.";
    
} catch (Exception $e) {
    $_SESSION['error'] = "Error updating user status: " . $e->getMessage();
}

header('Location: ' . $redirect);
exit();
?>
