<?php
session_start();
include '../src/db.php';
include '../src/auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'player') {
    header('Location: ../../src/login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$tournament_id = isset($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : 0;

if ($tournament_id <= 0) {
    header('Location: player_dashboard.php');
    exit();
}

// Fetch tournament and team details (supports both team members and solo/captain-only registrations)
$stmt = $conn->prepare("
    SELECT 
        t.*,
        r.id as registration_id,
        r.team_name,
        r.slot_no,
        r.payment_status,
        r.player_id as captain_id,
        u.name as captain_name,
        COALESCE(tr.role, 'captain') as user_role
    FROM tournaments t
    JOIN registrations r ON t.id = r.tournament_id
    LEFT JOIN team_registrations tr ON tr.registration_id = r.id AND tr.user_id = ? AND tr.invitation_status = 'accepted'
    JOIN users u ON r.player_id = u.id
    WHERE t.id = ? AND (r.player_id = ? OR (tr.user_id = ? AND tr.invitation_status = 'accepted'))
    LIMIT 1
");
$stmt->bind_param('iiii', $user_id, $tournament_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$team_data = $result->fetch_assoc();
$stmt->close();

if (!$team_data) {
    // Fallback: Show tournament info only
    $stmt = $conn->prepare("
        SELECT t.* FROM tournaments t WHERE t.id = ? LIMIT 1
    ");
    $stmt->bind_param('i', $tournament_id);
    $stmt->execute();
    $tournament_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$tournament_data) {
        $_SESSION['error_message'] = 'Tournament not found.';
        header('Location: player_dashboard.php');
        exit();
    }
    // Render minimal page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/gaming-theme.css">
</head>
    <body>
    <div class="container mt-5">
        <h2><?php echo htmlspecialchars($tournament_data['title']); ?></h2>
        <p><strong>Date:</strong> <?php echo htmlspecialchars($tournament_data['date']); ?> <strong>Time:</strong> <?php echo htmlspecialchars($tournament_data['time']); ?></p>
        <p><strong>Entry Fee:</strong> ₹<?php echo htmlspecialchars($tournament_data['entry_fee']); ?></p>
        <p><strong>Prize Pool:</strong> ₹<?php echo htmlspecialchars($tournament_data['prize_pool']); ?></p>
        <div class="alert alert-warning mt-4">You are not a member of this team. If you believe this is an error, contact support or your team captain.</div>
        <a href="player_dashboard.php" class="btn btn-primary">Back to Dashboard</a>
    </div>
    </body>
    </html>
    <?php
    exit();
}

// Fetch all team members (if team_registrations exist, otherwise show captain only)
$members_stmt = $conn->prepare("
    SELECT 
        u.id,
        u.name,
        u.email,
        pp.in_game_name,
        pp.game_uid,
        COALESCE(tr.role, 'captain') as role,
        COALESCE(tr.position_index, 1) as position_index
    FROM team_registrations tr
    JOIN users u ON tr.user_id = u.id
    LEFT JOIN players_profile pp ON u.id = pp.user_id
    WHERE tr.registration_id = ? AND tr.invitation_status = 'accepted'
    ORDER BY tr.position_index ASC
");
$members_stmt->bind_param('i', $team_data['registration_id']);
$members_stmt->execute();
$team_members = $members_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$members_stmt->close();

// If no team members found (solo registration), add the captain
if (empty($team_members)) {
    $captain_stmt = $conn->prepare("
        SELECT 
            u.id,
            u.name,
            u.email,
            pp.in_game_name,
            pp.game_uid,
            'captain' as role,
            1 as position_index
        FROM registrations r
        JOIN users u ON r.player_id = u.id
        LEFT JOIN players_profile pp ON u.id = pp.user_id
        WHERE r.id = ?
    ");
    $captain_stmt->bind_param('i', $team_data['registration_id']);
    $captain_stmt->execute();
    $team_members = $captain_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $captain_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/gaming-theme.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top" style="background: rgba(15, 25, 35, 0.95); border-bottom: 2px solid rgba(255, 70, 85, 0.3);">
        <div class="container">
            <a class="navbar-brand" href="../../src/index.php" style="font-family: 'Orbitron', sans-serif; color: #ff4655; font-weight: 700;">
                FF Tournaments
            </a>
        </div>
    </nav>

    <div class="team-container">
        <div class="team-card">
            <div class="team-header">
                <div class="team-title"><?php echo htmlspecialchars($team_data['title']); ?></div>
                <div class="team-name">
                    <i class="bi bi-people-fill"></i> <?php echo htmlspecialchars($team_data['team_name']); ?>
                </div>
                <span class="badge bg-primary" style="font-size: 0.9rem;">
                    Slot #<?php echo $team_data['slot_no']; ?>
                </span>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Match Type</div>
                    <div class="info-value"><?php echo ucfirst($team_data['match_type']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Map</div>
                    <div class="info-value"><?php echo htmlspecialchars($team_data['map_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date & Time</div>
                    <div class="info-value"><?php echo date('M d, Y', strtotime($team_data['date'])); ?><br><?php echo date('g:i A', strtotime($team_data['time'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Entry Fee</div>
                    <div class="info-value">₹<?php echo number_format($team_data['entry_fee'], 2); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Prize Pool</div>
                    <div class="info-value" style="color: var(--primary);">₹<?php echo number_format($team_data['prize_pool'], 2); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value"><?php echo ucfirst($team_data['status']); ?></div>
                </div>
            </div>
            
            <div class="members-section">
                <div class="section-title">
                    <i class="bi bi-people"></i>
                    Team Members (<?php echo count($team_members); ?>)
                </div>
                
                <?php foreach ($team_members as $member): ?>
                    <div class="member-card">
                        <div class="member-info">
                            <div class="member-name">
                                <?php echo htmlspecialchars($member['name']); ?>
                                <?php if ($member['id'] == $user_id): ?>
                                    <span class="badge bg-info" style="font-size: 0.75rem; margin-left: 0.5rem;">You</span>
                                <?php endif; ?>
                            </div>
                            <div class="member-details">
                                <i class="bi bi-controller"></i> IGN: <?php echo htmlspecialchars($member['in_game_name'] ?? 'N/A'); ?>
                                &nbsp;|&nbsp;
                                <i class="bi bi-hash"></i> UID: <?php echo htmlspecialchars($member['game_uid'] ?? 'N/A'); ?>
                            </div>
                        </div>
                        <div class="member-badge <?php echo $member['role'] === 'captain' ? 'captain-badge' : ''; ?>">
                            <?php echo $member['role'] === 'captain' ? '★ Captain' : 'Member'; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="warning-box">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <strong>Warning:</strong> If you leave this team, the entire team registration will be cancelled and all members will be removed from the tournament.
                <?php if ($team_data['entry_fee'] > 0): ?>
                    The captain will receive a full refund of ₹<?php echo number_format($team_data['entry_fee'], 2); ?>.
                <?php endif; ?>
            </div>
            
            <div class="action-buttons">
                <a href="player_dashboard.php" class="btn btn-back">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
                <button class="btn btn-leave leave-team-btn" data-registration-id="<?php echo $team_data['registration_id']; ?>" data-tournament-title="<?php echo htmlspecialchars($team_data['title']); ?>" data-team-name="<?php echo htmlspecialchars($team_data['team_name']); ?>">
                    <i class="bi bi-box-arrow-left"></i> Leave Team
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.leave-team-btn').addEventListener('click', async function() {
            const registrationId = this.dataset.registrationId;
            const tournamentTitle = this.dataset.tournamentTitle;
            const teamName = this.dataset.teamName;
            
            if (!confirm(`Are you sure you want to leave the team "${teamName}" for "${tournamentTitle}"?\n\n⚠️ WARNING: Leaving will CANCEL the ENTIRE team registration!\n\nAll team members will be removed from the tournament.`)) {
                return;
            }
            
            // Second confirmation for safety
            if (!confirm('This action cannot be undone. Are you absolutely sure?')) {
                return;
            }
            
            try {
                const response = await fetch('../api/leave_team.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `registration_id=${registrationId}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    window.location.href = 'player_dashboard.php';
                } else {
                    alert('Error: ' + (data.error || 'Failed to leave team'));
                }
            } catch (error) {
                alert('Error: Failed to connect to server');
            }
        });
    </script>
</body>
</html>
