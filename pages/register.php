<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);


require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';


if (isLoggedIn()) {
    header("Location: ../index.php");
    exit();
}


$error = '';
$success = '';
$username = $email = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    
    $username = sanitize($username);
    $email = sanitize($email);
    
  
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'All fields are required!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format!';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters!';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match!';
    } else {
        
        $stmt = $db->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Username or email already exists!';
        } else {
            
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
           
            $stmt = $db->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $password_hash);
            
            if ($stmt->execute()) {
               
                $user_id = $db->getLastInsertId();
                
              
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                
                
                header("Location: ../index.php");
                exit();
            } else {
                $error = 'Registration failed. Please try again. Error: ' . $db->getConnection()->error;
            }
        }
    }
}


require_once '../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <h2>Join MegaDj</h2>
        <p class="subtitle">Create your account to start sharing audio</p>
        
        <?php if ($error): ?>
            <div class="alert error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" 
                       value="<?php echo htmlspecialchars($username); ?>" 
                       required
                       placeholder="Choose a username">
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($email); ?>" 
                       required
                       placeholder="your@email.com">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" 
                       required
                       placeholder="At least 6 characters">
                <small>Must be at least 6 characters long</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" 
                       required
                       placeholder="Re-type your password">
            </div>
            
            <button type="submit" class="btn btn-primary">Create Account</button>
        </form>
        
        <div class="auth-links">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</div>

<?php 
require_once '../includes/footer.php';
?>