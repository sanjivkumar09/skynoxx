<?php
// Minimal change password stub
require_once __DIR__ . '/config.php';
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "Password changed (stub).";
    exit;
}
?>
<form method="post">
    <input type="password" name="old_password" placeholder="Old password">
    <input type="password" name="new_password" placeholder="New password">
    <button>Change</button>
</form>
