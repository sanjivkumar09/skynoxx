<?php
// admin/src/auth.php - minimal auth compatibility
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
// Basic helper for older code expecting auth checks
function ensure_logged_in_and_role($roles = []) {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . (defined('BASE_URL') ? BASE_URL . '/login.php' : '../src/login.php'));
        exit;
    }
    if (!empty($roles) && !in_array($_SESSION['role'] ?? '', $roles, true)) {
        die('Unauthorized');
    }
}
?>
