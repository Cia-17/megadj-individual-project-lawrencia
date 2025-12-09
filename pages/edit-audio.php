<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';


if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$audio_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$success = '';
$error = '';

if ($audio_id <= 0) {
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
    $_SESSION['error'] = 'Audio not found or you do not have permission to edit it.';
    header("Location: dashboard.php");
    exit();
}

$audio = $audio_result->fetch_assoc();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $genre = trim($_POST['genre']);
    

    $title = sanitize($title);
    $description = sanitize($description);
    $genre = sanitize($genre);
    

    if (empty($title)) {
        $error = 'Title is required!';
    } else {
 
        $cover_image = $audio['cover_image'];
        
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $cover_file = $_FILES['cover_image'];
            $cover_info = pathinfo($cover_file['name']);
            $cover_ext = strtolower($cover_info['extension']);
            $allowed_image_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($cover_ext, $allowed_image_ext)) {
                $error = 'Only JPG, PNG, GIF, and WebP files are allowed for cover images.';
            } elseif ($cover_file['size'] > MAX_IMAGE_SIZE) {
                $error = 'Cover image is too large. Maximum size is 5MB.';
            } else {
           
                $new_cover_filename = 'cover_' . getCurrentUserId() . '_' . time() . '.' . $cover_ext;
                $cover_path = '../' . IMAGE_UPLOAD_PATH . $new_cover_filename;
                
                if (move_uploaded_file($cover_file['tmp_name'], $cover_path)) {
               
                    if ($cover_image !== 'default-cover.jpg') {
                        @unlink('../' . IMAGE_UPLOAD_PATH . $cover_image);
                    }
                    $cover_image = $new_cover_filename;
                } else {
                    $error = 'Failed to upload new cover image.';
                }
            }
        }
        
        if (empty($error)) {
       
            $update_stmt = $db->prepare("
                UPDATE audio 
                SET title = ?, description = ?, cover_image = ?, genre = ?
                WHERE audio_id = ? AND user_id = ?
            ");
            $update_stmt->bind_param("ssssii", $title, $description, $cover_image, $genre, $audio_id, getCurrentUserId());
            
            if ($update_stmt->execute()) {
                $success = 'Audio updated successfully!';
          
                $audio['title'] = $title;
                $audio['description'] = $description;
                $audio['cover_image'] = $cover_image;
                $audio['genre'] = $genre;
            } else {
                $error = 'Failed to update audio. Please try again.';
            }
        }
    }
}

$page_title = "Edit Audio";
require_once '../includes/header.php';
?>

<div class="edit-audio-container">
    <h1>Edit Audio</h1>
    
    <?php if ($success): ?>
        <div class="alert success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="edit-audio-preview">
        <div class="preview-cover">
            <img src="<?php echo SITE_URL; ?>/uploads/covers/<?php echo htmlspecialchars($audio['cover_image']); ?>" 
                 alt="<?php echo htmlspecialchars($audio['title']); ?>">
        </div>
        <div class="preview-info">
            <h2><?php echo htmlspecialchars($audio['title']); ?></h2>
            <p>Original upload: <?php echo timeAgo($audio['upload_date']); ?></p>
            <p>Plays: <?php echo number_format($audio['play_count']); ?> • 
               Likes: <?php 
               $like_count_stmt = $db->prepare("SELECT COUNT(*) as count FROM likes WHERE audio_id = ?");
               $like_count_stmt->bind_param("i", $audio_id);
               $like_count_stmt->execute();
               $like_count = $like_count_stmt->get_result()->fetch_assoc()['count'];
               echo number_format($like_count);
               ?></p>
        </div>
    </div>
    
    <form method="POST" action="" enctype="multipart/form-data" class="edit-audio-form">
        <div class="form-section">
            <h2>Audio Details</h2>
            
            <div class="form-group">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" 
                       value="<?php echo htmlspecialchars($audio['title']); ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($audio['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="genre">Genre</label>
                <select id="genre" name="genre">
                    <option value="">Select a genre</option>
                    <option value="Music" <?php echo ($audio['genre'] === 'Music') ? 'selected' : ''; ?>>Music</option>
                    <option value="Podcast" <?php echo ($audio['genre'] === 'Podcast') ? 'selected' : ''; ?>>Podcast</option>
                    <option value="Voice Note" <?php echo ($audio['genre'] === 'Voice Note') ? 'selected' : ''; ?>>Voice Note</option>
                    <option value="Poetry" <?php echo ($audio['genre'] === 'Poetry') ? 'selected' : ''; ?>>Poetry</option>
                    <option value="Interview" <?php echo ($audio['genre'] === 'Interview') ? 'selected' : ''; ?>>Interview</option>
                    <option value="Sound Effect" <?php echo ($audio['genre'] === 'Sound Effect') ? 'selected' : ''; ?>>Sound Effect</option>
                    <option value="Demo" <?php echo ($audio['genre'] === 'Demo') ? 'selected' : ''; ?>>Demo</option>
                    <option value="Other" <?php echo ($audio['genre'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
        </div>
        
        <div class="form-section">
            <h2>Update Cover Image (Optional)</h2>
            <p class="section-note">Leave empty to keep current cover image. Maximum 5MB.</p>
            
            <div class="current-cover">
                <p>Current cover:</p>
                <img src="<?php echo SITE_URL; ?>/uploads/covers/<?php echo htmlspecialchars($audio['cover_image']); ?>" 
                     alt="Current cover" style="max-width: 200px; border-radius: 8px;">
            </div>
            
            <div class="file-upload-area">
                <div class="upload-icon">🖼️</div>
                <p class="upload-text">Click to upload new cover image</p>
                <input type="file" id="cover_image" name="cover_image" 
                       accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp" 
                       class="file-input">
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="audio-player.php?id=<?php echo $audio_id; ?>" class="btn btn-secondary">Cancel</a>
            <a href="delete-audio.php?id=<?php echo $audio_id; ?>" 
               class="btn btn-danger" 
               onclick="return confirm('WARNING: This will permanently delete this audio and all its comments/likes. This cannot be undone!')">
                Delete Audio
            </a>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>