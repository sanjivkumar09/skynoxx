-- Add FCM token columns to users table
ALTER TABLE users 
ADD COLUMN fcm_token VARCHAR(255) NULL,
ADD COLUMN fcm_token_updated_at DATETIME NULL;
