<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';


if (!isLoggedIn()) {
    $_SESSION['error'] = 'You must be logged in to follow users.';
    header("Location: login.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['creator_id'])) {
    $creator_id = intval($_POST['creator_id']);
    $action = isset($_POST['action']) ? $_POST['action'] : 'follow';
    
  
    $stmt = $db->prepare("SELECT user_id, username FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $creator_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
      
        $_SESSION['error'] = 'User not found.';
        header("Location: ../index.php");
        exit();
    }
    
    $creator = $result->fetch_assoc();
    $current_user_id = $_SESSION['user_id'];
    
  
    if ($creator_id == $current_user_id) {
        $_SESSION['error'] = 'You cannot follow yourself.';
        header("Location: profile.php?id=" . $creator_id);
        exit();
    }
    
    
    $check_stmt = $db->prepare("SELECT sub_id FROM subscriptions WHERE follower_id = ? AND creator_id = ?");
    $check_stmt->bind_param("ii", $current_user_id, $creator_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($action === 'follow') {
        if ($check_result->num_rows === 0) {
          
            $follow_stmt = $db->prepare("INSERT INTO subscriptions (follower_id, creator_id) VALUES (?, ?)");
            $follow_stmt->bind_param("ii", $current_user_id, $creator_id);
            
            if ($follow_stmt->execute()) {
                $_SESSION['success'] = 'You are now following ' . htmlspecialchars($creator['username']) . '!';
            } else {
                $_SESSION['error'] = 'Failed to follow user. Please try again.';
            }
        } else {
            $_SESSION['error'] = 'You are already following this user.';
        }
    } elseif ($action === 'unfollow') {
        if ($check_result->num_rows > 0) {
            
            $unfollow_stmt = $db->prepare("DELETE FROM subscriptions WHERE follower_id = ? AND creator_id = ?");
            $unfollow_stmt->bind_param("ii", $current_user_id, $creator_id);
            
            if ($unfollow_stmt->execute()) {
                $_SESSION['success'] = 'You have unfollowed ' . htmlspecialchars($creator['username']) . '.';
            } else {
                $_SESSION['error'] = 'Failed to unfollow user. Please try again.';
            }
        } else {
            $_SESSION['error'] = 'You are not following this user.';
        }
    }
    
    header("Location: profile.php?id=" . $creator_id);
    exit();
} else {
  
    $_SESSION['error'] = 'Invalid request.';
    header("Location: ../index.php");
    exit();
}
?>