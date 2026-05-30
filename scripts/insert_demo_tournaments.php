<?php
require_once __DIR__ . '/../src/db.php';

// Disable error reporting during mass insert for cleaner CLI output
mysqli_report(MYSQLI_REPORT_OFF);

// Ensure the Test Creator (user_id = 7) exists
$creator_check = $conn->query("SELECT id FROM users WHERE id = 7");
if ($creator_check->num_rows === 0) {
    die("Error: Creator with user ID 7 (Test Creator) does not exist in the users table. Please check users database first.\n");
}

echo "Generating 50 Free Fire demo tournaments...\n";

$titles_pool = [
    "Skynoxx Free Fire Pro League",
    "Bermuda Clash Squad Showdown",
    "Kalahari Survival Cup",
    "Purgatory Elite Solo Arena",
    "Alpine Duo Championship",
    "Nexterra Squad Rush Hour",
    "Grandmaster Booyah Cup",
    "Rampage Weekly Showdown",
    "Headshot Masterclass Solo",
    "Survival Battle Royale Pro",
    "Bermuda Clash Squad Underdogs",
    "Skynoxx Weekend Warzone",
    "Free Fire Elite Series - S1",
    "Booyah Championship League",
    "Midnight Madness Squad Cup"
];

$descriptions_pool = [
    "Prove your survival skills in the most competitive Battle Royale arena. Outlast all opponents and claim the crown of the ultimate survivalist!",
    "Gather your squad, call out your tactics, and dominate in the fast-paced Clash Squad tournament. Only the best team coordination will win.",
    "A test of precision, aiming, and positioning. Face off against the best solo players in a fight for the ultimate Booyah!",
    "Team up with your duo partner, share resources, watch each other's backs, and secure the top spot in this high-stakes Duo cup.",
    "Compete in the premium Skynoxx tournament. Huge prize pool, top-tier competitors, and full live-cast match rooms!"
];

$maps_pool = ["Bermuda", "Kalahari", "Purgatory", "Alpine", "Nexterra"];
$match_types_pool = ["solo", "duo", "squad"];
$statuses_pool = ["upcoming", "upcoming", "upcoming", "ongoing", "completed", "completed"];

$inserted_count = 0;

for ($i = 1; $i <= 50; $i++) {
    $title = $titles_pool[array_rand($titles_pool)] . " [Season $i]";
    $description = $descriptions_pool[array_rand($descriptions_pool)] . " Free Fire tournament rules and guidelines apply. Lobby room details will be shared 15 minutes before the start time.";
    
    // Realistic financial stats
    $is_free = (rand(1, 10) <= 3); // 30% chance of free entry
    $entry_fee = $is_free ? 0.00 : rand(10, 200);
    $prize_pool = $is_free ? rand(500, 2000) : ($entry_fee * rand(30, 48));
    
    $match_type = $match_types_pool[array_rand($match_types_pool)];
    $map_name = $maps_pool[array_rand($maps_pool)];
    
    // Generate dates: 15 past days, today (ongoing), 34 upcoming days
    $day_offset = rand(-15, 30);
    $date = date('Y-m-d', strtotime("$day_offset days"));
    
    $time = sprintf("%02d:%02d:00", rand(10, 22), rand(0, 3) * 15);
    
    // Status corresponding to date
    if ($day_offset < 0) {
        $status = "completed";
    } elseif ($day_offset == 0) {
        $status = (rand(1, 2) == 1) ? "ongoing" : "upcoming";
    } else {
        $status = "upcoming";
    }
    
    // Max players based on type
    if ($match_type === "solo") {
        $max_players = 48;
    } elseif ($match_type === "duo") {
        $max_players = 48; // 24 teams
    } else {
        $max_players = 48; // 12 teams
    }
    
    $room_id = ($status === "completed" || $status === "ongoing" || rand(1, 2) == 1) ? "ROOM_" . rand(100000, 999999) : "";
    $room_password = ($room_id !== "") ? "pass" . rand(100, 999) : "";
    
    $created_by = 7; // Test Creator
    
    $banner = "uploads/tournaments/default_banner.jpg";

    $stmt = $conn->prepare("INSERT INTO tournaments 
        (title, description, entry_fee, prize_pool, max_players, match_type, map_name, date, time, room_id, room_password, created_by, status, banner) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
    if ($stmt) {
        $stmt->bind_param("ssddissssssiss", 
            $title, $description, $entry_fee, $prize_pool, $max_players, $match_type, $map_name, $date, $time, $room_id, $room_password, $created_by, $status, $banner
        );
        
        if ($stmt->execute()) {
            $inserted_count++;
        } else {
            echo "Failed to insert tournament $i: " . $stmt->error . "\n";
        }
        $stmt->close();
    } else {
        echo "Failed to prepare tournament statement: " . $conn->error . "\n";
    }
}

echo "Successfully inserted $inserted_count tournaments into the database!\n";
?>
