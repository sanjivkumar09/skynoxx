<?php
require_once __DIR__ . '/config.php';

// If $conn is not available because DB doesn't exist yet, connect without selecting DB to create it
if (!isset($conn) || !$conn) {
    $adminConn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    if ($adminConn->connect_error) {
        echo "Cannot connect to MySQL: " . $adminConn->connect_error . "\n";
        exit(1);
    }
    $createDbSql = "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;";
    if (!$adminConn->query($createDbSql)) {
        echo "Failed to create database: " . $adminConn->error . "\n";
        $adminConn->close();
        exit(1);
    }
    $adminConn->close();
    // Now connect to the newly created database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        echo "Failed to connect to database after creation: " . $conn->connect_error . "\n";
        exit(1);
    }
}
$sqlFile = __DIR__ . '/ff (5).sql';
if (!file_exists($sqlFile)) {
    echo "SQL file not found: $sqlFile\n";
    exit(1);
}
$sql = file_get_contents($sqlFile);
if ($sql === false) { echo "Failed to read SQL file\n"; exit(1); }

// Execute multi-query
if ($conn->multi_query($sql)) {
    do {
        if ($res = $conn->use_result()) {
            // free result set
            $res->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "SQL import completed.\n";
    exit(0);
} else {
    echo "SQL import failed: " . $conn->error . "\n";
    exit(1);
}
?>
