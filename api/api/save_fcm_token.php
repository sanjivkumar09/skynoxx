<?php
/**
 * Save FCM Token API
 * Saves the device FCM token for the logged-in user
 */

// Start output buffering to catch any errors
ob_start();

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../src/config.php';

// Clear output buffer
ob_end_clean();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated - Please login first', 'redirect' => '/Free fire 1/free-fire-tournament-platform/src/login.php']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['fcm_token']) || empty($input['fcm_token'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'FCM token is required']);
    exit;
}

$userId = $_SESSION['user_id'];
$fcmToken = $input['fcm_token'];

try {
    // Create database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Update user's FCM token (use 'id' column instead of 'user_id')
    $stmt = $conn->prepare("UPDATE users SET fcm_token = ?, fcm_token_updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $fcmToken, $userId);
    
    if ($stmt->execute()) {
        error_log("FCM token saved for user ID: $userId - Token: " . substr($fcmToken, 0, 20) . "...");
        echo json_encode([
            'success' => true,
            'message' => 'FCM token saved successfully'
        ]);
    } else {
        throw new Exception("Failed to update FCM token: " . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    error_log("Error saving FCM token: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to save FCM token'
    ]);
}
