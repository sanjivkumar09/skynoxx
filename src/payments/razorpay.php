<?php
// razorpay.php

require_once '../db.php'; // Include database connection
require 'vendor/autoload.php'; // Include Razorpay PHP SDK

use Razorpay\Api\Api;

$apiKey = 'YOUR_RAZORPAY_KEY';
$apiSecret = 'YOUR_RAZORPAY_SECRET';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount']; // Amount to be charged
    $tournamentId = $_POST['tournament_id']; // Tournament ID
    $userId = $_POST['user_id']; // User ID

    $api = new Api($apiKey, $apiSecret);

    // Create an order
    $orderData = [
        'receipt'         => rand(1000, 9999),
        'amount'          => $amount * 100, // Amount in paise
        'currency'        => 'INR',
        'payment_capture' => 1 // Auto capture
    ];

    $order = $api->order->create($orderData);
    $orderId = $order['id'];

    // Store order details in the database
    $stmt = $conn->prepare("INSERT INTO payments (user_id, tournament_id, amount, method, txn_id, status, created_at) VALUES (?, ?, ?, 'razorpay', ?, 'pending', NOW())");
    $stmt->bind_param("iiis", $userId, $tournamentId, $amount, $orderId);
    $stmt->execute();

    // Return order ID to the frontend
    echo json_encode(['orderId' => $orderId]);
}
?>