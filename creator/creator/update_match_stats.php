<?php
session_start();
require_once __DIR__ . '/../src/db.php';

// Prevent browser caching of this page
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'creator') {
    header('Location: ../../src/login.php');
    exit;
}

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

$creator_id = (int)$_SESSION['user_id'];
$tournament_id = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : 0;

// Verify tournament ownership
$stmt = $conn->prepare("SELECT * FROM tournaments WHERE id = ? AND created_by = ?");
$stmt->bind_param('ii', $tournament_id, $creator_id);
$stmt->execute();
$tournament = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$tournament) {
    $_SESSION['flash_error'] = 'Tournament not found or access denied.';
    header('Location: creator_dashboard.php');
    exit;
}

// Default points distribution (Free Fire standard)
$default_points = json_encode([
    '1'=>12, '2'=>10, '3'=>8, '4'=>7, '5'=>6, '6'=>5,
    '7'=>4, '8'=>3, '9'=>2, '10'=>1, '11'=>1, '12'=>1
]);

// Initialize tournament match settings if not set
if (empty($tournament['number_of_matches'])) {
    $conn->query("UPDATE tournaments SET number_of_matches = 1, current_match_number = 1, points_distribution = '$default_points', kill_points = 1.00 WHERE id = $tournament_id");
    $tournament['number_of_matches'] = 1;
    $tournament['current_match_number'] = 1;
}

$points_config = !empty($tournament['points_distribution']) ? json_decode($tournament['points_distribution'], true) : json_decode($default_points, true);
$kill_points_value = (float)($tournament['kill_points'] ?? 1.00);

// Get match number to update
$match_number = isset($_GET['match']) ? (int)$_GET['match'] : (int)$tournament['current_match_number'];
if ($match_number < 1) $match_number = 1;
if ($match_number > $tournament['number_of_matches']) $match_number = $tournament['number_of_matches'];

// Fetch registered players/teams
$registrations_query = "
    SELECT 
        r.id as registration_id,
        r.player_id,
        u.name as player_name,
        u.email,
        r.slot_no,
        r.team_name,
        r.payment_status,
        GROUP_CONCAT(DISTINCT tr_members.name ORDER BY tr_members.name SEPARATOR ', ') as team_members
    FROM registrations r
    JOIN users u ON r.player_id = u.id
    LEFT JOIN team_registrations tr ON r.id = tr.registration_id AND tr.invitation_status = 'accepted'
    LEFT JOIN users tr_members ON tr.user_id = tr_members.id
    WHERE r.tournament_id = ? AND (r.payment_status IN ('success', 'paid') OR r.payment_status = '' OR r.payment_status IS NULL)
    GROUP BY r.id
    ORDER BY r.slot_no ASC
";
$stmt = $conn->prepare($registrations_query);
$stmt->bind_param('i', $tournament_id);
$stmt->execute();
$registrations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch existing match results for this match
$existing_results = [];
if (!empty($registrations)) {
    $reg_ids = array_column($registrations, 'registration_id');
    $ids_placeholder = implode(',', array_fill(0, count($reg_ids), '?'));
    $stmt = $conn->prepare("SELECT * FROM match_results WHERE tournament_id = ? AND match_number = ? AND registration_id IN ($ids_placeholder)");
    $types = 'ii' . str_repeat('i', count($reg_ids));
    $stmt->bind_param($types, $tournament_id, $match_number, ...$reg_ids);
    $stmt->execute();
    $results_raw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($results_raw as $row) {
        $existing_results[$row['registration_id']] = $row;
    }
    $stmt->close();
}

// Handle room setup update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_room'])) {
    $room_id = trim($_POST['room_id'] ?? '');
    $room_password = trim($_POST['room_password'] ?? '');
    
    $update_stmt = $conn->prepare("UPDATE tournaments SET room_id = ?, room_password = ? WHERE id = ? AND created_by = ?");
    $update_stmt->bind_param('ssii', $room_id, $room_password, $tournament_id, $creator_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['flash_success'] = 'Room details updated successfully!';
        $tournament['room_id'] = $room_id;
        $tournament['room_password'] = $room_password;
    } else {
        $_SESSION['flash_error'] = 'Failed to update room details.';
    }
    $update_stmt->close();
    header("Location: update_match_stats.php?tournament_id=$tournament_id&match=$match_number");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_stats'])) {
    $conn->begin_transaction();
    try {
        $updated_by = $creator_id;
        $stats_data = $_POST['stats'] ?? [];
        $manual_mode = isset($_POST['manual_mode']) ? 1 : 0;
        $booyah_reg = isset($_POST['booyah']) ? (int)$_POST['booyah'] : 0;
        
        foreach ($stats_data as $reg_id => $data) {
            $reg_id = (int)$reg_id;
            $placement = isset($data['placement']) && $data['placement'] > 0 ? (int)$data['placement'] : null;
            $kills = max(0, (int)($data['kills'] ?? 0));
            $bhooya_pts = max(0, (float)($data['bhooya_points'] ?? 0));
            $damage = max(0, (int)($data['damage'] ?? 0));
            $survival_time = max(0, (int)($data['survival_time'] ?? 0));
            $manual_points = isset($data['manual_points']) && $data['manual_points'] !== '' ? (float)$data['manual_points'] : null;
            
            // Apply Booyah selection (winner gets placement 1)
            if ($booyah_reg === $reg_id) {
                $placement = 1;
            }

            // In manual mode, if no placement provided, fall back to 99 (unknown) to satisfy NOT NULL
            if ($manual_mode && $placement === null) {
                $placement = 99;
            }

            // Skip if nothing to save (non-manual and no placement)
            if (!$manual_mode && $placement === null) continue;
            
            // Calculate points (manual override if provided)
            if ($manual_mode && $manual_points !== null) {
                $place_points = 0.0;
                $kill_pts = 0.0;
                $bonus_pts = $manual_points; // store manual total as bonus so total_points == manual
            } else {
                $place_points = (float)($points_config[$placement] ?? 0);
                $kill_pts = $kills * $kill_points_value;
                $bonus_pts = (float)($data['bonus'] ?? 0);
            }
            
            // Upsert match result
            $upsert = $conn->prepare("
                INSERT INTO match_results 
                (tournament_id, match_number, registration_id, placement, kills, damage, survival_time, placement_points, kill_points, bhooya_points, bonus_points, updated_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                placement = VALUES(placement),
                kills = VALUES(kills),
                damage = VALUES(damage),
                survival_time = VALUES(survival_time),
                placement_points = VALUES(placement_points),
                kill_points = VALUES(kill_points),
                bhooya_points = VALUES(bhooya_points),
                bonus_points = VALUES(bonus_points),
                updated_by = VALUES(updated_by),
                updated_at = CURRENT_TIMESTAMP
            ");
            $upsert->bind_param('iiiiiidddddi', $tournament_id, $match_number, $reg_id, $placement, $kills, $damage, $survival_time, $place_points, $kill_pts, $bhooya_pts, $bonus_pts, $updated_by);
            $upsert->execute();
            $upsert->close();
        }
        
        $conn->commit();
        $_SESSION['flash_success'] = "Match $match_number stats updated successfully!";
        header("Location: update_match_stats.php?tournament_id=$tournament_id&match=$match_number&t=" . time());
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['flash_error'] = 'Failed to save stats: ' . $e->getMessage();
    }
}

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
    <title>Update Match Stats - <?php echo h($tournament['title']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
<div class="container container-main py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-1"><?php echo h($tournament['title']); ?></h2>
            <small class="text-muted">Match Type: <?php echo h($tournament['match_type']); ?> | Map: <?php echo h($tournament['map_name']); ?></small>
        </div>
        <a href="creator_dashboard.php" class="btn btn-outline-light"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?php echo h($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?php echo h($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
    <?php endif; ?>

    <!-- Tournament Setup Card -->
    <div class="card card-glass card-body mb-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h6 class="mb-2"><i class="bi bi-gear-fill"></i> Tournament Setup</h6>
                <div class="d-flex gap-3">
                    <div>
                        <small class="text-muted d-block">Room ID</small>
                        <strong style="font-size: 1.1rem; color: #3b82f6;"><?php echo h($tournament['room_id'] ?? 'Not Set'); ?></strong>
                    </div>
                    <div>
                        <small class="text-muted d-block">Password</small>
                        <strong style="font-size: 1.1rem; color: #10b981;"><?php echo h($tournament['room_password'] ?? 'Not Set'); ?></strong>
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#setupModal">
                    <i class="bi bi-pencil-fill"></i> Update Room Details
                </button>
            </div>
        </div>
    </div>

    <!-- Match Selector -->
    <div class="match-selector card mb-3">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="mb-2"><i class="bi bi-trophy-fill"></i> Match Selection</h5>
                <p class="mb-0">Tournament has <strong><?php echo $tournament['number_of_matches']; ?></strong> match(es). Currently updating: <strong>Match <?php echo $match_number; ?></strong></p>
            </div>
            <div class="col-md-4 text-end">
                <div class="btn-group" role="group">
                    <?php for ($i = 1; $i <= $tournament['number_of_matches']; $i++): ?>
                        <a href="?tournament_id=<?php echo $tournament_id; ?>&match=<?php echo $i; ?>" 
                           class="btn <?php echo $i == $match_number ? 'btn-light' : 'btn-outline-light'; ?>">
                            Match <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Points Configuration Info -->
    <div class="card card-glass card-body mb-3">
        <h6><i class="bi bi-calculator"></i> Points Configuration</h6>
        <div class="row">
            <div class="col-md-9">
                <small class="text-muted">
                    Placement Points: 
                    <?php 
                    $pts_display = [];
                    foreach ($points_config as $pos => $pts) {
                        $pts_display[] = "#$pos=$pts";
                    }
                    echo implode(', ', array_slice($pts_display, 0, 12));
                    ?>
                </small>
            </div>
            <div class="col-md-3 text-end">
                <small class="text-muted">Kill Points: <strong><?php echo number_format($kill_points_value, 2); ?></strong> per kill</small>
            </div>
        </div>
    </div>

    <?php if (empty($registrations)): ?>
        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> No registered players/teams found for this tournament.</div>
    <?php else: ?>
    <!-- Stats Entry Form -->
    <form method="POST" action="">
        <div class="card card-glass card-body">
            <h5 class="mb-3"><i class="bi bi-bar-chart-fill"></i> Enter Match <?php echo $match_number; ?> Statistics</h5>
            <div class="alert alert-info mb-3" style="background: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.3); color: #93c5fd;">
                <i class="bi bi-info-circle-fill"></i> Enter kills, position, and bhooya points for each player/team. Check "bhooya" checkbox for the winner (will set position to 1). Total points = Placement Points + Kill Points + Bhooya Points.
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-hover stats-table" style="min-width: 800px;">
                    <thead>
                        <tr style="background: rgba(110, 180, 255, 0.15);">
                            <th style="width: 40px; text-align: center;">Slot</th>
                            <th style="width: 200px;">Name</th>
                            <th style="width: 90px; text-align: center;">Kills</th>
                            <th style="width: 100px; text-align: center;">Position</th>
                            <th style="width: 110px; text-align: center;">Bhooya Pts</th>
                            <th style="width: 80px; text-align: center;">Bhooya</th>
                            <th style="width: 120px; text-align: center;">Total Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $reg): ?>
                            <?php
                            $reg_id = $reg['registration_id'];
                            $existing = $existing_results[$reg_id] ?? null;
                            $placement = $existing['placement'] ?? '';
                            $kills = $existing['kills'] ?? 0;
                            $bhooya_points = $existing['bhooya_points'] ?? 0;
                            $calc_total = $existing['total_points'] ?? 0;
                            $is_booyah = ($placement == 1);
                            ?>
                            <tr>
                                <td class="text-center">
                                    <strong style="font-size: 1.1rem; color: #6eb4ff;">#<?php echo h($reg['slot_no']); ?></strong>
                                </td>
                                <td>
                                    <div style="font-weight: 600;"><?php echo h($reg['player_name']); ?></div>
                                    <?php if (!empty($reg['team_name'])): ?>
                                        <div class="team-members" style="font-size: 0.8rem;">Team: <?php echo h($reg['team_name']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($reg['team_members']): ?>
                                        <div class="team-members" style="font-size: 0.75rem; color: #64748b;">Members: <?php echo h($reg['team_members']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <input type="number" name="stats[<?php echo $reg_id; ?>][kills]" 
                                           class="stats-input text-center" min="0" max="99" 
                                           value="<?php echo h($kills); ?>" 
                                           style="width: 70px; font-size: 1rem; font-weight: 600;" 
                                           placeholder="0" />
                                </td>
                                <td class="text-center">
                                    <input type="number" name="stats[<?php echo $reg_id; ?>][placement]" 
                                           class="stats-input text-center" min="1" max="100" 
                                           value="<?php echo h($placement); ?>" 
                                           style="width: 80px; font-size: 1rem; font-weight: 600;" 
                                           placeholder="#" />
                                </td>
                                <td class="text-center">
                                    <input type="number" name="stats[<?php echo $reg_id; ?>][bhooya_points]" 
                                           class="stats-input text-center" min="0" max="100" step="0.5"
                                           value="<?php echo h($bhooya_points); ?>" 
                                           style="width: 90px; font-size: 1rem; font-weight: 600;" 
                                           placeholder="0" />
                                </td>
                                <td class="text-center">
                                    <input type="radio" name="booyah" value="<?php echo $reg_id; ?>" 
                                           <?php echo ($is_booyah ? 'checked' : ''); ?> 
                                           style="width: 20px; height: 20px; cursor: pointer;" />
                                </td>
                                <td class="text-center">
                                    <?php if ($existing): ?>
                                        <span class="badge bg-success" style="font-size: 1rem; padding: 0.5rem 0.75rem;">
                                            <?php echo number_format($calc_total, 1); ?> pts
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size: 0.9rem;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-4">
                <div>
                    <small class="text-muted"><i class="bi bi-calculator"></i> Total Points = Placement Points + (Kills × <?php echo $kill_points_value; ?>) + Bhooya Points</small><br>
                    <small class="text-muted"><i class="bi bi-info-circle"></i> Placement Points: #1=<?php echo $points_config['1'] ?? 12; ?>, #2=<?php echo $points_config['2'] ?? 10; ?>, #3=<?php echo $points_config['3'] ?? 8; ?>, etc.</small>
                </div>
                <button type="submit" name="save_stats" class="btn btn-success btn-lg">
                    <i class="bi bi-save"></i> Save & Update Leaderboard
                </button>
            </div>
        </div>
    </form>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="card card-glass card-body mt-3">
        <div class="row g-3">
            <div class="col-md-4">
                <a href="tournament_leaderboard.php?tournament_id=<?php echo $tournament_id; ?>" class="btn btn-outline-primary w-100">
                    <i class="bi bi-list-ol"></i> View Leaderboard
                </a>
            </div>
            <div class="col-md-4">
                <a href="view_tournament.php?id=<?php echo $tournament_id; ?>" class="btn btn-outline-info w-100">
                    <i class="bi bi-eye"></i> View Tournament
                </a>
            </div>
            <div class="col-md-4">
                <?php if ($tournament['status'] === 'ongoing' && $match_number >= $tournament['number_of_matches']): ?>
                    <a href="settle_tournament.php?tournament_id=<?php echo $tournament_id; ?>" class="btn btn-outline-warning w-100">
                        <i class="bi bi-trophy"></i> Settle & Distribute Prizes
                    </a>
                <?php else: ?>
                    <button class="btn btn-outline-secondary w-100" disabled>
                        Settle Tournament (after all matches)
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Room Setup Modal -->
<div class="modal fade" id="setupModal" tabindex="-1" aria-labelledby="setupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: #1e293b; border: 1px solid rgba(255,255,255,0.1);">
            <div class="modal-header" style="border-color: rgba(255,255,255,0.1);">
                <h5 class="modal-title" id="setupModalLabel"><i class="bi bi-gear-fill"></i> Update Room Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="color: #e2e8f0;">Room ID</label>
                        <input type="text" class="form-control" name="room_id" value="<?php echo h($tournament['room_id'] ?? ''); ?>" 
                               placeholder="e.g., 123456789" required
                               style="background: #0f172a; color: #e2e8f0; border-color: #334155;">
                        <div class="form-text" style="color: #94a3b8;">Enter the Free Fire room ID for this tournament</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="color: #e2e8f0;">Room Password</label>
                        <input type="text" class="form-control" name="room_password" value="<?php echo h($tournament['room_password'] ?? ''); ?>" 
                               placeholder="e.g., 1234" required
                               style="background: #0f172a; color: #e2e8f0; border-color: #334155;">
                        <div class="form-text" style="color: #94a3b8;">Enter the room password (4-6 digits)</div>
                    </div>
                    <div class="alert alert-info" style="background: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.3); color: #93c5fd;">
                        <i class="bi bi-info-circle-fill"></i> Players will see these details on the tournament page.
                    </div>
                </div>
                <div class="modal-footer" style="border-color: rgba(255,255,255,0.1);">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_room" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
