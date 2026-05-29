<?php
$conn = new mysqli('localhost', 'root', '', 'ff');

echo "Testing match_type insertion with prepared statement...\n\n";

// Test different type specs for match_type
$tests = [
    ['type' => 's', 'desc' => 'match_type as string'],
    ['type' => 'i', 'desc' => 'match_type as integer (wrong but testing)'],
];

foreach ($tests as $test) {
    echo "Test: {$test['desc']}\n";
    
    $types = "ssddi" . $test['type'] . "sssssi";
    echo "Type string: $types\n";
    
    $stmt = $conn->prepare("INSERT INTO tournaments (title, description, entry_fee, prize_pool, max_players, match_type, map_name, date, time, room_id, room_password, created_by, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'upcoming', NOW())");

    $title = "TEST DUO";
    $desc = "Testing duo";
    $fee = 0.0;
    $pool = 0.0;
    $max = 48;
    $match = "duo";  // lowercase duo
    $map = "Test";
    $date = "2025-11-01";
    $time = "12:00";
    $room = "test" . uniqid();
    $pass = "test";
    $creator = 7;

    $stmt->bind_param($types, $title, $desc, $fee, $pool, $max, $match, $map, $date, $time, $room, $pass, $creator);

    if ($stmt->execute()) {
        $id = $stmt->insert_id;
        echo "   ✅ Successfully inserted tournament ID: $id\n";
        
        // Check what was saved
        $check = $conn->query("SELECT id, title, match_type FROM tournaments WHERE id = $id");
        if ($check && $row = $check->fetch_assoc()) {
            echo "   Saved match_type: '{$row['match_type']}'\n";
            if ($row['match_type'] === 'duo') {
                echo "   ✅ Correctly saved as 'duo'!\n";
            } else if ($row['match_type'] === '') {
                echo "   ❌ ERROR: Saved as empty string!\n";
            } else {
                echo "   ⚠️  Saved as: '{$row['match_type']}'\n";
            }
        }
    } else {
        echo "   ❌ Error: " . $stmt->error . "\n";
    }
    echo "\n";
}

$conn->close();
?>
