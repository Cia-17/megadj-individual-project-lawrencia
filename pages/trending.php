<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

$period = isset($_GET['period']) ? $_GET['period'] : 'week';
$valid_periods = ['day', 'week', 'month', 'all'];
if (!in_array($period, $valid_periods)) {
    $period = 'week';
}


$date_condition = '';
switch ($period) {
    case 'day':
        $date_condition = "AND a.upload_date >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
        break;
    case 'week':
        $date_condition = "AND a.upload_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $date_condition = "AND a.upload_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    case 'all':
        $date_condition = "";
        break;
}


$trending_query = "
    SELECT a.*, u.username, u.profile_pic,
           COUNT(DISTINCT l.like_id) as like_count,
           COUNT(DISTINCT c.comment_id) as comment_count,
           (a.play_count * 0.5) + 
           (COUNT(DISTINCT l.like_id) * 2) + 
           (COUNT(DISTINCT c.comment_id) * 1.5) as trend_score
    FROM audio a
    JOIN users u ON a.user_id = u.user_id
    LEFT JOIN likes l ON a.audio_id = l.audio_id
    LEFT JOIN comments c ON a.audio_id = c.audio_id
    WHERE 1=1 $date_condition
    GROUP BY a.audio_id
    ORDER BY trend_score DESC, a.upload_date DESC
    LIMIT 50
";

$trending_result = $db->query($trending_query);


$creators_query = "
    SELECT u.*,
           COUNT(DISTINCT s.sub_id) as new_followers,
           COUNT(DISTINCT a.audio_id) as upload_count
    FROM users u
    LEFT JOIN subscriptions s ON u.user_id = s.creator_id
    LEFT JOIN audio a ON u.user_id = a.user_id
    WHERE s.sub_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY u.user_id
    ORDER BY new_followers DESC, upload_count DESC
    LIMIT 10
";

$creators_result = $db->query($creators_query);

$page_title = "Trending";
require_once '../includes/header.php';
?>

<div class="trending-container">
    <h1>Trending Now</h1>
    

    <div class="period-selector">
        <a href="?period=day" class="period-btn <?php echo ($period === 'day') ? 'active' : ''; ?>">Today</a>
        <a href="?period=week" class="period-btn <?php echo ($period === 'week') ? 'active' : ''; ?>">This Week</a>
        <a href="?period=month" class="period-btn <?php echo ($period === 'month') ? 'active' : ''; ?>">This Month</a>
        <a href="?period=all" class="period-btn <?php echo ($period === 'all') ? 'active' : ''; ?>">All Time</a>
    </div>
    
    
    <div class="trending-section">
        <h2>🔥 Trending Audio</h2>
        <p class="section-subtitle">Most popular audio based on plays, likes, and comments</p>
        
        <?php if ($trending_result && $trending_result->num_rows > 0): ?>
            <div class="trending-grid">
                <?php 
                $rank = 1;
                while ($audio = $trending_result->fetch_assoc()): 
                    $trend_score = round($audio['trend_score']); ?>
                    <div class="trending-item">
                        <div class="trending-rank">
                            <span class="rank-number">#<?php echo $rank; ?></span>
                            <?php if ($rank <= 3): ?>
                                <span class="rank-badge">🔥</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="trending-cover">
                            <a href="audio-player.php?id=<?php echo $audio['audio_id']; ?>">
                                <img src="<?php echo SITE_URL; ?>/uploads/covers/<?php echo htmlspecialchars($audio['cover_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($audio['title']); ?>">
                            </a>
                        </div>
                        
                        <div class="trending-info">
                            <h3>
                                <a href="audio-player.php?id=<?php echo $audio['audio_id']; ?>">
                                    <?php echo htmlspecialchars($audio['title']); ?>
                                </a>
                            </h3>
                            
                            <div class="trending-creator">
                                <img src="<?php echo SITE_URL; ?>/assets/images/<?php echo htmlspecialchars($audio['profile_pic']); ?>" 
                                     alt="<?php echo htmlspecialchars($audio['username']); ?>">
                                <a href="profile.php?id=<?php echo $audio['user_id']; ?>">
                                    <?php echo htmlspecialchars($audio['username']); ?>
                                </a>
                            </div>
                            
                            <div class="trending-stats">
                                <span class="stat plays">▶ <?php echo number_format($audio['play_count']); ?> plays</span>
                                <span class="stat likes">❤ <?php echo number_format($audio['like_count']); ?> likes</span>
                                <span class="stat comments">💬 <?php echo number_format($audio['comment_count']); ?> comments</span>
                                <span class="stat score">📈 Score: <?php echo number_format($trend_score); ?></span>
                            </div>
                            
                            <div class="trending-meta">
                                <span class="genre"><?php echo htmlspecialchars($audio['genre']); ?></span>
                                <span class="time"><?php echo timeAgo($audio['upload_date']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php $rank++; ?>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>No trending audio yet. Upload and engage with audio to see them here!</p>
            </div>
        <?php endif; ?>
    </div>
    

    <div class="trending-section">
        <h2>⭐ Trending Creators</h2>
        <p class="section-subtitle">Creators gaining followers this week</p>
        
        <?php if ($creators_result && $creators_result->num_rows > 0): ?>
            <div class="creators-grid">
                <?php 
                $creator_rank = 1;
                while ($creator = $creators_result->fetch_assoc()): ?>
                    <div class="creator-card">
                        <div class="creator-rank">
                            <span class="rank-number">#<?php echo $creator_rank; ?></span>
                        </div>
                        
                        <div class="creator-avatar">
                            <a href="profile.php?id=<?php echo $creator['user_id']; ?>">
                                <img src="<?php echo SITE_URL; ?>/assets/images/<?php echo htmlspecialchars($creator['profile_pic']); ?>" 
                                     alt="<?php echo htmlspecialchars($creator['username']); ?>">
                            </a>
                        </div>
                        
                        <div class="creator-info">
                            <h3>
                                <a href="profile.php?id=<?php echo $creator['user_id']; ?>">
                                    <?php echo htmlspecialchars($creator['username']); ?>
                                </a>
                            </h3>
                            
                            <div class="creator-stats">
                                <span class="stat">🎵 <?php echo $creator['upload_count']; ?> uploads</span>
                                <span class="stat">👥 <?php echo $creator['new_followers']; ?> new followers</span>
                            </div>
                            
                            <div class="creator-actions">
                                <a href="profile.php?id=<?php echo $creator['user_id']; ?>" class="btn btn-small">Visit Profile</a>
                            </div>
                        </div>
                    </div>
                    <?php $creator_rank++; ?>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>No trending creators this week.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>