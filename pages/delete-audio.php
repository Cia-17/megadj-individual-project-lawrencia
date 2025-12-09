<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    $_SESSION['error'] = 'You must be logged in.';
    header("Location: login.php");
    exit();
}

$audio_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($audio_id <= 0) {
    $_SESSION['error'] = 'Invalid audio ID.';
    header("Location: dashboard.php");
    exit();
}


$stmt = $db->prepare("
    SELECT a.*, u.username 
    FROM audio a
    JOIN users u ON a.user_id = u.user_id
    WHERE a.audio_id = ? AND a.user_id = ?
");
$stmt->bind_param("ii", $audio_id, getCurrentUserId());
$stmt->execute();
$audio_result = $stmt->get_result();

if ($audio_result->num_rows === 0) {
    $_SESSION['error'] = 'Audio not found or you do not have permission to delete it.';
    header("Location: dashboard.php");
    exit();
}

$audio = $audio_result->fetch_assoc();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = isset($_POST['confirm']) ? $_POST['confirm'] : '';
    
    if ($confirm === 'yes') {
      
        $audio_file = $audio['audio_file'];
        $cover_image = $audio['cover_image'];
        

        $delete_stmt = $db->prepare("DELETE FROM audio WHERE audio_id = ? AND user_id = ?");
        $delete_stmt->bind_param("ii", $audio_id, getCurrentUserId());
        
        if ($delete_stmt->execute()) {
            
            $audio_path = '../' . AUDIO_UPLOAD_PATH . $audio_file;
            if (file_exists($audio_path)) {
                @unlink($audio_path);
            }
            
            if ($cover_image !== 'default-cover.jpg') {
                $cover_path = '../' . IMAGE_UPLOAD_PATH . $cover_image;
                if (file_exists($cover_path)) {
                    @unlink($cover_path);
                }
            }
            
            $_SESSION['success'] = 'Audio deleted successfully.';
            header("Location: dashboard.php");
            exit();
        } else {
            $_SESSION['error'] = 'Failed to delete audio. Please try again.';
            header("Location: delete-audio.php?id=" . $audio_id);
            exit();
        }
    } else {
        $_SESSION['error'] = 'Deletion cancelled.';
        header("Location: audio-player.php?id=" . $audio_id);
        exit();
    }
}

$page_title = "Delete Audio";
require_once '../includes/header.php';
?>

<div class="delete-confirmation">
    <h1>Delete Audio</h1>
    
    <div class="warning-message">
        <div class="warning-icon">⚠️</div>
        <h2>Are you sure you want to delete this audio?</h2>
        <p>This action cannot be undone!</p>
    </div>
    
    <div class="audio-to-delete">
        <div class="audio-cover">
            <img src="<?php echo SITE_URL; ?>/uploads/covers/<?php echo htmlspecialchars($audio['cover_image']); ?>" 
                 alt="<?php echo htmlspecialchars($audio['title']); ?>">
        </div>
        <div class="audio-details">
            <h3><?php echo htmlspecialchars($audio['title']); ?></h3>
            <p>By: <?php echo htmlspecialchars($audio['username']); ?></p>
            <p>Uploaded: <?php echo timeAgo($audio['upload_date']); ?></p>
            <p>Plays: <?php echo number_format($audio['play_count']); ?></p>
        </div>
    </div>
    
    <div class="deletion-stats">
        <h4>This will also delete:</h4>
        <ul>
            <li>All likes on this audio</li>
            <li>All comments on this audio</li>
            <li>The audio file from our servers</li>
            <li>The cover image (if not default)</li>
        </ul>
    </div>
    
    <form method="POST" action="" class="confirmation-form">
        <div class="confirmation-options">
            <label class="confirmation-option">
                <input type="radio" name="confirm" value="no" checked>
                <span>No, keep my audio</span>
            </label>
            <label class="confirmation-option">
                <input type="radio" name="confirm" value="yes">
                <span>Yes, delete permanently</span>
            </label>
        </div>
        
        <div class="confirmation-actions">
            <button type="submit" class="btn btn-danger">Confirm Decision</button>
            <a href="audio-player.php?id=<?php echo $audio_id; ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>