<?php
session_start();
require_once __DIR__ . '/../src/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'player') {
    header('Location: ../../src/login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Ensure table exists
$createSql = "CREATE TABLE IF NOT EXISTS wallet_topup_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    upi_reference VARCHAR(100) NULL,
    screenshot_path VARCHAR(255) NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    remarks VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME NULL,
    admin_id INT NULL,
    INDEX (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
@$conn->query($createSql);

function redirect_with($msg = '', $err = '') {
    $q = [];
    if ($msg !== '') $q['msg'] = $msg;
    if ($err !== '') $q['err'] = $err;
    $qs = $q ? ('?' . http_build_query($q)) : '';
    header('Location: wallet_topup_manual.php' . $qs);
    exit;
}

// Validate amount
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
$upi_reference = isset($_POST['upi_reference']) ? trim($_POST['upi_reference']) : '';

if ($amount <= 0) {
    redirect_with('', 'Invalid amount.');
}

// Validate file
if (!isset($_FILES['screenshot']) || $_FILES['screenshot']['error'] !== UPLOAD_ERR_OK) {
    redirect_with('', 'Please upload a payment screenshot.');
}

$file = $_FILES['screenshot'];
if ($file['size'] > 5 * 1024 * 1024) { // 5MB
    redirect_with('', 'File too large. Max 5 MB.');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
if (!isset($allowed[$mime])) {
    redirect_with('', 'Invalid image type. Use JPG, PNG, or WEBP.');
}

$ext = $allowed[$mime];
$dir = __DIR__ . '/../uploads/topups';
if (!is_dir($dir)) { @mkdir($dir, 0777, true); }

$filename = 'topup_user_' . $user_id . '_' . time() . '.' . $ext;
$destPath = $dir . '/' . $filename;
$publicPath = '/uploads/topups/' . $filename;



if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    redirect_with('', 'Failed to save file. Please try again.');
}

// Insert request
$stmt = $conn->prepare("INSERT INTO wallet_topup_requests (user_id, amount, upi_reference, screenshot_path, status) VALUES (?, ?, ?, ?, 'pending')");
if (!$stmt) {
    @unlink($destPath);
    redirect_with('', 'Server error. Try again later.');
}
$stmt->bind_param('idss', $user_id, $amount, $upi_reference, $publicPath);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    @unlink($destPath);
    redirect_with('', 'Could not submit request.');
}

redirect_with('Top-up request submitted. Admin will review shortly.');
// End of script
