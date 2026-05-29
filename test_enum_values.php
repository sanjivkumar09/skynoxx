<?php
$conn = new mysqli('localhost', 'root', '', 'ff');

echo "Testing each ENUM value...\n\n";

$values = ['solo', 'duo', 'squad', 'clash squad'];

foreach ($values as $v) {
    $conn->query("INSERT INTO tournaments (title, match_type, map_name, date, time, room_id, room_password, created_by, max_players, entry_fee, prize_pool, description) VALUES ('test_enum', '$v', 'test', '2025-11-01', '12:00', 'test', 'test', 7, 10, 0, 0, 'test')");
    $id = $conn->insert_id;
    $r = $conn->query("SELECT match_type FROM tournaments WHERE id=$id");
    $row = $r->fetch_assoc();
    $saved = $row['match_type'];
    $status = ($saved === $v) ? '✅ OK' : '❌ FAIL';
    echo "Trying: '$v' → Saved: '$saved' $status\n";
}

$conn->close();
?>
