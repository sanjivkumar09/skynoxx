<?php
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');
if (isset($DB_CONNECTED) && $DB_CONNECTED && $conn) {
    echo "DB connected to " . DB_NAME . " as " . DB_USER . "@" . DB_HOST . "\n";
    // show a simple query
    $res = $conn->query('SELECT COUNT(*) as c FROM users');
    if ($res) {
        $row = $res->fetch_assoc();
        echo "users count: " . ($row['c'] ?? '0') . "\n";
    } else {
        echo "Query failed: " . $conn->error . "\n";
    }
} else {
    echo "DB not connected. Check config.php credentials.\n";
}
?>
