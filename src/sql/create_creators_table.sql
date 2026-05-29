-- Create creators table for storing additional creator information
CREATE TABLE IF NOT EXISTS creators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    mobile_no VARCHAR(15) NOT NULL,
    email VARCHAR(100) NOT NULL,
    game_uid VARCHAR(50),
    yt_channel_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add index for faster lookups
CREATE INDEX idx_user_id ON creators(user_id);
CREATE INDEX idx_email ON creators(email);
CREATE INDEX idx_game_uid ON creators(game_uid);
