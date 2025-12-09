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


$stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $bio = trim($_POST['bio']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $username = sanitize($username);
    $bio = sanitize($bio);
    

    if (empty($username)) {
        $error = 'Username is required!';
    } elseif ($username !== $user['username']) {

        $check_stmt = $db->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
        $check_stmt->bind_param("si", $username, $user_id);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $error = 'Username already taken!';
        }
    }
    

    $password_hash = $user['password_hash'];
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $error = 'Current password is required to change password!';
        } elseif (!password_verify($current_password, $user['password_hash'])) {
            $error = 'Current password is incorrect!';
        } elseif (strlen($new_password) < 6) {
            $error = 'New password must be at least 6 characters!';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match!';
        } else {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        }
    }
    

    $profile_pic = $user['profile_pic'];
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['profile_pic']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $file_size = $_FILES['profile_pic']['size'];
            if ($file_size <= 2 * 1024 * 1024) { // 2MB
                $file_ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
                $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $file_ext;
                $upload_path = '../assets/images/' . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path)) {
                    $profile_pic = $new_filename;
                }
            } else {
                $error = 'Profile picture must be less than 2MB!';
            }
        } else {
            $error = 'Invalid file type for profile picture!';
        }
    }
    

    if (empty($error)) {

        $show_liked_audio = isset($_POST['show_liked_audio']) ? 1 : 0;
        $show_following = isset($_POST['show_following']) ? 1 : 0;

        $update_stmt = $db->prepare("
            UPDATE users 
            SET username = ?, bio = ?, password_hash = ?, profile_pic = ?,
                show_liked_audio = ?, show_following = ?
            WHERE user_id = ?
        ");
        $update_stmt->bind_param("ssssiii", $username, $bio, $password_hash, $profile_pic, $show_liked_audio, $show_following, $user_id);

       
        if ($update_stmt->execute()) {
      
            $_SESSION['username'] = $username;
            $success = 'Profile updated successfully!';

            $user['username'] = $username;
            $user['bio'] = $bio;
            $user['profile_pic'] = $profile_pic;
        } else {
            $error = 'Failed to update profile. Please try again.';
        }
    }
}

$page_title = "Edit Profile";
require_once '../includes/header.php';
?>

<div class="edit-profile-container">
    <h1>Edit Profile</h1>
    
    <?php if ($success): ?>
        <div class="alert success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="" enctype="multipart/form-data" class="edit-profile-form">
        <div class="form-section">
            <h2>Profile Information</h2>
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" 
                       value="<?php echo htmlspecialchars($user['username']); ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="bio">Bio</label>
                <textarea id="bio" name="bio" rows="4" 
                          placeholder="Tell others about yourself..."><?php echo htmlspecialchars($user['bio']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="profile_pic">Profile Picture</label>
                <div class="current-avatar">
                    <img src="<?php echo SITE_URL; ?>/assets/images/<?php echo htmlspecialchars($user['profile_pic']); ?>" 
                         alt="Current avatar">
                </div>
                <input type="file" id="profile_pic" name="profile_pic" accept="image/*">
                <small>Max 2MB. JPG, PNG, GIF, or WebP.</small>
            </div>
        </div>
        
        <div class="form-section">



            <h2>Change Password</h2>
            <p class="section-note">Leave blank to keep current password</p>
            
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password">
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password">
                <small>At least 6 characters</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password">
            </div>


            
            <h2>Privacy Settings</h2>
            
            <div class="form-group checkbox">
                <input type="checkbox" id="show_liked_audio" name="show_liked_audio" value="1" 
                    <?php echo (isset($user['show_liked_audio']) && $user['show_liked_audio'] == 1) ? 'checked' : ''; ?>>
                <label for="show_liked_audio">Show my liked audio on profile</label>
            </div>
            
            <div class="form-group checkbox">
                <input type="checkbox" id="show_following" name="show_following" value="1"
                    <?php echo (isset($user['show_following']) && $user['show_following'] == 1) ? 'checked' : ''; ?>>
                <label for="show_following">Show who I'm following on profile</label>
            </div>
            
            <p class="privacy-note"><small>These settings only affect what others can see on your profile. You can always see your own liked audio and following list.</small></p>


        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="profile.php?id=<?php echo $user_id; ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>