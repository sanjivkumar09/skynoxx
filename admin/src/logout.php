<?php
require_once __DIR__ . '/config.php';
session_unset(); session_destroy();
header('Location: ' . (defined('BASE_URL') ? BASE_URL : '../../src/index.php'));
exit;
?>
