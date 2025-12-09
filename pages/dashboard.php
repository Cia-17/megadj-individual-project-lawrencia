<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';


if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = getCurrentUserId();


$stats_stmt = $db->prepare("
    SELECT 
        (SELECT COUNT(*) FROM audio WHERE user_id = ?) as upload_count,
        (SELECT COUNT(*) FROM likes WHERE audio_id IN (SELECT audio_id FROM audio WHERE user_id = ?)) as total_likes,
        (SELECT COUNT(*) FROM subscriptions WHERE creator_id = ?) as follower_count,
        (SELECT COUNT(*) FROM subscriptions WHERE follower_id = ?) as following_count,
        (SELECT SUM(play_count) FROM audio WHERE user_id = ?) as total_plays
");
$stats_stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();


$recent_stmt = $db->prepare("
    SELECT a.*, 
           COUNT(DISTINCT l.like_id) as like_count,
           COUNT(DISTINCT c.comment_id) as comment_count
    FROM audio a
    LEFT JOIN likes l ON a.audio_id = l.audio_id
    LEFT JOIN comments c ON a.audio_id = c.audio_id
    WHERE a.user_id = ?
    GROUP BY a.audio_id
    ORDER BY a.upload_date DESC
    LIMIT 5
");
$recent_stmt->bind_param("i", $user_id);
$recent_stmt->execute();
$recent_result = $recent_stmt->get_result();


$followers_stmt = $db->prepare("
    SELECT u.user_id, u.username, u.profile_pic, s.sub_date
    FROM subscriptions s
    JOIN users u ON s.follower_id = u.user_id
    WHERE s.creator_id = ?
    ORDER BY s.sub_date DESC
    LIMIT 5
");
$followers_stmt->bind_param("i", $user_id);
$followers_stmt->execute();
$followers_result = $followers_stmt->get_result();

$page_title = "Dashboard";
require_once '../includes/header.php';
?>

<div class="dashboard-container">
    <h1>Dashboard</h1>
    <p class="dashboard-subtitle">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
    

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">🎵</div>
            <div class="stat-info">
                <h3><?php echo $stats['upload_count']; ?></h3>
                <p>Uploads</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">❤️</div>
            <div class="stat-info">
                <h3><?php echo $stats['total_likes']; ?></h3>
                <p>Total Likes</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
                <h3><?php echo $stats['follower_count']; ?></h3>
                <p>Followers</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">▶️</div>
            <div class="stat-info">
                <h3><?php echo $stats['total_plays'] ?: 0; ?></h3>
                <p>Total Plays</p>
            </div>
        </div>
    </div>
    

    <div class="quick-actions">
        <h2>Quick Actions</h2>
        <div class="action-buttons">
            <a href="upload.php" class="action-btn">
                <span class="action-icon">➕</span>
                <span class="action-text">Upload New Audio</span>
            </a>
            <a href="profile.php?id=<?php echo $user_id; ?>" class="action-btn">
                <span class="action-icon">👤</span>
                <span class="action-text">View Profile</span>
            </a>
            <a href="edit-profile.php" class="action-btn">
                <span class="action-icon">⚙️</span>
                <span class="action-text">Edit Profile</span>
            </a>
        </div>
    </div>
    

    <div class="dashboard-columns">
        <div class="dashboard-column">
            <div class="dashboard-section">
                <h2>Recent Uploads</h2>
                <?php if ($recent_result->num_rows > 0): ?>
                    <div class="recent-list">
                        <?php while ($audio = $recent_result->fetch_assoc()): ?>
                            <div class="recent-item">
                                <div class="recent-cover">
                                    <img src="<?php echo SITE_URL; ?>/uploads/covers/<?php echo htmlspecialchars($audio['cover_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($audio['title']); ?>">
                                </div>
                                <div class="recent-info">
                                    <h4>
                                        <a href="audio-player.php?id=<?php echo $audio['audio_id']; ?>">
                                            <?php echo htmlspecialchars($audio['title']); ?>
                                        </a>
                                    </h4>
                                    <p class="recent-meta">
                                        <?php echo timeAgo($audio['upload_date']); ?> • 
                                        ▶ <?php echo $audio['play_count']; ?> plays • 
                                        ❤ <?php echo $audio['like_count']; ?> likes
                                    </p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <a href="profile.php?id=<?php echo $user_id; ?>" class="view-all">View All Uploads →</a>
                <?php else: ?>
                    <div class="empty-state">
                        <p>You haven't uploaded any audio yet.</p>
                        <a href="upload.php" class="btn btn-primary">Upload Your First Audio</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="dashboard-column">
            <div class="dashboard-section">
                <h2>Recent Followers</h2>
                <?php if ($followers_result->num_rows > 0): ?>
                    <div class="followers-list">
                        <?php while ($follower = $followers_result->fetch_assoc()): ?>
                            <div class="follower-item">
                                <div class="follower-avatar">
                                    <img src="<?php echo SITE_URL; ?>/assets/images/<?php echo htmlspecialchars($follower['profile_pic']); ?>" 
                                         alt="<?php echo htmlspecialchars($follower['username']); ?>">
                                </div>
                                <div class="follower-info">
                                    <h4>
                                        <a href="profile.php?id=<?php echo $follower['user_id']; ?>">
                                            <?php echo htmlspecialchars($follower['username']); ?>
                                        </a>
                                    </h4>
                                    <p class="follower-meta">Followed <?php echo timeAgo($follower['sub_date']); ?></p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No followers yet. Keep uploading to attract followers!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>