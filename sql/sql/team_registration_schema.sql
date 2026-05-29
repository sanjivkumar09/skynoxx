-- Team Registration System Schema
-- This enables profile-based team registration for Duo/Squad tournaments

-- Table: team_registrations
-- Links a tournament registration to verified user profiles for team members
CREATE TABLE IF NOT EXISTS team_registrations (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add team_name to registrations table for better identification
ALTER TABLE registrations 
ADD COLUMN IF NOT EXISTS team_name VARCHAR(100) NULL AFTER slot_no,
ADD INDEX idx_team_name (team_name);

-- Add profile_verified flag to users table for quick filtering
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS profile_verified TINYINT(1) DEFAULT 0 AFTER wallet_balance;

-- Create index on players_profile for faster searches
ALTER TABLE players_profile
ADD INDEX IF NOT EXISTS idx_game_uid (game_uid),
ADD INDEX IF NOT EXISTS idx_in_game_name (in_game_name);

-- View: Complete team roster with all member details
CREATE OR REPLACE VIEW team_roster_view AS
SELECT 
    tr.registration_id,
    tr.user_id,
    tr.role,
    tr.position_index,
    u.name AS user_name,
    u.email,
    u.profile_verified,
    pp.in_game_name,
    pp.game_uid,
    pp.avatar,
    r.tournament_id,
    r.slot_no,
    r.team_name,
    r.payment_status,
    r.joined_at,
    t.title AS tournament_title,
    t.match_type,
    t.entry_fee
FROM team_registrations tr
JOIN users u ON tr.user_id = u.id
LEFT JOIN players_profile pp ON tr.user_id = pp.user_id
JOIN registrations r ON tr.registration_id = r.id
JOIN tournaments t ON r.tournament_id = t.id
ORDER BY tr.registration_id, tr.role DESC, tr.position_index;

-- Stored Procedure: Validate team profile completeness
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_validate_team_profiles(
    IN p_registration_id INT,
    OUT p_valid BOOLEAN,
    OUT p_message VARCHAR(500)
)
BEGIN
    DECLARE incomplete_count INT DEFAULT 0;
    DECLARE incomplete_names TEXT DEFAULT '';
    
    -- Check for incomplete profiles
    SELECT 
        COUNT(*),
        GROUP_CONCAT(u.name SEPARATOR ', ')
    INTO incomplete_count, incomplete_names
    FROM team_registrations tr
    JOIN users u ON tr.user_id = u.id
    LEFT JOIN players_profile pp ON tr.user_id = pp.user_id
    WHERE tr.registration_id = p_registration_id
    AND (pp.game_uid IS NULL OR pp.game_uid = '' 
         OR pp.in_game_name IS NULL OR pp.in_game_name = '');
    
    IF incomplete_count > 0 THEN
        SET p_valid = FALSE;
        SET p_message = CONCAT('Incomplete profiles for: ', incomplete_names);
    ELSE
        SET p_valid = TRUE;
        SET p_message = 'All team members have complete profiles';
    END IF;
END //
DELIMITER ;

-- Function: Get team size for a registration
DELIMITER //
CREATE FUNCTION IF NOT EXISTS fn_get_team_size(p_registration_id INT)
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE team_size INT DEFAULT 0;
    
    SELECT COUNT(*) INTO team_size
    FROM team_registrations
    WHERE registration_id = p_registration_id;
    
    RETURN team_size;
END //
DELIMITER ;

-- Update profile_verified flag for existing users with complete profiles
UPDATE users u
SET profile_verified = 1
WHERE EXISTS (
    SELECT 1 FROM players_profile pp
    WHERE pp.user_id = u.id
    AND pp.game_uid IS NOT NULL 
    AND pp.game_uid != ''
    AND pp.in_game_name IS NOT NULL
    AND pp.in_game_name != ''
);
