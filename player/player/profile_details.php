<?php
session_start();
require_once '../src/db.php';

// Only players allowed here
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../src/login.php');
    exit();
}

// If ?id= is set and user is creator or admin, allow viewing any player profile
$view_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($view_id > 0 && ($_SESSION['role'] === 'creator' || $_SESSION['role'] === 'admin')) {
    $user_id = $view_id;
} else {
    $user_id = $_SESSION['user_id'];
}

// Flag: is an admin viewing someone else's profile? Used to hide password change form
$is_admin_viewing = false;
if ((isset($_SESSION['role']) && $_SESSION['role'] === 'admin') && isset($_SESSION['user_id'])) {
    $is_admin_viewing = ((int)$_SESSION['user_id'] !== (int)$user_id);
}

// Fetch user info
$stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

// If form submitted, update basic details and a profile table if needed
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect inputs
    $name = trim($_POST['name'] ?? '');
    $in_game_name = trim($_POST['in_game_name'] ?? '');
    $game_uid = trim($_POST['game_uid'] ?? '');
    $upi_id = trim($_POST['upi_id'] ?? '');

    // Update users.name
    $u_stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
    $u_stmt->bind_param('si', $name, $user_id);
    if ($u_stmt->execute()) {
        $message = 'Profile updated successfully.';
        $_SESSION['user_name'] = $name;
    } else {
        $message = 'Error updating profile: ' . $conn->error;
    }

    // Ensure players_profile table exists with required columns
    $create_sql = "CREATE TABLE IF NOT EXISTS players_profile (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNIQUE,
        in_game_name VARCHAR(150),
        avatar VARCHAR(255),
        screenshot VARCHAR(255),
        game_uid VARCHAR(100),
        upi_id VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($create_sql);

    // Handle file uploads
    $upload_dir = __DIR__ . '/../src/uploads/players';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $avatar_path = null;
    if (!empty($_FILES['avatar']['name'])) {
        $f = $_FILES['avatar'];
        if ($f['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg','image/png','image/webp','image/jpg'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $f['tmp_name']);
            finfo_close($finfo);
            if (in_array($mime, $allowed) && $f['size'] <= 2 * 1024 * 1024) {
                $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
                $target = $upload_dir . "/avatar_{$user_id}_" . time() . '.' . $ext;
                if (move_uploaded_file($f['tmp_name'], $target)) {
                    $avatar_path = 'src/uploads/players/' . basename($target);
                }
            }
        }
    }

    $screenshot_path = null;
    if (!empty($_FILES['screenshot']['name'])) {
        $f = $_FILES['screenshot'];
        if ($f['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg','image/png','image/webp','image/jpg'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $f['tmp_name']);
            finfo_close($finfo);
            if (in_array($mime, $allowed) && $f['size'] <= 6 * 1024 * 1024) {
                $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
                $target = $upload_dir . "/screenshot_{$user_id}_" . time() . '.' . $ext;
                if (move_uploaded_file($f['tmp_name'], $target)) {
                    $screenshot_path = 'src/uploads/players/' . basename($target);
                }
            }
        }
    }

    // Insert or update players_profile
    $up = $conn->prepare("INSERT INTO players_profile (user_id, in_game_name, avatar, screenshot, game_uid, upi_id) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE in_game_name = VALUES(in_game_name), avatar = COALESCE(VALUES(avatar), avatar), screenshot = COALESCE(VALUES(screenshot), screenshot), game_uid = VALUES(game_uid), upi_id = VALUES(upi_id)");
    // If no new avatar/screenshot uploaded, pass null so COALESCE keeps old value
    $av_param = $avatar_path ?? null;
    $sc_param = $screenshot_path ?? null;
    $up->bind_param('isssss', $user_id, $in_game_name, $av_param, $sc_param, $game_uid, $upi_id);
    $up->execute();
    $up->close();

    $u_stmt->close();
}

// Fetch existing profile for display (ensure players_profile exists)
$profile = [ 'in_game_name' => '', 'avatar' => '', 'screenshot' => '', 'game_uid' => '', 'upi_id' => '' ];

// Ensure table exists (in case not created earlier)
$conn->query("CREATE TABLE IF NOT EXISTS players_profile (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    in_game_name VARCHAR(150),
    avatar VARCHAR(255),
    screenshot VARCHAR(255),
    game_uid VARCHAR(100),
    upi_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$p_stmt = $conn->prepare("SELECT in_game_name, avatar, screenshot, game_uid, upi_id FROM players_profile WHERE user_id = ? LIMIT 1");
$p_stmt->bind_param('i', $user_id);
$p_stmt->execute();
$p_res = $p_stmt->get_result();
if ($p_res && $p_res->num_rows > 0) {
    $profile = $p_res->fetch_assoc();
}
$p_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/gaming-theme.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Details - Free Fire Tournament Platform</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg gaming-navbar">
        <div class="container d-flex align-items-center">
            <a class="navbar-brand" href="../../src/index.php">
                <img src="../../assets/images/logo.svg" alt="SKYNOXX FF Logo" class="brand-logo-img">
            </a>
            <div class="ms-auto d-flex align-items-center">
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
                    
                    // Get avatar image if exists
                    $avatar_url = '';
                    if (!empty($profile['avatar'])) {
                        $avatar_url = '../' . $profile['avatar'];
                    }
                ?>
                    <!-- Notification Bell (players) -->
                    <?php if ($role === 'player'): ?>
                    
                    <button id="notifBell" class="notif-btn" aria-label="Notifications">
                        <i class="bi bi-bell"></i>
                        <span id="notifCount" class="notif-count">0</span>
                    </button>
                    <div id="notifMenu" class="notif-menu">
                        <div class="notif-header">
                            <div class="fw-bold">Notifications</div>
                            <button id="notifMarkAll" class="btn btn-sm btn-gaming-outline">Mark all read</button>
                        </div>
                        <div id="notifList" class="notif-list"></div>
                    </div>
                    <?php endif; ?>

                    <div class="position-relative">
                        <button id="profileBtn" class="profile-btn" type="button" aria-haspopup="true" aria-expanded="false">
                            <?php if ($avatar_url): ?>
                                <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="Avatar" class="profile-avatar" style="object-fit:cover;">
                            <?php else: ?>
                                <span class="profile-avatar" role="img" aria-label="User avatar"><?php echo htmlspecialchars($initials ?: 'U'); ?></span>
                            <?php endif; ?>
                        </button>
                        <div id="profileMenu" class="profile-menu">
                            <div class="profile-header">
                                <div class="d-flex align-items-center gap-2 px-2">
                                    <?php if ($avatar_url): ?>
                                        <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="Avatar" class="profile-avatar" style="width:48px;height:48px;object-fit:cover;">
                                    <?php else: ?>
                                        <div class="profile-avatar" style="width:48px;height:48px;font-size:16px;"><?php echo htmlspecialchars($initials ?: 'U'); ?></div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($user_name ?: 'User'); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($role); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group list-group-flush mt-2">
                                <a href="../player/profile_details.php" class="list-group-item list-group-item-action">View all details</a>
                                <a href="../../src/logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <script src="../../assets/js/header.js"></script>
    <script src="../../assets/js/notifications.js"></script>

    <div class="profile-container">
        <!-- Profile Header Section -->
        <div class="profile-header-section">
            <h1 class="page-title">Player Profile</h1>
            <p class="page-subtitle">Manage your gaming profile and account settings</p>
            
            <!-- Profile Stats Bar -->
            <div class="profile-stats-bar">
                <div class="stat-item">
                    <div class="stat-label">User ID</div>
                    <div class="stat-value">#<?php echo htmlspecialchars($user['id']); ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Account Status</div>
                    <div class="stat-value text-success">
                        <i class="fas fa-check-circle"></i> Active
                    </div>
                </div>
                <?php if (!empty($profile['game_uid'])): ?>
                <div class="stat-item">
                    <div class="stat-label">Game UID</div>
                    <div class="stat-value"><?php echo htmlspecialchars($profile['game_uid']); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <!-- Profile Grid Layout -->
        <div class="profile-grid">
            <!-- Sidebar with Avatar and Quick Info -->
            <div class="profile-sidebar">
                <!-- Avatar Card -->
                <div class="gaming-card profile-avatar-card">
                    <div class="avatar-wrapper">
                        <?php if (!empty($profile['avatar'])): ?>
                            <img src="../<?php echo htmlspecialchars($profile['avatar']); ?>" alt="Profile Avatar" class="profile-avatar-large">
                            <div class="avatar-status"></div>
                        <?php else: 
                            $initials = '';
                            if ($user['name']) {
                                $parts = explode(' ', trim($user['name']));
                                $initials = strtoupper(substr($parts[0],0,1) . (isset($parts[1]) ? substr($parts[1],0,1) : ''));
                            }
                        ?>
                            <div class="profile-avatar-large" style="display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg, #ff4655, #e03e4c);font-size:3rem;font-weight:700;">
                                <?php echo htmlspecialchars($initials ?: 'U'); ?>
                            </div>
                            <div class="avatar-status"></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="profile-name"><?php echo htmlspecialchars($user['name']); ?></div>
                    <div class="profile-role">
                        <i class="fas fa-gamepad"></i> <?php echo htmlspecialchars($_SESSION['role'] ?? 'Player'); ?>
                    </div>
                    
                    <?php 
                    $profileComplete = !empty($profile['in_game_name']) && !empty($profile['game_uid']);
                    ?>
                    <div class="completion-badge <?php echo $profileComplete ? '' : 'incomplete'; ?>">
                        <i class="fas <?php echo $profileComplete ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                        <?php echo $profileComplete ? 'Profile Complete' : 'Profile Incomplete'; ?>
                    </div>
                    
                    <!-- Quick Info -->
                    <ul class="profile-info-list">
                        <li class="profile-info-item">
                            <div class="profile-info-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="profile-info-content">
                                <div class="profile-info-label">Email</div>
                                <div class="profile-info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                        </li>
                        <?php if (!empty($profile['in_game_name'])): ?>
                        <li class="profile-info-item">
                            <div class="profile-info-icon">
                                <i class="fas fa-fire"></i>
                            </div>
                            <div class="profile-info-content">
                                <div class="profile-info-label">In-Game Name</div>
                                <div class="profile-info-value"><?php echo htmlspecialchars($profile['in_game_name']); ?></div>
                            </div>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($profile['upi_id'])): ?>
                        <li class="profile-info-item">
                            <div class="profile-info-icon">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div class="profile-info-content">
                                <div class="profile-info-label">UPI ID</div>
                                <div class="profile-info-value"><?php echo htmlspecialchars($profile['upi_id']); ?></div>
                            </div>
                        </li>
                        <?php endif; ?>
                    </ul>
                    
                    <!-- Quick Actions -->
                    <div class="quick-actions">
                        <a href="../player/player_dashboard.php" class="quick-action-btn">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="../player/wallet_dashboard.php" class="quick-action-btn">
                            <i class="fas fa-wallet"></i>
                            <span>My Wallet</span>
                        </a>
                        <a href="player_dashboard.php" class="quick-action-btn">
                            <i class="fas fa-trophy"></i>
                            <span>Tournaments</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="profile-main">
                <!-- Edit Profile Card -->
                <div class="gaming-card">
                    <h3 class="card-title">
                        <i class="fas fa-user-edit"></i>
                        <span>Edit Profile Information</span>
                    </h3>
                    <form method="post" enctype="multipart/form-data">
                        <!-- Basic Information Section -->
                        <div class="form-section">
                            <div class="section-label">
                                <i class="fas fa-id-card"></i>
                                Basic Information
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-hashtag"></i>
                                        User ID
                                    </label>
                                    <input class="form-control" value="<?php echo htmlspecialchars($user['id']); ?>" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-user"></i>
                                        Full Name
                                    </label>
                                    <input name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" placeholder="Enter your full name" required>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Gaming Information Section -->
                        <div class="form-section">
                            <div class="section-label">
                                <i class="fas fa-gamepad"></i>
                                Gaming Information
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-fire"></i>
                                        In-Game Name
                                    </label>
                                    <input name="in_game_name" class="form-control" value="<?php echo htmlspecialchars($profile['in_game_name'] ?? ''); ?>" placeholder="Your Free Fire name">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-id-badge"></i>
                                        Game UID
                                    </label>
                                    <input name="game_uid" class="form-control" value="<?php echo htmlspecialchars($profile['game_uid'] ?? ''); ?>" placeholder="Your Free Fire UID">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment Information Section -->
                        <div class="form-section">
                            <div class="section-label">
                                <i class="fas fa-credit-card"></i>
                                Payment Information
                            </div>
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-wallet"></i>
                                    UPI ID (For Withdrawals)
                                </label>
                                <input name="upi_id" class="form-control" value="<?php echo htmlspecialchars($profile['upi_id'] ?? ''); ?>" placeholder="yourname@upi">
                                <small class="upload-hint">
                                    <i class="fas fa-info-circle"></i> This UPI ID will be used for prize withdrawals
                                </small>
                            </div>
                        </div>
                        
                        <!-- Profile Images Section -->
                        <div class="form-section">
                            <div class="section-label">
                                <i class="fas fa-images"></i>
                                Profile Images
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-image"></i>
                                        Profile Avatar
                                    </label>
                                    <?php if (!empty($profile['avatar'])): ?>
                                        <div class="image-preview-wrapper">
                                            <img src="../<?php echo htmlspecialchars($profile['avatar']); ?>" alt="avatar" class="profile-image-preview">
                                            <div class="preview-badge">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="file-upload-wrapper">
                                        <label class="file-upload-btn">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <span><?php echo empty($profile['avatar']) ? 'Upload Avatar' : 'Change Avatar'; ?></span>
                                            <input type="file" name="avatar" accept="image/*">
                                        </label>
                                    </div>
                                    <small class="upload-hint">Square image recommended, max 2MB</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-desktop"></i>
                                        Game Screenshot
                                    </label>
                                    <?php if (!empty($profile['screenshot'])): ?>
                                        <div class="image-preview-wrapper">
                                            <img src="../<?php echo htmlspecialchars($profile['screenshot']); ?>" alt="screenshot" class="screenshot-preview">
                                            <div class="preview-badge">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="file-upload-wrapper">
                                        <label class="file-upload-btn">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <span><?php echo empty($profile['screenshot']) ? 'Upload Screenshot' : 'Change Screenshot'; ?></span>
                                            <input type="file" name="screenshot" accept="image/*">
                                        </label>
                                    </div>
                                    <small class="upload-hint">Your Free Fire profile screen, max 6MB</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-gaming">
                                <i class="fas fa-save me-2"></i>
                                Save Profile Changes
                            </button>
                        </div>
                    </form>
                </div>
                
                <?php if (!$is_admin_viewing): ?>
                    <!-- Change Password Card -->
                    <div class="gaming-card">
                        <h3 class="card-title">
                            <i class="fas fa-lock"></i>
                            <span>Change Password</span>
                        </h3>
                        <div id="passwordMessage"></div>
                        <form id="changePasswordForm">
                            <div class="form-section">
                                <div class="section-label">
                                    <i class="fas fa-shield-alt"></i>
                                    Security Settings
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-key"></i>
                                            Current Password
                                        </label>
                                        <input type="password" id="currentPassword" class="form-control" placeholder="Enter current password" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-lock"></i>
                                            New Password
                                        </label>
                                        <input type="password" id="newPassword" class="form-control" placeholder="Enter new password" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-check-double"></i>
                                            Confirm Password
                                        </label>
                                        <input type="password" id="confirmPassword" class="form-control" placeholder="Confirm new password" required>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-gaming-secondary">
                                    <i class="fas fa-key me-2"></i>
                                    Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const currentPass = document.getElementById('currentPassword').value;
            const newPass = document.getElementById('newPassword').value;
            const confirmPass = document.getElementById('confirmPassword').value;
            const messageDiv = document.getElementById('passwordMessage');
            
            // Clear previous messages
            messageDiv.innerHTML = '';
            
            // Validation
            if (newPass !== confirmPass) {
                messageDiv.innerHTML = '<div class="alert alert-danger">New passwords do not match!</div>';
                return;
            }
            
            if (newPass.length < 6) {
                messageDiv.innerHTML = '<div class="alert alert-danger">New password must be at least 6 characters long!</div>';
                return;
            }
            
            // Send request
            fetch('../../src/change_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    current_password: currentPass,
                    new_password: newPass,
                    confirm_password: confirmPass
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                    // Clear form
                    document.getElementById('changePasswordForm').reset();
                } else {
                    messageDiv.innerHTML = '<div class="alert alert-danger">' + (data.message || 'Error changing password') + '</div>';
                }
            })
            .catch(error => {
                messageDiv.innerHTML = '<div class="alert alert-danger">Network error. Please try again.</div>';
                console.error('Error:', error);
            });
        });
        
        // File upload button text update with preview
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function() {
                const label = this.closest('.file-upload-wrapper').querySelector('.file-upload-btn span');
                if (this.files && this.files[0]) {
                    const fileName = this.files[0].name;
                    const fileSize = (this.files[0].size / 1024 / 1024).toFixed(2);
                    label.textContent = `${fileName} (${fileSize} MB)`;
                    
                    // Show preview for images
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const wrapper = input.closest('.col-md-6');
                        let preview = wrapper.querySelector('.image-preview-wrapper');
                        if (!preview) {
                            preview = document.createElement('div');
                            preview.className = 'image-preview-wrapper';
                            wrapper.querySelector('label.form-label').after(preview);
                        }
                        
                        const isAvatar = input.name === 'avatar';
                        preview.innerHTML = `
                            <img src="${e.target.result}" alt="preview" class="${isAvatar ? 'profile-image-preview' : 'screenshot-preview'}">
                            <div class="preview-badge">
                                <i class="fas fa-eye"></i>
                            </div>
                        `;
                    };
                    reader.readAsDataURL(this.files[0]);
                } else {
                    label.textContent = input.name === 'avatar' ? 'Upload Avatar' : 'Upload Screenshot';
                }
            });
        });
    </script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    

</body>
</html>