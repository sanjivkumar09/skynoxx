-- CLEANUP: Remove Duplicate/Old Registration Records
-- This will fix the "Duplicate entry '2-1'" error by cleaning up old records
-- Date: November 5, 2025

-- ========================================
-- STEP 1: Check for duplicate registrations
-- ========================================
SELECT 
    tournament_id,
    player_id,
    COUNT(*) as count,
    GROUP_CONCAT(id ORDER BY id) as registration_ids,
    GROUP_CONCAT(payment_status ORDER BY id) as statuses,
    GROUP_CONCAT(joined_at ORDER BY id) as dates
FROM registrations
GROUP BY tournament_id, player_id
HAVING COUNT(*) > 1;

-- ========================================
-- STEP 2: Delete OLD/INCOMPLETE registrations
-- Keep only the most recent one for each player-tournament pair
-- ========================================

-- This will remove old duplicate records, keeping the newest one
DELETE r1 FROM registrations r1
INNER JOIN registrations r2 
WHERE r1.tournament_id = r2.tournament_id 
  AND r1.player_id = r2.player_id 
  AND r1.id < r2.id;

-- ========================================
-- STEP 3: Clean up any pending/incomplete registrations
-- ========================================

-- Remove registrations that are stuck in pending status with no payment
DELETE FROM registrations 
WHERE payment_status = 'pending' 
  AND payment_screenshot IS NULL
  AND joined_at < DATE_SUB(NOW(), INTERVAL 1 HOUR);

-- ========================================
-- STEP 4: Verify cleanup
-- ========================================

-- Check if duplicates are gone
SELECT 
    'Cleanup Status' as action,
    COUNT(*) as remaining_duplicates
FROM (
    SELECT tournament_id, player_id, COUNT(*) as cnt
    FROM registrations
    GROUP BY tournament_id, player_id
    HAVING COUNT(*) > 1
) as duplicates;

-- Show remaining registrations for tournament 2
SELECT 
    id,
    tournament_id,
    player_id,
    slot_no,
    payment_status,
    joined_at
FROM registrations
WHERE tournament_id = 2
ORDER BY player_id, id;
