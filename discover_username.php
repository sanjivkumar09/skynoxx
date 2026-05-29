<?php
// Discover the actual database username
echo "<h1>Database Username Discovery</h1>";
echo "<p>Testing common Hostinger username patterns...</p>";

$host = 'localhost';
$database = 'u93857826_ff';
$password = 'SkyNoxx2024'; // The password you just set

// Common Hostinger username patterns
$usernames_to_test = [
    'u93857826_skynoxx',
    'u93857826_root',
    'u93857826_admin',
    'u93857826_ff',        // Sometimes same as database name
    'u93857826',           // Just the prefix
    'root',                // Plain root
    'admin',               // Plain admin
];

echo "<h2>Testing Usernames:</h2>";

$found = false;

foreach ($usernames_to_test as $index => $username) {
    echo "<p><strong>Test " . ($index + 1) . ":</strong> Username = <code>" . htmlspecialchars($username) . "</code><br>";
    
    try {
        $conn = @new mysqli($host, $username, $password, $database);
        
        if ($conn->connect_error) {
            echo "<span style='color:red;'>❌ Failed: " . $conn->connect_error . "</span></p>";
        } else {
            echo "<span style='color:green;'>✅ SUCCESS!</span><br>";
            echo "Server: " . $conn->server_info . "</p>";
            
            // Get tables
            $result = $conn->query("SHOW TABLES");
            echo "<p>Tables found: " . $result->num_rows . "</p>";
            
            echo "<div style='background:green; color:white; padding:30px; margin:20px 0; border:5px solid darkgreen;'>";
            echo "<h2>🎉 FOUND THE CORRECT USERNAME!</h2>";
            echo "<p style='font-size:24px;'><strong>Username:</strong> <code style='background:yellow; color:black; padding:10px;'>" . htmlspecialchars($username) . "</code></p>";
            echo "<p style='font-size:24px;'><strong>Password:</strong> <code style='background:yellow; color:black; padding:10px;'>" . htmlspecialchars($password) . "</code></p>";
            echo "</div>";
            
            echo "<div style='background:yellow; padding:20px; margin:20px 0;'>";
            echo "<h2>NOW UPDATE YOUR FILES:</h2>";
            echo "<ol style='font-size:16px;'>";
            echo "<li>In <strong>src/db.php</strong> line 15: <code>\$user = '" . $username . "';</code></li>";
            echo "<li>In <strong>src/db.php</strong> line 16: <code>\$password = '" . $password . "';</code></li>";
            echo "<li>In <strong>src/config.php</strong> DB_USER: <code>define('DB_USER', '" . $username . "');</code></li>";
            echo "<li>In <strong>src/config.php</strong> DB_PASS: <code>define('DB_PASS', '" . $password . "');</code></li>";
            echo "</ol>";
            echo "</div>";
            
            $conn->close();
            $found = true;
            break;
        }
    } catch (Exception $e) {
        echo "<span style='color:red;'>❌ Exception: " . $e->getMessage() . "</span></p>";
    }
}

if (!$found) {
    echo "<div style='background:red; color:white; padding:30px; margin:20px 0;'>";
    echo "<h2>❌ NO USERNAME WORKED!</h2>";
    echo "<p style='font-size:18px;'><strong>This means:</strong></p>";
    echo "<ul style='font-size:16px;'>";
    echo "<li>No database user exists for database <code>u93857826_ff</code></li>";
    echo "<li>The password <code>SkyNoxx2024</code> is not correct</li>";
    echo "<li>Database permissions not set up</li>";
    echo "</ul>";
    
    echo "<h2>YOU MUST DO THIS NOW:</h2>";
    echo "<ol style='font-size:16px;'>";
    echo "<li><strong>Go to Hostinger Control Panel</strong></li>";
    echo "<li>Navigate to: <strong>Databases → MySQL Databases</strong></li>";
    echo "<li>Find database: <code>u93857826_ff</code></li>";
    echo "<li>Look for section: <strong>\"MySQL Users\"</strong> or <strong>\"Database Users\"</strong></li>";
    echo "<li><strong>TAKE A SCREENSHOT</strong> of the entire page</li>";
    echo "<li>Share the screenshot with me</li>";
    echo "</ol>";
    
    echo "<p style='font-size:18px; margin-top:20px;'><strong>OR</strong> - Create a new user:</p>";
    echo "<ol style='font-size:16px;'>";
    echo "<li>Click <strong>\"Add New User\"</strong> or <strong>\"Create MySQL User\"</strong></li>";
    echo "<li>Username: <code>skynoxx</code> (Hostinger will prefix it automatically)</li>";
    echo "<li>Password: <code>SkyNoxx2024</code></li>";
    echo "<li>Assign user to database: <code>u93857826_ff</code></li>";
    echo "<li>Grant: <strong>ALL PRIVILEGES</strong></li>";
    echo "<li>Save and test again</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<hr>";
echo "<p style='color:red; font-weight:bold; font-size:18px;'>DELETE THIS FILE AFTER TESTING!</p>";
?>
