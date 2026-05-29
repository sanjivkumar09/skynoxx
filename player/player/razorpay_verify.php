<?php
session_start();
header('Content-Type: application/json');
http_response_code(503);
echo json_encode(['success' => false, 'message' => 'Online payments are disabled.']);
exit;

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'player') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

$razorpay_payment_id = $input['razorpay_payment_id'] ?? '';
$razorpay_order_id = $input['razorpay_order_id'] ?? '';
$razorpay_signature = $input['razorpay_signature'] ?? '';
$registration_id = (int)($input['registration_id'] ?? 0);
$amount = floatval($input['amount'] ?? 0);

// Validate inputs
if (empty($razorpay_payment_id) || empty($razorpay_order_id) || empty($razorpay_signature) || $registration_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment data']);
    exit;
}

// Verify signature
$generated_signature = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, RAZORPAY_KEY_SECRET);

if ($generated_signature !== $razorpay_signature) {
    // Log failed verification attempt
    error_log("Razorpay signature verification failed for payment: $razorpay_payment_id");
    echo json_encode(['success' => false, 'message' => 'Payment signature verification failed']);
    exit;
}

// Verify payment status with Razorpay API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, RAZORPAY_API_URL . 'payments/' . $razorpay_payment_id);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERPWD, RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    echo json_encode(['success' => false, 'message' => 'Failed to verify payment with Razorpay']);
    exit;
}

$payment_data = json_decode($response, true);

// validate payment token if provided
$input_raw = json_decode(file_get_contents('php://input'), true);
$provided_token = $input_raw['payment_token'] ?? '';
$reg_id_for_token = (int)($input_raw['registration_id'] ?? 0);

if (empty($provided_token) || $reg_id_for_token <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing payment token']);
    exit;
}

// Ensure payment_tokens table exists (safety)
$check_table = $conn->query("SHOW TABLES LIKE 'payment_tokens'");
if ($check_table->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Payment token record not found']);
    exit;
}

// Fetch token record
$tok_stmt = $conn->prepare("SELECT id, registration_id, player_id, amount, expires_at, used FROM payment_tokens WHERE registration_id = ? AND token = ? LIMIT 1");
$tok_stmt->bind_param('is', $reg_id_for_token, $provided_token);
$tok_stmt->execute();
$tok_res = $tok_stmt->get_result();
$token_row = $tok_res->fetch_assoc();
$tok_stmt->close();

if (!$token_row) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment token']);
    exit;
}

if ((int)$token_row['used'] === 1) {
    echo json_encode(['success' => false, 'message' => 'Payment token already used']);
    exit;
}

if (strtotime($token_row['expires_at']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Payment token expired']);
    exit;
}

// Check if payment is captured/authorized
if ($payment_data['status'] !== 'captured' && $payment_data['status'] !== 'authorized') {
    echo json_encode(['success' => false, 'message' => 'Payment not completed']);
    exit;
}

// Verify amount matches
$paid_amount = $payment_data['amount'] / 100; // Convert from paise to rupees
if ($paid_amount != $amount) {
    echo json_encode(['success' => false, 'message' => 'Payment amount mismatch']);
    exit;
}

// Update registration in database
$player_id = $_SESSION['user_id'];
$payment_method = $payment_data['method'] ?? 'razorpay';
$payment_status = 'Completed';

$stmt = $conn->prepare("
    UPDATE registrations 
    SET payment_status = ?, 
        payment_method = ?, 
        transaction_id = ?,
        payment_date = NOW()
    WHERE id = ? AND player_id = ?
");
$stmt->bind_param('sssii', $payment_status, $payment_method, $razorpay_payment_id, $registration_id, $player_id);

if ($stmt->execute()) {
    // Mark payment token used
    if (!empty($token_row['id'])) {
        $upd_tok = $conn->prepare("UPDATE payment_tokens SET used = 1 WHERE id = ?");
        if ($upd_tok) {
            $upd_tok->bind_param('i', $token_row['id']);
            $upd_tok->execute();
            $upd_tok->close();
        }
    }
    // Insert into payment_transactions table if it exists
    $check_table = $conn->query("SHOW TABLES LIKE 'payment_transactions'");
    if ($check_table->num_rows > 0) {
        // Get tournament_id
        $tournament_stmt = $conn->prepare("SELECT tournament_id FROM registrations WHERE id = ?");
        $tournament_stmt->bind_param('i', $registration_id);
        $tournament_stmt->execute();
        $tournament_result = $tournament_stmt->get_result();
        $tournament_data = $tournament_result->fetch_assoc();
        $tournament_id = $tournament_data['tournament_id'] ?? 0;
        
        if ($tournament_id > 0) {
            $trans_stmt = $conn->prepare("
                INSERT INTO payment_transactions 
                (registration_id, player_id, tournament_id, transaction_type, amount, payment_method, transaction_id, status, payment_gateway, gateway_response)
                VALUES (?, ?, ?, 'entry_fee', ?, ?, ?, 'Completed', 'razorpay', ?)
            ");
            $gateway_response = json_encode($payment_data);
            $trans_stmt->bind_param('iiiisss', $registration_id, $player_id, $tournament_id, $amount, $payment_method, $razorpay_payment_id, $gateway_response);
            $trans_stmt->execute();
        }
    }
    
    // Log successful payment
    error_log("Razorpay payment successful - Registration ID: $registration_id, Payment ID: $razorpay_payment_id, Amount: ₹$amount");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Payment verified successfully',
        'payment_id' => $razorpay_payment_id
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update payment status']);
}

$stmt->close();
$conn->close();
?>
