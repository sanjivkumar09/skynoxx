-- EMERGENCY FIX: Delete the specific duplicate record causing the error
-- Error: "Duplicate entry '2-1' for key 'uniq_tournament_slot'"
-- This means: Tournament ID = 2, Player ID = 1

-- ========================================
-- QUICK FIX: Delete the problem record
-- ========================================

-- Option 1: Delete ALL registrations for this player in this tournament
-- This is the safest and quickest solution
DELETE FROM registrations 
WHERE tournament_id = 2 
  AND player_id = 1;

-- Verify deletion
SELECT * FROM registrations 
WHERE tournament_id = 2 
  AND player_id = 1;

-- Should return 0 rows

-- ========================================
-- If you want to see what was deleted first:
-- ========================================
-- Run this BEFORE the DELETE to see what will be removed:
-- SELECT * FROM registrations 
-- WHERE tournament_id = 2 
--   AND player_id = 1;
