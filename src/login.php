<?php
session_start();
require_once 'db.php';
require_once 'config.php';
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim(strtolower($_POST['email']));
    $password = $_POST['password'];
    $selected_role = $_POST['role'];
    
    $login_result = false;

    // Use different login method based on selected role
    if ($selected_role === 'admin') {
        // For admin, use admin table login
        $login_result = adminLogin($email, $password);
        
        if (!$login_result) {
            $error_message = "Invalid admin credentials. Please check your email and password.";
        }
    } elseif ($selected_role === 'creator') {
        // For creator, use game_uid instead of password
        $game_uid = $_POST['password']; // Password field will contain game UID for creators
        $login_result = creatorLogin($email, $game_uid);
        
        if (!$login_result) {
            $error_message = "Invalid email or game UID. Please check your credentials.";
        }
    } else {
        // For player, use regular email/password login
        $login_result = login($email, $password);
        
        if ($login_result) {
            // Verify the selected role matches the user's actual role
            $actual_role = $_SESSION['role'];
            
            if ($actual_role !== $selected_role) {
                // Role mismatch - logout and show error
                session_unset();
                session_destroy();
                session_start();
                $error_message = "Invalid role selected. Please select the correct role for your account.";
                $login_result = false;
            }
        } else {
            $error_message = "Invalid email or password.";
        }
    }

    // Redirect to appropriate dashboard if login successful
    if ($login_result) {
        $role = $_SESSION['role'];
        
        if ($role === 'admin') {
            header('Location: ../admin/admin/admin_dashboard.php');
        } elseif ($role === 'creator') {
            header('Location: ../creator/creator/creator_dashboard.php');
        } elseif ($role === 'player') {
            header('Location: ../player/player/player_dashboard.php');
        } else {
            header('Location: index.php');
        }
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Free Fire Tournament Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/gaming-theme.css">
</head>
<body>
    <!-- Gaming Navigation -->
    <nav class="navbar navbar-expand-lg gaming-navbar">
        <div class="container">
            <a class="navbar-brand" href="login.php">
                <img src="assets/images/SKYNOXX.png" alt="SKYNOXX Logo" class="brand-logo-img">
                Free<span>Fire</span>
            </a>
            <div class="ms-auto">
                <a href="signup.php" class="btn btn-gaming-outline me-2">Sign Up</a>
                <a href="login.php" class="btn btn-gaming">Login</a>
            </div>
        </div>
    </nav>

    <!-- Login Section -->
    <section class="login-container">
        <div class="login-card">
            <div class="card-header">
                <h1 class="card-title">Tournament Login</h1>
                <p class="card-subtitle">Enter your credentials to access the arena</p>
            </div>
            <div class="card-body">
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php" class="needs-validation" novalidate>
                    <div class="form-group">
                        <label for="role" class="form-label">Select Role</label>
                        <select class="form-control" id="role" name="role" required>
                            <option value="">Choose your role...</option>
                            <option value="player">Player</option>
                            <option value="creator">Creator</option>
                            <option value="admin">Admin</option>
                        </select>
                        <div class="invalid-feedback">Please select a role.</div>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                        <div class="invalid-feedback">Please provide a valid email address.</div>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                            <button type="button" class="password-toggle" id="togglePassword">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <div class="invalid-feedback">Please enter your password.</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-gaming login-btn">Login to Arena</button>
                </form>

                <div class="divider">
                    <span>Or continue with</span>
                </div>

                <div class="social-login">
                    <button type="button" class="social-btn google">
                        <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Google
                    </button>
                    <button type="button" class="social-btn facebook">
                        <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path fill="#4267B2" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        Facebook
                    </button>
                </div>

                <div class="signup-link">
                    Don't have an account? <a href="signup.php">Join the battle</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="gaming-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">Free<span>Fire</span> Tournaments</div>
                <div class="footer-links">
                    <a href="about.php">About</a>
                    <a href="contact.php">Contact</a>
                    <a href="privacy.php">Privacy</a>
                    <a href="terms.php">Terms</a>
                </div>
                <div class="copyright">
                    &copy; 2023 Free Fire Tournament Platform. All rights reserved.
                </div>
            </div>
        </div>
    </footer>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Change password field label based on role selection
        document.getElementById('role').addEventListener('change', function() {
            var passwordLabel = document.querySelector('label[for="password"]');
            var passwordInput = document.getElementById('password');
            var passwordPlaceholder = passwordInput.getAttribute('placeholder');
            
            if (this.value === 'creator') {
                passwordLabel.textContent = 'Game UID';
                passwordInput.setAttribute('placeholder', 'Enter your Free Fire Game UID');
                passwordInput.setAttribute('type', 'text');
            } else {
                passwordLabel.textContent = 'Password';
                passwordInput.setAttribute('placeholder', 'Enter your password');
                passwordInput.setAttribute('type', 'password');
            }
        });

        // Bootstrap form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })();

        // Password visibility toggle
        document.getElementById('togglePassword').addEventListener('click', function () {
            var pw = document.getElementById('password');
            var icon = this.querySelector('svg path');
            var role = document.getElementById('role').value;
            
            // Don't toggle if creator (Game UID should always be visible)
            if (role === 'creator') {
                return;
            }
            
            if (pw.type === 'password') {
                pw.type = 'text';
                // Change icon to "eye closed"
                icon.setAttribute('d', 'M23.271 9.419C21.72 6.893 18.192 2.655 12 2.655S2.28 6.893.729 9.419a4.908 4.908 0 0 0 0 5.162C2.28 17.107 5.808 21.345 12 21.345s9.72-4.238 11.271-6.764a4.908 4.908 0 0 0 0-5.162zm-1.705 4.115C20.234 15.7 17.219 19.345 12 19.345S3.766 15.7 2.434 13.534a2.918 2.918 0 0 1 0-3.068C3.766 8.3 6.781 4.655 12 4.655s8.234 3.645 9.566 5.811a2.918 2.918 0 0 1 0 3.068zM12 7a5 5 0 1 0 5 5 5.006 5.006 0 0 0-5-5zm0 8a3 3 0 1 1 3-3 3 3 0 0 1-3 3z');
            } else {
                pw.type = 'password';
                // Change icon to "eye open"
                icon.setAttribute('d', 'M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z');
            }
        });
    </script>
</body>
</html>