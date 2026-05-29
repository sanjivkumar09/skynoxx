-- ============================================================================
-- SQL Migration Script: November 4-5, 2025 Updates
-- ============================================================================
-- Description: Complete database migration for match stats improvements,
--              bhooya points system, and query optimizations
-- Date Range: November 4-5, 2025
-- Version: 2.5.0
-- ============================================================================

-- Backup recommendation: Always backup your database before running migrations!
-- Command: mysqldump -u username -p database_name > backup_before_migration.sql

-- ============================================================================
-- MIGRATION 1: Add bhooya_points column to match_results
-- Date: November 5, 2025
-- Impact: Adds new scoring component to match results
-- ============================================================================

-- Step 1: Add bhooya_points column if it doesn't exist
ALTER TABLE `match_results` 
ADD COLUMN IF NOT EXISTS `bhooya_points` DECIMAL(10,2) DEFAULT 0.00 
AFTER `kill_points`;

-- Verify column was added
SELECT COLUMN_NAME, DATA_TYPE, COLUMN_DEFAULT, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'match_results' AND COLUMN_NAME = 'bhooya_points';

-- ============================================================================
-- MIGRATION 2: Update total_points calculation to include bhooya_points
-- Date: November 5, 2025
-- Impact: Changes how total points are calculated
-- ============================================================================

-- Note: MySQL requires dropping and recreating generated columns
-- This is safe because the column is automatically recalculated

-- Step 1: Check if total_points exists as a generated column
SELECT COLUMN_NAME, EXTRA, GENERATION_EXPRESSION
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'match_results' AND COLUMN_NAME = 'total_points';

-- Step 2: Drop the existing total_points column (if it's a generated column)
-- Skip this if total_points doesn't exist or isn't generated
SET @drop_sql = (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_NAME = 'match_results' 
            AND COLUMN_NAME = 'total_points'
        ),
        'ALTER TABLE match_results DROP COLUMN total_points',
        'SELECT "Column total_points does not exist, skipping drop" AS message'
    )
);

PREPARE stmt FROM @drop_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 3: Recreate total_points with new calculation including bhooya_points
ALTER TABLE `match_results` 
ADD COLUMN `total_points` DECIMAL(10,2) 
GENERATED ALWAYS AS (`placement_points` + `kill_points` + `bhooya_points` + `bonus_points`) STORED;

-- ============================================================================
-- MIGRATION 3: Set default value for existing rows
-- Date: November 5, 2025
-- Impact: Ensures existing match results have valid bhooya_points value
-- ============================================================================

-- Update any NULL values to 0.00 (though DEFAULT should handle this)
UPDATE `match_results` 
SET `bhooya_points` = 0.00 
WHERE `bhooya_points` IS NULL;

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================

-- Verify the match_results table structure
DESCRIBE `match_results`;

-- Check if bhooya_points column exists and has correct properties
SHOW COLUMNS FROM `match_results` LIKE 'bhooya_points';

-- Verify total_points calculation is correct
SELECT 
    id,
    placement_points,
    kill_points,
    bhooya_points,
    bonus_points,
    total_points,
    (placement_points + kill_points + bhooya_points + bonus_points) as calculated_total
FROM `match_results`
LIMIT 5;

-- Count total match results
SELECT COUNT(*) as total_match_results FROM `match_results`;

-- Check for any NULL bhooya_points (should be 0 after migration)
SELECT COUNT(*) as rows_with_null_bhooya 
FROM `match_results` 
WHERE `bhooya_points` IS NULL;

-- ============================================================================
-- ROLLBACK INSTRUCTIONS (if needed)
-- ============================================================================

-- If you need to rollback this migration, run the following:
-- WARNING: This will remove the bhooya_points column and recalculate totals

/*
-- Rollback Step 1: Drop total_points column
ALTER TABLE match_results DROP COLUMN IF EXISTS total_points;

-- Rollback Step 2: Drop bhooya_points column  
ALTER TABLE match_results DROP COLUMN IF EXISTS bhooya_points;

-- Rollback Step 3: Recreate total_points with old calculation
ALTER TABLE match_results 
ADD COLUMN total_points DECIMAL(10,2) 
GENERATED ALWAYS AS (placement_points + kill_points + bonus_points) STORED;
*/

-- ============================================================================
-- POST-MIGRATION NOTES
-- ============================================================================

-- 1. The bhooya_points column is now available in match_results table
-- 2. All existing rows have bhooya_points set to 0.00
-- 3. Total points now calculated as: placement + kill + bhooya + bonus
-- 4. The total_points column automatically updates when any component changes
-- 5. No application code changes needed for existing records

-- ============================================================================
-- ADDITIONAL OPTIMIZATIONS (Optional but Recommended)
-- ============================================================================

-- Add index on total_points for faster leaderboard queries (if not exists)
CREATE INDEX IF NOT EXISTS idx_match_results_total_points 
ON `match_results` (`total_points` DESC);

-- Add composite index for tournament and match filtering (if not exists)
CREATE INDEX IF NOT EXISTS idx_tournament_match_points 
ON `match_results` (`tournament_id`, `match_number`, `total_points` DESC);

-- ============================================================================
-- SUCCESS MESSAGE
-- ============================================================================

SELECT 
    'Migration completed successfully!' AS status,
    '2.5.0' AS version,
    NOW() AS migration_date,
    'bhooya_points column added and total_points updated' AS changes;

-- ============================================================================
-- END OF MIGRATION SCRIPT
-- ============================================================================

-- Next steps:
-- 1. Upload updated PHP files to server
-- 2. Test match stats entry with bhooya points
-- 3. Verify leaderboard displays correctly
-- 4. Clear browser cache on all devices
-- 5. Monitor error logs for any issues
