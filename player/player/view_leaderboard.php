<?php
session_start();
require_once __DIR__ . '/../src/db.php';

// Prevent browser caching of leaderboard data
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'player') {
    header('Location: ../../src/login.php');
    exit;
}

$player_id = (int)$_SESSION['user_id'];
$tournament_id = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : 0;

// Verify player is registered for this tournament
$check_stmt = $conn->prepare("
    SELECT r.id 
    FROM registrations r
    LEFT JOIN team_registrations tr ON r.id = tr.registration_id AND tr.user_id = ?
    WHERE r.tournament_id = ? AND (r.player_id = ? OR tr.user_id = ?)
    LIMIT 1
");
$check_stmt->bind_param('iiii', $player_id, $tournament_id, $player_id, $player_id);
$check_stmt->execute();
$is_registered = $check_stmt->get_result()->num_rows > 0;
$check_stmt->close();

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
            r.team_name,
            GROUP_CONCAT(DISTINCT tr_members.name ORDER BY tr_members.name SEPARATOR ', ') as team_members,
            mr.placement,
            mr.kills,
            mr.placement_points,
            mr.kill_points,
            mr.bhooya_points,
            mr.bonus_points,
            mr.total_points
        FROM registrations r
        JOIN users u ON r.player_id = u.id
        LEFT JOIN team_registrations tr ON r.id = tr.registration_id AND tr.invitation_status = 'accepted'
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
            r.team_name,
            GROUP_CONCAT(DISTINCT tr_members.name ORDER BY tr_members.name SEPARATOR ', ') as team_members,
            COUNT(DISTINCT mr.match_number) as matches_played,
            SUM(mr.kills) as total_kills,
            AVG(mr.placement) as avg_placement,
            MIN(mr.placement) as best_placement,
            SUM(mr.placement_points) as total_placement_points,
            SUM(mr.kill_points) as total_kill_points,
            SUM(mr.bhooya_points) as total_bhooya_points,
            SUM(mr.bonus_points) as total_bonus_points,
            SUM(mr.total_points) as cumulative_points
        FROM registrations r
        JOIN users u ON r.player_id = u.id
        LEFT JOIN team_registrations tr ON r.id = tr.registration_id AND tr.invitation_status = 'accepted'
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/gaming-theme.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - <?php echo h($tournament['title']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
<div class="container container-main py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-trophy-fill text-warning"></i> Leaderboard</h2>
            <h5 class="text-muted"><?php echo h($tournament['title']); ?></h5>
            <small class="text-muted"><?php echo h($tournament['match_type']); ?> | <?php echo h($tournament['map_name']); ?></small>
        </div>
        <a href="player_dashboard.php" class="btn btn-outline-light">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <!-- Match Selector -->
    <div class="match-selector card">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h6 class="mb-0 text-white"><i class="bi bi-list-ol"></i> View Results</h6>
                <small class="text-white-50">Select match or view cumulative standings</small>
            </div>
            <div class="col-md-6 text-end">
                <div class="btn-group" role="group">
                    <a href="?tournament_id=<?php echo $tournament_id; ?>&match=0" 
                       class="btn <?php echo $match_filter == 0 ? 'btn-light' : 'btn-outline-light'; ?>">
                        <i class="bi bi-bar-chart-fill"></i> Cumulative
                    </a>
                    <?php for ($i = 1; $i <= $number_of_matches; $i++): ?>
                        <a href="?tournament_id=<?php echo $tournament_id; ?>&match=<?php echo $i; ?>" 
                           class="btn <?php echo $i == $match_filter ? 'btn-light' : 'btn-outline-light'; ?>">
                            Match <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaderboard Table -->
    <div class="card-glass">
        <?php if (empty($leaderboard)): ?>
            <div class="alert alert-info mb-0">
                <i class="bi bi-info-circle-fill"></i> No stats available yet for this tournament. Check back after the tournament creator updates match results.
            </div>
        <?php else: ?>
            <h5 class="mb-3">
                <i class="bi bi-list-ol"></i> 
                <?php echo $match_filter > 0 ? "Match $match_filter Results" : "Cumulative Standings"; ?>
            </h5>
            <div class="table-responsive">
                <table class="table table-dark table-hover leaderboard-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Rank</th>
                            <th>Player / Team</th>
                            <th style="text-align: center;">Slot</th>
                            <?php if ($match_filter > 0): ?>
                                <th style="text-align: center;">Position</th>
                                <th style="text-align: center;">Kills</th>
                                <th style="text-align: center;">Placement Pts</th>
                                <th style="text-align: center;">Kill Pts</th>
                                <th style="text-align: center;">Bhooya Pts</th>
                            <?php else: ?>
                                <th style="text-align: center;">Matches</th>
                                <th style="text-align: center;">Total Kills</th>
                                <th style="text-align: center;">Best Position</th>
                            <?php endif; ?>
                            <th style="text-align: center; font-size: 1.1rem;">Total Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        foreach ($leaderboard as $entry): 
                            $is_current_player = ($entry['player_id'] == $player_id);
                            $rank_class = '';
                            $rank_badge_class = '';
                            if ($rank == 1) {
                                $rank_class = 'rank-1';
                                $rank_badge_class = 'gold';
                            } elseif ($rank == 2) {
                                $rank_class = 'rank-2';
                                $rank_badge_class = 'silver';
                            } elseif ($rank == 3) {
                                $rank_class = 'rank-3';
                                $rank_badge_class = 'bronze';
                            }
                            if ($is_current_player) {
                                $rank_class .= ' highlight-player';
                            }
                        ?>
                            <tr class="<?php echo $rank_class; ?>">
                                <td class="text-center">
                                    <span class="rank-badge <?php echo $rank_badge_class; ?>">
                                        <?php echo $rank; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight: 600; font-size: 1rem;">
                                        <?php echo h($entry['player_name']); ?>
                                        <?php if ($is_current_player): ?>
                                            <span class="badge bg-primary ms-2" style="font-size: 0.7rem;">YOU</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($entry['team_name'])): ?>
                                        <div class="team-members">Team: <?php echo h($entry['team_name']); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($entry['team_members'])): ?>
                                        <div class="team-members">Members: <?php echo h($entry['team_members']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary" style="font-size: 0.95rem;">#<?php echo h($entry['slot_no']); ?></span>
                                </td>
                                <?php if ($match_filter > 0): ?>
                                    <td class="text-center" style="font-weight: 600; font-size: 1rem;">
                                        #<?php echo h($entry['placement']); ?>
                                    </td>
                                    <td class="text-center" style="font-weight: 600; font-size: 1rem;">
                                        <?php echo h($entry['kills']); ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="stat-badge" style="background: rgba(110, 180, 255, 0.2); color: #6eb4ff;">
                                            <?php echo number_format($entry['placement_points'], 1); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="stat-badge" style="background: rgba(16, 185, 129, 0.2); color: #10b981;">
                                            <?php echo number_format($entry['kill_points'], 1); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="stat-badge" style="background: rgba(251, 191, 36, 0.2); color: #fbbf24;">
                                            <?php echo number_format($entry['bhooya_points'], 1); ?>
                                        </span>
                                    </td>
                                <?php else: ?>
                                    <td class="text-center" style="font-weight: 600;">
                                        <?php echo h($entry['matches_played']); ?>
                                    </td>
                                    <td class="text-center" style="font-weight: 600;">
                                        <?php echo h($entry['total_kills']); ?>
                                    </td>
                                    <td class="text-center" style="font-weight: 600;">
                                        #<?php echo number_format($entry['best_placement'], 0); ?>
                                    </td>
                                <?php endif; ?>
                                <td class="text-center">
                                    <span class="badge bg-success" style="font-size: 1.1rem; padding: 0.6rem 1rem;">
                                        <?php 
                                        $total = $match_filter > 0 ? $entry['total_points'] : $entry['cumulative_points'];
                                        echo number_format($total, 1); 
                                        ?> pts
                                    </span>
                                </td>
                            </tr>
                        <?php 
                            $rank++;
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
