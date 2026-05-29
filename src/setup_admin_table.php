<?php
require_once 'db.php';

// Get admin user from users table
$admin_user = mysqli_query($conn, "SELECT * FROM users WHERE email = 'admin@freefire.com' AND role = 'admin'");

if (mysqli_num_rows($admin_user) > 0) {
    $user = mysqli_fetch_assoc($admin_user);
    $user_id = $user['id'];
    
    // Check if admin already exists in admins table
    $check_admin = mysqli_query($conn, "SELECT id FROM admins WHERE user_id = $user_id");
    
    if (mysqli_num_rows($check_admin) == 0) {
        // Insert admin into admins table
        $sql = "INSERT INTO admins (user_id, admin_name, admin_email, admin_password, mobile_no, access_level) 
                VALUES (?, ?, ?, ?, ?, 'super_admin')";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "issss", 
            $user['id'], 
            $user['name'], 
            $user['email'], 
            $user['password'],
            $user['phone']
        );
        
        if (mysqli_stmt_execute($stmt)) {
            echo "✅ Admin added to admins table successfully!<br><br>";
            echo "=================================<br>";
            echo "ADMIN LOGIN CREDENTIALS<br>";
            echo "=================================<br>";
            echo "Role: Admin<br>";
            echo "Email: admin@freefire.com<br>";
            echo "Password: admin123<br>";
            echo "Access Level: Super Admin<br>";
            echo "=================================<br>";
        } else {
            echo "❌ Error adding admin: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "✅ Admin already exists in admins table!<br><br>";
        echo "=================================<br>";
        echo "ADMIN LOGIN CREDENTIALS<br>";
        echo "=================================<br>";
        echo "Role: Admin<br>";
        echo "Email: admin@freefire.com<br>";
        echo "Password: admin123<br>";
        echo "Access Level: Super Admin<br>";
        echo "=================================<br>";
    }
} else {
    echo "❌ Admin user not found in users table. Please run create_admin.php first.";
}

mysqli_close($conn);
?>
