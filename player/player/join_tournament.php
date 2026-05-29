<?php
session_start();
include '../src/db.php';
include '../src/auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'player') {
    header('Location: ../../src/login.php');
    exit();
}

// Fetch player's avatar/profile picture for header
$profile_pic = null;
if (isset($_SESSION['user_id'])) {
    $hdr_uid = (int)$_SESSION['user_id'];
    if ($profile_stmt = $conn->prepare("SELECT avatar FROM players_profile WHERE user_id = ? LIMIT 1")) {
        $profile_stmt->bind_param('i', $hdr_uid);
        $profile_stmt->execute();
        $profile_res = $profile_stmt->get_result();
        if ($profile_row = $profile_res->fetch_assoc()) {
            $profile_pic = $profile_row['avatar'];
        }
        $profile_stmt->close();
    }
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = (int)$_SESSION['user_id'];
$tournament_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch tournament
$tournament = null;
if ($tournament_id > 0) {
    if ($st = $conn->prepare("SELECT id, title, description, entry_fee, prize_pool, date, time, status, created_by FROM tournaments WHERE id = ? LIMIT 1")) {
        $st->bind_param('i', $tournament_id);
        $st->execute();
        $res = $st->get_result();
        $tournament = $res->fetch_assoc();
        $st->close();
    }
}

if (!$tournament) {
    header('Location: player_dashboard.php');
    exit();
}

// Check if already registered
$alreadyJoined = false;
if ($chk = $conn->prepare("SELECT id FROM registrations WHERE tournament_id = ? AND player_id = ? LIMIT 1")) {
    $chk->bind_param('ii', $tournament_id, $user_id);
    $chk->execute();
    $r = $chk->get_result();
    $alreadyJoined = (bool)$r->fetch_assoc();
    $chk->close();
}

// Handle join POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tournament_id'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        $tournament_id = (int)$_POST['tournament_id'];
    // Use server-side entry_fee from DB to avoid tampering
    $entry_fee = isset($tournament['entry_fee']) ? (float)$tournament['entry_fee'] : 0.0;

        // Re-check tournament status
        $status = strtolower($tournament['status'] ?? '');
        if ($alreadyJoined) {
            $error = 'You have already joined this tournament.';
        } elseif ($status !== 'upcoming') {
            $error = 'You can only join upcoming tournaments.';
        } else {
            // Deduct and register within a transaction to prevent negative balances
            if ($entry_fee > 0) {
                $conn->begin_transaction();
                try {
                    // Attempt conditional deduction (only if enough balance)
                    if ($upd = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ? AND wallet_balance >= ?")) {
                        $upd->bind_param('dii', $entry_fee, $user_id, $entry_fee);
                        $upd->execute();
                        $affected = $upd->affected_rows;
                        $upd->close();
                    } else {
                        throw new Exception('Failed to prepare wallet update');
                    }
                    if ($affected !== 1) {
                        // Insufficient funds
                        $conn->rollback();
                        $error = 'Insufficient wallet balance. Please add money to your wallet.';
                    } else {
                        // Ensure tournament wallet exists and credit entry fee to it
                        @$conn->query("CREATE TABLE IF NOT EXISTS tournament_wallets (
                            tournament_id INT PRIMARY KEY,
                            balance DECIMAL(12,2) NOT NULL DEFAULT 0,
                            required_prize_total DECIMAL(12,2) NOT NULL DEFAULT 0,
                            prize_distributed_total DECIMAL(12,2) NOT NULL DEFAULT 0,
                            status ENUM('open','settled','cancelled') NOT NULL DEFAULT 'open',
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            CONSTRAINT fk_tw_tournament FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                        if ($iw = $conn->prepare("INSERT IGNORE INTO tournament_wallets (tournament_id, balance, required_prize_total, prize_distributed_total, status) VALUES (?, 0, (SELECT prize_pool FROM tournaments WHERE id = ?), 0, 'open')")) {
                            $iw->bind_param('ii', $tournament_id, $tournament_id);
                            $iw->execute();
                            $iw->close();
                        }
                        if ($tw = $conn->prepare("UPDATE tournament_wallets SET balance = balance + ? WHERE tournament_id = ? AND status = 'open'")) {
                            $tw->bind_param('di', $entry_fee, $tournament_id);
                            $tw->execute();
                            $tw->close();
                        }
                        // Record transactions (player debit only)
                        if ($wt = $conn->prepare("INSERT INTO wallet_transactions (user_id, type, amount, related_user_id, tournament_id, description, status) VALUES (?, 'deduct', ?, ?, ?, ?, 'completed')")) {
                            $wt->bind_param('idiss', $user_id, $entry_fee, $tournament['created_by'], $tournament_id, $tournament['title']);
                            $wt->execute();
                            $wt->close();
                        }
                        // Register player in tournament
                        if ($ins = $conn->prepare("INSERT INTO registrations (tournament_id, player_id, payment_status, joined_at) VALUES (?, ?, 'success', NOW())")) {
                            $ins->bind_param('ii', $tournament_id, $user_id);
                            if ($ins->execute()) {
                                $success = true;
                                $conn->commit();
                            } else {
                                throw new Exception('Failed to register for the tournament.');
                            }
                            $ins->close();
                        } else {
                            throw new Exception('Server error. Try again later.');
                        }
                    }
                } catch (Exception $ex) {
                    $conn->rollback();
                    if (!isset($error)) { $error = 'Server error. Try again later.'; }
                }
            } else {
                // Free tournament: just register
                if ($ins = $conn->prepare("INSERT INTO registrations (tournament_id, player_id, payment_status, joined_at) VALUES (?, ?, 'success', NOW())")) {
                    $ins->bind_param('ii', $tournament_id, $user_id);
                    if ($ins->execute()) {
                        $success = true;
                    } else {
                        $error = 'Failed to register for the tournament.';
                    }
                    $ins->close();
                } else {
                    $error = 'Server error. Try again later.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
        :root { --primary:#ff4655; --primary-dark:#e03e4c; --secondary:#0f1923; --text:#ece8e1; --text-muted:#b8b3ad; }
        .gaming-navbar { background: rgba(15,25,35,0.95); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(255,70,85,0.2); padding: .6rem 0; position: sticky; top: 0; z-index: 1000; }
        .navbar-brand { font-family:'Orbitron',sans-serif; font-weight:800; font-size:1.3rem; color:var(--text); text-transform:uppercase; letter-spacing:1px; display:flex; align-items:center; text-decoration:none; gap:8px; }
        .navbar-brand span { color: var(--primary); }
        .brand-logo-img { width:150px !important; height:40px !important; margin-right:10px !important; border-radius:6px !important; object-fit:contain !important; }
        .ms-auto { display:flex; align-items:center; gap:.75rem; }
        .btn-gaming { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); border: none; color: #fff!important; font-weight: 700; padding: .5rem 1.2rem; border-radius: 4px; font-size: .85rem; text-transform: uppercase; letter-spacing: 0.5px; text-decoration: none; display: inline-block; transition: all 0.3s ease; width: auto !important; line-height: 1.2; }
        .btn-gaming-outline { background: transparent; border: 2px solid var(--primary); color: var(--primary)!important; font-weight: 700; padding: .5rem 1.2rem; border-radius: 4px; font-size: .85rem; text-transform: uppercase; letter-spacing: 0.5px; text-decoration: none; display: inline-block; transition: all 0.3s ease; width: auto !important; line-height: 1.2; }
        .btn-gaming:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(255,70,85,0.4); color: #fff!important; }
        .btn-gaming-outline:hover { background: var(--primary); color: #fff!important; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(255,70,85,0.4); }
        .profile-avatar { width: 48px; height: 48px; border-radius: 50% !important; display:inline-flex; align-items:center; justify-content:center; background: linear-gradient(135deg,#ff4655,#e03e4c); color:#fff; font-weight:700; overflow:hidden; }
        .profile-avatar img { width:100%; height:100%; object-fit:cover; display:block; border-radius:50%; }
        .profile-btn { background: transparent; border: none; padding: 0; cursor: pointer; }
        .profile-menu { position:absolute; right:0; top:54px; min-width:260px; display:none; flex-direction:column; gap:.25rem; padding:.5rem; border-radius:8px; box-shadow:0 10px 30px rgba(0,0,0,.4); background:linear-gradient(180deg, rgba(26,43,60,.98), rgba(15,25,35,.98)); border:1px solid rgba(255,255,255,.04); transform-origin:top right; opacity:0; transform:translateY(-6px) scale(.98); transition:opacity 180ms ease, transform 180ms ease; z-index:1100; pointer-events:none; }
        .profile-menu.show { display:flex; opacity:1; transform:translateY(0) scale(1); pointer-events:auto; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg gaming-navbar">
        <div class="container">
            <a class="navbar-brand" href="../../src/index.php">
                <img src="../../assets/images/logo.svg" alt="SKYNOXX FF Logo" class="brand-logo-img">
            </a>
            <div class="ms-auto">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="../signup.php" class="btn btn-gaming-outline me-2">Sign Up</a>
                    <a href="../login.php" class="btn btn-gaming">Login</a>
                <?php else:
                    $role = $_SESSION['role'] ?? '';
                    $user_name = $_SESSION['user_name'] ?? '';
                    $initials = '';
                    if ($user_name) {
                        $parts = explode(' ', trim($user_name));
                        $initials = strtoupper(substr($parts[0],0,1) . (isset($parts[1]) ? substr($parts[1],0,1) : ''));
                    }
                    if ($role === 'admin') {
                        $dash = '../admin/admin_dashboard.php';
                    }
                ?>
                    <div class="position-relative">
                        <button id="profileBtn" class="profile-btn" type="button" aria-haspopup="true" aria-expanded="false">
                            <?php if (!empty($profile_pic) && file_exists('../' . $profile_pic)): ?>
                                <span class="profile-avatar" style="width:48px;height:48px;"><img src="../<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile"></span>
                            <?php else: ?>
                                <span class="profile-avatar" style="width:48px;height:48px;font-size:16px;" role="img" aria-label="User avatar"><?php echo htmlspecialchars($initials ?: 'U'); ?></span>
                            <?php endif; ?>
                        </button>
                        <div id="profileMenu" class="profile-menu">
                            <div class="profile-header">
                                <div class="d-flex align-items-center gap-2 px-2">
                                    <div class="profile-avatar" style="width:48px;height:48px;font-size:16px;"><?php echo htmlspecialchars($initials ?: 'U'); ?></div>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($user_name ?: 'User'); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($role); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group list-group-flush mt-2">
                                <a href="../player/profile_details.php" class="list-group-item list-group-item-action">View all details</a>
                                <a href="../../src/change_password.php" data-change-password="1" class="list-group-item list-group-item-action">Change password</a>
                                <a href="../../src/logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <script src="../../assets/js/header.js"></script>

    <div class="container my-4">
        <h1 class="mb-3">Join Tournament: <?php echo htmlspecialchars($tournament['title']); ?></h1>
        <p class="text-muted"><?php echo nl2br(htmlspecialchars($tournament['description'])); ?></p>
        <p><strong>Entry Fee:</strong> ₹<?php echo htmlspecialchars($tournament['entry_fee']); ?></p>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

                <?php if ($alreadyJoined): ?>
            <div class="alert alert-info">You have already joined this tournament.</div>
        <?php elseif (strtolower($tournament['status']) !== 'upcoming'): ?>
            <div class="alert alert-warning">This tournament is not open for joining.</div>
        <?php else: ?>
                        <form action="" method="POST" class="mt-3" id="playerJoinForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="tournament_id" value="<?php echo htmlspecialchars($tournament['id']); ?>">
                <input type="hidden" name="entry_fee" value="<?php echo htmlspecialchars($tournament['entry_fee']); ?>">
                                <button type="submit" class="btn btn-primary">Join Tournament</button>
            </form>
        <?php endif; ?>
    </div>

    <?php include '../src/includes/footer.php'; ?>
        <?php if (!empty($success)): ?>
        <!-- Success Modal -->
        <div class="modal fade" id="joinSuccessModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="bi bi-check-circle-fill me-2"></i>Registration Successful</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        You have successfully registered for "<?php echo htmlspecialchars($tournament['title']); ?>".
                    </div>
                    <div class="modal-footer">
                        <a href="player_dashboard.php" class="btn btn-success">Go to Dashboard</a>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
        // Confirm before submitting
        document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('playerJoinForm');
                if (form) {
                        form.addEventListener('submit', function(e) {
                                const title = <?php echo json_encode($tournament['title']); ?>;
                                const fee = <?php echo json_encode((float)$tournament['entry_fee']); ?>;
                                const message = `Confirm registration for "${title}"${fee>0?` with entry fee ₹${fee.toFixed(2)}`:''}?`;
                                if (!window.confirm(message)) {
                                        e.preventDefault();
                                        e.stopPropagation();
                                }
                        });
                }
                // Auto-show success modal
                <?php if (!empty($success)): ?>
                const modal = new bootstrap.Modal(document.getElementById('joinSuccessModal'));
                modal.show();
                <?php endif; ?>
        });
        </script>
</body>
</html>