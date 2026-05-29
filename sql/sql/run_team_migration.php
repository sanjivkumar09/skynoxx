<?php
/**
 * Team Registration System Migration
 * Run this file once to set up all tables
 */

include __DIR__ . '/../src/db.php';

echo "Starting Team Registration Migration...\n\n";

// 1. Create team_registrations table
echo "1. Creating team_registrations table...\n";
$sql1 = "CREATE TABLE IF NOT EXISTS team_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('captain', 'member') NOT NULL DEFAULT 'member',
    position_index TINYINT NOT NULL DEFAULT 1,
    invited_by INT NULL,
    invitation_status ENUM('pending', 'accepted', 'declined') DEFAULT 'accepted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_registration_user (registration_id, user_id),
    INDEX idx_registration (registration_id),
    INDEX idx_user (user_id),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql1)) {
    echo "   ✓ team_registrations table created\n\n";
} else {
    echo "   ✗ Error: " . $conn->error . "\n\n";
}

// 2. Add team_name column to registrations
echo "2. Adding team_name column to registrations...\n";
$check_col = $conn->query("SHOW COLUMNS FROM registrations LIKE 'team_name'");
if ($check_col->num_rows == 0) {
    $sql2 = "ALTER TABLE registrations ADD COLUMN team_name VARCHAR(100) NULL AFTER slot_no";
    if ($conn->query($sql2)) {
        echo "   ✓ team_name column added\n\n";
    } else {
        echo "   ✗ Error: " . $conn->error . "\n\n";
    }
} else {
    echo "   ✓ team_name column already exists\n\n";
}

// 3. Add profile_verified flag to users
echo "3. Adding profile_verified column to users...\n";
$check_pv = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_verified'");
if ($check_pv->num_rows == 0) {
    $sql3 = "ALTER TABLE users ADD COLUMN profile_verified TINYINT(1) DEFAULT 0 AFTER wallet_balance";
    if ($conn->query($sql3)) {
        echo "   ✓ profile_verified column added\n\n";
    } else {
        echo "   ✗ Error: " . $conn->error . "\n\n";
    }
} else {
    echo "   ✓ profile_verified column already exists\n\n";
}

// 4. Add indexes to players_profile
echo "4. Adding indexes to players_profile...\n";
$indexes = [
    "ALTER TABLE players_profile ADD INDEX idx_game_uid (game_uid)",
    "ALTER TABLE players_profile ADD INDEX idx_in_game_name (in_game_name)"
];

foreach ($indexes as $idx_sql) {
    $conn->query($idx_sql); // Ignore errors if index exists
}
echo "   ✓ Indexes added/checked\n\n";

// 5. Update profile_verified for existing users
echo "5. Updating profile_verified for existing users...\n";
$sql5 = "UPDATE users u
         SET profile_verified = 1
         WHERE EXISTS (
             SELECT 1 FROM players_profile pp
             WHERE pp.user_id = u.id
             AND pp.game_uid IS NOT NULL 
             AND pp.game_uid != ''
             AND pp.in_game_name IS NOT NULL
             AND pp.in_game_name != ''
         )";
         
if ($conn->query($sql5)) {
    $affected = $conn->affected_rows;
    echo "   ✓ Updated $affected users with profile_verified flag\n\n";
} else {
    echo "   ✗ Error: " . $conn->error . "\n\n";
}

echo "==============================================\n";
echo "✓ Migration completed successfully!\n";
echo "==============================================\n\n";
echo "Next steps:\n";
echo "1. Test the team builder on join tournament page\n";
echo "2. Check team registration in creator view\n";
echo "3. Review analytics dashboard\n\n";

$conn->close();
