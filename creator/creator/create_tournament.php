<?php
session_start();
include '../src/db.php';
include '../src/auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'creator') {
    header('Location: ../../src/login.php');
    exit();
}

// Check if editing an existing tournament
$edit_mode = false;
$edit_tournament = null;
$tournament_id = 0;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $tournament_id = (int)$_GET['edit'];
    $creator_id = (int)$_SESSION['user_id'];
    
    // Fetch tournament ensuring ownership
    $stmt = $conn->prepare("SELECT * FROM tournaments WHERE id = ? AND created_by = ? LIMIT 1");
    $stmt->bind_param('ii', $tournament_id, $creator_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_tournament = $result->fetch_assoc();
    $stmt->close();
    
    if ($edit_tournament) {
        $edit_mode = true;
    } else {
        // Not authorized or tournament doesn't exist
        header('Location: creator_dashboard.php');
        exit();
    }
}

// CSRF token setup
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if this is an edit operation
    if (isset($_POST['tournament_id']) && is_numeric($_POST['tournament_id'])) {
        $tournament_id = (int)$_POST['tournament_id'];
        $edit_mode = true;
    }
    
    // CSRF check
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error_message = 'Invalid request. Please refresh and try again.';
    } else {
        // Sanitize and validate inputs
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $entry_fee = isset($_POST['entry_fee']) ? (float)$_POST['entry_fee'] : 0;
        $prize_pool = isset($_POST['prize_pool']) ? (float)$_POST['prize_pool'] : 0;
        $max_players = isset($_POST['max_players']) ? (int)$_POST['max_players'] : 0;
        $number_of_matches = isset($_POST['number_of_matches']) ? (int)$_POST['number_of_matches'] : 1;
        if ($number_of_matches < 1) $number_of_matches = 1;
        if ($number_of_matches > 5) $number_of_matches = 5;
        
        // Get match_type and normalize it for the database ENUM (solo, duo, squad, clash squad)
        $match_type_raw = trim($_POST['match_type'] ?? '');
        // Convert to lowercase for database ENUM compatibility
        $match_type = strtolower($match_type_raw);
        
        // Debug: Log the match_type conversion
        error_log("Match Type - Raw: '$match_type_raw', Converted: '$match_type'");
        
        $map_name = trim($_POST['map_name'] ?? '');
        $date = trim($_POST['date'] ?? '');
        $time = trim($_POST['time'] ?? '');
        $manual_room = isset($_POST['manual_room']) && $_POST['manual_room'] === '1';
        $room_id = $manual_room ? trim($_POST['room_id'] ?? '') : uniqid('room_');
        $room_password = $manual_room ? trim($_POST['room_password'] ?? '') : bin2hex(random_bytes(4));

        // Collect errors early so upload issues are not lost later
        $errors = [];

        // Banner upload
        $banner_path = null;
        if (isset($_FILES['banner']) && is_array($_FILES['banner'])) {
            $bannerError = $_FILES['banner']['error'];
            $bannerSize  = (int)($_FILES['banner']['size'] ?? 0);
            $bannerName  = $_FILES['banner']['name'] ?? '';
            $tmpName     = $_FILES['banner']['tmp_name'] ?? '';

            // Only handle upload if a file was actually selected
            if ($bannerError !== UPLOAD_ERR_NO_FILE) {
                // Surface PHP upload errors first
                if ($bannerError !== UPLOAD_ERR_OK) {
                    $phpUploadErrors = [
                        UPLOAD_ERR_INI_SIZE   => 'Uploaded file exceeds upload_max_filesize in php.ini',
                        UPLOAD_ERR_FORM_SIZE  => 'Uploaded file exceeds the MAX_FILE_SIZE directive in the form',
                        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
                        UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on server',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk (permissions?)',
                        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload'
                    ];
                    $errors[] = 'Banner upload failed: ' . ($phpUploadErrors[$bannerError] ?? ('Error code ' . $bannerError));
                } else {
                    // Validate size and extension
                    $maxBytes = 5 * 1024 * 1024; // 5 MB
                    if ($bannerSize <= 0) {
                        $errors[] = 'Banner file is empty.';
                    } elseif ($bannerSize > $maxBytes) {
                        $errors[] = 'Banner file is too large. Max 5MB allowed.';
                    } else {
                        $ext = strtolower(pathinfo($bannerName, PATHINFO_EXTENSION));
                        $allowed = ['jpg','jpeg','png','gif','webp'];
                        if (!in_array($ext, $allowed, true)) {
                            $errors[] = 'Invalid banner file type: ' . htmlspecialchars($ext);
                        } else {
                            // Build absolute filesystem path for reliability
                            $upload_dir_fs = rtrim(dirname(__DIR__), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'tournament_banners' . DIRECTORY_SEPARATOR;
                            if (!is_dir($upload_dir_fs)) {
                                @mkdir($upload_dir_fs, 0777, true);
                            }
                            $filename = uniqid('banner_') . '.' . $ext;
                            $target_fs = $upload_dir_fs . $filename;
                            if (!is_uploaded_file($tmpName)) {
                                $errors[] = 'Invalid upload source (tmp file not found).';
                            } elseif (!@move_uploaded_file($tmpName, $target_fs)) {
                                $errors[] = 'Failed to save banner to server. Check folder permissions: ' . htmlspecialchars($upload_dir_fs);
                            } else {
                                // Web path stored in DB
                                $banner_path = 'src/uploads/tournament_banners/' . $filename;
                            }
                        }
                    }
                }
            }
        }

        if ($title === '') $errors[] = 'Title is required';
        if ($description === '') $errors[] = 'Description is required';
        if ($entry_fee < 0) $errors[] = 'Entry fee cannot be negative';
        if ($prize_pool < 0) $errors[] = 'Prize pool cannot be negative';
        if ($max_players < 2) $errors[] = 'Max players must be at least 2';
        if ($match_type === '') $errors[] = 'Match type is required';
        if ($map_name === '') $errors[] = 'Map name is required';
        if ($date === '') $errors[] = 'Date is required';
        if ($time === '') $errors[] = 'Time is required';
        if ($manual_room && ($room_id === '' || $room_password === '')) $errors[] = 'Room ID and Password are required when not auto-generating';

        if (empty($errors)) {
            if ($edit_mode && $tournament_id > 0) {
                // UPDATE existing tournament
                $update_sql = "UPDATE tournaments SET title=?, description=?, entry_fee=?, prize_pool=?, max_players=?, match_type=?, map_name=?, date=?, time=?, room_id=?, room_password=?, number_of_matches=?";
                
                // Add banner to update if a new one was uploaded
                if ($banner_path !== null) {
                    $update_sql .= ", banner=?";
                }
                
                $update_sql .= " WHERE id=? AND created_by=?";
                
                $stmt = $conn->prepare($update_sql);
                if ($stmt) {
                    $created_by = (int)$_SESSION['user_id'];
                    
                    if ($banner_path !== null) {
                        // With banner: title, desc, fee, pool, max, type, map, date, time, room_id, pass, num_matches, banner, id, creator
                        // Types: s s d d i s s s s s s i s i i (15 params total)
                        $types = "ssddissssssisii";
                        $stmt->bind_param($types, $title, $description, $entry_fee, $prize_pool, $max_players, $match_type, $map_name, $date, $time, $room_id, $room_password, $number_of_matches, $banner_path, $tournament_id, $created_by);
                    } else {
                        // Without banner: title, desc, fee, pool, max, type, map, date, time, room_id, pass, num_matches, id, creator
                        // Types: s s d d i s s s s s s i i i (14 params total)
                        $types = "ssddissssssiii";
                        $stmt->bind_param($types, $title, $description, $entry_fee, $prize_pool, $max_players, $match_type, $map_name, $date, $time, $room_id, $room_password, $number_of_matches, $tournament_id, $created_by);
                    }
                    
                    // Debug: log what we're trying to save
                    error_log("UPDATE Tournament ID $tournament_id - match_type value: '$match_type'");
                    
                    if ($stmt->execute()) {
                        $success_message = "Tournament updated successfully! Match type set to: " . htmlspecialchars($match_type);
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        // Don't redirect immediately so we can see the success message
                        // header('Location: creator_dashboard.php');
                        // exit();
                    } else {
                        $error_message = "Error updating tournament: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error_message = "Database error: " . $conn->error;
                }
            } else {
                // INSERT new tournament (with banner column and number_of_matches)
                // 14 placeholders: title, description, entry_fee, prize_pool, max_players, match_type, map_name, date, time, room_id, room_password, number_of_matches, created_by, banner
                
                // Debug: Log match_type before INSERT
                error_log("INSERT Tournament - match_type value: '$match_type' (length: " . strlen($match_type) . ")");
                
                // Initialize match stats columns with defaults
                $default_points = json_encode(['1'=>12,'2'=>10,'3'=>8,'4'=>7,'5'=>6,'6'=>5,'7'=>4,'8'=>3,'9'=>2,'10'=>1,'11'=>1,'12'=>1]);
                
                $stmt = $conn->prepare("INSERT INTO tournaments (title, description, entry_fee, prize_pool, max_players, match_type, map_name, date, time, room_id, room_password, number_of_matches, current_match_number, points_distribution, kill_points, created_by, status, created_at, banner) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, 1.00, ?, 'upcoming', NOW(), ?)");
                if ($stmt) {
                    $created_by = (int)$_SESSION['user_id'];
                    // Types: s     s    d    d     i    s    s    s    s    s     s       i            s               i       s
                    //        title desc fee  pool  max  type map  date time room  pass    num_matches  points_json     creator banner
                    $types = "ssdsissssssisis";
                    $stmt->bind_param($types, $title, $description, $entry_fee, $prize_pool, $max_players, $match_type, $map_name, $date, $time, $room_id, $room_password, $number_of_matches, $default_points, $created_by, $banner_path);
                    if ($stmt->execute()) {
                        $success_message = "Tournament created successfully!";
                        // Ensure tournament wallet table exists and create wallet row
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
                        // Insert wallet row if missing
                        $new_tid = $stmt->insert_id;
                        if ($iw = $conn->prepare("INSERT IGNORE INTO tournament_wallets (tournament_id, balance, required_prize_total, prize_distributed_total, status) VALUES (?, 0, ?, 0, 'open')")) {
                            $reqPrize = (float)$prize_pool;
                            $iw->bind_param('id', $new_tid, $reqPrize);
                            $iw->execute();
                            $iw->close();
                        }
                        // Create notifications tables if not exist
                        @$conn->query("CREATE TABLE IF NOT EXISTS notifications (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            type VARCHAR(50) NOT NULL DEFAULT 'tournament_created',
                            title VARCHAR(255) NOT NULL,
                            message TEXT,
                            tournament_id INT NULL,
                            audience ENUM('all','players','creators','user') NOT NULL DEFAULT 'players',
                            audience_user_id INT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            INDEX idx_created_at (created_at),
                            INDEX idx_audience (audience)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                        @$conn->query("CREATE TABLE IF NOT EXISTS notification_reads (
                            user_id INT PRIMARY KEY,
                            last_read_at DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            CONSTRAINT fk_nr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                        // Insert broadcast notification for players
                        if ($nn = $conn->prepare("INSERT INTO notifications (type, title, message, tournament_id, audience) VALUES ('tournament_created', ?, ?, ?, 'players')")) {
                            $ntitle = 'New Tournament: ' . $title;
                            $nmsg = 'Creator posted a new tournament on ' . $date . ' ' . $time . ' | Entry ₹' . number_format($entry_fee,2) . ' | Prize ₹' . number_format($prize_pool,2);
                            $nn->bind_param('ssi', $ntitle, $nmsg, $new_tid);
                            $nn->execute();
                            $nn->close();
                        }
                        
                        // Send push notification to all players
                        require_once __DIR__ . '/../src/fcm_notification_service.php';
                        $fcmService = new FCMNotificationService();
                        $pushTitle = '🎮 New Tournament!';
                        $pushBody = $title . ' - Entry ₹' . number_format($entry_fee,2) . ' | Prize ₹' . number_format($prize_pool,2);
                        $pushData = [
                            'type' => 'tournament_created',
                            'tournament_id' => (string)$new_tid,
                            'click_action' => 'OPEN_TOURNAMENT'
                        ];
                        $result = $fcmService->sendToRole('player', $pushTitle, $pushBody, $pushData);
                        error_log("Tournament created - Push notifications sent: " . json_encode($result));
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        header('Location: creator_dashboard.php');
                        exit();
                    } else {
                        $error_message = "Error creating tournament: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error_message = "Database error: " . $conn->error;
                }
            }
        } else {
            $error_message = implode('<br>', array_map('htmlspecialchars', $errors));
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/gaming-theme.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_mode ? 'Edit Tournament' : 'Create Tournament'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="animate-fade-in">
    <!-- Header -->
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
                    } elseif ($role === 'creator') {
                        $dash = '../creator/creator_dashboard.php';
                    } else {
                        $dash = '../player/player_dashboard.php';
                    }
                ?>
                    <div class="position-relative">
                        <button id="profileBtn" class="profile-btn" type="button" aria-haspopup="true" aria-expanded="false">
                            <span class="profile-avatar" role="img" aria-label="User avatar"><?php echo htmlspecialchars($initials ?: 'U'); ?></span>
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
                                <a href="creator_profile_details.php" class="list-group-item list-group-item-action">View all details</a>
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
    
    <!-- Main Content -->
    <div class="dashboard-container">
        <div class="page-header">
            <h1 class="page-title"><?php echo $edit_mode ? 'Edit Tournament' : 'Create Tournament'; ?></h1>
            <p class="page-subtitle"><?php echo $edit_mode ? 'Update your tournament details' : 'Set up a new tournament for players to join'; ?></p>
        </div>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success mb-4"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger mb-4"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="tournament_id" value="<?php echo htmlspecialchars($tournament_id); ?>">
            <?php endif; ?>
            
            <!-- Basic Information Card -->
            <div class="form-card">
                <div class="card-header">
                    <h2 class="card-title"><i class="bi bi-info-circle"></i> Basic Information</h2>
                </div>
                <div class="card-body">
                    <div class="form-section">
                        <h3 class="section-title"><i class="bi bi-award"></i> Tournament Details</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label required" for="title">
                                    <i class="bi bi-trophy"></i> Tournament Title
                                </label>
                                <input class="form-control" type="text" id="title" name="title" required value="<?php echo htmlspecialchars($_POST['title'] ?? ($edit_tournament['title'] ?? '')); ?>" placeholder="e.g., Epic Free Fire Showdown">
                            </div>
                            <div class="form-group">
                                <label class="form-label required" for="match_type">
                                    <i class="bi bi-people"></i> Match Type
                                </label>
                                <select class="form-select" id="match_type" name="match_type" required>
                                    <?php 
                                        $mt = $_POST['match_type'] ?? ($edit_tournament['match_type'] ?? '');
                                        // Normalize old values to standard format
                                        $norm = strtolower(preg_replace('/[^a-z]/', '', $mt));
                                        if (in_array($norm, ['cs','clashsquad','clashsquard'], true)) {
                                            $mt = 'Clash Squad';
                                        } elseif ($norm === 'squard') {
                                            $mt = 'Squad';
                                        }
                                    ?>
                                    <option value="" disabled <?php echo $mt === '' ? 'selected' : ''; ?>>Select type</option>
                                    <option value="Solo" <?php echo $mt === 'Solo' ? 'selected' : ''; ?>>Solo</option>
                                    <option value="Duo" <?php echo $mt === 'Duo' ? 'selected' : ''; ?>>Duo</option>
                                    <option value="Squad" <?php echo $mt === 'Squad' ? 'selected' : ''; ?>>Squad</option>
                                    <option value="Clash Squad" <?php echo $mt === 'Clash Squad' ? 'selected' : ''; ?>>Clash Squad</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required" for="description">
                                <i class="bi bi-card-text"></i> Tournament Description
                            </label>
                            <textarea class="form-control" id="description" name="description" rows="4" required placeholder="Describe the tournament rules, scoring system, eligibility, and any special notes..."><?php echo htmlspecialchars($_POST['description'] ?? ($edit_tournament['description'] ?? '')); ?></textarea>
                            <div class="form-text">Include rules, scoring, and important announcements for participants.</div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="section-title"><i class="bi bi-image"></i> Tournament Banner</h3>
                        <div class="form-group">
                            <label class="form-label" for="banner">
                                <i class="bi bi-upload"></i> Upload Banner Image
                            </label>
                            <input class="form-control" type="file" id="banner" name="banner" accept="image/*">
                            <div class="form-text">
                                <?php if ($edit_mode): ?>
                                    Leave empty to keep current banner. Upload new image to replace it.
                                <?php else: ?>
                                    Recommended size: 1200x400px. This will be shown to players.
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Financials Card -->
            <div class="form-card">
                <div class="card-header">
                    <h2 class="card-title"><i class="bi bi-currency-rupee"></i> Financials</h2>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required" for="entry_fee">
                                <i class="bi bi-arrow-down-circle"></i> Entry Fee
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input class="form-control" type="number" id="entry_fee" name="entry_fee" step="0.01" min="0" required value="<?php echo htmlspecialchars($_POST['entry_fee'] ?? ($edit_tournament['entry_fee'] ?? '0')); ?>" placeholder="0.00">
                            </div>
                            <div class="form-text">Enter 0 for free tournaments. Players won't be charged.</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label required" for="prize_pool">
                                <i class="bi bi-arrow-up-circle"></i> Prize Pool
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input class="form-control" type="number" id="prize_pool" name="prize_pool" step="0.01" min="0" required value="<?php echo htmlspecialchars($_POST['prize_pool'] ?? ($edit_tournament['prize_pool'] ?? '0')); ?>" placeholder="0.00">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label required" for="max_players">
                                <i class="bi bi-people-fill"></i> Max Participants
                            </label>
                            <input class="form-control" type="number" id="max_players" name="max_players" min="2" max="1000" required value="<?php echo htmlspecialchars($_POST['max_players'] ?? ($edit_tournament['max_players'] ?? '48')); ?>" placeholder="e.g., 48">
                        </div>
                        <div class="form-group">
                            <label class="form-label required" for="number_of_matches">
                                <i class="bi bi-trophy-fill"></i> Number of Matches
                            </label>
                            <select class="form-select" id="number_of_matches" name="number_of_matches" required>
                                <?php $num_matches = (int)($_POST['number_of_matches'] ?? ($edit_tournament['number_of_matches'] ?? 1)); ?>
                                <option value="1" <?php echo $num_matches == 1 ? 'selected' : ''; ?>>1 Match</option>
                                <option value="2" <?php echo $num_matches == 2 ? 'selected' : ''; ?>>2 Matches</option>
                                <option value="3" <?php echo $num_matches == 3 ? 'selected' : ''; ?>>3 Matches</option>
                                <option value="4" <?php echo $num_matches == 4 ? 'selected' : ''; ?>>4 Matches</option>
                                <option value="5" <?php echo $num_matches == 5 ? 'selected' : ''; ?>>5 Matches</option>
                            </select>
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle"></i> Select the number of matches for this tournament. Points are cumulative across all matches.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Logistics Card -->
            <div class="form-card">
                <div class="card-header">
                    <h2 class="card-title"><i class="bi bi-geo-alt"></i> Logistics</h2>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required" for="map_name">
                                <i class="bi bi-map"></i> Map
                            </label>
                            <?php $map = $_POST['map_name'] ?? ($edit_tournament['map_name'] ?? ''); ?>
                            <select class="form-select" id="map_name" name="map_name" required>
                                <option value="" disabled <?php echo $map === '' ? 'selected' : ''; ?>>Select map</option>
                                <option value="Bermuda" <?php echo $map === 'Bermuda' ? 'selected' : ''; ?>>Bermuda</option>
                                <option value="Kalahari" <?php echo $map === 'Kalahari' ? 'selected' : ''; ?>>Kalahari</option>
                                <option value="Purgatory" <?php echo $map === 'Purgatory' ? 'selected' : ''; ?>>Purgatory</option>
                                <option value="Alpine" <?php echo $map === 'Alpine' ? 'selected' : ''; ?>>Alpine</option>
                                <option value="Nexterra" <?php echo $map === 'Nexterra' ? 'selected' : ''; ?>>Nexterra</option>
                                <option value="Other" <?php echo $map === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label required" for="date">
                                <i class="bi bi-calendar-event"></i> Date
                            </label>
                            <input class="form-control" type="date" id="date" name="date" required value="<?php echo htmlspecialchars($_POST['date'] ?? ($edit_tournament['date'] ?? '')); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label required" for="time">
                                <i class="bi bi-clock"></i> Time
                            </label>
                            <input class="form-control" type="time" id="time" name="time" required value="<?php echo htmlspecialchars($_POST['time'] ?? ($edit_tournament['time'] ?? '')); ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Room Setup Card -->
            <div class="form-card">
                <div class="card-header">
                    <h2 class="card-title"><i class="bi bi-door-open"></i> Room Setup</h2>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <div class="form-check form-switch">
                            <?php $manual = isset($_POST['manual_room']) && $_POST['manual_room'] === '1'; ?>
                            <input class="form-check-input" type="checkbox" id="manual_room" name="manual_room" value="1" <?php echo $manual ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="manual_room">
                                Enter room credentials manually
                            </label>
                        </div>
                        <div class="form-text">Auto-generate for quick setup</div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="room_id">
                                <i class="bi bi-key"></i> Room ID
                            </label>
                            <input class="form-control" type="text" id="room_id" name="room_id" placeholder="Auto-generated if unchecked" value="<?php echo htmlspecialchars($_POST['room_id'] ?? ($edit_tournament['room_id'] ?? '')); ?>" <?php echo $manual ? '' : 'disabled'; ?>>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="room_password">
                                <i class="bi bi-lock"></i> Room Password
                            </label>
                            <input class="form-control" type="text" id="room_password" name="room_password" placeholder="Auto-generated if unchecked" value="<?php echo htmlspecialchars($_POST['room_password'] ?? ($edit_tournament['room_password'] ?? '')); ?>" <?php echo $manual ? '' : 'disabled'; ?>>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="form-card">
                <div class="card-body">
                    <div class="d-flex gap-3 btn-group-mobile">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-check-lg"></i> <?php echo $edit_mode ? 'Update Tournament' : 'Create Tournament'; ?>
                        </button>
                        <a href="creator_dashboard.php" class="btn btn-outline flex-fill text-center">
                            <i class="bi bi-x-lg"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <?php include '../../src/includes/footer.php'; ?>

    <script>
        // Toggle manual room fields enable/disable
        document.addEventListener('DOMContentLoaded', function(){
            const chk = document.getElementById('manual_room');
            const roomId = document.getElementById('room_id');
            const roomPwd = document.getElementById('room_password');
            
            function toggleFields() {
                const enabled = chk.checked;
                roomId.disabled = !enabled;
                roomPwd.disabled = !enabled;
                
                if (enabled) {
                    roomId.placeholder = "Enter Room ID";
                    roomPwd.placeholder = "Enter Room Password";
                } else {
                    roomId.placeholder = "Auto-generated if unchecked";
                    roomPwd.placeholder = "Auto-generated if unchecked";
                }
            }
            
            if (chk) {
                chk.addEventListener('change', toggleFields);
                toggleFields();
            }
            
            // Add focus styles for disabled fields
            const style = document.createElement('style');
            style.textContent = `
                .form-control:disabled {
                    background: rgba(255, 255, 255, 0.02) !important;
                    color: var(--text-muted) !important;
                    cursor: not-allowed;
                }
            `;
            document.head.appendChild(style);
            
            // Profile menu toggle
            const profileBtn = document.getElementById('profileBtn');
            const profileMenu = document.getElementById('profileMenu');
            
            if (profileBtn && profileMenu) {
                profileBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    profileMenu.classList.toggle('show');
                });
                
                // Close menu when clicking outside
                document.addEventListener('click', function() {
                    profileMenu.classList.remove('show');
                });
                
                // Prevent menu from closing when clicking inside it
                profileMenu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        });
    </script>
</body>
</html>