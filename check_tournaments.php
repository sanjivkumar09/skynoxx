<?php
// Check what's in the tournaments table

$conn = new mysqli('localhost', 'root', '', 'ff', 3306);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Checking tournaments table for match_type values...\n\n";

$result = $conn->query("SELECT id, title, match_type, created_at FROM tournaments ORDER BY id DESC LIMIT 10");

if ($result) {
    echo "Last 10 tournaments:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-5s | %-20s | %-15s | %s\n", "ID", "Title", "Match Type", "Created At");
    echo str_repeat("-", 80) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        $match_display = $row['match_type'] === '' ? '(EMPTY STRING)' : $row['match_type'];
        $match_display = $row['match_type'] === null ? '(NULL)' : $match_display;
        printf("%-5s | %-20s | %-15s | %s\n", 
            $row['id'], 
            substr($row['title'], 0, 20), 
            $match_display,
            $row['created_at']
        );
    }
    echo str_repeat("-", 80) . "\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
?>
