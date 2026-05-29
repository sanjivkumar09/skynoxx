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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Details - Free Fire Tournament Platform</title>
    <link rel="stylesheet" href="../../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/mobile-responsive.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #ff4655;
            --primary-dark: #e03e4c;
            --secondary: #0f1923;
            --accent: #00d4ff;
            --success: #00ff88;
            --warning: #ffd700;
            --text: #ece8e1;
            --text-muted: #b8b3ad;
            --card-bg: rgba(26, 43, 60, 0.85);
            --card-border: rgba(255, 255, 255, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #0f1923 0%, #0a0f17 50%, #1a0f1f 100%);
            color: var(--text);
            min-height: 100vh;
            background-attachment: fixed;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(255, 70, 85, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(0, 212, 255, 0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }
        
        .gaming-navbar {
            background: rgba(15, 25, 35, 0.98);
            backdrop-filter: blur(20px);
            border-bottom: 2px solid rgba(255, 70, 85, 0.4);
            padding: 0.75rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
        }
        
        .gaming-navbar .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 1;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        
        .navbar-brand span {
            color: var(--primary);
        }
        
        .brand-logo-img {
            width: 150px !important;
            height: 40px !important;
            margin-right: 10px !important;
            border-radius: 6px !important;
            object-fit: contain !important;
        }
        
        .ms-auto {
            margin-left: auto!important;
            display: flex;
            align-items: center;
        }
        
        .profile-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%!important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            color: #fff;
            background: linear-gradient(135deg, #ff4655, #e03e4c);
        }
        
        .profile-btn {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
        }
        
        .profile-btn:focus {
            outline: none;
        }
        
        .position-relative {
            position: relative;
        }
        
        .profile-menu {
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            min-width: 260px;
            display: none;
            flex-direction: column;
            gap: .25rem;
            padding: .5rem;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,.4);
            background: linear-gradient(180deg, rgba(26,43,60,.98), rgba(15,25,35,.98));
            border: 1px solid rgba(255,255,255,.04);
            transform-origin: top right;
            opacity: 0;
            transform: translateY(-6px) scale(.98);
            transition: opacity 180ms ease, transform 180ms ease;
            z-index: 1100;
            pointer-events: none;
        }
        
        .profile-menu.show {
            display: flex;
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }
        
        .profile-header {
            padding: 1rem;
            background: rgba(102,126,234,0.08);
            border-bottom: 1px solid rgba(102,126,234,0.15);
            border-radius: 6px 6px 0 0;
        }
        
        .profile-menu .list-group-item {
            background: transparent;
            border: none;
            padding: 0.75rem 1rem;
            color: var(--text);
            transition: all 0.2s ease;
            border-radius: 6px;
        }
        
        .profile-menu .list-group-item:hover {
            background: rgba(255,70,85,0.1);
            color: var(--primary);
        }
        
        /* Profile Page Styles */
        .profile-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 3rem 1.5rem;
            position: relative;
            z-index: 1;
        }
        
        .profile-header-section {
            text-align: center;
            margin-bottom: 3rem;
            animation: fadeInDown 0.6s ease;
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .page-title {
            font-family: 'Orbitron', sans-serif;
            font-weight: 800;
            font-size: 3rem;
            color: var(--text);
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #ff4655, #00d4ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .page-subtitle {
            color: var(--text-muted);
            font-size: 1.1rem;
            font-weight: 400;
        }
        
        .profile-stats-bar {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }
        
        .stat-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }
        
        .stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--accent);
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 2rem;
            animation: fadeInUp 0.6s ease 0.2s both;
        }
        
        .profile-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .profile-main {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .gaming-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(12px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .gaming-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
            transition: left 0.6s ease;
        }
        
        .gaming-card:hover::before {
            left: 100%;
        }
        
        .gaming-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 48px rgba(255, 70, 85, 0.2);
            border-color: rgba(255, 70, 85, 0.3);
        }
        
        .profile-avatar-card {
            text-align: center;
            padding: 2.5rem 1.5rem;
        }
        
        .avatar-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 1.5rem;
        }
        
        .profile-avatar-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary);
            box-shadow: 0 8px 32px rgba(255, 70, 85, 0.4);
            transition: transform 0.3s ease;
        }
        
        .profile-avatar-large:hover {
            transform: scale(1.05);
        }
        
        .avatar-status {
            position: absolute;
            bottom: 8px;
            right: 8px;
            width: 20px;
            height: 20px;
            background: var(--success);
            border: 3px solid var(--card-bg);
            border-radius: 50%;
            box-shadow: 0 0 16px var(--success);
        }
        
        .profile-name {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.5rem;
        }
        
        .profile-role {
            display: inline-block;
            padding: 0.4rem 1rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1rem;
        }
        
        .profile-info-list {
            list-style: none;
            padding: 0;
            margin: 1.5rem 0 0 0;
            text-align: left;
        }
        
        .profile-info-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            transition: background 0.3s ease;
        }
        
        .profile-info-item:hover {
            background: rgba(255, 255, 255, 0.06);
        }
        
        .profile-info-icon {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 70, 85, 0.15);
            border-radius: 8px;
            color: var(--primary);
            margin-right: 1rem;
        }
        
        .profile-info-content {
            flex: 1;
        }
        
        .profile-info-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 0.2rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .profile-info-value {
            font-size: 0.95rem;
            color: var(--text);
            font-weight: 600;
        }
        
        .quick-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }
        
        .quick-action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: var(--text);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .quick-action-btn:hover {
            background: rgba(255, 70, 85, 0.15);
            border-color: var(--primary);
            color: var(--primary);
            transform: translateX(5px);
        }
        
        .card-title {
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            font-size: 1.35rem;
            color: var(--text);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(255, 70, 85, 0.3);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .card-title i {
            font-size: 1.5rem;
            color: var(--primary);
        }
        
        .form-section {
            margin-bottom: 2rem;
        }
        
        .section-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--accent);
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-label::before {
            content: '';
            width: 4px;
            height: 16px;
            background: var(--primary);
            border-radius: 2px;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.6rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-label i {
            color: var(--primary);
            font-size: 0.85rem;
        }
        
        .form-control {
            background: rgba(15, 25, 35, 0.8);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: var(--text);
            padding: 0.85rem 1.2rem;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        
        .form-control:focus {
            background: rgba(15, 25, 35, 0.95);
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(255, 70, 85, 0.15), 0 0 20px rgba(255, 70, 85, 0.2);
            color: var(--text);
            transform: translateY(-2px);
        }
        
        .form-control::placeholder {
            color: var(--text-muted);
            opacity: 0.6;
        }
        
        .form-control:disabled,
        .form-control[readonly] {
            background: rgba(255, 255, 255, 0.03);
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        .btn-gaming {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            color: white;
            padding: 1rem 2.5rem;
            border-radius: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(255, 70, 85, 0.4);
            position: relative;
            overflow: hidden;
            font-size: 1rem;
        }
        
        .btn-gaming::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .btn-gaming:hover::before {
            left: 100%;
        }
        
        .btn-gaming:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 70, 85, 0.5);
            color: white;
        }
        
        .btn-gaming:active {
            transform: translateY(-1px);
        }
        
        .btn-gaming-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            padding: 0.85rem 2rem;
            border-radius: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }
        
        .btn-gaming-outline:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 70, 85, 0.4);
        }
        
        .btn-gaming-secondary {
            background: rgba(0, 212, 255, 0.15);
            border: 2px solid var(--accent);
            color: var(--accent);
            padding: 0.85rem 2rem;
            border-radius: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }
        
        .btn-gaming-secondary:hover {
            background: var(--accent);
            color: var(--secondary);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 212, 255, 0.4);
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideIn 0.4s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .alert i {
            font-size: 1.5rem;
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(46, 204, 113, 0.15), rgba(39, 174, 96, 0.15));
            color: #2ecc71;
            border-left: 4px solid #2ecc71;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.15), rgba(192, 57, 43, 0.15));
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
        }
        
        .alert-info {
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.15), rgba(0, 168, 204, 0.15));
            color: var(--accent);
            border-left: 4px solid var(--accent);
        }
        
        .profile-image-preview {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            object-fit: cover;
            border: 3px solid var(--primary);
            box-shadow: 0 4px 20px rgba(255, 70, 85, 0.3);
            transition: transform 0.3s ease;
        }
        
        .profile-image-preview:hover {
            transform: scale(1.05);
        }
        
        .screenshot-preview {
            max-width: 100%;
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 12px;
            border: 3px solid var(--accent);
            box-shadow: 0 4px 20px rgba(0, 212, 255, 0.3);
            transition: transform 0.3s ease;
        }
        
        .screenshot-preview:hover {
            transform: scale(1.02);
        }
        
        .file-upload-wrapper {
            position: relative;
            overflow: hidden;
            display: block;
            width: 100%;
        }
        
        .file-upload-btn {
            background: rgba(15, 25, 35, 0.8);
            border: 2px dashed rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: var(--text-muted);
            padding: 2rem 1.5rem;
            text-align: center;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
        }
        
        .file-upload-btn i {
            font-size: 2.5rem;
            color: var(--primary);
        }
        
        .file-upload-btn:hover {
            border-color: var(--primary);
            background: rgba(255, 70, 85, 0.05);
            color: var(--primary);
            transform: translateY(-2px);
        }
        
        .file-upload-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .upload-hint {
            display: block;
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
            font-style: italic;
        }
        
        .image-preview-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .preview-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background: var(--success);
            color: var(--secondary);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(0, 255, 136, 0.5);
        }
        
        .divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            margin: 2rem 0;
        }
        
        .completion-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(0, 255, 136, 0.15);
            border: 2px solid var(--success);
            border-radius: 20px;
            color: var(--success);
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .completion-badge.incomplete {
            background: rgba(255, 215, 0, 0.15);
            border-color: var(--warning);
            color: var(--warning);
        }
        
        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-sidebar {
                order: 2;
            }
            
            .profile-main {
                order: 1;
            }
        }
        
        @media (max-width: 768px) {
            .profile-container {
                padding: 1.5rem 1rem;
            }
            
            .gaming-card {
                padding: 1.5rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .profile-stats-bar {
                gap: 1rem;
            }
            
            .stat-item {
                padding: 0.6rem 1rem;
            }
            
            .profile-avatar-large {
                width: 120px;
                height: 120px;
            }
        }
        
        @media (max-width: 576px) {
            .btn-gaming, .btn-gaming-outline, .btn-gaming-secondary {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            
            .profile-stats-bar {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .stat-item {
                width: 100%;
            }
            
            .page-title {
                font-size: 1.75rem;
            }
            
            .card-title {
                font-size: 1.15rem;
            }
        }
    </style>
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
                    <style>
                        .notif-btn{position:relative;background:transparent;border:none;color:#fff;margin-right:12px;cursor:pointer}
                        .notif-btn .bi{font-size:1.4rem}
                        .notif-count{position:absolute;top:-6px;right:-6px;background:#ff4655;color:#fff;border-radius:999px;padding:0 6px;height:18px;min-width:18px;display:none;align-items:center;justify-content:center;font-size:11px;line-height:18px}
                        .notif-menu{position:absolute;right:70px;top:54px;min-width:320px;display:none;flex-direction:column;background:linear-gradient(180deg, rgba(26,43,60,0.98), rgba(15,25,35,0.98));border:1px solid rgba(255,255,255,0.08);border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,0.4);z-index:1100;pointer-events:none;opacity:0;transform:translateY(-6px);transition:opacity .18s ease, transform .18s ease}
                        .notif-menu.show{display:flex;pointer-events:auto;opacity:1;transform:translateY(0)}
                        .notif-header{padding:.6rem .8rem;border-bottom:1px solid rgba(255,255,255,0.06);display:flex;justify-content:space-between;align-items:center}
                        .notif-list{max-height:360px;overflow:auto;display:flex;flex-direction:column}
                        .notif-item{display:block;padding:.6rem .8rem;border-bottom:1px solid rgba(255,255,255,0.04);text-decoration:none;color:#eaeaea}
                        .notif-item:hover{background:rgba(255,70,85,0.06)}
                        .notif-title{font-weight:700;font-size:.95rem}
                        .notif-message{font-size:.82rem;color:#c9c9c9}
                        .notif-meta{font-size:.75rem;color:#999;margin-top:2px}
                        .notif-item.unread .notif-title{color:#fff}
                        .notif-empty{padding:1rem;color:#bbb;text-align:center}
                    </style>
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