<?php
// api/src/auth.php - minimal auth compatibility for APIs
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
// For APIs, prefer token-based auth; fallback to session user
function api_require_auth() {
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) return true; // leave details to API code
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    return true;
}
?>
