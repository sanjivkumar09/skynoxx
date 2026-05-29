<?php
// creator/src/auth.php - minimal auth compatibility
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
?>
