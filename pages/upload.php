<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';


if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = getCurrentUserId();
$success = '';
$error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $genre = trim($_POST['genre']);
    
    
    $title = sanitize($title);
    $description = sanitize($description);
    $genre = sanitize($genre);
    
   
    if (empty($title)) {
        $error = 'Title is required!';
    } elseif (empty($_FILES['audio_file']['name'])) {
        $error = 'Audio file is required!';
    } elseif (!isset($_FILES['audio_file']) || $_FILES['audio_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Error uploading audio file. Please try again.';
    } else {
       
        $audio_file = $_FILES['audio_file'];
        $cover_file = $_FILES['cover_image'];
      
        $audio_info = pathinfo($audio_file['name']);
        $audio_ext = strtolower($audio_info['extension']);
        $allowed_audio_ext = ['mp3', 'wav', 'ogg', 'm4a'];
        
        if (!in_array($audio_ext, $allowed_audio_ext)) {
            $error = 'Only MP3, WAV, OGG, and M4A files are allowed for audio.';
        } elseif ($audio_file['size'] > MAX_AUDIO_SIZE) {
            $error = 'Audio file is too large. Maximum size is 10MB.';
        } else {
          
            $cover_image = 'default-cover.jpg'; 
            
            if ($cover_file['error'] === UPLOAD_ERR_OK && $cover_file['size'] > 0) {
                $cover_info = pathinfo($cover_file['name']);
                $cover_ext = strtolower($cover_info['extension']);
                $allowed_image_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (!in_array($cover_ext, $allowed_image_ext)) {
                    $error = 'Only JPG, PNG, GIF, and WebP files are allowed for cover images.';
                } elseif ($cover_file['size'] > MAX_IMAGE_SIZE) {
                    $error = 'Cover image is too large. Maximum size is 2MB.';
                } else {
                
                    $cover_filename = 'cover_' . $user_id . '_' . time() . '.' . $cover_ext;
                    $cover_path = '../' . IMAGE_UPLOAD_PATH . $cover_filename;
                    
                    if (move_uploaded_file($cover_file['tmp_name'], $cover_path)) {
                        $cover_image = $cover_filename;
                    } else {
                        $error = 'Failed to upload cover image.';
                    }
                }
            }
            
            if (empty($error)) {
          
                $audio_filename = 'audio_' . $user_id . '_' . time() . '.' . $audio_ext;
                $audio_path = '../' . AUDIO_UPLOAD_PATH . $audio_filename;
                
            
                $duration = 0;
                if ($audio_ext === 'mp3') {
                 
                    $duration = round($audio_file['size'] / 128000); 
                }
                
           
                if (move_uploaded_file($audio_file['tmp_name'], $audio_path)) {
                    
                    $stmt = $db->prepare("
                        INSERT INTO audio (user_id, title, description, audio_file, cover_image, genre, duration) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param("isssssi", $user_id, $title, $description, $audio_filename, $cover_image, $genre, $duration);
                    
                    if ($stmt->execute()) {
                        $audio_id = $db->getLastInsertId();
                        $success = 'Audio uploaded successfully!';
                     
                        header("Refresh: 2; url=audio-player.php?id=" . $audio_id);
                    } else {
                        $error = 'Failed to save audio information to database.';
                     
                        @unlink($audio_path);
                        if ($cover_image !== 'default-cover.jpg') {
                            @unlink('../' . IMAGE_UPLOAD_PATH . $cover_image);
                        }
                    }
                } else {
                    $error = 'Failed to upload audio file. Please try again.';
                }
            }
        }
    }
}

$page_title = "Upload Audio";
require_once '../includes/header.php';


if ($success): ?>
    <div class="alert success"><?php echo $success; ?> Redirecting to your audio...</div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="upload-container">
    <h1>Upload Your Audio</h1>
    <p class="upload-subtitle">Share your music, podcast, or sound clip with the world</p>
    
    <form method="POST" action="" enctype="multipart/form-data" class="upload-form">
        <div class="upload-section">
            <h2>Audio Details</h2>
            
            <div class="form-group">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" 
                       value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                       required
                       placeholder="Give your audio a title">
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" 
                          placeholder="Tell listeners about your audio..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="genre">Genre</label>
                <select id="genre" name="genre">
                    <option value="">Select a genre</option>
                    <option value="Music" <?php echo (isset($_POST['genre']) && $_POST['genre'] === 'Music') ? 'selected' : ''; ?>>Music</option>
                    <option value="Podcast" <?php echo (isset($_POST['genre']) && $_POST['genre'] === 'Podcast') ? 'selected' : ''; ?>>Podcast</option>
                    <option value="Voice Note" <?php echo (isset($_POST['genre']) && $_POST['genre'] === 'Voice Note') ? 'selected' : ''; ?>>Voice Note</option>
                    <option value="Poetry" <?php echo (isset($_POST['genre']) && $_POST['genre'] === 'Poetry') ? 'selected' : ''; ?>>Poetry</option>
                    <option value="Interview" <?php echo (isset($_POST['genre']) && $_POST['genre'] === 'Interview') ? 'selected' : ''; ?>>Interview</option>
                    <option value="Sound Effect" <?php echo (isset($_POST['genre']) && $_POST['genre'] === 'Sound Effect') ? 'selected' : ''; ?>>Sound Effect</option>
                    <option value="Demo" <?php echo (isset($_POST['genre']) && $_POST['genre'] === 'Demo') ? 'selected' : ''; ?>>Demo</option>
                    <option value="Other" <?php echo (isset($_POST['genre']) && $_POST['genre'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
                <small>Or type your own genre</small>
                <input type="text" id="custom_genre" name="custom_genre" 
                       placeholder="Custom genre"
                       style="margin-top: 5px; display: none;">
            </div>
        </div>
        
        <div class="upload-section">
            <h2>Audio File *</h2>
            <p class="section-note">Maximum file size: 10MB. Supported formats: MP3, WAV, OGG, M4A</p>
            
            <div class="file-upload-area" id="audio-upload-area">
                <div class="upload-icon">🎵</div>
                <p class="upload-text">Click to upload or drag and drop</p>
                <p class="upload-subtext">Your audio file (MP3, WAV, OGG, M4A)</p>
                <input type="file" id="audio_file" name="audio_file" 
                       accept=".mp3,.wav,.ogg,.m4a,audio/mpeg,audio/wav,audio/ogg,audio/mp4" 
                       required
                       class="file-input">
                <div class="file-preview" id="audio-preview"></div>
            </div>
        </div>
        
        <div class="upload-section">
            <h2>Cover Image (Optional)</h2>
            <p class="section-note">Maximum file size: 2MB. Supported formats: JPG, PNG, GIF, WebP</p>
            
            <div class="file-upload-area" id="image-upload-area">
                <div class="upload-icon">🖼️</div>
                <p class="upload-text">Click to upload or drag and drop</p>
                <p class="upload-subtext">Cover image (optional)</p>
                <input type="file" id="cover_image" name="cover_image" 
                       accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp" 
                       class="file-input">
                <div class="file-preview" id="image-preview"></div>
            </div>
        </div>
        
        <div class="upload-actions">
            <button type="submit" class="btn btn-primary btn-large">Upload Audio</button>
            <button type="reset" class="btn btn-secondary">Clear Form</button>
        </div>
    </form>
    
    <div class="upload-tips">
        <h3>Upload Tips:</h3>
        <ul>
            <li>Use descriptive titles to help listeners find your audio</li>
            <li>Add a compelling description to engage your audience</li>
            <li>Choose appropriate genres for better discovery</li>
            <li>High-quality cover images attract more listeners</li>
            <li>Keep audio files under 10MB for faster uploads</li>
        </ul>
    </div>
</div>

<script>

document.getElementById('genre').addEventListener('change', function() {
    const customGenreInput = document.getElementById('custom_genre');
    if (this.value === 'Other') {
        customGenreInput.style.display = 'block';
        customGenreInput.name = 'genre';
        this.name = '';
    } else {
        customGenreInput.style.display = 'none';
        customGenreInput.name = '';
        this.name = 'genre';
    }
});


function setupFileUpload(inputId, previewId, isImage) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    const uploadArea = input.closest('.file-upload-area');
    
    input.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            preview.innerHTML = '';
            
            if (isImage) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.style.maxWidth = '200px';
                img.style.maxHeight = '200px';
                img.style.borderRadius = '8px';
                preview.appendChild(img);
            }
            
            const fileInfo = document.createElement('div');
            fileInfo.className = 'file-info';
            fileInfo.innerHTML = `
                <strong>${file.name}</strong><br>
                <small>${(file.size / 1024 / 1024).toFixed(2)} MB</small>
            `;
            preview.appendChild(fileInfo);
            
            uploadArea.classList.add('has-file');
        }
    });
    

    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.classList.add('drag-over');
    });
    
    uploadArea.addEventListener('dragleave', function() {
        uploadArea.classList.remove('drag-over');
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('drag-over');
        input.files = e.dataTransfer.files;
        input.dispatchEvent(new Event('change'));
    });
}


setupFileUpload('audio_file', 'audio-preview', false);
setupFileUpload('cover_image', 'image-preview', true);
</script>

<?php require_once '../includes/footer.php'; ?>