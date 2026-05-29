<?php
// Final test with simple password
echo "<h1>Simple Password Test</h1>";

$host = 'localhost';
$user = 'u93857826_skynoxx';
$database = 'u93857826_ff';
$password = 'SkyNoxx2024'; // Simple password you set in Hostinger

echo "<p><strong>Testing with:</strong></p>";
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
        echo "<h2>❌ STILL FAILED!</h2>";
        echo "<p>Error: " . $conn->connect_error . "</p>";
        echo "<p><strong>This means:</strong></p>";
        echo "<ul>";
        echo "<li>Username is wrong, OR</li>";
        echo "<li>User not assigned to database, OR</li>";
        echo "<li>Database doesn't exist</li>";
        echo "</ul>";
        echo "<p><strong>SOLUTION:</strong> Take a screenshot of your Hostinger Database page and show me!</p>";
        echo "</div>";
    } else {
        echo "<div style='background:green; color:white; padding:20px;'>";
        echo "<h2>✅ SUCCESS! PASSWORD WORKS!</h2>";
        echo "<p>Server: " . $conn->server_info . "</p>";
        echo "</div>";
        
        // Get tables
        $result = $conn->query("SHOW TABLES");
        echo "<h2>Tables (" . $result->num_rows . "):</h2><ul>";
        while ($row = $result->fetch_array()) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
        
        echo "<div style='background:yellow; padding:20px;'>";
        echo "<h2>NOW DO THIS:</h2>";
        echo "<ol>";
        echo "<li>Update src/db.php line 16: <code>\$password = 'SkyNoxx2024';</code></li>";
        echo "<li>Update src/config.php DB_PASS: <code>define('DB_PASS', 'SkyNoxx2024');</code></li>";
        echo "<li>Upload both files to Hostinger /public_html/src/</li>";
        echo "<li>Test your site!</li>";
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

echo "<p style='color:red; font-weight:bold; margin-top:30px;'>DELETE THIS FILE AFTER TESTING!</p>";
?>
