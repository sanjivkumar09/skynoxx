<?php
// Migration script to add status column to team_registrations table
// Using local XAMPP connection
$conn = new mysqli('localhost', 'root', '', 'ff', 3306);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Starting migration: Adding status column to team_registrations...\n";

try {
    // Check if team_registrations table exists
    $check_table = $conn->query("SHOW TABLES LIKE 'team_registrations'");
    
    if ($check_table && $check_table->num_rows > 0) {
        echo "team_registrations table exists.\n";
        
        // Check if status column already exists
        $check_column = $conn->query("SHOW COLUMNS FROM team_registrations LIKE 'status'");
        
        if ($check_column && $check_column->num_rows > 0) {
            echo "✓ Status column already exists. No migration needed.\n";
        } else {
            echo "Adding status column...\n";
            
            // Add status column
            $conn->query("ALTER TABLE team_registrations 
                ADD COLUMN status ENUM('accepted', 'pending', 'rejected') NOT NULL DEFAULT 'pending' AFTER position_index");
            
            echo "✓ Status column added successfully.\n";
            
            // Update existing records to 'accepted' (backward compatibility)
            $conn->query("UPDATE team_registrations SET status = 'accepted' WHERE status = 'pending'");
            
            echo "✓ Existing records updated to 'accepted' status.\n";
        }
        
        // Check if updated_at column exists
        $check_updated = $conn->query("SHOW COLUMNS FROM team_registrations LIKE 'updated_at'");
        
        if ($check_updated && $check_updated->num_rows === 0) {
            echo "Adding updated_at column...\n";
            $conn->query("ALTER TABLE team_registrations 
                ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
            echo "✓ updated_at column added successfully.\n";
        }
        
    } else {
        echo "team_registrations table does not exist. Will be created on first team registration.\n";
    }
    
    // Create team_invitations table if it doesn't exist
    echo "\nChecking team_invitations table...\n";
    
    $conn->query("CREATE TABLE IF NOT EXISTS team_invitations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tournament_id INT NOT NULL,
        invited_by INT NOT NULL,
        invited_user INT NOT NULL,
        registration_id INT NOT NULL,
        team_name VARCHAR(100),
        status ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        responded_at TIMESTAMP NULL,
        FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
        FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (invited_user) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE,
        UNIQUE KEY unique_invitation (tournament_id, invited_user, registration_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    echo "✓ team_invitations table checked/created.\n";
    
    // Add invitation_id column to notifications table if it doesn't exist
    echo "\nChecking notifications table...\n";
    
    $check_notif_col = $conn->query("SHOW COLUMNS FROM notifications LIKE 'invitation_id'");
    
    if ($check_notif_col && $check_notif_col->num_rows === 0) {
        echo "Adding invitation_id column to notifications...\n";
        $conn->query("ALTER TABLE notifications 
            ADD COLUMN invitation_id INT NULL AFTER tournament_id,
            ADD INDEX idx_invitation (invitation_id)");
        echo "✓ invitation_id column added successfully.\n";
    } else {
        echo "✓ invitation_id column already exists.\n";
    }
    
    echo "\n✅ Migration completed successfully!\n";
    echo "\nYou can now use the team invitation system with accept/reject functionality.\n";
    
} catch (Exception $e) {
    echo "❌ Error during migration: " . $e->getMessage() . "\n";
    exit(1);
}
