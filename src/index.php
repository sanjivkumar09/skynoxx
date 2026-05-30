<?php
session_start();

// Redirect logged-in users directly to their dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        header('Location: ../admin/admin/admin_dashboard.php');
        exit();
    } elseif ($role === 'creator') {
        header('Location: ../creator/creator/creator_dashboard.php');
        exit();
    } elseif ($role === 'player') {
        header('Location: ../player/player/player_dashboard.php');
        exit();
    }
}

include 'includes/header.php';
include 'db.php';

// 1. Fetch live platform stats
$active_query = "SELECT COUNT(*) FROM tournaments WHERE status IN ('upcoming', 'ongoing')";
$active_result = mysqli_query($conn, $active_query);
$active_count = $active_result ? (mysqli_fetch_row($active_result)[0] ?? 0) : 0;

$pool_query = "SELECT SUM(prize_pool) FROM tournaments WHERE status != 'cancelled'";
$pool_result = mysqli_query($conn, $pool_query);
$total_pool = $pool_result ? (mysqli_fetch_row($pool_result)[0] ?? 0) : 0;
if ($total_pool >= 1000) {
    $total_pool_formatted = '₹' . round($total_pool / 1000, 1) . 'K+';
} else {
    $total_pool_formatted = '₹' . number_format($total_pool);
}

$players_query = "SELECT COUNT(*) FROM users WHERE role = 'player'";
$players_result = mysqli_query($conn, $players_query);
$players_count = $players_result ? (mysqli_fetch_row($players_result)[0] ?? 0) : 0;
if ($players_count >= 100) {
    $players_count_formatted = $players_count . '+';
} else {
    $players_count_formatted = $players_count;
}

$matches_query = "SELECT COUNT(*) FROM tournaments WHERE status = 'completed'";
$matches_result = mysqli_query($conn, $matches_query);
$matches_count = $matches_result ? (mysqli_fetch_row($matches_result)[0] ?? 0) : 0;

// 2. Fetch tournaments (ongoing, upcoming, completed) with slot registration counts
$tournaments_query = "SELECT t.*, 
       (SELECT COUNT(*) FROM registrations WHERE tournament_id = t.id) as registered_count 
FROM tournaments t 
WHERE t.status != 'cancelled' 
ORDER BY FIELD(t.status, 'ongoing', 'upcoming', 'completed'), t.date ASC, t.time ASC 
LIMIT 12";
$tournaments_result = mysqli_query($conn, $tournaments_query);
$tournaments = $tournaments_result ? mysqli_fetch_all($tournaments_result, MYSQLI_ASSOC) : [];

// 3. Fetch top 5 players for the leaderboard
$leaderboard_query = "SELECT u.name, p.in_game_name, p.game_uid, p.avatar, 
       SUM(r.prize_won) as total_earnings, COUNT(r.id) as matches_played 
FROM users u 
JOIN players_profile p ON u.id = p.user_id 
LEFT JOIN registrations r ON u.id = r.player_id 
GROUP BY u.id, p.in_game_name, p.game_uid, p.avatar 
ORDER BY total_earnings DESC, matches_played DESC 
LIMIT 5";
$leaderboard_result = mysqli_query($conn, $leaderboard_query);
$leaderboard = $leaderboard_result ? mysqli_fetch_all($leaderboard_result, MYSQLI_ASSOC) : [];
?>

<!-- Cinematic Hero Section -->
<section class="landing-hero">
    <div class="container">
        <h1 class="hero-glow-title">Skynoxx Arena</h1>
        <p class="landing-hero-subtitle">Compete in high-stakes Free Fire tournaments, climb the ultimate leaderboard, and claim massive prize pools. Proving grounds for the elite.</p>
        
        <div class="hero-cta-buttons">
            <a href="#tournaments" class="btn-cyan"><i class="fas fa-crosshairs"></i> Explore Tournaments</a>
            <a href="signup.php" class="btn-cyan-outline"><i class="fas fa-user-plus"></i> Join the Platform</a>
        </div>
        
        <div class="landing-stats-container">
            <div class="row g-4">
                <div class="col-md-3 col-6">
                    <div class="landing-stat-card">
                        <span class="landing-stat-num highlight-crimson"><?php echo htmlspecialchars($active_count); ?></span>
                        <span class="landing-stat-label">Active Tournaments</span>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="landing-stat-card">
                        <span class="landing-stat-num highlight-cyan"><?php echo htmlspecialchars($total_pool_formatted); ?></span>
                        <span class="landing-stat-label">Prize Pools</span>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="landing-stat-card">
                        <span class="landing-stat-num highlight-crimson"><?php echo htmlspecialchars($players_count_formatted); ?></span>
                        <span class="landing-stat-label">Registered Players</span>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="landing-stat-card">
                        <span class="landing-stat-num highlight-cyan"><?php echo htmlspecialchars($matches_count); ?>+</span>
                        <span class="landing-stat-label">Completed Matches</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Tournaments Section -->
<section class="homepage-tournaments-section" id="tournaments">
    <div class="container">
        <h2 class="section-title">Upcoming Tournaments</h2>
        
        <!-- Filter Tabs -->
        <div class="filter-pills-row">
            <button class="filter-pill active" onclick="filterTournaments('all')">All Tournaments</button>
            <button class="filter-pill" onclick="filterTournaments('free')">Free Entry</button>
            <button class="filter-pill" onclick="filterTournaments('premium')">Premium</button>
            <button class="filter-pill" onclick="filterTournaments('solo')">Solo</button>
            <button class="filter-pill" onclick="filterTournaments('squad')">Squad</button>
            <button class="filter-pill" onclick="filterTournaments('starting-soon')">Starting Soon</button>
        </div>
        
        <div class="row" id="tournaments-grid">
            <?php if (count($tournaments) > 0): ?>
                <?php foreach ($tournaments as $tournament): ?>
                    <?php 
                    // Calculate if tournament is starting in next 48 hours
                    $tournament_time = strtotime($tournament['date'] . ' ' . $tournament['time']);
                    $is_starting_soon = ($tournament_time - time() > 0) && ($tournament_time - time() < 172800) && ($tournament['status'] === 'upcoming');
                    ?>
                    <div class="col-lg-4 col-md-6 mb-4 tournament-card-item" 
                         data-fee="<?php echo ($tournament['entry_fee'] == 0) ? 'free' : 'premium'; ?>" 
                         data-type="<?php echo htmlspecialchars($tournament['match_type']); ?>" 
                         data-starting="<?php echo $is_starting_soon ? 'true' : 'false'; ?>"
                         data-status="<?php echo htmlspecialchars($tournament['status']); ?>">
                        <div class="tournament-card-v2">
                            <div class="card-banner-wrapper">
                                <div class="badge-status badge-<?php echo htmlspecialchars($tournament['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($tournament['status'])); ?>
                                </div>
                                <?php 
                                $banner_src = '../assets/images/esports_hero_bg.png';
                                if (!empty($tournament['banner'])) {
                                    $av_path = $tournament['banner'];
                                    if (strpos($av_path, 'src/') === 0) {
                                        $av_path = substr($av_path, 4);
                                    }
                                    if (file_exists('c:/xampp/htdocs/ff/' . $av_path)) {
                                        $banner_src = '../' . $av_path;
                                    }
                                }
                                ?>
                                <img src="<?php echo htmlspecialchars($banner_src); ?>" alt="<?php echo htmlspecialchars($tournament['title']); ?> Cover" class="card-banner-img">
                                <div class="card-banner-overlay"></div>
                            </div>
                            
                            <div class="card-content-wrapper">
                                <div>
                                    <span class="card-cat-tag"><?php echo strtoupper(htmlspecialchars($tournament['match_type'])); ?> &bull; <?php echo htmlspecialchars($tournament['map_name']); ?></span>
                                    <h3 class="card-title-v2"><?php echo htmlspecialchars($tournament['title']); ?></h3>
                                    
                                    <!-- Slot capacity progress bar -->
                                    <div class="slot-progress-wrapper">
                                        <div class="slot-progress-header">
                                            <span>Slots Filled</span>
                                            <span class="slots-number"><?php echo htmlspecialchars($tournament['registered_count']); ?> / <?php echo htmlspecialchars($tournament['max_players']); ?></span>
                                        </div>
                                        <?php 
                                        $slots_percentage = ($tournament['max_players'] > 0) ? ($tournament['registered_count'] / $tournament['max_players']) * 100 : 0;
                                        $slots_percentage = min($slots_percentage, 100);
                                        $bar_class = ($slots_percentage >= 80) ? '' : 'cyan-fill';
                                        ?>
                                        <div class="progress-bar-v2">
                                            <div class="progress-bar-v2-fill <?php echo $bar_class; ?>" style="width: <?php echo $slots_percentage; ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="card-details-grid">
                                        <div class="card-detail-item">
                                            <span class="card-detail-label">Prize Pool</span>
                                            <span class="card-detail-val val-prize">₹<?php echo number_format($tournament['prize_pool']); ?></span>
                                        </div>
                                        <div class="card-detail-item">
                                            <span class="card-detail-label">Entry Fee</span>
                                            <span class="card-detail-val val-fee <?php echo ($tournament['entry_fee'] > 0) ? 'fee-premium' : ''; ?>">
                                                <?php echo ($tournament['entry_fee'] > 0) ? '₹' . number_format($tournament['entry_fee']) : 'FREE'; ?>
                                            </span>
                                        </div>
                                        <div class="card-detail-item">
                                            <span class="card-detail-label">Date</span>
                                            <span class="card-detail-val"><?php echo date('M j, Y', $tournament_time); ?></span>
                                        </div>
                                        <div class="card-detail-item">
                                            <span class="card-detail-label">Time</span>
                                            <span class="card-detail-val"><?php echo date('g:i A', $tournament_time); ?></span>
                                        </div>
                                    </div>
                                    
                                    <?php if ($tournament['status'] === 'completed'): ?>
                                        <button class="btn btn-gaming-outline card-action-btn" disabled>Tournament Ended</button>
                                    <?php elseif ($tournament['registered_count'] >= $tournament['max_players']): ?>
                                        <button class="btn btn-gaming card-action-btn" disabled>Slots Full</button>
                                    <?php else: ?>
                                        <a href="join_tournament.php?tournament_id=<?php echo $tournament['id']; ?>" class="btn btn-gaming card-action-btn">
                                            <i class="fas fa-gamepad"></i> Join Tournament
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="empty-state" style="background: rgba(21, 34, 46, 0.45); border: 1px dashed rgba(255, 70, 85, 0.2); border-radius: 8px; padding: 4rem 2rem;">
                        <i class="fas fa-gamepad fa-4x mb-3 text-muted"></i>
                        <h4 style="font-family: 'Orbitron', sans-serif; font-weight: 700; color: #ece8e1;">No Active Tournaments</h4>
                        <p class="text-secondary">There are currently no active tournaments. Check back later for new events!</p>
                        <a href="signup.php" class="btn btn-gaming-outline mt-3">Register Now</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Dynamic No Tournaments matches message -->
            <div class="col-12 text-center py-5" id="no-tournaments-msg" style="display: none;">
                <div class="empty-state" style="background: rgba(21, 34, 46, 0.45); border: 1px dashed rgba(255, 70, 85, 0.2); border-radius: 8px; padding: 3rem 1.5rem;">
                    <i class="fas fa-ghost fa-3x mb-3 text-muted"></i>
                    <h4 style="font-family: 'Orbitron', sans-serif; font-weight: 700; color: #ece8e1;">No Matching Arenas</h4>
                    <p class="text-secondary">We couldn't find any tournaments matching the selected criteria. Check other filters!</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Leaderboard Arena -->
<section class="homepage-leaderboard-section">
    <div class="container">
        <h2 class="section-title">Hall of Fame</h2>
        <p class="section-subtitle-center">Meet the highest earning gladiators who dominated the arena and claimed the spoils of victory.</p>
        
        <div class="leaderboard-table-wrapper">
            <div class="table-responsive">
                <table class="leaderboard-table-v2">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 80px;">Rank</th>
                            <th>Gladiator</th>
                            <th>Matches Played</th>
                            <th class="text-end">Total Winnings</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($leaderboard) > 0): ?>
                            <?php $rank = 1; foreach ($leaderboard as $player): ?>
                                <?php 
                                $row_class = ($rank === 1) ? 'top-rank-1' : '';
                                
                                $rank_badge_class = 'rank-other';
                                if ($rank === 1) $rank_badge_class = 'rank-1';
                                elseif ($rank === 2) $rank_badge_class = 'rank-2';
                                elseif ($rank === 3) $rank_badge_class = 'rank-3';
                                
                                $earnings_class = ($rank === 1) ? 'rank-1-earnings' : '';
                                
                                // Resolve profile avatar path
                                $avatar_src = '../assets/images/SKYNOXX.png'; // default fallback
                                if (!empty($player['avatar'])) {
                                    $av_path = $player['avatar'];
                                    // Strip src/ prefix if exists locally
                                    if (strpos($av_path, 'src/') === 0) {
                                        $av_path = substr($av_path, 4);
                                    }
                                    if (file_exists('c:/xampp/htdocs/ff/' . $av_path)) {
                                        $avatar_src = '../' . $av_path;
                                    }
                                }
                                ?>
                                <tr class="leaderboard-row-v2 <?php echo $row_class; ?>">
                                    <td class="text-center">
                                        <span class="rank-badge <?php echo $rank_badge_class; ?>">
                                            <?php if ($rank <= 3): ?>
                                                <i class="fas fa-trophy" style="font-size: 0.85rem;"></i>
                                            <?php else: ?>
                                                <?php echo $rank; ?>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="leaderboard-player-info">
                                            <img src="<?php echo htmlspecialchars($avatar_src); ?>" alt="Player Avatar" class="leaderboard-avatar">
                                            <div>
                                                <span class="leaderboard-player-name"><?php echo htmlspecialchars(!empty($player['in_game_name']) ? $player['in_game_name'] : $player['name']); ?></span>
                                                <span class="leaderboard-player-uid">UID: <?php echo htmlspecialchars($player['game_uid'] ?? 'N/A'); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-weight: 600;"><?php echo htmlspecialchars($player['matches_played']); ?></span>
                                    </td>
                                    <td class="text-end">
                                        <span class="earnings-glowing <?php echo $earnings_class; ?>">
                                            ₹<?php echo number_format($player['total_earnings'], 2); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php $rank++; endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-secondary">
                                    <i class="fas fa-medal mb-2"></i> The Arena is waiting for its first champion. Join a tournament and place in the top ranks!
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Platform Highlights Section -->
<section class="homepage-features-section">
    <div class="container">
        <h2 class="section-title">Platform Highlights</h2>
        <div class="row g-4 mt-2">
            <div class="col-lg-3 col-md-6">
                <div class="feature-card-v2">
                    <div class="feature-icon-v2">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <h4 class="feature-title-v2">Anti-Cheat System</h4>
                    <p class="feature-text-v2">State-of-the-art room screening and manual lobby checks to ensure fair gameplay for every single participant.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="feature-card-v2">
                    <div class="feature-icon-v2">
                        <i class="fas fa-bolt-lightning"></i>
                    </div>
                    <h4 class="feature-title-v2">Instant Payouts</h4>
                    <p class="feature-text-v2">Claim your victory and withdraw your wallet winnings instantly via verified payment processors.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="feature-card-v2">
                    <div class="feature-icon-v2">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <h4 class="feature-title-v2">Premium Tournaments</h4>
                    <p class="feature-text-v2">Daily, weekly, and monthly tournament events featuring professional streams and massive prize pools.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="feature-card-v2">
                    <div class="feature-icon-v2">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h4 class="feature-title-v2">24/7 Support Desk</h4>
                    <p class="feature-text-v2">Have an issue? Connect immediately with support staff to resolve tournament, withdrawal, or lobby questions.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Client Side Filters and Bootstrap scripts -->
<script>
function filterTournaments(category) {
    // 1. Update filter pill classes
    const pills = document.querySelectorAll('.filter-pill');
    pills.forEach(p => p.classList.remove('active'));
    
    // Find the pill that was clicked based on event context
    if (window.event && window.event.currentTarget) {
        window.event.currentTarget.classList.add('active');
    }
    
    // 2. Filter elements
    const cards = document.querySelectorAll('.tournament-card-item');
    let matchCount = 0;
    
    cards.forEach(card => {
        const fee = card.getAttribute('data-fee');       // 'free' or 'premium'
        const type = card.getAttribute('data-type');     // 'solo', 'duo', 'squad'
        const starting = card.getAttribute('data-starting'); // 'true' or 'false'
        
        let show = false;
        if (category === 'all') {
            show = true;
        } else if (category === 'free' && fee === 'free') {
            show = true;
        } else if (category === 'premium' && fee === 'premium') {
            show = true;
        } else if (category === 'solo' && type === 'solo') {
            show = true;
        } else if (category === 'squad' && type === 'squad') {
            show = true;
        } else if (category === 'starting-soon' && starting === 'true') {
            show = true;
        }
        
        if (show) {
            card.style.display = 'block';
            card.style.animation = 'none';
            card.offsetHeight; // trigger reflow
            card.style.animation = 'springPop 0.4s cubic-bezier(0.25, 0.8, 0.25, 1.25) forwards';
            matchCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // 3. Show/hide empty state
    const emptyMsg = document.getElementById('no-tournaments-msg');
    if (matchCount === 0) {
        emptyMsg.style.display = 'block';
    } else {
        emptyMsg.style.display = 'none';
    }
}
</script>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/footer.php'; ?>