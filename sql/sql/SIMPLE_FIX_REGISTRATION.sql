-- ===========================================
-- SIMPLE FIX FOR DUPLICATE REGISTRATION ERROR
-- Copy and paste each section separately
-- ===========================================

-- SECTION 1: Check what indexes exist
-- Copy this, run it, see results
SHOW INDEX FROM registrations WHERE Key_name LIKE '%tournament%';

-- SECTION 2: Drop the unique constraint
-- Copy ONLY ONE of these commands and run it:

-- Option A: If index name is 'uniq_tournament_player'
ALTER TABLE registrations DROP INDEX uniq_tournament_player;

-- Option B: If index name is 'uniq_tournament_slot'  
-- ALTER TABLE registrations DROP INDEX uniq_tournament_slot;

-- SECTION 3: Add new non-unique index
-- Copy and run this
CREATE INDEX idx_tournament_player ON registrations(tournament_id, player_id);

-- SECTION 4: Verify it worked
-- Copy and run this
SHOW INDEX FROM registrations WHERE Key_name = 'idx_tournament_player';

-- ===========================================
-- DONE! The duplicate error should be fixed
-- ===========================================
