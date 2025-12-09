<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';


$profile_user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;


$show_liked_audio = isset($user['show_liked_audio']) ? $user['show_liked_audio'] : 1;
$show_following = isset($user['show_following']) ? $user['show_following'] : 1;
$can_view_private = (isLoggedIn() && getCurrentUserId() == $profile_user_id);


if ($profile_user_id <= 0) {
    
    if (isLoggedIn()) {
        $profile_user_id = $_SESSION['user_id']; 
    } else {
       
        header("Location: login.php");
        exit();
    }
}


$stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows === 0) {
   
    echo "<div class='alert error'>User not found!</div>";
    require_once '../includes/footer.php';
    exit();
}

$user = $user_result->fetch_assoc();


$audio_stmt = $db->prepare("
    SELECT a.*, 
           COUNT(DISTINCT l.like_id) as like_count,
           COUNT(DISTINCT c.comment_id) as comment_count
    FROM audio a
    LEFT JOIN likes l ON a.audio_id = l.audio_id
    LEFT JOIN comments c ON a.audio_id = c.audio_id
    WHERE a.user_id = ?
    GROUP BY a.audio_id
    ORDER BY a.upload_date DESC
");
$audio_stmt->bind_param("i", $profile_user_id);
$audio_stmt->execute();
$audio_result = $audio_stmt->get_result();


$current_user_id_for_stats = $profile_user_id;
$stats_stmt = $db->prepare("
    SELECT 
        (SELECT COUNT(*) FROM audio WHERE user_id = ?) as upload_count,
        (SELECT COUNT(*) FROM likes WHERE audio_id IN (SELECT audio_id FROM audio WHERE user_id = ?)) as total_likes,
        (SELECT COUNT(*) FROM subscriptions WHERE creator_id = ?) as follower_count,
        (SELECT COUNT(*) FROM subscriptions WHERE follower_id = ?) as following_count
");
$stats_stmt->bind_param("iiii", 
    $current_user_id_for_stats, 
    $current_user_id_for_stats, 
    $current_user_id_for_stats, 
    $current_user_id_for_stats
);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();


$is_following = false;
if (isLoggedIn()) {
    $current_user_id = $_SESSION['user_id']; 
    $follow_stmt = $db->prepare("SELECT * FROM subscriptions WHERE follower_id = ? AND creator_id = ?");
    $follow_stmt->bind_param("ii", $current_user_id, $profile_user_id);
    $follow_stmt->execute();
    $is_following = ($follow_stmt->get_result()->num_rows > 0);
}

$page_title = $user['username'] . "'s Profile";
require_once '../includes/header.php';


if (isset($_SESSION['success'])) {
    echo '<div class="alert success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    echo '<div class="alert error">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}

?>



<div class="profile-container">

    <div class="profile-header">
        <div class="profile-avatar">
            <img src="<?php echo SITE_URL; ?>/assets/images/<?php echo htmlspecialchars($user['profile_pic']); ?>" 
                 alt="<?php echo htmlspecialchars($user['username']); ?>">
        </div>
        
        <div class="profile-info">
            <h1 class="profile-username"><?php echo htmlspecialchars($user['username']); ?></h1>
            
            <?php if (!empty($user['bio'])): ?>
                <p class="profile-bio"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
            <?php endif; ?>
            
            <div class="profile-stats">
                <div class="stat">
                    <span class="stat-number"><?php echo $stats['upload_count']; ?></span>
                    <span class="stat-label">Uploads</span>
                </div>
                <div class="stat">
                    <span class="stat-number"><?php echo $stats['follower_count']; ?></span>
                    <span class="stat-label">Followers</span>
                </div>
                <div class="stat">
                    <span class="stat-number"><?php echo $stats['following_count']; ?></span>
                    <span class="stat-label">Following</span>
                </div>
                <div class="stat">
                    <span class="stat-number"><?php echo $stats['total_likes']; ?></span>
                    <span class="stat-label">Likes Received</span>
                </div>
            </div>
            
            <div class="profile-actions">
                <?php if (isLoggedIn() && getCurrentUserId() == $profile_user_id): ?>
                  
                    <a href="edit-profile.php" class="btn btn-secondary">Edit Profile</a>
                <?php elseif (isLoggedIn() && getCurrentUserId() != $profile_user_id): ?>
                    
                    <form action="follow-action.php" method="POST" class="follow-form">
                        <input type="hidden" name="creator_id" value="<?php echo $profile_user_id; ?>">
                        <?php if ($is_following): ?>
                            <button type="submit" name="action" value="unfollow" class="btn btn-secondary">
                                Unfollow
                            </button>
                        <?php else: ?>
                            <button type="submit" name="action" value="follow" class="btn btn-primary">
                                Follow
                            </button>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    
    <div class="profile-tabs">
        <button class="tab-button active" onclick="switchTab('uploads')">Uploads</button>
        
        <?php if ($can_view_private || $show_liked_audio): ?>
            <button class="tab-button" onclick="switchTab('likes')">Liked Audio</button>
        <?php endif; ?>
        
        <?php if ($can_view_private || $show_following): ?>
            <button class="tab-button" onclick="switchTab('following')">Following</button>
        <?php endif; ?>
    </div>
    

    <div id="uploads-tab" class="tab-content active">
        <?php if ($audio_result->num_rows > 0): ?>
            <div class="audio-grid">
                <?php while ($audio = $audio_result->fetch_assoc()): ?>
                    <div class="audio-card">
                        <div class="audio-cover">
                            <img src="<?php echo SITE_URL; ?>/uploads/covers/<?php echo htmlspecialchars($audio['cover_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($audio['title']); ?>">
                            <div class="play-overlay">
                                <span>▶</span>
                            </div>
                        </div>
                        <div class="audio-info">
                            <h3>
                                <a href="audio-player.php?id=<?php echo $audio['audio_id']; ?>">
                                    <?php echo htmlspecialchars($audio['title']); ?>
                                </a>
                            </h3>
                            <div class="audio-meta">
                                <span class="audio-date"><?php echo timeAgo($audio['upload_date']); ?></span>
                                <span class="audio-duration"><?php echo formatDuration($audio['duration']); ?></span>
                            </div>
                            <div class="audio-stats">
                                <span>▶ <?php echo number_format($audio['play_count']); ?></span>
                                <span>❤ <?php echo number_format($audio['like_count']); ?></span>
                                <span>💬 <?php echo number_format($audio['comment_count']); ?></span>
                            </div>
                            <span class="audio-genre"><?php echo htmlspecialchars($audio['genre']); ?></span>

                            
                        <?php if (isLoggedIn() && getCurrentUserId() == $profile_user_id): ?>
                            <div class="audio-actions">
                                <a href="edit-audio.php?id=<?php echo $audio['audio_id']; ?>" class="btn-edit">Edit</a>
                            </div>
                        <?php endif; ?>

                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>No audio uploaded yet.</p>
                <?php if (isLoggedIn() && getCurrentUserId() == $profile_user_id): ?>
                    <a href="upload.php" class="btn btn-primary">Upload Your First Audio</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    

    
    <div id="likes-tab" class="tab-content">
        <?php if ($can_view_private || $show_liked_audio): ?>
            <?php
            
            $liked_stmt = $db->prepare("
                SELECT a.*, u.username, u.profile_pic,
                    COUNT(DISTINCT l2.like_id) as like_count,
                    COUNT(DISTINCT c.comment_id) as comment_count
                FROM likes l
                JOIN audio a ON l.audio_id = a.audio_id
                JOIN users u ON a.user_id = u.user_id
                LEFT JOIN likes l2 ON a.audio_id = l2.audio_id
                LEFT JOIN comments c ON a.audio_id = c.audio_id
                WHERE l.user_id = ?
                GROUP BY a.audio_id
                ORDER BY l.like_date DESC
            ");
            $liked_stmt->bind_param("i", $profile_user_id);
            $liked_stmt->execute();
            $liked_result = $liked_stmt->get_result();
            
            if ($liked_result->num_rows > 0): 
                if (!$can_view_private && !$show_liked_audio): ?>
                    <div class="privacy-notice">
                        <p>This user has chosen to keep their liked audio private.</p>
                    </div>
                <?php else: ?>
                    <div class="section-header">
                        <h3>Liked Audio (<?php echo $liked_result->num_rows; ?>)</h3>
                        <?php if ($can_view_private): ?>
                            <small>Only you can see this section</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="audio-grid">
                        <?php while ($audio = $liked_result->fetch_assoc()): ?>
                            <div class="audio-card">
                                <div class="audio-cover">
                                    <img src="<?php echo SITE_URL; ?>/uploads/covers/<?php echo htmlspecialchars($audio['cover_image']); ?>" 
                                        alt="<?php echo htmlspecialchars($audio['title']); ?>">
                                    <div class="play-overlay">
                                        <span>▶</span>
                                    </div>
                                </div>
                                <div class="audio-info">
                                    <h3>
                                        <a href="audio-player.php?id=<?php echo $audio['audio_id']; ?>">
                                            <?php echo htmlspecialchars($audio['title']); ?>
                                        </a>
                                    </h3>
                                    <p class="audio-creator">
                                        <img src="<?php echo SITE_URL; ?>/assets/images/<?php echo htmlspecialchars($audio['profile_pic']); ?>" 
                                            alt="<?php echo htmlspecialchars($audio['username']); ?>" class="creator-avatar">
                                        <?php echo htmlspecialchars($audio['username']); ?>
                                    </p>
                                    <div class="audio-stats">
                                        <span>▶ <?php echo number_format($audio['play_count']); ?></span>
                                        <span>❤ <?php echo number_format($audio['like_count']); ?></span>
                                        <span>💬 <?php echo number_format($audio['comment_count']); ?></span>
                                    </div>
                                    <div class="audio-actions">
                                        <form action="like-action.php" method="POST" class="inline-form">
                                            <input type="hidden" name="audio_id" value="<?php echo $audio['audio_id']; ?>">
                                            <button type="submit" name="action" value="unlike" class="btn-unlike" title="Remove like">
                                                ❌ Remove
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>No liked audio yet.</p>
                    <?php if ($can_view_private): ?>
                        <p><small>Audio you like will appear here.</small></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="privacy-notice">
                <p>This user has chosen to keep their liked audio private.</p>
            </div>
        <?php endif; ?>
    </div>
    

   
    <div id="following-tab" class="tab-content">
        <?php if ($can_view_private || $show_following): ?>
            <?php
        
            $following_stmt = $db->prepare("
                SELECT u.*, s.sub_date,
                    (SELECT COUNT(*) FROM audio WHERE user_id = u.user_id) as upload_count,
                    (SELECT COUNT(*) FROM subscriptions WHERE creator_id = u.user_id) as follower_count
                FROM subscriptions s
                JOIN users u ON s.creator_id = u.user_id
                WHERE s.follower_id = ?
                ORDER BY s.sub_date DESC
            ");
            $following_stmt->bind_param("i", $profile_user_id);
            $following_stmt->execute();
            $following_result = $following_stmt->get_result();
            
            if ($following_result->num_rows > 0): 
                if (!$can_view_private && !$show_following): ?>
                    <div class="privacy-notice">
                        <p>This user has chosen to keep their following list private.</p>
                    </div>
                <?php else: ?>
                    <div class="section-header">
                        <h3>Following (<?php echo $following_result->num_rows; ?>)</h3>
                        <?php if ($can_view_private): ?>
                            <small>Only you can see this section</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="following-grid">
                        <?php while ($followed_user = $following_result->fetch_assoc()): ?>
                            <div class="following-card">
                                <div class="following-avatar">
                                    <a href="profile.php?id=<?php echo $followed_user['user_id']; ?>">
                                        <img src="<?php echo SITE_URL; ?>/assets/images/<?php echo htmlspecialchars($followed_user['profile_pic']); ?>" 
                                            alt="<?php echo htmlspecialchars($followed_user['username']); ?>">
                                    </a>
                                </div>
                                
                                <div class="following-info">
                                    <h4>
                                        <a href="profile.php?id=<?php echo $followed_user['user_id']; ?>">
                                            <?php echo htmlspecialchars($followed_user['username']); ?>
                                        </a>
                                    </h4>
                                    
                                    <div class="following-stats">
                                        <span>🎵 <?php echo $followed_user['upload_count']; ?> uploads</span>
                                        <span>👥 <?php echo $followed_user['follower_count']; ?> followers</span>
                                    </div>
                                    
                                    <div class="following-meta">
                                        <span>Following since <?php echo timeAgo($followed_user['sub_date']); ?></span>
                                    </div>
                                    
                                    <?php if ($can_view_private): ?>
                                        <div class="following-actions">
                                            <form action="follow-action.php" method="POST" class="inline-form">
                                                <input type="hidden" name="creator_id" value="<?php echo $followed_user['user_id']; ?>">
                                                <button type="submit" name="action" value="unfollow" class="btn-unfollow">
                                                    Unfollow
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>Not following anyone yet.</p>
                    <?php if ($can_view_private): ?>
                        <p><small>Users you follow will appear here.</small></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="privacy-notice">
                <p>This user has chosen to keep their following list private.</p>
            </div>
        <?php endif; ?>
    </div>


</div>

<script>
function switchTab(tabName) {

    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
 
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });
    

    document.getElementById(tabName + '-tab').classList.add('active');
    

    event.target.classList.add('active');
}
</script>

<?php require_once '../includes/footer.php'; ?>