<?php
require_once 'db.php';

// Admin credentials
$name = 'Admin User';
$email = 'admin@freefire.com';
$phone = '9999999999';
$role = 'admin';
$password = password_hash('admin123', PASSWORD_DEFAULT);

// Check if admin already exists
$check = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
if (mysqli_num_rows($check) > 0) {
    echo "Admin user already exists!<br>";
    echo "Email: admin@freefire.com<br>";
    echo "Password: admin123<br>";
} else {
    // Insert admin user
    $sql = "INSERT INTO users (name, email, phone, role, password) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $phone, $role, $password);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "✅ Admin user created successfully!<br><br>";
        echo "=================================<br>";
        echo "ADMIN LOGIN CREDENTIALS<br>";
        echo "=================================<br>";
        echo "Email: admin@freefire.com<br>";
        echo "Password: admin123<br>";
        echo "=================================<br><br>";
        echo "You can now login at: <a href='login.php'>Login Page</a>";
    } else {
        echo "❌ Error creating admin user: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
}

// Also create test player and creator
$users = [
    ['Test Player', 'player@test.com', '8888888888', 'player'],
    ['Test Creator', 'creator@test.com', '7777777777', 'creator']
];

foreach ($users as $user) {
    $check = mysqli_query($conn, "SELECT * FROM users WHERE email = '{$user[1]}'");
    if (mysqli_num_rows($check) == 0) {
        $pass = password_hash('admin123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (name, email, phone, role, password) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssss", $user[0], $user[1], $user[2], $user[3], $pass);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "✅ {$user[3]} created: {$user[1]} / admin123<br>";
        }
        mysqli_stmt_close($stmt);
    }
}

mysqli_close($conn);
?>
