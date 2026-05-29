<?php
// Minimal wallet deposit order creation stub for testing
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');
$data = ['success' => false];
// In production, integrate with Razorpay; here return dummy order
$data['success'] = true;
$data['order'] = ['id' => 'order_test_' . time(), 'amount' => intval($_POST['amount'] ?? 0)];
echo json_encode($data);
?>
