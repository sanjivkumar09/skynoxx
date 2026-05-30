<?php
session_start();
require_once '../src/db.php';

// Only creators allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'creator') {
    header('Location: ../../src/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user basic info
$stmt = $conn->prepare("SELECT id, name, email, phone FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Ensure creators table has the profile picture columns
$check_columns = "SHOW COLUMNS FROM creators LIKE 'profile_pic'";
$col_result = $conn->query($check_columns);
if ($col_result->num_rows === 0) {
    $conn->query("ALTER TABLE creators ADD COLUMN profile_pic VARCHAR(255) AFTER yt_channel_name");
    $conn->query("ALTER TABLE creators ADD COLUMN game_profile_pic VARCHAR(255) AFTER profile_pic");
}

// Ensure yt_channel_link column exists
$check_yt_link = "SHOW COLUMNS FROM creators LIKE 'yt_channel_link'";
$yt_link_result = $conn->query($check_yt_link);
if ($yt_link_result->num_rows === 0) {
    $conn->query("ALTER TABLE creators ADD COLUMN yt_channel_link VARCHAR(500) AFTER yt_channel_name");
}

// Fetch creator profile details
$profile_stmt = $conn->prepare("SELECT name, mobile_no, email, game_uid, yt_channel_name, yt_channel_link, profile_pic, game_profile_pic, created_at, updated_at FROM creators WHERE user_id = ? LIMIT 1");
$profile_stmt->bind_param('i', $user_id);
$profile_stmt->execute();
$profile_result = $profile_stmt->get_result();
$profile = $profile_result->fetch_assoc();

// Handle form submission for profile picture updates
$message = '';
$message_type = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle file uploads
    $upload_dir = __DIR__ . '/../src/uploads/creators';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $profile_pic_path = null;
    if (!empty($_FILES['profile_pic']['name'])) {
        $f = $_FILES['profile_pic'];
        if ($f['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg','image/png','image/webp','image/jpg'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $f['tmp_name']);
            finfo_close($finfo);
            if (in_array($mime, $allowed) && $f['size'] <= 2 * 1024 * 1024) {
                $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
                $target = $upload_dir . "/profile_{$user_id}_" . time() . '.' . $ext;
                if (move_uploaded_file($f['tmp_name'], $target)) {
                    $profile_pic_path = 'src/uploads/creators/' . basename($target);
                }
            } else {
                $message = 'Profile picture must be an image (JPEG, PNG, WebP) and less than 2MB.';
                $message_type = 'danger';
            }
        }
    }

    $game_profile_pic_path = null;
    if (!empty($_FILES['game_profile_pic']['name'])) {
        $f = $_FILES['game_profile_pic'];
        if ($f['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg','image/png','image/webp','image/jpg'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $f['tmp_name']);
            finfo_close($finfo);
            if (in_array($mime, $allowed) && $f['size'] <= 6 * 1024 * 1024) {
                $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
                $target = $upload_dir . "/game_profile_{$user_id}_" . time() . '.' . $ext;
                if (move_uploaded_file($f['tmp_name'], $target)) {
                    $game_profile_pic_path = 'src/uploads/creators/' . basename($target);
                }
            } else {
                $message = 'Game profile picture must be an image (JPEG, PNG, WebP) and less than 6MB.';
                $message_type = 'danger';
            }
        }
    }

    // Update only the profile pictures
    if ($profile_pic_path || $game_profile_pic_path) {
        $updates = [];
        $params = [];
        $types = '';

        if ($profile_pic_path) {
            $updates[] = "profile_pic = ?";
            $params[] = $profile_pic_path;
            $types .= 's';
        }
        if ($game_profile_pic_path) {
            $updates[] = "game_profile_pic = ?";
            $params[] = $game_profile_pic_path;
            $types .= 's';
        }

        if (!empty($updates)) {
            $params[] = $user_id;
            $types .= 'i';
            
            $update_sql = "UPDATE creators SET " . implode(', ', $updates) . " WHERE user_id = ?";
            $upd_stmt = $conn->prepare($update_sql);
            $upd_stmt->bind_param($types, ...$params);
            
            if ($upd_stmt->execute()) {
                $message = 'Profile pictures updated successfully!';
                $message_type = 'success';
                
                // Refresh profile data
                $profile_stmt = $conn->prepare("SELECT name, mobile_no, email, game_uid, yt_channel_name, yt_channel_link, profile_pic, game_profile_pic, created_at, updated_at FROM creators WHERE user_id = ? LIMIT 1");
                $profile_stmt->bind_param('i', $user_id);
                $profile_stmt->execute();
                $profile_result = $profile_stmt->get_result();
                $profile = $profile_result->fetch_assoc();
            } else {
                $message = 'Error updating profile: ' . $conn->error;
                $message_type = 'danger';
            }
        }
    } else if (empty($message)) {
        $message = 'Please select at least one image to upload.';
        $message_type = 'warning';
    }
}

// Get initials for avatar
$name_parts = explode(' ', $user['name'] ?? 'Creator');
$initials = strtoupper(substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/gaming-theme.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creator Profile Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="profile-header-section">
        <div class="container">
            <div class="d-flex align-items-center gap-3">
                <a href="../../src/index.php">
                    <img src="../../assets/images/logo.svg" alt="SKYNOXX FF Logo" style="height: 48px; width: auto; cursor: pointer; transition: all 0.3s ease;">
                </a>
                <a href="creator_dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <h1 class="page-title text-center">CREATOR PROFILE</h1>
        <p class="text-center text-muted mb-4">View and update your profile information</p>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <!-- Basic Information Card (Read-Only) -->
                <div class="gaming-card">
                    <h3 class="card-title">
                        <i class="fas fa-user-circle"></i>
                        Basic Information
                    </h3>
                    <span class="info-badge">
                        <i class="fas fa-lock me-1"></i>Read Only - Contact Admin to Update
                    </span>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">User ID</label>
                            <input class="form-control" value="#<?php echo htmlspecialchars($user['id']); ?>" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input class="form-control" value="<?php echo htmlspecialchars($profile['name'] ?? $user['name']); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address</label>
                            <input class="form-control" value="<?php echo htmlspecialchars($profile['email'] ?? $user['email']); ?>" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mobile Number</label>
                            <input class="form-control" value="<?php echo htmlspecialchars($profile['mobile_no'] ?? 'Not set'); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Game UID</label>
                            <input class="form-control" value="<?php echo htmlspecialchars($profile['game_uid'] ?? 'Not set'); ?>" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">YouTube Channel Name</label>
                            <input class="form-control" value="<?php echo htmlspecialchars($profile['yt_channel_name'] ?? 'Not set'); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">YouTube Channel Link</label>
                        <?php if (!empty($profile['yt_channel_link'])): ?>
                            <div class="input-group">
                                <input class="form-control" value="<?php echo htmlspecialchars($profile['yt_channel_link']); ?>" readonly>
                                <a href="<?php echo htmlspecialchars($profile['yt_channel_link']); ?>" 
                                   target="_blank" 
                                   class="btn btn-outline-danger">
                                    <i class="fab fa-youtube me-1"></i>Visit Channel
                                </a>
                            </div>
                        <?php else: ?>
                            <input class="form-control" value="Not set" readonly>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Profile Created</label>
                            <input class="form-control" value="<?php echo $profile ? date('M d, Y', strtotime($profile['created_at'])) : 'N/A'; ?>" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Updated</label>
                            <input class="form-control" value="<?php echo $profile ? date('M d, Y h:i A', strtotime($profile['updated_at'])) : 'N/A'; ?>" readonly>
                        </div>
                    </div>
                </div>

                <div class="divider"></div>

                <!-- Profile Pictures Update Form -->
                <div class="gaming-card">
                    <h3 class="card-title">
                        <i class="fas fa-images"></i>
                        Update Profile Pictures
                    </h3>
                    <span class="info-badge">
                        <i class="fas fa-edit me-1"></i>Editable Section
                    </span>
                    
                    <form method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label">
                                    <i class="fas fa-user me-1"></i>Profile Picture
                                </label>
                                <?php if (!empty($profile['profile_pic']) && file_exists('../' . $profile['profile_pic'])): ?>
                                    <div class="text-center mb-3">
                                        <img src="../<?php echo htmlspecialchars($profile['profile_pic']); ?>" 
                                             alt="Profile Picture" 
                                             class="profile-image-preview">
                                    </div>
                                <?php else: ?>
                                    <div class="text-center mb-3">
                                        <div class="avatar-large mx-auto">
                                            <?php echo $initials; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="file-upload-wrapper">
                                    <div class="file-upload-btn">
                                        <i class="fas fa-cloud-upload-alt me-2"></i>Choose Profile Picture
                                    </div>
                                    <input type="file" name="profile_pic" accept="image/*" class="form-control">
                                </div>
                                <small class="text-muted d-block mt-2">
                                    <i class="fas fa-info-circle me-1"></i>Square image recommended, max 2MB (JPEG, PNG, WebP)
                                </small>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <label class="form-label">
                                    <i class="fas fa-gamepad me-1"></i>Game Profile Picture
                                </label>
                                <?php if (!empty($profile['game_profile_pic']) && file_exists('../' . $profile['game_profile_pic'])): ?>
                                    <div class="mb-3">
                                        <img src="../<?php echo htmlspecialchars($profile['game_profile_pic']); ?>" 
                                             alt="Game Profile" 
                                             class="game-screenshot-preview">
                                    </div>
                                <?php else: ?>
                                    <div class="text-center mb-3 p-4" style="background: rgba(255,255,255,0.05); border-radius: 12px; border: 2px dashed var(--border);">
                                        <i class="fas fa-image fa-3x text-muted mb-2"></i>
                                        <p class="text-muted mb-0">No game profile picture uploaded</p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="file-upload-wrapper">
                                    <div class="file-upload-btn">
                                        <i class="fas fa-cloud-upload-alt me-2"></i>Choose Game Profile Picture
                                    </div>
                                    <input type="file" name="game_profile_pic" accept="image/*" class="form-control">
                                </div>
                                <small class="text-muted d-block mt-2">
                                    <i class="fas fa-info-circle me-1"></i>Screenshot of your game profile, max 6MB (JPEG, PNG, WebP)
                                </small>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-gaming px-5">
                                <i class="fas fa-save me-2"></i>Update Profile Pictures
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update file upload button text with selected filename
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function() {
                const fileName = this.files[0] ? this.files[0].name : 'No file chosen';
                const button = this.previousElementSibling;
                if (this.files[0]) {
                    button.innerHTML = `<i class="fas fa-check-circle me-2"></i>${fileName}`;
                    button.style.borderColor = 'var(--primary)';
                    button.style.color = 'var(--primary)';
                } else {
                    button.innerHTML = '<i class="fas fa-cloud-upload-alt me-2"></i>Choose ' + (this.name === 'profile_pic' ? 'Profile Picture' : 'Game Profile Picture');
                }
            });
        });
    </script>
</body>
</html>
