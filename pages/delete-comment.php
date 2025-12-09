<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';


if (!isLoggedIn()) {
    $_SESSION['error'] = 'You must be logged in.';
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_id']) && isset($_POST['audio_id'])) {
    $comment_id = intval($_POST['comment_id']);
    $audio_id = intval($_POST['audio_id']);
    $user_id = $_SESSION['user_id'];
    
  
    $stmt = $db->prepare("SELECT user_id, audio_id FROM comments WHERE comment_id = ?");
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Comment not found.';
        header("Location: audio-player.php?id=" . $audio_id);
        exit();
    }
    
    $comment = $result->fetch_assoc();
    

    $audio_stmt = $db->prepare("SELECT user_id FROM audio WHERE audio_id = ?");
    $audio_stmt->bind_param("i", $comment['audio_id']);
    $audio_stmt->execute();
    $audio_result = $audio_stmt->get_result();
    $audio = $audio_result->fetch_assoc();
    
    $can_delete = ($user_id == $comment['user_id'] || $user_id == $audio['user_id']);
    
    if (!$can_delete) {
        $_SESSION['error'] = 'You do not have permission to delete this comment.';
        header("Location: audio-player.php?id=" . $audio_id);
        exit();
    }
    
 
    $delete_stmt = $db->prepare("DELETE FROM comments WHERE comment_id = ?");
    $delete_stmt->bind_param("i", $comment_id);
    
    if ($delete_stmt->execute()) {
        $_SESSION['success'] = 'Comment deleted successfully.';
    } else {
        $_SESSION['error'] = 'Failed to delete comment.';
    }
    
    header("Location: audio-player.php?id=" . $audio_id);
    exit();
} else {
    $_SESSION['error'] = 'Invalid request.';
    header("Location: ../index.php");
    exit();
}
?>