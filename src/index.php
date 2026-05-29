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

// Fetch upcoming tournaments
$query = "SELECT * FROM tournaments WHERE date >= CURDATE() ORDER BY date, time LIMIT 5";
$result = mysqli_query($conn, $query);
$tournaments = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournaments - Free Fire Tournament Platform</title>
    <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #ff4655;
            --primary-dark: #e03e4c;
            --secondary: #0f1923;
            --accent: #1a2b3c;
            --accent-light: #2a3b4c;
            --text: #ece8e1;
            --text-muted: #b8b3ad;
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --info: #3498db;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, var(--secondary) 0%, #0a0f17 100%);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(255, 70, 85, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(26, 43, 60, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(15, 25, 35, 0.8) 0%, transparent 50%);
            z-index: -1;
        }
        
        .tournaments-hero {
            background: linear-gradient(135deg, rgba(255, 70, 85, 0.1) 0%, rgba(15, 25, 35, 0.9) 100%);
            padding: 4rem 0 3rem;
            border-bottom: 1px solid rgba(255, 70, 85, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .tournaments-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" opacity="0.05"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
        }
        
        .hero-title {
            font-family: 'Orbitron', sans-serif;
            font-weight: 800;
            font-size: 3.5rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--text) 0%, var(--primary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .hero-subtitle {
            font-size: 1.2rem;
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto 2rem;
        }
        
        .stats-container {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        .stat-card {
            background: rgba(15, 25, 35, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            min-width: 150px;
            backdrop-filter: blur(10px);
        }
        
        .stat-number {
            font-family: 'Orbitron', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            display: block;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .tournaments-section {
            padding: 3rem 0;
        }
        
        .section-title {
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            font-size: 2rem;
            text-align: center;
            margin-bottom: 3rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary) 0%, transparent 100%);
        }
        
        .tournament-card {
            background: rgba(26, 43, 60, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
            position: relative;
        }
        
        .tournament-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--accent-light) 100%);
        }
        
        .tournament-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
            border-color: rgba(255, 70, 85, 0.3);
        }
        
        .tournament-header {
            background: rgba(15, 25, 35, 0.8);
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .tournament-title {
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            font-size: 1.4rem;
            color: var(--text);
            margin-bottom: 0.5rem;
        }
        
        .tournament-description {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        .tournament-body {
            padding: 1.5rem;
        }
        
        .tournament-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.3rem;
        }
        
        .detail-value {
            font-weight: 600;
            color: var(--text);
            font-size: 1rem;
        }
        
        .prize-pool {
            color: var(--primary);
            font-weight: 700;
        }
        
        .entry-fee {
            color: var(--warning);
            font-weight: 700;
        }
        
        .btn-gaming {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.8rem 1.5rem;
            border-radius: 4px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 70, 85, 0.3);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.9rem;
            width: 100%;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-gaming:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 70, 85, 0.4);
            color: white;
        }
        
        .btn-gaming-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            font-weight: 600;
            padding: 0.8rem 1.5rem;
            border-radius: 4px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.9rem;
            width: 100%;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-gaming-outline:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 70, 85, 0.4);
        }
        
        .tournament-status {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--primary);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            background: rgba(26, 43, 60, 0.5);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .empty-icon {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }
        
        .empty-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-muted);
        }
        
        .empty-text {
            color: var(--text-muted);
            margin-bottom: 2rem;
        }
        
        .filter-section {
            background: rgba(15, 25, 35, 0.7);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .filter-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: var(--text);
        }
        
        .filter-options {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            background: rgba(26, 43, 60, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text);
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .filter-btn.active, .filter-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .tournament-details {
                grid-template-columns: 1fr;
            }
            
            .stats-container {
                gap: 1rem;
            }
            
            .stat-card {
                min-width: 120px;
                padding: 1rem;
            }
            
            .stat-number {
                font-size: 1.5rem;
            }
        }
        
        /* Animation for cards */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .tournament-card {
            animation: fadeInUp 0.5s ease forwards;
        }
        
        .tournament-card:nth-child(1) { animation-delay: 0.1s; }
        .tournament-card:nth-child(2) { animation-delay: 0.2s; }
        .tournament-card:nth-child(3) { animation-delay: 0.3s; }
        .tournament-card:nth-child(4) { animation-delay: 0.4s; }
        .tournament-card:nth-child(5) { animation-delay: 0.5s; }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="tournaments-hero">
        <div class="container">
            <div class="text-center">
                <h1 class="hero-title">Tournament Arena</h1>
                <p class="hero-subtitle">Compete in the most exciting Free Fire tournaments with massive prize pools. Prove your skills and climb the leaderboards!</p>
                
                <div class="stats-container">
                    <div class="stat-card">
                        <span class="stat-number"><?php echo count($tournaments); ?></span>
                        <span class="stat-label">Active Tournaments</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">₹50K+</span>
                        <span class="stat-label">Total Prize Pool</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">500+</span>
                        <span class="stat-label">Active Players</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Tournaments Section -->
    <section class="tournaments-section">
        <div class="container">
            <h2 class="section-title">Upcoming Tournaments</h2>
            
            <!-- Filter Section -->
            <div class="filter-section">
                <h3 class="filter-title">Filter Tournaments</h3>
                <div class="filter-options">
                    <button class="filter-btn active">All Tournaments</button>
                    <button class="filter-btn">Free Entry</button>
                    <button class="filter-btn">Premium</button>
                    <button class="filter-btn">Solo</button>
                    <button class="filter-btn">Squad</button>
                    <button class="filter-btn">Starting Soon</button>
                </div>
            </div>
            
            <div class="row">
                <?php if (count($tournaments) > 0): ?>
                    <?php foreach ($tournaments as $tournament): ?>
                        <div class="col-lg-6 mb-4">
                            <div class="tournament-card">
                                <div class="tournament-status">Upcoming</div>
                                <div class="tournament-header">
                                    <h3 class="tournament-title"><?php echo htmlspecialchars($tournament['title']); ?></h3>
                                    <p class="tournament-description"><?php echo htmlspecialchars($tournament['description']); ?></p>
                                </div>
                                <div class="tournament-body">
                                    <div class="tournament-details">
                                        <div class="detail-item">
                                            <span class="detail-label">Entry Fee</span>
                                            <span class="detail-value entry-fee"><?php echo htmlspecialchars($tournament['entry_fee']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Prize Pool</span>
                                            <span class="detail-value prize-pool"><?php echo htmlspecialchars($tournament['prize_pool']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Date</span>
                                            <span class="detail-value"><?php echo date('M j, Y', strtotime($tournament['date'])); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Time</span>
                                            <span class="detail-value"><?php echo date('g:i A', strtotime($tournament['time'])); ?></span>
                                        </div>
                                    </div>
                                    <a href="join_tournament.php?tournament_id=<?php echo $tournament['id']; ?>" class="btn btn-gaming">Join Tournament</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="empty-state">
                            <div class="empty-icon">🎮</div>
                            <h3 class="empty-title">No Tournaments Available</h3>
                            <p class="empty-text">There are currently no upcoming tournaments. Check back later for new events!</p>
                            <a href="#" class="btn btn-gaming-outline">Notify Me</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const filterBtns = document.querySelectorAll('.filter-btn');
            
            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active class from all buttons
                    filterBtns.forEach(b => b.classList.remove('active'));
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // In a real implementation, you would filter the tournaments here
                    // For now, we'll just log the filter
                    console.log('Filter: ' + this.textContent);
                });
            });
        });
    </script>
</body>
</html>