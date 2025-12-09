<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Audio Sharing Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">Mega<span>Dj</span></a>
            
            <div class="search-bar">
                <input type="text" placeholder="Search audio, creators...">
                <button>Search</button>
            </div>
            
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="trending.php">Trending</a>
                
                <?php if (isLoggedIn()): ?>
                    <a href="upload.php">Upload</a>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="profile.php?user_id=<?php echo getCurrentUserId(); ?>">Profile</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <div class="container">