<?php
require_once __DIR__ . '/config.php';
mysqli_report(MYSQLI_REPORT_OFF);
if (!isset($conn) || !$conn) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        echo "DB connection failed: " . $conn->connect_error . "\n";
        exit(1);
    }
}
$sqlFile = __DIR__ . '/ff (5).sql';
if (!file_exists($sqlFile)) { echo "SQL file not found\n"; exit(1); }
$content = file_get_contents($sqlFile);
if ($content === false) { echo "Failed to read SQL file\n"; exit(1); }

// Remove CREATE DATABASE and USE statements to avoid conflicts
$content = preg_replace('/CREATE\s+DATABASE[^;]*;/i', '', $content);
$content = preg_replace('/USE\s+`?\w+`?\s*;/i', '', $content);

// Remove MySQL specific comments like /*!40101 SET ... */;
$content = preg_replace('/\/\*!.*?\*\//s', '', $content);

$content = preg_replace('/CREATE\s+TABLE\s+`(\w+)`/i', 'CREATE TABLE IF NOT EXISTS `\1`', $content);

// Execute statements one by one to avoid multi_query issues
$statements = preg_split('/;\s*\n/', $content);
$executed = 0; $failed = 0;
foreach ($statements as $stmt) {
    $s = trim($stmt);
    if ($s === '') continue;
    if ($s === '') continue;
    $res = $conn->query($s);
    if ($res === false) {
        error_log('Failed statement: ' . $conn->error . ' -- ' . substr($s,0,200));
        $failed++;
        // continue to next statement
    } else {
        $executed++;
    }
}
echo "Import done. Executed: $executed ; Failed: $failed\n";
?>
