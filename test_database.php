<?php
// Test Database Connection
echo "<h1>Database Connection Test</h1>";

$host = 'localhost';
$username = 'u938578626_skynoxx';
$password = 'SkyNoxx2024';
$database = 'u938578626_ff';

try {
    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        echo "<p style='color:red;'>❌ Connection Failed: " . $conn->connect_error . "</p>";
        echo "<p>Host: $host</p>";
        echo "<p>Username: $username</p>";
        echo "<p>Database: $database</p>";
    } else {
        echo "<p style='color:green;'>✅ Database Connected Successfully!</p>";
        echo "<p>Server Info: " . $conn->server_info . "</p>";
        
        // Check if tables exist
        $result = $conn->query("SHOW TABLES");
        if ($result) {
            echo "<h2>Tables in Database:</h2>";
            echo "<ul>";
            while ($row = $result->fetch_array()) {
                echo "<li>" . $row[0] . "</li>";
            }
            echo "</ul>";
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Exception: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>If database connection failed, check:</strong></p>";
echo "<ol>";
echo "<li>Database name is correct: u93857826_ff</li>";
echo "<li>Username is correct: u93857826_skynoxx</li>";
echo "<li>Password is correct: E1RP5lk9w</li>";
echo "<li>Database was imported via phpMyAdmin</li>";
echo "</ol>";
echo "<p><strong>Delete this file after testing!</strong></p>";
?>
