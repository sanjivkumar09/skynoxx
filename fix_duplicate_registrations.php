<?php
/**
 * Fix Duplicate Registrations - Run this once to clean up and add UNIQUE constraint
 */

require_once 'config.php';

echo "<h2>Fixing Duplicate Registration Issue</h2>";

// Step 1: Check for existing duplicates
echo "<h3>Step 1: Checking for duplicate registrations...</h3>";
$dupQuery = "
    SELECT tournament_id, player_id, COUNT(*) as cnt, GROUP_CONCAT(id) as ids
    FROM registrations 
    GROUP BY tournament_id, player_id 
    HAVING cnt > 1
";
$result = $conn->query($dupQuery);

if ($result && $result->num_rows > 0) {
    echo "<p style='color: orange;'>Found " . $result->num_rows . " duplicate registrations:</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Tournament ID</th><th>Player ID</th><th>Count</th><th>Registration IDs</th><th>Action</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['tournament_id'] . "</td>";
        echo "<td>" . $row['player_id'] . "</td>";
        echo "<td>" . $row['cnt'] . "</td>";
        echo "<td>" . $row['ids'] . "</td>";
        
        // Keep the first registration, delete others
        $ids = explode(',', $row['ids']);
        $keep_id = $ids[0];
        $delete_ids = array_slice($ids, 1);
        
        if (!empty($delete_ids)) {
            $delete_ids_str = implode(',', $delete_ids);
            $deleteQuery = "DELETE FROM registrations WHERE id IN ($delete_ids_str)";
            if ($conn->query($deleteQuery)) {
                echo "<td style='color: green;'>Deleted IDs: " . implode(', ', $delete_ids) . " (kept ID: $keep_id)</td>";
            } else {
                echo "<td style='color: red;'>Failed to delete: " . $conn->error . "</td>";
            }
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: green;'>✓ No duplicate registrations found!</p>";
}

// Step 2: Check if UNIQUE constraint exists
echo "<h3>Step 2: Checking UNIQUE constraint...</h3>";
$checkIndex = $conn->query("SHOW INDEX FROM registrations WHERE Key_name = 'uniq_tournament_player'");

if ($checkIndex && $checkIndex->num_rows > 0) {
    echo "<p style='color: green;'>✓ UNIQUE constraint 'uniq_tournament_player' already exists!</p>";
} else {
    echo "<p style='color: orange;'>UNIQUE constraint does not exist. Adding it now...</p>";
    
    // Add UNIQUE constraint
    $addConstraint = "ALTER TABLE registrations ADD UNIQUE KEY uniq_tournament_player (tournament_id, player_id)";
    if ($conn->query($addConstraint)) {
        echo "<p style='color: green;'>✓ Successfully added UNIQUE constraint!</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to add constraint: " . $conn->error . "</p>";
        echo "<p>This might be due to existing duplicates. Re-run this script after manually checking the data.</p>";
    }
}

// Step 3: Verify the fix
echo "<h3>Step 3: Verification</h3>";
$verifyQuery = "
    SELECT tournament_id, player_id, COUNT(*) as cnt
    FROM registrations 
    GROUP BY tournament_id, player_id 
    HAVING cnt > 1
";
$verifyResult = $conn->query($verifyQuery);

if ($verifyResult && $verifyResult->num_rows > 0) {
    echo "<p style='color: red;'>✗ Still have " . $verifyResult->num_rows . " duplicates. Manual cleanup needed.</p>";
} else {
    echo "<p style='color: green;'>✓ All duplicates resolved!</p>";
}

// Step 4: Check constraint again
$finalCheck = $conn->query("SHOW INDEX FROM registrations WHERE Key_name = 'uniq_tournament_player'");
if ($finalCheck && $finalCheck->num_rows > 0) {
    echo "<p style='color: green;'>✓ UNIQUE constraint is active!</p>";
    echo "<p><strong>Players can no longer register multiple times for the same tournament.</strong></p>";
} else {
    echo "<p style='color: orange;'>⚠ UNIQUE constraint not active. Check database permissions.</p>";
}

echo "<hr>";
echo "<h3>Summary:</h3>";
echo "<ul>";
echo "<li>Duplicate registrations have been cleaned up (keeping oldest registration)</li>";
echo "<li>UNIQUE constraint (tournament_id, player_id) has been added</li>";
echo "<li>Future duplicate registrations will be automatically blocked</li>";
echo "<li>Error messages will inform users they're already registered</li>";
echo "</ul>";

echo "<p><a href='index.php'>← Back to Home</a></p>";

$conn->close();
?>
