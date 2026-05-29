# Match Statistics & Multi-Match Tournament System - Deployment Guide

## Overview
Complete match statistics and leaderboard system for Free Fire tournaments with support for 1-3 matches per tournament, just like official FFML/FFIC tournaments.

## Features Implemented
✅ Multi-match tournaments (1-3 matches per tournament)
✅ Per-match statistics entry (placement, kills, damage, survival time)
✅ Auto-calculated points (placement + kill points + bonus)
✅ Cumulative leaderboard across all matches
✅ Match-by-match leaderboard view
✅ Default Free Fire points distribution (12-10-8-7-6-5-4-3-2-1-1-1 for top 12)
✅ Creator match stats management interface
✅ Player-facing public leaderboard
✅ Golden/Silver/Bronze rankings with trophy icons
✅ Team support (shows team members)

---

## SQL Schema Changes

### Run this SQL on your server:

```sql
-- 1. Add multi-match columns to tournaments table
ALTER TABLE tournaments 
  ADD COLUMN IF NOT EXISTS number_of_matches TINYINT DEFAULT 1 COMMENT '1-3 matches per tournament',
  ADD COLUMN IF NOT EXISTS current_match_number TINYINT DEFAULT 1 COMMENT 'Track which match is currently being played/updated',
  ADD COLUMN IF NOT EXISTS points_distribution TEXT COMMENT 'JSON: placement points config',
  ADD COLUMN IF NOT EXISTS kill_points DECIMAL(4,2) DEFAULT 1.00 COMMENT 'Points per kill (default 1)';

-- 2. Create match_results table
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
  bonus_points DECIMAL(10,2) DEFAULT 0 COMMENT 'Any bonus points',
  total_points DECIMAL(10,2) GENERATED ALWAYS AS (placement_points + kill_points + bonus_points) STORED,
  
  -- Metadata
  updated_by INT NULL COMMENT 'Creator who entered stats',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX idx_tournament_match (tournament_id, match_number),
  INDEX idx_registration (registration_id),
  INDEX idx_match_results_points (total_points DESC),
  UNIQUE KEY unique_match_registration (tournament_id, match_number, registration_id),
  
  FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
  FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE,
  FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_tournaments_matches ON tournaments(number_of_matches, current_match_number);
```

---

## Files to Upload

### New Files Created:
1. **sql/match_stats_system.sql** — Complete SQL schema (includes the above + optional view)
2. **creator/update_match_stats.php** — Creator interface to enter match stats
3. **creator/tournament_leaderboard.php** — Public leaderboard page (cumulative + per-match view)

### Updated Files:
1. **creator/create_tournament.php** — Added "Number of Matches" field (1-3 dropdown)
2. **creator/creator_dashboard.php** — Added "Match Stats" and "Leaderboard" buttons for each tournament

---

## How It Works

### For Creators:

1. **Create Tournament**
   - When creating a tournament, select "Number of Matches" (1, 2, or 3)
   - System auto-initializes with default Free Fire points (12,10,8,7,6,5,4,3,2,1,1,1) and 1 kill point per kill

2. **Update Match Stats**
   - Go to Creator Dashboard → Click "Match Stats" button on any tournament
   - Select which match (1, 2, or 3) to update
   - Enter for each player/team:
     - **Placement** (1st, 2nd, 3rd, etc.)
     - **Kills** (total eliminations)
     - **Damage** (optional)
     - **Survival Time** (optional, in seconds)
     - **Bonus Points** (optional, for special achievements)
   - Points auto-calculate on save:
     - Placement Points = configured value (e.g., #1 = 12 pts)
     - Kill Points = kills × kill_points_value (default 1.0)
     - Total = Placement + Kill + Bonus

3. **View Leaderboard**
   - Click "Leaderboard" button to see:
     - **Cumulative** standings (all matches combined)
     - **Per-match** results (select Match 1, 2, or 3)
   - Shows rank, player/team, stats, and points
   - Top 3 get trophy icons (🏆 gold, silver, bronze)

### For Players:

1. **View Leaderboard**
   - Access `tournament_leaderboard.php?tournament_id=X`
   - Filter by match or view cumulative standings
   - See detailed stats:
     - Match-specific: placement, kills, damage, points breakdown
     - Cumulative: total kills, best placement, avg placement, total points

2. **Rankings**
   - Sorted by total points (desc)
   - Tie-breaker: total kills (desc)
   - Visual indicators for top 3

---

## Points System (Default Free Fire FFML)

### Placement Points:
- 1st Place: 12 points
- 2nd Place: 10 points
- 3rd Place: 8 points
- 4th Place: 7 points
- 5th Place: 6 points
- 6th Place: 5 points
- 7th Place: 4 points
- 8th Place: 3 points
- 9th Place: 2 points
- 10th-12th: 1 point each
- 13th+: 0 points

### Kill Points:
- 1 point per kill (configurable per tournament)

### Total Points Formula:
```
Total Points = Placement Points + (Kills × Kill Points) + Bonus Points
```

---

## UI/UX Features

### Creator Dashboard:
- 🎯 **Match Stats** button — Enter/update match results
- 🏆 **Leaderboard** button — View standings
- Visual match selector (Match 1, Match 2, Match 3 tabs)
- Real-time point calculations
- Slot-based entry (shows team members for duo/squad)

### Leaderboard Page:
- Filter: Cumulative | Match 1 | Match 2 | Match 3
- Trophy badges for top 3 (gold/silver/bronze)
- Color-coded stat badges (placement, kills)
- Responsive tables with detailed stats
- Shows team composition for duo/squad modes

---

## Testing Checklist

- [ ] Run SQL schema on server database
- [ ] Upload new/updated files to server
- [ ] Create a test tournament with "3 Matches"
- [ ] Register test players
- [ ] Enter stats for Match 1
- [ ] Verify leaderboard shows Match 1 results
- [ ] Enter stats for Match 2
- [ ] Verify cumulative leaderboard updates correctly
- [ ] Test with solo/duo/squad tournaments
- [ ] Verify top 3 rankings show trophy icons
- [ ] Test player-facing leaderboard access

---

## Folders Changed

```
sql/
└── match_stats_system.sql  [NEW]

creator/
├── update_match_stats.php      [NEW]
├── tournament_leaderboard.php  [NEW]
├── create_tournament.php       [UPDATED - added number_of_matches field]
└── creator_dashboard.php       [UPDATED - added Match Stats & Leaderboard buttons]
```

---

## Technical Notes

1. **Generated Column**: `total_points` in `match_results` is auto-calculated by MySQL using GENERATED ALWAYS AS

2. **Points Distribution**: Stored as JSON in `tournaments.points_distribution`:
   ```json
   {"1":12,"2":10,"3":8,"4":7,"5":6,"6":5,"7":4,"8":3,"9":2,"10":1,"11":1,"12":1}
   ```

3. **Match Tracking**: `current_match_number` can be used to track progress (future feature)

4. **Unique Constraint**: One result per player/team per match (`unique_match_registration`)

5. **Team Support**: Shows team member names via JOIN with `team_registrations` table

---

## Future Enhancements (Optional)

- [ ] Auto-increment `current_match_number` after stats submission
- [ ] Lock match stats after settlement
- [ ] Export leaderboard as PDF/image
- [ ] Real-time leaderboard updates (WebSocket/AJAX)
- [ ] Custom points distribution per tournament
- [ ] Match MVP awards (most kills, highest damage, etc.)
- [ ] Historical match data analytics

---

## Support

If you encounter issues:
1. Check SQL errors in PHP error logs
2. Verify `match_results` table exists
3. Ensure `tournaments` table has new columns
4. Check file permissions for new PHP files
5. Verify registration IDs exist before entering stats

---

**Deployment Complete! 🎮🏆**

Your tournament platform now supports official-style multi-match tournaments with comprehensive statistics tracking and leaderboards!
