<?php
require_once 'database.php';

function redirect($url) {
    header("Location: $url");
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}


function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}


function timeAgo($timestamp) {
    $timeDiff = time() - strtotime($timestamp);
    
    if ($timeDiff < 60) {
        return "just now";
    } elseif ($timeDiff < 3600) {
        $minutes = floor($timeDiff / 60);
        return "$minutes minute" . ($minutes > 1 ? "s" : "") . " ago";
    } elseif ($timeDiff < 86400) {
        $hours = floor($timeDiff / 3600);
        return "$hours hour" . ($hours > 1 ? "s" : "") . " ago";
    } elseif ($timeDiff < 604800) {
        $days = floor($timeDiff / 86400);
        return "$days day" . ($days > 1 ? "s" : "") . " ago";
    } else {
        return date("M d, Y", strtotime($timestamp));
    }
}


function formatDuration($seconds) {
    $minutes = floor($seconds / 60);
    $seconds = $seconds % 60;
    return sprintf("%02d:%02d", $minutes, $seconds);
}


function sanitize($input) {
    global $db;
    return htmlspecialchars(trim($db->escape($input)));
}
?>