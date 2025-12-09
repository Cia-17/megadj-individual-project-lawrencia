<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"], 
        $params["secure"], 
        $params["httponly"]
    );
}

session_destroy();

if (isset($_COOKIE['megadj_remember'])) {
    setcookie('megadj_remember', '', time() - 3600, "/");
}


setcookie('PHPSESSID', '', time() - 3600, "/");

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("Location: ../index.php");
exit();
?>