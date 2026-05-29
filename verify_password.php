<?php
// Quick password verification
echo "<h1>Password Check</h1>";
echo "<p>This file shows what password is in db.php on the SERVER</p>";

// Include db.php to see what password it's using
$is_local = false; // Force production mode

// Production settings
$host = 'localhost';
$user = 'u93857826_skynoxx';
$password = 'E1RP5lk9w';
$database = 'u93857826_ff';

echo "<p><strong>Attempting connection with:</strong></p>";
echo "<ul>";
echo "<li>Host: $host</li>";
echo "<li>User: $user</li>";
echo "<li>Password: $password</li>";
echo "<li>Database: $database</li>";
echo "</ul>";

try {
    $conn = new mysqli($host, $user, $password, $database);
    
    if ($conn->connect_error) {
        echo "<p style='color:red;'>❌ Connection Failed: " . $conn->connect_error . "</p>";
    } else {
        echo "<p style='color:green;'>✅ Database Connected Successfully!</p>";
        echo "<p>Server Info: " . $conn->server_info . "</p>";
        
        // List tables
        $result = $conn->query("SHOW TABLES");
        if ($result) {
            echo "<h2>Tables (" . $result->num_rows . "):</h2><ul>";
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
echo "<p><strong>If this works, then db.php is the problem.</strong></p>";
echo "<p><strong>Delete this file after testing!</strong></p>";
?>
