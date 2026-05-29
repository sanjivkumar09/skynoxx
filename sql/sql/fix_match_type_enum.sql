-- Add Clash Squad to match_type ENUM
-- Run this SQL in phpMyAdmin or MySQL command line

-- Option 1: Modify ENUM to add 'clash squad' (RECOMMENDED)
ALTER TABLE `tournaments` 
MODIFY COLUMN `match_type` ENUM('solo','duo','squad','clash squad') DEFAULT 'squad';

-- If you want to update existing empty match_type values to 'squad':
UPDATE `tournaments` SET `match_type` = 'squad' WHERE `match_type` = '' OR `match_type` IS NULL;
