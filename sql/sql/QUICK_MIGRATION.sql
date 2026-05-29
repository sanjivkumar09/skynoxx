-- ============================================================================
-- QUICK MIGRATION SCRIPT - November 4-5, 2025
-- ============================================================================
-- Run this in phpMyAdmin or MySQL command line
-- Estimated time: < 1 minute
-- ============================================================================

-- BACKUP YOUR DATABASE FIRST!

-- Add bhooya_points column
ALTER TABLE `match_results` 
ADD COLUMN IF NOT EXISTS `bhooya_points` DECIMAL(10,2) DEFAULT 0.00 
AFTER `kill_points`;

-- Drop old total_points column
ALTER TABLE `match_results` 
DROP COLUMN IF EXISTS `total_points`;

-- Recreate total_points with new calculation
ALTER TABLE `match_results` 
ADD COLUMN `total_points` DECIMAL(10,2) 
GENERATED ALWAYS AS (`placement_points` + `kill_points` + `bhooya_points` + `bonus_points`) STORED;

-- Set default values for existing records
UPDATE `match_results` 
SET `bhooya_points` = 0.00 
WHERE `bhooya_points` IS NULL;

-- Add indexes for performance (optional but recommended)
CREATE INDEX IF NOT EXISTS `idx_match_results_total_points` 
ON `match_results` (`total_points` DESC);

-- Verify migration
SELECT 'Migration Complete!' AS Status,
       COUNT(*) AS Total_Records,
       SUM(CASE WHEN bhooya_points IS NOT NULL THEN 1 ELSE 0 END) AS Records_With_Bhooya
FROM `match_results`;

-- Done! Now upload the updated PHP files.
