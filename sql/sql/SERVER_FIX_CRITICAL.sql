-- CRITICAL SERVER DATABASE FIX
-- Run this IMMEDIATELY on your live server database
-- Date: November 5, 2025
-- Issue: payment_status ENUM mismatch + missing tournament_leaderboard view

-- ========================================
-- STEP 1: Fix payment_status column type
-- ========================================
-- Current: enum('pending','paid','free')
-- Problem: PHP code uses 'success' and '' (empty string) which aren't in ENUM
-- Solution: Change to VARCHAR to allow all values

ALTER TABLE registrations 
MODIFY COLUMN payment_status VARCHAR(50) DEFAULT NULL;

-- Update existing 'free' values to empty string for consistency
UPDATE registrations 
SET payment_status = '' 
WHERE payment_status = 'free';

-- Verify the change
SELECT DISTINCT payment_status, COUNT(*) as count
FROM registrations 
GROUP BY payment_status;

-- ========================================
-- STEP 2: Create missing tournament_leaderboard view
-- ========================================
-- This view is used by leaderboard pages
-- Without it, player/creator leaderboard pages will fail

CREATE OR REPLACE VIEW tournament_leaderboard AS
SELECT 
  t.id AS tournament_id,
  t.title AS tournament_title,
  t.match_type,
  r.id AS registration_id,
  r.player_id,
  u.name AS player_name,
  COALESCE(r.team_name, 'Solo Player') AS team_name,
  
  -- Aggregate stats across all matches
  COUNT(DISTINCT mr.match_number) AS matches_played,
  SUM(mr.kills) AS total_kills,
  SUM(mr.damage) AS total_damage,
  AVG(mr.placement) AS avg_placement,
  SUM(mr.total_points) AS cumulative_points,
  
  -- Best performance
  MIN(mr.placement) AS best_placement,
  MAX(mr.kills) AS max_kills_single_match
  
FROM tournaments t
JOIN registrations r ON t.id = r.tournament_id
JOIN users u ON r.player_id = u.id
LEFT JOIN match_results mr ON r.id = mr.registration_id

WHERE (r.payment_status IN ('success', 'paid', 'free') 
       OR r.payment_status = '' 
       OR r.payment_status IS NULL)
GROUP BY t.id, r.id, r.player_id, u.name, r.team_name
ORDER BY cumulative_points DESC, total_kills DESC;

-- Verify the view works
SELECT * FROM tournament_leaderboard LIMIT 5;

-- ========================================
-- VERIFICATION CHECKS
-- ========================================

-- 1. Check match_results has bhooya_points
SHOW COLUMNS FROM match_results LIKE 'bhooya_points';

-- 2. Check total_points is GENERATED column
SHOW CREATE TABLE match_results;

-- 3. Check tournament_leaderboard view exists
SHOW CREATE VIEW tournament_leaderboard;

-- 4. Check payment_status is now VARCHAR
SHOW COLUMNS FROM registrations LIKE 'payment_status';

-- 5. Count registrations by payment status
SELECT 
    payment_status,
    COUNT(*) as count,
    GROUP_CONCAT(DISTINCT tournament_id) as tournament_ids
FROM registrations
GROUP BY payment_status;

-- ========================================
-- STEP 3: Fix duplicate registration error
-- ========================================
-- Error: "Duplicate entry '2-1' for key 'uniq_tournament_slot'"
-- Cause: UNIQUE constraint prevents players from re-joining tournaments

-- Check existing constraints first
SELECT 
    CONSTRAINT_NAME, 
    CONSTRAINT_TYPE
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_NAME = 'registrations' 
  AND TABLE_SCHEMA = DATABASE()
  AND CONSTRAINT_TYPE = 'UNIQUE';

-- Drop the problematic unique constraint
-- Run this command - if it fails, the index doesn't exist (which is fine)
ALTER TABLE registrations DROP INDEX uniq_tournament_player;

-- If above fails with error, uncomment and try this alternative name:
-- ALTER TABLE registrations DROP INDEX uniq_tournament_slot;

-- Add regular index for performance (allows duplicates)
CREATE INDEX idx_tournament_player ON registrations(tournament_id, player_id);

-- ========================================
-- SUCCESS MESSAGE
-- ========================================
SELECT 'SERVER DATABASE FIX COMPLETE!' AS status,
       'payment_status fixed, tournament_leaderboard view created, and duplicate registration error fixed' AS message;
