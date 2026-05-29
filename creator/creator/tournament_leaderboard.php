<?php
session_start();
require_once __DIR__ . '/../src/db.php';

// Prevent browser caching of leaderboard data
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$tournament_id = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : 0;

// Ensure match_results table exists (runtime safety)
@$conn->query("CREATE TABLE IF NOT EXISTS match_results (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tournament_id INT NOT NULL,
  match_number TINYINT NOT NULL,
  registration_id INT NOT NULL,
  placement INT NOT NULL,
  kills INT DEFAULT 0,
  damage INT DEFAULT 0,
  survival_time INT DEFAULT 0,
  placement_points DECIMAL(10,2) DEFAULT 0,
  kill_points DECIMAL(10,2) DEFAULT 0,
  bhooya_points DECIMAL(10,2) DEFAULT 0,
  bonus_points DECIMAL(10,2) DEFAULT 0,
  total_points DECIMAL(10,2) GENERATED ALWAYS AS (placement_points + kill_points + bhooya_points + bonus_points) STORED,
  updated_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tournament_match (tournament_id, match_number),
  INDEX idx_registration (registration_id),
  INDEX idx_match_results_points (total_points DESC),
  UNIQUE KEY unique_match_registration (tournament_id, match_number, registration_id),
  FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
  FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE,
  FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Add bhooya_points column if it doesn't exist
@$conn->query("ALTER TABLE match_results ADD COLUMN IF NOT EXISTS bhooya_points DECIMAL(10,2) DEFAULT 0 AFTER kill_points");

// Ensure tournaments has match columns
@$conn->query("ALTER TABLE tournaments 
  ADD COLUMN IF NOT EXISTS number_of_matches TINYINT DEFAULT 1,
  ADD COLUMN IF NOT EXISTS current_match_number TINYINT DEFAULT 1,
  ADD COLUMN IF NOT EXISTS points_distribution TEXT,
  ADD COLUMN IF NOT EXISTS kill_points DECIMAL(4,2) DEFAULT 1.00");

// Fetch tournament details
$stmt = $conn->prepare("SELECT * FROM tournaments WHERE id = ?");
$stmt->bind_param('i', $tournament_id);
$stmt->execute();
$tournament = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$tournament) {
    die('Tournament not found.');
}

// Get match filter
$match_filter = isset($_GET['match']) ? (int)$_GET['match'] : 0; // 0 = cumulative/all
$number_of_matches = (int)($tournament['number_of_matches'] ?? 1);

// Fetch leaderboard data
if ($match_filter > 0) {
    // Single match view
    $query = "
        SELECT 
            r.id as registration_id,
            r.slot_no,
            r.player_id,
            u.name as player_name,
            GROUP_CONCAT(DISTINCT tr_members.name ORDER BY tr_members.name SEPARATOR ', ') as team_members,
            mr.placement,
            mr.kills,
            mr.damage,
            mr.survival_time,
            mr.placement_points,
            mr.kill_points,
            mr.bhooya_points,
            mr.bonus_points,
            mr.total_points
        FROM registrations r
        JOIN users u ON r.player_id = u.id
        LEFT JOIN team_registrations tr ON r.id = tr.registration_id AND tr.status = 'accepted'
        LEFT JOIN users tr_members ON tr.user_id = tr_members.id
        LEFT JOIN match_results mr ON r.id = mr.registration_id AND mr.match_number = ?
        WHERE r.tournament_id = ? AND (r.payment_status IN ('success', 'paid') OR r.payment_status = '' OR r.payment_status IS NULL)
        GROUP BY r.id
        HAVING mr.placement IS NOT NULL
        ORDER BY mr.total_points DESC, mr.kills DESC, mr.placement ASC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $match_filter, $tournament_id);
} else {
    // Cumulative view (all matches)
    $query = "
        SELECT 
            r.id as registration_id,
            r.slot_no,
            r.player_id,
            u.name as player_name,
            GROUP_CONCAT(DISTINCT tr_members.name ORDER BY tr_members.name SEPARATOR ', ') as team_members,
            COUNT(DISTINCT mr.match_number) as matches_played,
            SUM(mr.kills) as total_kills,
            SUM(mr.damage) as total_damage,
            AVG(mr.placement) as avg_placement,
            MIN(mr.placement) as best_placement,
            SUM(mr.placement_points) as total_placement_points,
            SUM(mr.kill_points) as total_kill_points,
            SUM(mr.bhooya_points) as total_bhooya_points,
            SUM(mr.bonus_points) as total_bonus_points,
            SUM(mr.total_points) as cumulative_points
        FROM registrations r
        JOIN users u ON r.player_id = u.id
        LEFT JOIN team_registrations tr ON r.id = tr.registration_id AND tr.status = 'accepted'
        LEFT JOIN users tr_members ON tr.user_id = tr_members.id
        LEFT JOIN match_results mr ON r.id = mr.registration_id
        WHERE r.tournament_id = ? AND (r.payment_status IN ('success', 'paid') OR r.payment_status = '' OR r.payment_status IS NULL)
        GROUP BY r.id
        HAVING cumulative_points > 0
        ORDER BY cumulative_points DESC, total_kills DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $tournament_id);
}

$stmt->execute();
$leaderboard = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - <?php echo h($tournament['title']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background: #0f172a; color: #e2e8f0; }
        .container-main { max-width: 1200px; margin: 24px auto; }
        .card-glass { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; }
        .leaderboard-table { background: rgba(255,255,255,0.02); }
        .leaderboard-table th { background: rgba(255, 215, 0, 0.15); color: #ffd700; font-weight: 700; border-bottom: 2px solid rgba(255, 215, 0, 0.3); }
        .rank-1 { background: linear-gradient(135deg, rgba(255, 215, 0, 0.2), rgba(255, 215, 0, 0.05)) !important; }
        .rank-2 { background: linear-gradient(135deg, rgba(192, 192, 192, 0.2), rgba(192, 192, 192, 0.05)) !important; }
        .rank-3 { background: linear-gradient(135deg, rgba(205, 127, 50, 0.2), rgba(205, 127, 50, 0.05)) !important; }
        .rank-badge { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; font-weight: 800; font-size: 1.1rem; }
        .rank-badge.gold { background: linear-gradient(135deg, #ffd700, #ffed4e); color: #000; box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4); }
        .rank-badge.silver { background: linear-gradient(135deg, #c0c0c0, #e8e8e8); color: #000; box-shadow: 0 4px 15px rgba(192, 192, 192, 0.4); }
        .rank-badge.bronze { background: linear-gradient(135deg, #cd7f32, #e6a55b); color: #000; box-shadow: 0 4px 15px rgba(205, 127, 50, 0.4); }
        .stat-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .team-members { font-size: 0.8rem; color: #94a3b8; }
        .match-selector { background: linear-gradient(135deg, #8b5cf6, #6366f1); padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; }
    </style>
</head>
<body>
<div class="container container-main py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-1"><i class="bi bi-trophy-fill text-warning"></i> Leaderboard</h2>
            <h5 class="text-muted"><?php echo h($tournament['title']); ?></h5>
            <small class="text-muted"><?php echo h($tournament['match_type']); ?> | <?php echo h($tournament['map_name']); ?></small>
        </div>
        <div>
            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'creator' && $tournament['created_by'] == $_SESSION['user_id']): ?>
                <a href="update_match_stats.php?tournament_id=<?php echo $tournament_id; ?>" class="btn btn-primary me-2">
                    <i class="bi bi-pencil-square"></i> Update Stats
                </a>
            <?php endif; ?>
            <a href="<?php echo isset($_SESSION['role']) && $_SESSION['role'] === 'creator' ? 'creator_dashboard.php' : '../../src/index.php'; ?>" class="btn btn-outline-light">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- Match Filter -->
    <div class="match-selector card">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h6 class="mb-1"><i class="bi bi-filter-circle"></i> View Results</h6>
                <small>Select match or view cumulative standings</small>
            </div>
            <div class="col-md-6 text-end">
                <div class="btn-group" role="group">
                    <a href="?tournament_id=<?php echo $tournament_id; ?>&match=0" 
                       class="btn <?php echo $match_filter == 0 ? 'btn-light' : 'btn-outline-light'; ?>">
                        <i class="bi bi-bar-chart-fill"></i> Cumulative
                    </a>
                    <?php for ($i = 1; $i <= $number_of_matches; $i++): ?>
                        <a href="?tournament_id=<?php echo $tournament_id; ?>&match=<?php echo $i; ?>" 
                           class="btn <?php echo $match_filter == $i ? 'btn-light' : 'btn-outline-light'; ?>">
                            Match <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($leaderboard)): ?>
        <div class="alert alert-info"><i class="bi bi-info-circle"></i> No stats available yet for this <?php echo $match_filter > 0 ? "match" : "tournament"; ?>.</div>
    <?php else: ?>
    <div class="card card-glass card-body">
        <h5 class="mb-3">
            <?php if ($match_filter > 0): ?>
                <i class="bi bi-list-ol"></i> Match <?php echo $match_filter; ?> Results
            <?php else: ?>
                <i class="bi bi-trophy"></i> Overall Standings (All Matches)
            <?php endif; ?>
        </h5>
        <div class="table-responsive">
            <table class="table table-dark table-hover leaderboard-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Slot</th>
                        <th>Player / Team</th>
                        <?php if ($match_filter > 0): ?>
                            <th>Placement</th>
                            <th>Kills</th>
                            <th>Damage</th>
                            <th>Place Pts</th>
                            <th>Kill Pts</th>
                            <th>Bhooya Pts</th>
                            <th>Bonus</th>
                            <th>Total Pts</th>
                        <?php else: ?>
                            <th>Matches</th>
                            <th>Total Kills</th>
                            <th>Best Place</th>
                            <th>Avg Place</th>
                            <th>Total Pts</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    foreach ($leaderboard as $row): 
                        $rank_class = '';
                        $rank_badge_class = '';
                        if ($rank == 1) { $rank_class = 'rank-1'; $rank_badge_class = 'gold'; }
                        elseif ($rank == 2) { $rank_class = 'rank-2'; $rank_badge_class = 'silver'; }
                        elseif ($rank == 3) { $rank_class = 'rank-3'; $rank_badge_class = 'bronze'; }
                    ?>
                    <tr class="<?php echo $rank_class; ?>">
                        <td>
                            <span class="rank-badge <?php echo $rank_badge_class; ?>">
                                <?php if ($rank <= 3): ?>
                                    <i class="bi bi-trophy-fill"></i>
                                <?php else: ?>
                                    <?php echo $rank; ?>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td><strong>#<?php echo h($row['slot_no']); ?></strong></td>
                        <td>
                            <div><strong><?php echo h($row['player_name']); ?></strong></div>
                            <?php if ($row['team_members']): ?>
                                <div class="team-members">Team: <?php echo h($row['team_members']); ?></div>
                            <?php endif; ?>
                        </td>
                        <?php if ($match_filter > 0): ?>
                            <td><span class="stat-badge bg-info">#<?php echo h($row['placement']); ?></span></td>
                            <td><span class="stat-badge bg-danger"><?php echo h($row['kills']); ?> K</span></td>
                            <td><?php echo number_format($row['damage']); ?></td>
                            <td><?php echo number_format($row['placement_points'], 1); ?></td>
                            <td><?php echo number_format($row['kill_points'], 1); ?></td>
                            <td><?php echo number_format($row['bhooya_points'], 1); ?></td>
                            <td><?php echo number_format($row['bonus_points'], 1); ?></td>
                            <td><strong class="text-warning"><?php echo number_format($row['total_points'], 2); ?></strong></td>
                        <?php else: ?>
                            <td><?php echo h($row['matches_played']); ?>/<?php echo $number_of_matches; ?></td>
                            <td><span class="stat-badge bg-danger"><?php echo h($row['total_kills']); ?> K</span></td>
                            <td><span class="stat-badge bg-success">#<?php echo h($row['best_placement']); ?></span></td>
                            <td><?php echo number_format($row['avg_placement'], 1); ?></td>
                            <td><strong class="text-warning"><?php echo number_format($row['cumulative_points'], 2); ?></strong></td>
                        <?php endif; ?>
                    </tr>
                    <?php 
                    $rank++;
                    endforeach; 
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
