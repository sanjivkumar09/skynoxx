<?php
// Run this file to fix the match_type ENUM column

// Connect directly to localhost XAMPP
$conn = new mysqli('localhost', 'root', '', 'ff', 3306);

echo "Running database migration to fix match_type ENUM...\n\n";

// First, let's check current ENUM values
$check = $conn->query("SHOW COLUMNS FROM tournaments LIKE 'match_type'");
if ($check) {
    $row = $check->fetch_assoc();
    echo "Current match_type definition:\n";
    echo "Type: " . $row['Type'] . "\n\n";
}

// Run the ALTER TABLE command
$sql = "ALTER TABLE tournaments MODIFY COLUMN match_type ENUM('solo','duo','squad','clash squad') DEFAULT 'squad'";

if ($conn->query($sql)) {
    echo "✅ SUCCESS! match_type ENUM has been updated!\n\n";
    
    // Verify the change
    $verify = $conn->query("SHOW COLUMNS FROM tournaments LIKE 'match_type'");
    if ($verify) {
        $row = $verify->fetch_assoc();
        echo "New match_type definition:\n";
        echo "Type: " . $row['Type'] . "\n\n";
    }
    
    echo "Now you can create tournaments with:\n";
    echo "- solo\n";
    echo "- duo\n";
    echo "- squad\n";
    echo "- clash squad\n";
} else {
    echo "❌ ERROR: " . $conn->error . "\n";
}

$conn->close();
?>
