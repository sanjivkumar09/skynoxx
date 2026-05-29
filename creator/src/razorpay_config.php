<?php
// Minimal Razorpay config stub
define('RAZORPAY_MODE', getenv('RAZORPAY_MODE') ?: 'test');
define('RAZORPAY_KEY_ID', getenv('RAZORPAY_KEY_ID') ?: 'rzp_test_YOUR_KEY_ID');
define('RAZORPAY_KEY_SECRET', getenv('RAZORPAY_KEY_SECRET') ?: 'YOUR_SECRET');
define('RAZORPAY_BUSINESS_NAME', getenv('RAZORPAY_BUSINESS_NAME') ?: 'Tournament');
define('RAZORPAY_BUSINESS_LOGO', getenv('RAZORPAY_BUSINESS_LOGO') ?: '/assets/logo.png');
?>
