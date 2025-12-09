<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';


if (!isLoggedIn()) {
    $_SESSION['error'] = 'You must be logged in to comment.';
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['audio_id'])) {
    $audio_id = intval($_POST['audio_id']);
    $comment_text = trim($_POST['comment_text']);
    $user_id = $_SESSION['user_id'];
    
 
    if (empty($comment_text)) {
        $_SESSION['error'] = 'Comment cannot be empty.';
        header("Location: audio-player.php?id=" . $audio_id);
        exit();
    }
    
    if (strlen($comment_text) > 500) {
        $_SESSION['error'] = 'Comment is too long (max 500 characters).';
        header("Location: audio-player.php?id=" . $audio_id);
        exit();
    }
    

    $comment_text = sanitize($comment_text);
    

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
    

    $insert_stmt = $db->prepare("INSERT INTO comments (user_id, audio_id, comment_text) VALUES (?, ?, ?)");
    $insert_stmt->bind_param("iis", $user_id, $audio_id, $comment_text);
    
    if ($insert_stmt->execute()) {
        $_SESSION['success'] = 'Comment posted successfully!';
        
   
        if ($audio['user_id'] != $user_id) {
        
        }
    } else {
        $_SESSION['error'] = 'Failed to post comment. Please try again.';
    }
    

    header("Location: audio-player.php?id=" . $audio_id);
    exit();
} else {
    $_SESSION['error'] = 'Invalid request.';
    header("Location: ../index.php");
    exit();
}
?>