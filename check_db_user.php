<?php
// Check what's actually configured in Hostinger
echo "<h1>Database Configuration Check</h1>";
echo "<p>Testing different connection scenarios...</p>";

$host = 'localhost';
$user = 'u93857826_skynoxx';
$database = 'u93857826_ff';

// Test passwords user has mentioned
$passwords_to_test = [
    'E1RP5lk9w',
    'Skysanjiv',
    'SkyNoxx2024!',
    'e1rp5lk9w', // lowercase variant
    'E1RP5LK9W', // uppercase variant
];

echo "<h2>Testing Connection Attempts:</h2>";

foreach ($passwords_to_test as $index => $password) {
    echo "<p><strong>Test " . ($index + 1) . ":</strong> Password = <code>" . htmlspecialchars($password) . "</code><br>";
    
    try {
        $conn = @new mysqli($host, $user, $password, $database);
        
        if ($conn->connect_error) {
            echo "<span style='color:red;'>❌ Failed: " . $conn->connect_error . "</span></p>";
        } else {
            echo "<span style='color:green;'>✅ SUCCESS! This password works!</span><br>";
            echo "Server: " . $conn->server_info . "<br>";
            
            // Get table count
            $result = $conn->query("SHOW TABLES");
            echo "Tables found: " . $result->num_rows . "</p>";
            
            echo "<div style='background:yellow; padding:20px; margin:20px 0;'>";
            echo "<h2>✅ WORKING PASSWORD: <code>" . htmlspecialchars($password) . "</code></h2>";
            echo "</div>";
            
            $conn->close();
            break; // Stop testing once we find the right one
        }
    } catch (Exception $e) {
        echo "<span style='color:red;'>❌ Exception: " . $e->getMessage() . "</span></p>";
    }
}

echo "<hr>";
echo "<h2>Hostinger Database Panel Check:</h2>";
echo "<ol>";
echo "<li>Go to Hostinger control panel</li>";
echo "<li>Click 'Databases' → 'MySQL Databases'</li>";
echo "<li>Find database: <strong>u93857826_ff</strong></li>";
echo "<li>Check the username: Should be <strong>u93857826_skynoxx</strong></li>";
echo "<li>If password wrong, click 'Change Password' and set a NEW simple password</li>";
echo "<li>Common issue: Extra spaces or special characters in password</li>";
echo "</ol>";

echo "<h2>Alternative Solution:</h2>";
echo "<p>If none work, you may need to:</p>";
echo "<ul>";
echo "<li><strong>Reset the database user password</strong> in Hostinger panel</li>";
echo "<li>Choose something simple like: <code>SkyNoxx2024</code> (no special chars)</li>";
echo "<li>Then update all PHP files with new password</li>";
echo "</ul>";

echo "<p style='color:red;'><strong>DELETE THIS FILE after testing!</strong></p>";
?>
