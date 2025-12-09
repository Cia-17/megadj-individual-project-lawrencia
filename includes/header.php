<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/assets/images/favicon.ico">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="<?php echo SITE_URL; ?>/index.php" class="logo">
                Mega<span>Dj</span>
            </a>
            
            
            <div class="search-bar">
                <form action="<?php echo SITE_URL; ?>/pages/search.php" method="GET" class="search-form-nav">
                    <input type="text" name="q" placeholder="Search users, audio, genres..." 
                        value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                    <button type="submit">search 🔍</button>
                </form>
            </div>
            
            <div class="nav-links">
                <a href="<?php echo SITE_URL; ?>/index.php">Home</a>
                 <a href="<?php echo SITE_URL; ?>/pages/browse-users.php">Browse Users</a>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo SITE_URL; ?>/pages/upload.php">Upload</a>
                    <a href="<?php echo SITE_URL; ?>/pages/dashboard.php">Dashboard</a>
                    <a href="<?php echo SITE_URL; ?>/pages/profile.php?id=<?php echo $_SESSION['user_id']; ?>">
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </a>
                    <a href="<?php echo SITE_URL; ?>/pages/logout.php" class="logout-link">Logout</a>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/pages/trending.php">Trending</a>
                    <a href="<?php echo SITE_URL; ?>/pages/login.php">Login</a>
                    <a href="<?php echo SITE_URL; ?>/pages/register.php" class="btn-register">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
<div class="container">