<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';


$trending_query = "SELECT a.*, u.username, u.profile_pic, 
                   COUNT(DISTINCT l.like_id) as like_count,
                   COUNT(DISTINCT c.comment_id) as comment_count
                   FROM audio a
                   JOIN users u ON a.user_id = u.user_id
                   LEFT JOIN likes l ON a.audio_id = l.audio_id
                   LEFT JOIN comments c ON a.audio_id = c.audio_id
                   GROUP BY a.audio_id
                   ORDER BY a.play_count DESC, like_count DESC
                   LIMIT 10";

$trending_result = $db->query($trending_query);

require_once 'includes/header.php';
?>

<div class="hero">
    <h1>Share Your Sound with the World</h1>
    <p>Upload, discover, and connect through audio on MegaDj</p>
    
    <?php if (!isLoggedIn()): ?>
        <div class="hero-buttons">
            <a href="pages/register.php" class="btn btn-primary">Get Started</a>
        
        </div>
    <?php else: ?>
        <div class="hero-buttons">
            <a href="pages/upload.php" class="btn btn-primary">Upload Audio</a>
            <a href="pages/dashboard.php" class="btn btn-secondary">Go to Dashboard</a>
        </div>
    <?php endif; ?>
</div>

<div class="content">
    <div class="section-header">
        <h2>Trending Now</h2>
        <a href="pages/trending.php" class="view-all">View All</a>
    </div>
    
    <?php if ($trending_result && $trending_result->num_rows > 0): ?>
        <div class="audio-grid">
            <?php while ($audio = $trending_result->fetch_assoc()): ?>
                <div class="audio-card">
                    <div class="audio-cover">
                        <img src="uploads/covers/<?php echo htmlspecialchars($audio['cover_image']); ?>" 
                             alt="<?php echo htmlspecialchars($audio['title']); ?>">
                        <div class="play-overlay">
                            <span>▶</span>
                        </div>
                    </div>
                    <div class="audio-info">
                        <h3>
                            <a href="pages/audio-player.php?id=<?php echo $audio['audio_id']; ?>">
                                <?php echo htmlspecialchars($audio['title']); ?>
                            </a>
                        </h3>
                        <p class="audio-creator">
                            <img src="assets/images/<?php echo htmlspecialchars($audio['profile_pic']); ?>" 
                                 alt="<?php echo htmlspecialchars($audio['username']); ?>" class="creator-avatar">
                            <?php echo htmlspecialchars($audio['username']); ?>
                        </p>
                        <div class="audio-stats">
                            <span>▶ <?php echo number_format($audio['play_count']); ?></span>
                            <span>❤ <?php echo number_format($audio['like_count']); ?></span>
                            <span>💬 <?php echo number_format($audio['comment_count']); ?></span>
                        </div>
                        <span class="audio-genre"><?php echo htmlspecialchars($audio['genre']); ?></span>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <p>No audio uploaded yet. Be the first to share!</p>
        </div>
    <?php endif; ?>
</div>






<?php require_once 'includes/footer.php'; ?>