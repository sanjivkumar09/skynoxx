<?php
// Database configuration
if (defined('DB_HOST')) {
    $host = DB_HOST;
    $user = DB_USER;
    $password = DB_PASS;
    $database = DB_NAME;
} else {
    // Try to locate and include config.php
    $config_paths = [
        __DIR__ . '/../config.php',
        __DIR__ . '/../../config.php',
        __DIR__ . '/config.php'
    ];
    $config_loaded = false;
    foreach ($config_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $host = DB_HOST;
            $user = DB_USER;
            $password = DB_PASS;
            $database = DB_NAME;
            $config_loaded = true;
            break;
        }
    }
    if (!$config_loaded) {
        $host = 'localhost'; // Database host
        $user = 'root'; // Database username
        $password = 'alex'; // Local database password
        $database = 'ff'; // Database name
    }
}

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to execute a query
function executeQuery($query) {
    global $conn;
    return $conn->query($query);
}

// Function to fetch all results
function fetchAll($result) {
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to fetch a single result
function fetchOne($result) {
    return $result->fetch_assoc();
}

// Close connection
function closeConnection() {
    global $conn;
    $conn->close();
}
?>