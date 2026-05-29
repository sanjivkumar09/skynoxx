-- Enhanced Features Migration SQL
-- Live Tournament Status & Enhanced Notifications

-- Add max_participants to tournaments table
ALTER TABLE tournaments 
ADD COLUMN IF NOT EXISTS max_participants INT DEFAULT NULL COMMENT 'Maximum number of players allowed',
ADD COLUMN IF NOT EXISTS current_participants INT DEFAULT 0 COMMENT 'Current number of registered players',
ADD COLUMN IF NOT EXISTS auto_start TINYINT(1) DEFAULT 0 COMMENT 'Auto start when capacity reached',
ADD COLUMN IF NOT EXISTS reminder_sent TINYINT(1) DEFAULT 0 COMMENT 'Whether 1-hour reminder was sent';

-- Create tournament_status_log table for tracking status changes
CREATE TABLE IF NOT EXISTS tournament_status_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tournament_id INT NOT NULL,
    old_status ENUM('upcoming','ongoing','completed','cancelled') DEFAULT NULL,
    new_status ENUM('upcoming','ongoing','completed','cancelled') NOT NULL,
    changed_by INT DEFAULT NULL COMMENT 'User ID who made the change',
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reason VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_tournament_status (tbournament_id, changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Expand notification types
ALTER TABLE notifications 
MODIFY COLUMN type ENUM(
    'tournament_created',
    'tournament_starting_soon',
    'tournament_started',
    'tournament_completed',
    'tournament_cancelled',
    'prize_credited',
    'withdrawal_approved',
    'withdrawal_rejected',
    'low_balance',
    'payment_received',
    'player_joined',
    'system_announcement'
) NOT NULL DEFAULT 'tournament_created';

-- Add notification preferences table
CREATE TABLE IF NOT EXISTS notification_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    tournament_starting_soon TINYINT(1) DEFAULT 1,
    tournament_results TINYINT(1) DEFAULT 1,
    prize_credited TINYINT(1) DEFAULT 1,
    withdrawal_updates TINYINT(1) DEFAULT 1,
    low_balance_alert TINYINT(1) DEFAULT 1,
    payment_updates TINYINT(1) DEFAULT 1,
    email_notifications TINYINT(1) DEFAULT 1,
    push_notifications TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_prefs (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add push notification tokens table for mobile app
CREATE TABLE IF NOT EXISTS push_notification_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(512) NOT NULL COMMENT 'FCM or device token',
    device_type ENUM('android','ios','web') NOT NULL DEFAULT 'web',
    device_info TEXT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_device (user_id, token(255)),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_active_tokens (is_active, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add metadata to wallet_transactions for better tracking
ALTER TABLE wallet_transactions 
ADD COLUMN IF NOT EXISTS metadata JSON DEFAULT NULL COMMENT 'Additional transaction data',
ADD COLUMN IF NOT EXISTS notification_sent TINYINT(1) DEFAULT 0 COMMENT 'Whether notification was sent';

-- Create tournament results table
CREATE TABLE IF NOT EXISTS tournament_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tournament_id INT NOT NULL,
    player_id INT NOT NULL,
    position INT NOT NULL COMMENT 'Final position/rank',
    prize_amount DECIMAL(10,2) DEFAULT 0.00,
    prize_distributed TINYINT(1) DEFAULT 0,
    kills INT DEFAULT 0,
    points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_tournament_player (tournament_id, player_id),
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_tournament_position (tournament_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default notification preferences for existing users
INSERT IGNORE INTO notification_preferences (user_id)
SELECT id FROM users;
