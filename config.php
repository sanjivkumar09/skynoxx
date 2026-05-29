<?php
// Project configuration - fill these values before running the site

// Database credentials
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_USER', getenv('DB_USER') ?: 'root');
// Password provided by user
define('DB_PASS', getenv('DB_PASS') ?: 'alex');
// Database name from provided SQL dump
define('DB_NAME', getenv('DB_NAME') ?: 'ff');

// Base URL for the site (update when moving to production)
define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost/ff');

// Create MySQLi connection used by scripts
// Try creating MySQLi connection but don't fatal — allow the app to load for testing.
$DB_CONNECTED = false;
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    $DB_CONNECTED = true;
} catch (Exception $e) {
    // Log the error and continue. Pages that require DB will need a valid connection.
    error_log($e->getMessage());
    $conn = null;
}

// Optional: start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helpful: prevent direct access to this file revealing secrets
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "Config file loaded. Edit DB_* constants and BASE_URL before use.";
}

?>
