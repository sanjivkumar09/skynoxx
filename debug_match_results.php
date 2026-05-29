<?php
// Debug script to check match_results table structure and data
require_once __DIR__ . '/../src/db.php';

echo "<h2>Match Results Table Structure:</h2>";
$result = $conn->query("DESCRIBE match_results");
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
    echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><br><h2>Recent Match Results (Last 10):</h2>";
$result = $conn->query("SELECT * FROM match_results ORDER BY updated_at DESC LIMIT 10");
if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; font-size: 12px;'>";
    echo "<tr>";
    $first_row = $result->fetch_assoc();
    foreach ($first_row as $key => $value) {
        echo "<th>" . htmlspecialchars($key) . "</th>";
    }
    echo "</tr>";
    
    // Reset pointer and display all rows
    $result->data_seek(0);
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No match results found.</p>";
}

echo "<br><br><h2>Check if bhooya_points column exists:</h2>";
$result = $conn->query("SHOW COLUMNS FROM match_results LIKE 'bhooya_points'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✓ bhooya_points column EXISTS</p>";
    $col_info = $result->fetch_assoc();
    echo "<pre>" . print_r($col_info, true) . "</pre>";
} else {
    echo "<p style='color: red;'>✗ bhooya_points column DOES NOT EXIST - Need to run migration!</p>";
    echo "<p>Run this SQL command:</p>";
    echo "<pre>ALTER TABLE match_results ADD COLUMN bhooya_points DECIMAL(10,2) DEFAULT 0 AFTER kill_points;</pre>";
}
?>
