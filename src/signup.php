<?php
session_start();
include('db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = trim(strtolower($_POST['email']));
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $role = 'player'; // Default role for new users

    // Hash the password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user into the database
    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, role, password, joined_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $name, $email, $phone, $role, $hashed_password);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Registration successful! You can now log in.";
        header('Location: login.php');
        exit();
    } else {
        $_SESSION['error'] = "Registration failed. Please try again.";
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Free Fire Tournament Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/gaming-theme.css">
</head>
<body>
    <?php include('includes/header.php'); ?>

    <section class="login-container">
        <div class="login-card">
            <div class="card-header">
                <h1 class="card-title">Create Account</h1>
                <p class="card-subtitle">Join the elite tournament arena today</p>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <form action="signup.php" method="POST" class="needs-validation" novalidate>
                    <div class="form-group mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Enter your full name" required>
                        <div class="invalid-feedback">Please enter your name.</div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                        <div class="invalid-feedback">Please provide a valid email address.</div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" placeholder="Enter your phone number" required>
                        <div class="invalid-feedback">Please enter your phone number.</div>
                    </div>

                    <div class="form-group mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Create password" required>
                        <div class="invalid-feedback">Please enter a password.</div>
                    </div>

                    <button type="submit" class="btn btn-gaming w-100 login-btn">Sign Up to Platform</button>
                </form>

                <div class="divider my-4">
                    <span>Already have an account?</span>
                </div>

                <div class="text-center">
                    <a href="login.php" class="btn btn-gaming-outline w-100">Log In Here</a>
                </div>
            </div>
        </div>
    </section>

    <?php include('includes/footer.php'); ?>
</body>
</html>