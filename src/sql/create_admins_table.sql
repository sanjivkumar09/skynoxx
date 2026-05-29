-- Create admins table for storing admin credentials
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    admin_name VARCHAR(100) NOT NULL,
    admin_email VARCHAR(100) NOT NULL UNIQUE,
    admin_password VARCHAR(255) NOT NULL,
    mobile_no VARCHAR(15),
    access_level ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add indexes for faster lookups
CREATE INDEX idx_admin_email ON admins(admin_email);
CREATE INDEX idx_admin_user_id ON admins(user_id);
CREATE INDEX idx_admin_is_active ON admins(is_active);

-- Insert default admin from existing users table
INSERT INTO admins (user_id, admin_name, admin_email, admin_password, mobile_no, access_level)
SELECT id, name, email, password, phone, 'super_admin'
FROM users 
WHERE email = 'admin@freefire.com' AND role = 'admin'
ON DUPLICATE KEY UPDATE admin_email = admin_email;
