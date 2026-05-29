-- Match Statistics and Multi-Match Tournament System
-- Add support for multiple matches per tournament with detailed stats tracking

-- 1. Extend tournaments table for multi-match support
ALTER TABLE tournaments 
  ADD COLUMN IF NOT EXISTS number_of_matches TINYINT DEFAULT 1 COMMENT '1-3 matches per tournament',
  ADD COLUMN IF NOT EXISTS current_match_number TINYINT DEFAULT 1 COMMENT 'Track which match is currently being played/updated',
  ADD COLUMN IF NOT EXISTS points_distribution TEXT COMMENT 'JSON: placement points config e.g. {1:12, 2:10, ...}',
  ADD COLUMN IF NOT EXISTS kill_points DECIMAL(4,2) DEFAULT 1.00 COMMENT 'Points per kill (default 1)';

-- 2. Create match_results table to store per-match statistics
CREATE TABLE IF NOT EXISTS match_results (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tournament_id INT NOT NULL,
  match_number TINYINT NOT NULL COMMENT '1, 2, or 3',
  registration_id INT NOT NULL COMMENT 'Links to registrations table (player or team captain)',
  
  -- Performance metrics
  placement INT NOT NULL COMMENT 'Final rank: 1st, 2nd, 3rd, etc.',
  kills INT DEFAULT 0 COMMENT 'Total kills in this match',
  damage INT DEFAULT 0 COMMENT 'Total damage dealt',
  survival_time INT DEFAULT 0 COMMENT 'Time survived in seconds',
  
  -- Calculated points
  placement_points DECIMAL(10,2) DEFAULT 0 COMMENT 'Points for placement',
  kill_points DECIMAL(10,2) DEFAULT 0 COMMENT 'Points for kills',
  bhooya_points DECIMAL(10,2) DEFAULT 0 COMMENT 'Points for winner/bhooya achievement',
  bonus_points DECIMAL(10,2) DEFAULT 0 COMMENT 'Any bonus points (e.g., most kills)',
  total_points DECIMAL(10,2) GENERATED ALWAYS AS (placement_points + kill_points + bhooya_points + bonus_points) STORED,
  
  -- Metadata
  updated_by INT NULL COMMENT 'Creator who entered stats',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX idx_tournament_match (tournament_id, match_number),
  INDEX idx_registration (registration_id),
  UNIQUE KEY unique_match_registration (tournament_id, match_number, registration_id),
  
  FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
  FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE,
  FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Stores match-by-match statistics for tournaments';

-- 3. Create leaderboard view for easy querying
CREATE OR REPLACE VIEW tournament_leaderboard AS
SELECT 
  t.id AS tournament_id,
  t.title AS tournament_title,
  t.match_type,
  r.id AS registration_id,
  r.player_id,
  u.name AS player_name,
  COALESCE(team_name.team_name, 'Solo Player') AS team_name,
  
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
LEFT JOIN (
  SELECT registration_id, team_name 
  FROM team_registrations 
  WHERE status = 'accepted' 
  GROUP BY registration_id
) team_name ON r.id = team_name.registration_id

WHERE (r.payment_status IN ('success', 'paid') OR r.payment_status = '' OR r.payment_status IS NULL)
GROUP BY t.id, r.id, r.player_id, u.name, team_name.team_name
ORDER BY cumulative_points DESC, total_kills DESC;

-- 4. Default points distribution (Free Fire standard)
-- Will be stored as JSON in tournaments.points_distribution
-- Example: {"1":12,"2":10,"3":8,"4":7,"5":6,"6":5,"7":4,"8":3,"9":2,"10":1,"11":1,"12":1}
-- Top 12 placements get points, kill points = 1 per kill

-- 5. Add indexes for performance
CREATE INDEX IF NOT EXISTS idx_match_results_points ON match_results(total_points DESC);
CREATE INDEX IF NOT EXISTS idx_tournaments_matches ON tournaments(number_of_matches, current_match_number);

-- Note: To drop this schema if needed:
-- DROP VIEW IF EXISTS tournament_leaderboard;
-- DROP TABLE IF EXISTS match_results;
-- ALTER TABLE tournaments DROP COLUMN IF EXISTS number_of_matches, DROP COLUMN IF EXISTS current_match_number, DROP COLUMN IF EXISTS points_distribution, DROP COLUMN IF EXISTS kill_points;
