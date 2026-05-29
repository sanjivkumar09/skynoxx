<?php
require_once 'db.php';

function login($email, $password) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
    }
    return false;
}

function creatorLogin($email, $game_uid) {
    global $conn;
    
    // Check if creator exists with matching email and game_uid
    $stmt = $conn->prepare("SELECT c.*, u.id as user_id, u.name, u.role 
                            FROM creators c 
                            JOIN users u ON c.user_id = u.id 
                            WHERE c.email = ? AND c.game_uid = ? AND u.role = 'creator'");
    $stmt->bind_param("ss", $email, $game_uid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $creator = $result->fetch_assoc();
        $_SESSION['user_id'] = $creator['user_id'];
        $_SESSION['user_name'] = $creator['name'];
        $_SESSION['user_email'] = $creator['email'];
        $_SESSION['role'] = $creator['role'];
        $_SESSION['creator_id'] = $creator['id'];
        $_SESSION['game_uid'] = $creator['game_uid'];
        $_SESSION['yt_channel'] = $creator['yt_channel_name'];
        return true;
    }
    return false;
}

function adminLogin($email, $password) {
    global $conn;
    
    // Check if admin exists with matching email
    $stmt = $conn->prepare("SELECT a.*, u.id as user_id, u.role 
                            FROM admins a 
                            JOIN users u ON a.user_id = u.id 
                            WHERE a.admin_email = ? AND a.is_active = 1 AND u.role = 'admin'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $admin['admin_password'])) {
            $_SESSION['user_id'] = $admin['user_id'];
            $_SESSION['user_name'] = $admin['admin_name'];
            $_SESSION['user_email'] = $admin['admin_email'];
            $_SESSION['role'] = $admin['role'];
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['access_level'] = $admin['access_level'];
            
            // Update last login
            $update_stmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
            $update_stmt->bind_param("i", $admin['id']);
            $update_stmt->execute();
            $update_stmt->close();
            
            return true;
        }
    }
    return false;
}

function logout() {
    session_start();
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getUserName() {
    return $_SESSION['user_name'] ?? null;
}

function requireRole($role) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== $role) {
        header('Location: ../src/login.php');
        exit();
    }
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../src/login.php');
        exit();
    }
}
?>