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
$email = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? 1 : 0;
    
   
    $email = sanitize($email);
    
 
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password!';
    } else {
        
        $stmt = $db->prepare("SELECT user_id, username, email, password_hash FROM users WHERE email = ? AND is_active = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
           
            if (password_verify($password, $user['password_hash'])) {
              
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                

                if ($remember) {
                    $cookie_value = $user['user_id'] . ':' . hash('sha256', $user['password_hash']);
                    setcookie('megadj_remember', $cookie_value, time() + (86400 * 30), "/"); // 30 days
                }
                
            
                header("Location: ../index.php");
                exit();
            } else {
                $error = 'Invalid password!';
            }
        } else {
            $error = 'No account found with that email!';
        }
    }
}


require_once '../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <h2>Welcome Back</h2>
        <p class="subtitle">Login to your MegaDj account</p>
        
        <?php if ($error): ?>
            <div class="alert error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
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
                       placeholder="Enter your password">
            </div>
            
            <div class="form-group checkbox">
                <input type="checkbox" id="remember" name="remember" value="1">
                <label for="remember">Remember me for 30 days</label>
            </div>
            
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        
        <div class="auth-links">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</div>

<?php 
require_once '../includes/footer.php';
?>