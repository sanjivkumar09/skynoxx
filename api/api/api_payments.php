<?php
// api/api_payments.php

require_once '../src/db.php';
require_once '../src/config.php';
require_once '../src/helpers.php';

// Function to handle payment processing
function processPayment($userId, $tournamentId, $amount, $method) {
    // Here you would integrate with Razorpay or Paytm based on the method
    // For example, if using Razorpay:
    // $response = callRazorpayAPI($amount, $method);
    
    // Assuming payment is successful for demonstration
    $txnId = uniqid(); // Generate a unique transaction ID
    $status = 'success'; // This should be based on the actual payment response

    // Store payment details in the database
    $db = new Database();
    $query = "INSERT INTO payments (user_id, tournament_id, amount, method, txn_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($query);
    $stmt->bind_param("iidssi", $userId, $tournamentId, $amount, $method, $txnId, $status);
    $stmt->execute();
    
    return $stmt->affected_rows > 0;
}

// Function to retrieve payment status
function getPaymentStatus($txnId) {
    $db = new Database();
    $query = "SELECT * FROM payments WHERE txn_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("s", $txnId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'];
    $tournamentId = $_POST['tournament_id'];
    $amount = $_POST['amount'];
    $method = $_POST['method'];

    if (processPayment($userId, $tournamentId, $amount, $method)) {
        echo json_encode(['status' => 'success', 'message' => 'Payment processed successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Payment processing failed.']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $txnId = $_GET['txn_id'];
    $paymentInfo = getPaymentStatus($txnId);
    
    if ($paymentInfo) {
        echo json_encode(['status' => 'success', 'data' => $paymentInfo]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Transaction not found.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>