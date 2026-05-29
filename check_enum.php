<?php
$conn = new mysqli('localhost', 'root', '', 'ff');
$r = $conn->query("SHOW COLUMNS FROM tournaments LIKE 'match_type'");
$row = $r->fetch_assoc();
echo "ENUM Definition: " . $row['Type'] . "\n";
echo "Default: " . $row['Default'] . "\n";
echo "Null: " . $row['Null'] . "\n";
?>
