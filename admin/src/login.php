<?php
// Minimal login placeholder for compatibility
require_once __DIR__ . '/config.php';
if (isset($_POST['username'])) {
    // This is only a compatibility stub: set a demo session
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
    header('Location: ' . (defined('BASE_URL') ? BASE_URL : '../../src/index.php'));
    exit;
}
?>
<form method="post">
    <label>Username: <input name="username"></label>
    <label>Password: <input name="password" type="password"></label>
    <button>Login (stub)</button>
</form>
