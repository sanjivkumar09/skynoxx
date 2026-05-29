<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Free Fire Tournament Platform</title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #ff4655;
            --primary-dark: #e03e4c;
            --secondary: #0f1923;
            --accent: #1a2b3c;
            --accent-light: #2a3b4c;
            --text: #ece8e1;
            --text-muted: #b8b3ad;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, var(--secondary) 0%, #0a0f17 100%);
            color: var(--text);
            min-height: 100vh;
        }
        
        .gaming-navbar {
            background: rgba(15, 25, 35, 0.85);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 70, 85, 0.2);
            padding: 0.5rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .gaming-navbar .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .gaming-navbar .navbar-nav {
            align-items: center;
            flex-direction: row;
            margin: 0;
        }
        
        .gaming-navbar .navbar-collapse {
            align-items: center;
            justify-content: flex-end;
        }
        
        .gaming-navbar .nav-item {
            display: inline-flex;
            align-items: center;
            margin: 0;
        }
        
        .navbar-brand {
            font-family: 'Orbitron', sans-serif;
            font-weight: 800;
            font-size: 1.3rem;
            color: var(--text);
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
        }
        
        .navbar-brand span {
            color: var(--primary);
        }
        
        .brand-logo-img {
            width: 40px;
            height: 40px;
            margin-right: 10px;
            border-radius: 6px;
            object-fit: contain;
        }
        
        .nav-link {
            color: var(--text-muted);
            font-weight: 500;
            margin: 0 0.3rem;
            padding: 0.4rem 0.6rem !important;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .nav-link:hover, .nav-link.active {
            color: var(--text);
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.3s ease;
        }
        
        .nav-link:hover::after, .nav-link.active::after {
            width: 100%;
        }
        
        .btn-gaming {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.4rem 1rem;
            border-radius: 4px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 70, 85, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
        }
        
        .btn-gaming:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 70, 85, 0.4);
            color: white;
        }
        
        .btn-gaming-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            font-weight: 600;
            padding: 0.4rem 1rem;
            border-radius: 4px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
        }
        
        .btn-gaming-outline:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 70, 85, 0.4);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg gaming-navbar">
        <div class="container">
            <a class="navbar-brand" href="../login.php">
                <img src="../assets/images/SKYNOXX.png" alt="SKYNOXX Logo" class="brand-logo-img">
                Free<span>Fire</span>
            </a>
            <div class="ms-auto">
                <a href="../signup.php" class="btn btn-gaming-outline me-2">Sign Up</a>
                <a href="../login.php" class="btn btn-gaming">Login</a>
            </div>
        </div>
    </nav>