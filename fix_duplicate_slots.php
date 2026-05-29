<?php
// Maintenance script to fix duplicate slots per tournament
require_once __DIR__ . '/src/db.php';

echo "<h2>Fix Duplicate Slots</h2>";

// First, show current duplicates
echo "<h3>Current Duplicate Slots:</h3>";
$dupQuery = "SELECT tournament_id, slot_no, GROUP_CONCAT(id ORDER BY joined_at) as reg_ids, COUNT(*) as cnt 
             FROM registrations 
             WHERE payment_status IN ('success','paid','') AND slot_no > 0
             GROUP BY tournament_id, slot_no 
             HAVING cnt > 1";
$dupResult = $conn->query($dupQuery);
if ($dupResult && $dupResult->num_rows > 0) {
    echo "<table border='1' cellpadding='5'><tr><th>Tournament ID</th><th>Slot #</th><th>Count</th><th>Registration IDs</th></tr>";
    while ($dup = $dupResult->fetch_assoc()) {
        echo "<tr><td>{$dup['tournament_id']}</td><td>{$dup['slot_no']}</td><td>{$dup['cnt']}</td><td>{$dup['reg_ids']}</td></tr>";
    }
    echo "</table><br>";
} else {
    echo "<p style='color:green'>No duplicate slots found!</p>";
}

// Build an index of used slots per tournament (include all statuses to avoid conflicts)
$q = $conn->query("SELECT id, tournament_id, player_id, slot_no, payment_status, joined_at FROM registrations ORDER BY tournament_id, slot_no, joined_at");

$byT = [];
while ($row = $q->fetch_assoc()) {
    $tid = (int)$row['tournament_id'];
    $byT[$tid]['rows'][] = $row;
}

$fixed = 0; $checked = 0; $errors = 0;
echo "<h3>Processing Registrations:</h3>";
foreach ($byT as $tid => $data) {
    // Fetch max players for tournament
    $maxRes = $conn->query("SELECT max_players FROM tournaments WHERE id = $tid");
    $maxPlayers = ($maxRes && $maxRes->num_rows) ? (int)$maxRes->fetch_assoc()['max_players'] : 100; // Default to 100 if not set
    $used = [];
    
    foreach ($data['rows'] as $row) {
        $checked++;
        $sid = (int)$row['slot_no'];
        $rid = (int)$row['id'];
        
        // If slot is 0 or duplicate, reassign
        if ($sid <= 0 || isset($used[$sid])) {
            // Find next available slot
            $new = 0;
            for ($i = 1; $i <= $maxPlayers; $i++) { 
                if (empty($used[$i])) { 
                    $new = $i; 
                    break; 
                } 
            }
            if ($new === 0) { 
                echo "<p style='color:red'>Tournament $tid: No available slots for registration $rid</p>";
                $errors++; 
                continue; 
            }
            
            echo "<p>Tournament $tid: Reassigning registration $rid from slot $sid to slot $new</p>";
            if ($conn->query("UPDATE registrations SET slot_no = $new WHERE id = $rid")) {
                $fixed++;
                $sid = $new;
            } else {
                echo "<p style='color:red'>Failed to update registration $rid: " . $conn->error . "</p>";
                $errors++;
            }
        }
        $used[$sid] = true;
    }
}

echo "<h3>Summary:</h3>";
echo "<p>Total registrations checked: $checked</p>";
echo "<p style='color:" . ($fixed > 0 ? "green" : "gray") . "'>Reassigned slots: $fixed</p>";
echo "<p style='color:" . ($errors > 0 ? "red" : "green") . "'>Errors: $errors</p>";

// Check for remaining duplicates
echo "<h3>Verification:</h3>";
$dupCheck = $conn->query("SELECT tournament_id, slot_no, COUNT(*) c FROM registrations WHERE slot_no>0 GROUP BY tournament_id, slot_no HAVING c>1");
if ($dupCheck && $dupCheck->num_rows > 0) {
    echo "<p style='color:red'>⚠ Still have " . $dupCheck->num_rows . " duplicate slots remaining!</p>";
    echo "<p>Please refresh this page to run the fix again.</p>";
} else {
    echo "<p style='color:green'>✓ No duplicate slots remaining!</p>";
    
    // Try to add unique index
    $idx = $conn->query("SHOW INDEX FROM registrations WHERE Key_name='uniq_tournament_slot'");
    if ($idx && $idx->num_rows === 0) {
        echo "<p>Adding UNIQUE index to prevent future duplicates...</p>";
        if ($conn->query("ALTER TABLE registrations ADD UNIQUE KEY uniq_tournament_slot (tournament_id, slot_no)")) {
            echo "<p style='color:green'>✓ Successfully added UNIQUE index uniq_tournament_slot!</p>";
            echo "<p><strong>Players can no longer get duplicate slots.</strong></p>";
        } else {
            echo "<p style='color:red'>✗ Failed to add UNIQUE index: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color:green'>✓ UNIQUE index uniq_tournament_slot already exists!</p>";
    }
}

echo "<hr><p><a href='creator/creator_dashboard.php'>← Back to Dashboard</a></p>";

?>