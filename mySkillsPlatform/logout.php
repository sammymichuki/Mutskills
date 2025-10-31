<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie if it exists
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Clear any authentication cookies
$cookies_to_clear = ['token', 'user', 'loginTime', 'user_id', 'remember_token'];
foreach ($cookies_to_clear as $cookie) {
    if (isset($_COOKIE[$cookie])) {
        setcookie($cookie, '', time() - 3600, '/');
        setcookie($cookie, '', time() - 3600, '/', $_SERVER['HTTP_HOST']);
    }
}

// Set a flag cookie to show logout message on login page
setcookie('justLoggedOut', 'true', time() + 60, '/');

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
?>