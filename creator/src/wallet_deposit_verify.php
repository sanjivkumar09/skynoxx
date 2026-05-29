<?php
// Minimal wallet deposit verification stub
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');
$ok = true;
if ($ok) {
    echo json_encode(['success' => true, 'message' => 'Verified (stub)']);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Verification failed']);
}
?>
