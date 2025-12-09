<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';


if (!isLoggedIn()) {
    $_SESSION['error'] = 'You must be logged in to like audio.';
    header("Location: login.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['audio_id'])) {
    $audio_id = intval($_POST['audio_id']);
    $action = isset($_POST['action']) ? $_POST['action'] : 'like';
    $user_id = $_SESSION['user_id'];
    
   
    $stmt = $db->prepare("SELECT audio_id, title, user_id FROM audio WHERE audio_id = ?");
    $stmt->bind_param("i", $audio_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
       
        $_SESSION['error'] = 'Audio not found.';
        header("Location: ../index.php");
        exit();
    }
    
    $audio = $result->fetch_assoc();
    

    $check_stmt = $db->prepare("SELECT like_id FROM likes WHERE user_id = ? AND audio_id = ?");
    $check_stmt->bind_param("ii", $user_id, $audio_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($action === 'like') {
        if ($check_result->num_rows === 0) {
           
            $like_stmt = $db->prepare("INSERT INTO likes (user_id, audio_id) VALUES (?, ?)");
            $like_stmt->bind_param("ii", $user_id, $audio_id);
            
            if ($like_stmt->execute()) {
                $_SESSION['success'] = 'You liked "' . htmlspecialchars($audio['title']) . '"!';
            } else {
                $_SESSION['error'] = 'Failed to like audio. Please try again.';
            }
        } else {
            $_SESSION['error'] = 'You already liked this audio.';
        }
    } elseif ($action === 'unlike') {
        if ($check_result->num_rows > 0) {
          
            $unlike_stmt = $db->prepare("DELETE FROM likes WHERE user_id = ? AND audio_id = ?");
            $unlike_stmt->bind_param("ii", $user_id, $audio_id);
            
            if ($unlike_stmt->execute()) {
                $_SESSION['success'] = 'You unliked "' . htmlspecialchars($audio['title']) . '".';
            } else {
                $_SESSION['error'] = 'Failed to unlike audio. Please try again.';
            }
        } else {
            $_SESSION['error'] = 'You have not liked this audio.';
        }
    }
    
   
    header("Location: audio-player.php?id=" . $audio_id);
    exit();
} else {
 
    $_SESSION['error'] = 'Invalid request.';
    header("Location: ../index.php");
    exit();
}
?>