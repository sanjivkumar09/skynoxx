<?php
session_start();
require_once '../src/db.php';

// Only admins allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../src/login.php');
    exit();
}

// Get creator ID
if (!isset($_GET['creator_id'])) {
    header('Location: admin_dashboard.php');
    exit();
}

$creator_id = (int)$_GET['creator_id'];

// Fetch user basic info
$stmt = $conn->prepare("SELECT id, name, email, phone FROM users WHERE id = ? AND role = 'creator' LIMIT 1");
$stmt->bind_param('i', $creator_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    $_SESSION['error'] = "Creator not found.";
    header('Location: admin_dashboard.php');
    exit();
}

// Fetch creator profile
$profile_stmt = $conn->prepare("SELECT name as creator_name, mobile_no, email as creator_email, game_uid, yt_channel_name, yt_channel_link FROM creators WHERE user_id = ? LIMIT 1");
$profile_stmt->bind_param('i', $creator_id);
$profile_stmt->execute();
$profile_result = $profile_stmt->get_result();
$profile = $profile_result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $creator_name = trim($_POST['creator_name']);
    $mobile_no = trim($_POST['mobile_no']);
    $creator_email = trim($_POST['creator_email']);
    $game_uid = trim($_POST['game_uid']);
    $yt_channel_name = trim($_POST['yt_channel_name']);
    $yt_channel_link = trim($_POST['yt_channel_link']);
    
    $conn->begin_transaction();
    
    try {
        // Update users table
        $update_user = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
        $update_user->bind_param('sssi', $name, $email, $phone, $creator_id);
        $update_user->execute();
        
        // Update or insert creator profile
        if ($profile) {
            $update_profile = $conn->prepare("UPDATE creators SET name = ?, mobile_no = ?, email = ?, game_uid = ?, yt_channel_name = ?, yt_channel_link = ?, updated_at = NOW() WHERE user_id = ?");
            $update_profile->bind_param('ssssssi', $creator_name, $mobile_no, $creator_email, $game_uid, $yt_channel_name, $yt_channel_link, $creator_id);
            $update_profile->execute();
        } else {
            $insert_profile = $conn->prepare("INSERT INTO creators (user_id, name, mobile_no, email, game_uid, yt_channel_name, yt_channel_link, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $insert_profile->bind_param('issssss', $creator_id, $creator_name, $mobile_no, $creator_email, $game_uid, $yt_channel_name, $yt_channel_link);
            $insert_profile->execute();
        }
        
        $conn->commit();
        $_SESSION['success'] = "Creator profile updated successfully!";
        header('Location: view_creator_profile.php?creator_id=' . $creator_id);
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error updating profile: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Creator Profile - SKYNOXX Admin</title>
    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #ff4655;
            --primary-dark: #e03e4c;
            --secondary: #0f1923;
            --text: #ece8e1;
            --text-muted: #b8b3ad;
        }
        
        body {
            background: linear-gradient(135deg, #0f1923 0%, #1a2b3c 100%);
            color: var(--text);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .edit-card {
            background: rgba(26, 43, 60, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 2rem;
            backdrop-filter: blur(10px);
        }
        
        .form-label {
            color: var(--text);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text);
            border-radius: 8px;
            padding: 0.75rem 1rem;
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary);
            color: var(--text);
            box-shadow: 0 0 0 0.2rem rgba(255, 70, 85, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            padding: 0.75rem 2rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: var(--text);
            padding: 0.75rem 2rem;
            font-weight: 700;
        }
        
        .page-title {
            color: var(--text);
            font-weight: 800;
            margin-bottom: 2rem;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="page-title">
                    <i class="fas fa-edit me-2"></i>Edit Creator Profile
                </h1>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                        echo htmlspecialchars($_SESSION['error']); 
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <div class="edit-card">
                    <form method="POST" action="">
                        <h3 class="mb-4">
                            <i class="fas fa-user me-2"></i>User Information
                        </h3>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name (User Account) *</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email (User Account) *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        
                        <hr class="my-4">
                        
                        <h3 class="mb-4">
                            <i class="fas fa-trophy me-2"></i>Creator Profile
                        </h3>
                        
                        <div class="mb-3">
                            <label for="creator_name" class="form-label">Creator Display Name</label>
                            <input type="text" class="form-control" id="creator_name" name="creator_name" value="<?php echo htmlspecialchars($profile['creator_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="mobile_no" class="form-label">Mobile Number</label>
                            <input type="tel" class="form-control" id="mobile_no" name="mobile_no" value="<?php echo htmlspecialchars($profile['mobile_no'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="creator_email" class="form-label">Creator Email</label>
                            <input type="email" class="form-control" id="creator_email" name="creator_email" value="<?php echo htmlspecialchars($profile['creator_email'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="game_uid" class="form-label">Game UID</label>
                            <input type="text" class="form-control" id="game_uid" name="game_uid" value="<?php echo htmlspecialchars($profile['game_uid'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="yt_channel_name" class="form-label">YouTube Channel Name</label>
                            <input type="text" class="form-control" id="yt_channel_name" name="yt_channel_name" value="<?php echo htmlspecialchars($profile['yt_channel_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-4">
                            <label for="yt_channel_link" class="form-label">YouTube Channel Link</label>
                            <input type="url" class="form-control" id="yt_channel_link" name="yt_channel_link" value="<?php echo htmlspecialchars($profile['yt_channel_link'] ?? ''); ?>" placeholder="https://youtube.com/@channel">
                        </div>
                        
                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                            <a href="view_creator_profile.php?creator_id=<?php echo $creator_id; ?>" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
