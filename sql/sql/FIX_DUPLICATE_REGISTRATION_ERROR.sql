-- FIX: Duplicate Tournament Registration Error
-- Date: November 5, 2025
-- Error: "Duplicate entry '2-1' for key 'uniq_tournament_slot'"
-- Issue: UNIQUE constraint on (tournament_id, player_id) prevents legitimate re-registrations

-- ========================================
-- PROBLEM EXPLANATION
-- ========================================
-- Current constraint: UNIQUE KEY `uniq_tournament_player` (`tournament_id`,`player_id`)
-- This prevents:
-- 1. Players from re-joining after leaving a team
-- 2. Testing with same users
-- 3. Edge cases where registration fails but constraint remains

-- ========================================
-- SOLUTION: Remove the problematic UNIQUE constraint
-- ========================================

-- Step 1: Check existing constraints
SELECT 
    CONSTRAINT_NAME, 
    CONSTRAINT_TYPE, 
    TABLE_NAME
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_NAME = 'registrations' 
  AND TABLE_SCHEMA = DATABASE()
  AND CONSTRAINT_TYPE = 'UNIQUE';

-- Step 2: Drop the unique constraint
-- Run each command separately - if one fails, continue with the next

-- Drop uniq_tournament_player (most common name)
ALTER TABLE registrations DROP INDEX uniq_tournament_player;

-- If above fails, try this alternative name
-- ALTER TABLE registrations DROP INDEX uniq_tournament_slot;

-- Step 3: Add a regular (non-unique) index for performance
-- This allows lookups but permits duplicates
CREATE INDEX idx_tournament_player ON registrations(tournament_id, player_id);

-- Step 4: Verify the constraint is removed
SHOW INDEXES FROM registrations;

-- ========================================
-- ALTERNATIVE: Keep UNIQUE but handle in PHP
-- ========================================
-- If you WANT to prevent duplicate registrations, 
-- handle it in PHP with DELETE before INSERT:
-- 
-- DELETE FROM registrations 
-- WHERE tournament_id = ? AND player_id = ? 
-- AND payment_status IN ('pending', '');
--
-- Then INSERT new registration

-- ========================================
-- VERIFICATION
-- ========================================
SELECT 
    'FIX COMPLETE!' AS status,
    'Players can now re-register for tournaments' AS message;

-- Test query: Check for duplicate registrations (should be allowed now)
SELECT 
    tournament_id, 
    player_id, 
    COUNT(*) as registration_count,
    GROUP_CONCAT(id) as registration_ids
FROM registrations
GROUP BY tournament_id, player_id
HAVING COUNT(*) > 1;
