<?php
require_once 'db.php';

// First, get or create a creator user
$email = 'creator@test.com';
$check_user = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email' AND role = 'creator'");

if (mysqli_num_rows($check_user) > 0) {
    $user = mysqli_fetch_assoc($check_user);
    $user_id = $user['id'];
    
    // Check if creator profile exists
    $check_creator = mysqli_query($conn, "SELECT id FROM creators WHERE user_id = $user_id");
    
    if (mysqli_num_rows($check_creator) == 0) {
        // Insert creator profile
        $sql = "INSERT INTO creators (user_id, name, mobile_no, email, game_uid, yt_channel_name) 
                VALUES (?, 'Test Creator', '7777777777', 'creator@test.com', 'FF123456789', 'TestCreatorYT')";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "✅ Creator profile created successfully!<br><br>";
            echo "=================================<br>";
            echo "CREATOR LOGIN CREDENTIALS<br>";
            echo "=================================<br>";
            echo "Role: Creator<br>";
            echo "Email: creator@test.com<br>";
            echo "Game UID: FF123456789<br>";
            echo "=================================<br>";
        } else {
            echo "❌ Error creating creator profile: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "✅ Creator profile already exists!<br><br>";
        echo "=================================<br>";
        echo "CREATOR LOGIN CREDENTIALS<br>";
        echo "=================================<br>";
        echo "Role: Creator<br>";
        echo "Email: creator@test.com<br>";
        echo "Game UID: FF123456789<br>";
        echo "=================================<br>";
    }
} else {
    echo "❌ Creator user not found. Please create a user with role 'creator' first.";
}

mysqli_close($conn);
?>
