<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

$audio_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($audio_id <= 0) {
    header("Location: ../index.php");
    exit();
}


$stmt = $db->prepare("
    SELECT a.*, u.username, u.user_id as creator_id, u.profile_pic
    FROM audio a
    JOIN users u ON a.user_id = u.user_id
    WHERE a.audio_id = ?
");
$stmt->bind_param("i", $audio_id);
$stmt->execute();
$audio_result = $stmt->get_result();

if ($audio_result->num_rows === 0) {
   
    $page_title = "Audio Not Found";
    require_once '../includes/header.php';
    echo '<div class="alert error">Audio not found.</div>';
    echo '<p><a href="../index.php">Return to homepage</a></p>';
    require_once '../includes/footer.php';
    exit();
}

$audio = $audio_result->fetch_assoc();


$like_stmt = $db->prepare("SELECT COUNT(*) as like_count FROM likes WHERE audio_id = ?");
$like_stmt->bind_param("i", $audio_id);
$like_stmt->execute();
$like_result = $like_stmt->get_result();
$like_data = $like_result->fetch_assoc();
$audio['like_count'] = $like_data['like_count'];

$comment_stmt = $db->prepare("SELECT COUNT(*) as comment_count FROM comments WHERE audio_id = ?");
$comment_stmt->bind_param("i", $audio_id);
$comment_stmt->execute();
$comment_result = $comment_stmt->get_result();
$comment_data = $comment_result->fetch_assoc();
$audio['comment_count'] = $comment_data['comment_count'];


$is_liked = false;
if (isLoggedIn()) {
    $check_like_stmt = $db->prepare("SELECT like_id FROM likes WHERE user_id = ? AND audio_id = ?");
    $check_like_stmt->bind_param("ii", $_SESSION['user_id'], $audio_id);
    $check_like_stmt->execute();
    $is_liked = ($check_like_stmt->get_result()->num_rows > 0);
}


$update_stmt = $db->prepare("UPDATE audio SET play_count = play_count + 1 WHERE audio_id = ?");
$update_stmt->bind_param("i", $audio_id);
$update_stmt->execute();


if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}

$page_title = $audio['title'];
require_once '../includes/header.php';
?>

<div class="audio-player-container">
    <?php if (isset($success_message)): ?>
        <div class="alert success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert error"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
   
    <div class="player-section">
        <div class="audio-cover-large">
            <img src="<?php echo SITE_URL; ?>/uploads/covers/<?php echo htmlspecialchars($audio['cover_image']); ?>" 
                 alt="<?php echo htmlspecialchars($audio['title']); ?>">
        </div>
        
        <div class="audio-info-large">
            <h1><?php echo htmlspecialchars($audio['title']); ?></h1>
            
            <div class="audio-creator-large">
                <img src="<?php echo SITE_URL; ?>/assets/images/<?php echo htmlspecialchars($audio['profile_pic']); ?>" 
                     alt="<?php echo htmlspecialchars($audio['username']); ?>" class="creator-avatar-large">
                <a href="profile.php?id=<?php echo $audio['creator_id']; ?>">
                    <?php echo htmlspecialchars($audio['username']); ?>
                </a>
            </div>
            
            <?php if (!empty($audio['description'])): ?>
                <div class="audio-description">
                    <h3>Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($audio['description'])); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="audio-meta-large">
                <span>🎵 Genre: <?php echo htmlspecialchars($audio['genre']); ?></span>
                <span>📅 Uploaded: <?php echo timeAgo($audio['upload_date']); ?></span>
                <span>⏱️ Duration: <?php echo formatDuration($audio['duration']); ?></span>
            </div>
            
            <div class="player-actions">
              
                <form action="like-action.php" method="POST" class="like-form">
                    <input type="hidden" name="audio_id" value="<?php echo $audio_id; ?>">
                    <?php if ($is_liked): ?>
                        <button type="submit" name="action" value="unlike" class="btn-action liked">
                            ❤️ <?php echo number_format($audio['like_count']); ?>
                        </button>
                    <?php else: ?>
                        <button type="submit" name="action" value="like" class="btn-action">
                            🤍 <?php echo number_format($audio['like_count']); ?>
                        </button>
                    <?php endif; ?>
                </form>
                
                <?php if (isLoggedIn() && $audio['creator_id'] != $_SESSION['user_id']): ?>
                    <?php 
                   
                    $follow_check = $db->prepare("SELECT sub_id FROM subscriptions WHERE follower_id = ? AND creator_id = ?");
                    $follow_check->bind_param("ii", $_SESSION['user_id'], $audio['creator_id']);
                    $follow_check->execute();
                    $is_following = ($follow_check->get_result()->num_rows > 0);
                    ?>
                    <form action="follow-action.php" method="POST" class="follow-form-inline">
                        <input type="hidden" name="creator_id" value="<?php echo $audio['creator_id']; ?>">
                        <?php if ($is_following): ?>
                            <button type="submit" name="action" value="unfollow" class="btn-action">
                                👥 Unfollow
                            </button>
                        <?php else: ?>
                            <button type="submit" name="action" value="follow" class="btn-action">
                                👤 Follow Creator
                            </button>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
                
                
                <button class="btn-action" onclick="shareAudio()">🔗 Share</button>
                
             
                <div class="audio-stats-large">
                    <span>▶ <?php echo number_format($audio['play_count'] + 1); ?> plays</span>
                    <span>💬 <?php echo number_format($audio['comment_count']); ?> comments</span>
                </div>
            </div>
           
            <div class="html5-player">
                <audio controls style="width: 100%;" id="audio-player">
                    <source src="<?php echo SITE_URL; ?>/uploads/audio/<?php echo htmlspecialchars($audio['audio_file']); ?>" 
                            type="audio/mpeg">
                    Your browser does not support the audio element.
                </audio>
                <div class="player-controls">
                    <button onclick="document.getElementById('audio-player').play()" class="btn-control">▶ Play</button>
                    <button onclick="document.getElementById('audio-player').pause()" class="btn-control">⏸️ Pause</button>
                    <button onclick="document.getElementById('audio-player').currentTime = 0" class="btn-control">⏮️ Restart</button>
                </div>
            </div>
        </div>
    </div>
    
   
    <div class="comments-section">
        <h2>Comments (<?php echo number_format($audio['comment_count']); ?>)</h2>
        
        <?php if (isLoggedIn()): ?>
            <div class="comment-form">
                <form action="comment-action.php" method="POST">
                    <input type="hidden" name="audio_id" value="<?php echo $audio_id; ?>">
                    <textarea name="comment_text" placeholder="Add a comment..." rows="3" required></textarea>
                    <div class="comment-form-actions">
                        <button type="submit" class="btn btn-primary">Post Comment</button>
                        <small>Maximum 500 characters</small>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="login-prompt">
                <p><a href="login.php">Login</a> to join the discussion!</p>
            </div>
        <?php endif; ?>
        
    
        <div class="comments-list">
            <?php
            
            $comments_stmt = $db->prepare("
                SELECT c.*, u.username, u.profile_pic 
                FROM comments c
                JOIN users u ON c.user_id = u.user_id
                WHERE c.audio_id = ?
                ORDER BY c.comment_date DESC
            ");
            $comments_stmt->bind_param("i", $audio_id);
            $comments_stmt->execute();
            $comments_result = $comments_stmt->get_result();
            
            if ($comments_result->num_rows > 0): 
                while ($comment = $comments_result->fetch_assoc()): ?>
                    <div class="comment-item">
                        <div class="comment-avatar">
                            <a href="profile.php?id=<?php echo $comment['user_id']; ?>">
                                <img src="<?php echo SITE_URL; ?>/assets/images/<?php echo htmlspecialchars($comment['profile_pic']); ?>" 
                                    alt="<?php echo htmlspecialchars($comment['username']); ?>">
                            </a>
                        </div>
                        
                        <div class="comment-content">
                            <div class="comment-header">
                                <a href="profile.php?id=<?php echo $comment['user_id']; ?>" class="comment-author">
                                    <?php echo htmlspecialchars($comment['username']); ?>
                                </a>
                                <span class="comment-time"><?php echo timeAgo($comment['comment_date']); ?></span>
                                
                                <?php if (isLoggedIn() && ($_SESSION['user_id'] == $comment['user_id'] || $_SESSION['user_id'] == $audio['creator_id'])): ?>
                                    <form action="delete-comment.php" method="POST" class="comment-delete-form">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['comment_id']; ?>">
                                        <input type="hidden" name="audio_id" value="<?php echo $audio_id; ?>">
                                        <button type="submit" class="delete-comment-btn" onclick="return confirm('Delete this comment?')">×</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            
                            <div class="comment-text">
                                <?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; 
            else: ?>
                <div class="empty-state">
                    <p>No comments yet. Be the first to comment!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>


</div>

<script>
function shareAudio() {
    const title = "<?php echo addslashes($audio['title']); ?>";
    const url = window.location.href;
    const text = `Listen to "${title}" on MegaDj: ${url}`;
    
    if (navigator.share) {
        navigator.share({
            title: title,
            text: text,
            url: url
        });
    } else {
        navigator.clipboard.writeText(text);
        alert('Link copied to clipboard!');
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>