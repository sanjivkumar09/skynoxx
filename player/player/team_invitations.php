<?php
session_start();
include '../src/db.php';
include '../src/auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'player') {
    header('Location: ../../src/login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Fetch all pending invitations
$invitations_stmt = $conn->prepare("
    SELECT 
        ti.id as invitation_id,
        ti.tournament_id,
        ti.team_name,
        ti.status,
        ti.created_at,
        t.title as tournament_title,
        t.date,
        t.time,
        t.entry_fee,
        t.prize_pool,
        t.match_type,
        t.map_name,
        u.name as invited_by_name,
        u.email as invited_by_email
    FROM team_invitations ti
    JOIN tournaments t ON ti.tournament_id = t.id
    JOIN users u ON ti.invited_by = u.id
    WHERE ti.invited_user = ?
    ORDER BY 
        CASE ti.status 
            WHEN 'pending' THEN 1 
            WHEN 'accepted' THEN 2 
            WHEN 'rejected' THEN 3 
        END,
        ti.created_at DESC
");
$invitations_stmt->bind_param('i', $user_id);
$invitations_stmt->execute();
$invitations = $invitations_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$invitations_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Invitations - Free Fire Tournament Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        }
        
        .invitation-card {
            background: var(--bg-card);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 2px solid rgba(255, 70, 85, 0.3);
            transition: all 0.3s ease;
        }
        
        .invitation-card:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(255, 70, 85, 0.3);
        }
        
        .invitation-card.pending {
            border-color: rgba(255, 193, 7, 0.5);
        }
        
        .invitation-card.accepted {
            border-color: rgba(40, 167, 69, 0.5);
            opacity: 0.7;
        }
        
        .invitation-card.rejected {
            border-color: rgba(220, 53, 69, 0.5);
            opacity: 0.7;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }
        
        .status-accepted {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }
        
        .status-rejected {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }
        
        .btn-accept {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            font-weight: 600;
        }
        
        .btn-accept:hover {
            background: linear-gradient(135deg, #218838, #1aa179);
            color: white;
        }
        
        .btn-reject {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            color: white;
            font-weight: 600;
        }
        
        .btn-reject:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
            color: white;
        }
        
        .tournament-info {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .info-label {
            color: #aaa;
        }
        
        .info-value {
            color: #fff;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include '../src/includes/navbar.php'; ?>
    
    <div class="container mt-5 pt-5">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4" style="font-family: 'Orbitron', sans-serif; color: var(--primary);">
                    <i class="fas fa-envelope"></i> Team Invitations
                </h1>
                
                <?php if (empty($invitations)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> You don't have any team invitations yet.
                    </div>
                <?php else: ?>
                    <?php foreach ($invitations as $inv): ?>
                        <div class="invitation-card <?php echo strtolower($inv['status']); ?>" data-invitation-id="<?php echo $inv['invitation_id']; ?>">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h4 style="color: var(--primary); font-weight: 700;">
                                        <?php echo htmlspecialchars($inv['tournament_title']); ?>
                                    </h4>
                                    <p class="mb-1">
                                        <strong>Team:</strong> <?php echo htmlspecialchars($inv['team_name']); ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Invited by:</strong> <?php echo htmlspecialchars($inv['invited_by_name']); ?>
                                    </p>
                                    <p class="mb-0 text-muted">
                                        <small><?php echo date('M d, Y g:i A', strtotime($inv['created_at'])); ?></small>
                                    </p>
                                </div>
                                <span class="status-badge status-<?php echo strtolower($inv['status']); ?>">
                                    <?php echo ucfirst($inv['status']); ?>
                                </span>
                            </div>
                            
                            <div class="tournament-info">
                                <div class="info-row">
                                    <span class="info-label">Match Type:</span>
                                    <span class="info-value"><?php echo ucfirst($inv['match_type']); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Map:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($inv['map_name']); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Date & Time:</span>
                                    <span class="info-value"><?php echo date('M d, Y', strtotime($inv['date'])); ?> at <?php echo date('g:i A', strtotime($inv['time'])); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Entry Fee:</span>
                                    <span class="info-value">₹<?php echo number_format($inv['entry_fee'], 2); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Prize Pool:</span>
                                    <span class="info-value" style="color: var(--primary);">₹<?php echo number_format($inv['prize_pool'], 2); ?></span>
                                </div>
                            </div>
                            
                            <?php if ($inv['status'] === 'pending'): ?>
                                <div class="mt-3 d-flex gap-2">
                                    <button class="btn btn-accept flex-fill accept-btn" data-id="<?php echo $inv['invitation_id']; ?>">
                                        <i class="fas fa-check"></i> Accept
                                    </button>
                                    <button class="btn btn-reject flex-fill reject-btn" data-id="<?php echo $inv['invitation_id']; ?>">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                    <a href="join_tournament.php?id=<?php echo $inv['tournament_id']; ?>" class="btn btn-outline-light" style="border-color: var(--primary); color: var(--primary);">
                                        <i class="fas fa-info-circle"></i> View Details
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-kit-id.js" crossorigin="anonymous"></script>
    <script>
        document.querySelectorAll('.accept-btn, .reject-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const invitationId = this.dataset.id;
                const action = this.classList.contains('accept-btn') ? 'accept' : 'reject';
                const card = this.closest('.invitation-card');
                
                if (!confirm(`Are you sure you want to ${action} this invitation?`)) {
                    return;
                }
                
                try {
                    const response = await fetch('../api/team_invitation_response.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `invitation_id=${invitationId}&action=${action}`
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + (data.error || 'Failed to process response'));
                    }
                } catch (error) {
                    alert('Error: Failed to connect to server');
                }
            });
        });
    </script>
</body>
</html>
