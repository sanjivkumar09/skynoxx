-- Add is_active column to users table for blocking/unblocking accounts
-- Run this SQL in phpMyAdmin on your Hostinger database

ALTER TABLE `users` ADD COLUMN `is_active` TINYINT(1) DEFAULT 1 AFTER `profile_verified`;

-- Update existing users to be active by default
UPDATE `users` SET `is_active` = 1 WHERE `is_active` IS NULL;
