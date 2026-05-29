<?php
require_once 'db.php';

/**
 * Create or update creator profile
 */
function saveCreatorProfile($user_id, $name, $mobile_no, $email, $game_uid = null, $yt_channel_name = null) {
    global $conn;
    
    // Check if creator profile exists
    $check = mysqli_query($conn, "SELECT id FROM creators WHERE user_id = $user_id");
    
    if (mysqli_num_rows($check) > 0) {
        // Update existing profile
        $sql = "UPDATE creators SET name = ?, mobile_no = ?, email = ?, game_uid = ?, yt_channel_name = ? WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssssi", $name, $mobile_no, $email, $game_uid, $yt_channel_name, $user_id);
    } else {
        // Insert new profile
        $sql = "INSERT INTO creators (user_id, name, mobile_no, email, game_uid, yt_channel_name) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isssss", $user_id, $name, $mobile_no, $email, $game_uid, $yt_channel_name);
    }
    
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}

/**
 * Get creator profile by user_id
 */
function getCreatorProfile($user_id) {
    global $conn;
    
    $sql = "SELECT * FROM creators WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $profile = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    return $profile;
}

/**
 * Get all creators
 */
function getAllCreators() {
    global $conn;
    
    $sql = "SELECT c.*, u.joined_at as user_joined_at 
            FROM creators c 
            JOIN users u ON c.user_id = u.id 
            ORDER BY c.created_at DESC";
    
    $result = mysqli_query($conn, $sql);
    $creators = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $creators[] = $row;
    }
    
    return $creators;
}

/**
 * Delete creator profile
 */
function deleteCreatorProfile($user_id) {
    global $conn;
    
    $sql = "DELETE FROM creators WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}

/**
 * Search creators by name, email, or game UID
 */
function searchCreators($search_term) {
    global $conn;
    
    $search = "%$search_term%";
    $sql = "SELECT c.*, u.joined_at as user_joined_at 
            FROM creators c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.name LIKE ? OR c.email LIKE ? OR c.game_uid LIKE ? OR c.yt_channel_name LIKE ?
            ORDER BY c.created_at DESC";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssss", $search, $search, $search, $search);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $creators = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $creators[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    return $creators;
}
?>
