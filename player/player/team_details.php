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
    LEFT JOIN team_registrations tr ON tr.registration_id = r.id AND tr.user_id = ? AND tr.status = 'accepted'
    JOIN users u ON r.player_id = u.id
    WHERE t.id = ? AND (r.player_id = ? OR (tr.user_id = ? AND tr.status = 'accepted'))
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
        <title>Tournament Details</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
    WHERE tr.registration_id = ? AND tr.status = 'accepted'
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
    <title>Team Details - <?php echo htmlspecialchars($team_data['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <style>
        :root {
            --primary: #ff4655;
            --primary-dark: #e03e4c;
            --bg-dark: #0f1923;
            --bg-card: #1a2332;
        }
        
        body {
            background: linear-gradient(135deg, var(--bg-dark) 0%, #1a2332 100%);
            min-height: 100vh;
            color: #fff;
            font-family: 'Montserrat', sans-serif;
        }
        
        .team-container {
            max-width: 900px;
            margin: 80px auto 40px;
            padding: 20px;
        }
        
        .team-card {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 2px solid rgba(255, 70, 85, 0.3);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }
        
        .team-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid rgba(255, 70, 85, 0.2);
        }
        
        .team-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .team-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: #ffd700;
            margin-bottom: 1rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .info-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 10px;
            border: 1px solid rgba(255, 70, 85, 0.2);
        }
        
        .info-label {
            font-size: 0.85rem;
            color: #aaa;
            margin-bottom: 0.3rem;
        }
        
        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #fff;
        }
        
        .members-section {
            margin-top: 2rem;
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .member-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 1.2rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .member-card:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary);
        }
        
        .member-info {
            flex: 1;
        }
        
        .member-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 0.3rem;
        }
        
        .member-details {
            font-size: 0.9rem;
            color: #aaa;
        }
        
        .member-badge {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .captain-badge {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #000;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        .btn-leave {
            flex: 1;
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            color: white;
            padding: 0.8rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-leave:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(220, 53, 69, 0.4);
        }
        
        .btn-back {
            flex: 1;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.8rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-back:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: var(--primary);
            color: white;
        }
        
        .warning-box {
            background: rgba(255, 193, 7, 0.1);
            border: 2px solid rgba(255, 193, 7, 0.5);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1.5rem;
        }
        
        .warning-box i {
            color: #ffc107;
            margin-right: 0.5rem;
        }
    </style>
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
