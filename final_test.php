<?php
// FINAL CONNECTION TEST with CORRECT credentials
echo "<h1>✅ Final Database Connection Test</h1>";

$host = 'localhost';
$user = 'u938578626_skynoxx';
$password = 'SkyNoxx2024';
$database = 'u938578626_ff';

echo "<p><strong>Testing with CORRECT credentials:</strong></p>";
echo "<ul>";
echo "<li>Host: $host</li>";
echo "<li>User: $user</li>";
echo "<li>Password: $password</li>";
echo "<li>Database: $database</li>";
echo "</ul>";

try {
    $conn = new mysqli($host, $user, $password, $database);
    
    if ($conn->connect_error) {
        echo "<div style='background:red; color:white; padding:20px;'>";
        echo "<h2>❌ FAILED!</h2>";
        echo "<p>Error: " . $conn->connect_error . "</p>";
        echo "<p><strong>If this fails, the password in Hostinger is NOT 'SkyNoxx2024'</strong></p>";
        echo "<p>Click the EYE icon (👁️) in Hostinger to see the actual password!</p>";
        echo "</div>";
    } else {
        echo "<div style='background:green; color:white; padding:30px; margin:20px 0;'>";
        echo "<h2>🎉 SUCCESS! DATABASE CONNECTED!</h2>";
        echo "<p>Server: " . $conn->server_info . "</p>";
        echo "</div>";
        
        // Get table count
        $result = $conn->query("SHOW TABLES");
        echo "<h2>Database Tables (" . $result->num_rows . "):</h2>";
        echo "<ul>";
        while ($row = $result->fetch_array()) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
        
        echo "<div style='background:yellow; padding:20px; margin:20px 0;'>";
        echo "<h2>✅ NEXT STEPS:</h2>";
        echo "<ol>";
        echo "<li><strong>Upload these files to Hostinger:</strong>";
        echo "<ul>";
        echo "<li>src/db.php</li>";
        echo "<li>src/config.php</li>";
        echo "</ul>";
        echo "</li>";
        echo "<li><strong>Delete test files from server:</strong>";
        echo "<ul>";
        echo "<li>test_database.php</li>";
        echo "<li>test_server.php</li>";
        echo "<li>verify_password.php</li>";
        echo "<li>check_db_user.php</li>";
        echo "<li>test_simple_password.php</li>";
        echo "<li>discover_username.php</li>";
        echo "<li>final_test.php (this file)</li>";
        echo "</ul>";
        echo "</li>";
        echo "<li><strong>Test your website:</strong> https://skynoxx.live</li>";
        echo "</ol>";
        echo "</div>";
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "<div style='background:red; color:white; padding:20px;'>";
    echo "<h2>❌ Exception</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
