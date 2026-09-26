<?php
// Start the session
session_start();

// CSRF protection (A-11): only log out on a request carrying this session's token,
// so a cross-site GET can't force-log-out the user. If the token is missing or
// wrong, do nothing and return to the app.
$token    = isset($_GET['token']) ? $_GET['token'] : (isset($_POST['token']) ? $_POST['token'] : '');
$expected = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';
if ($expected === '' || !is_string($token) || !hash_equals($expected, $token)) {
    header('Location: index.php');
    exit();
}

// Unset all session variables
$_SESSION = array();

// Expire the session cookie itself so the browser drops the session id.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}

// Destroy the session
session_destroy();

// Redirect to the login page or any other page after logout
header("Location: index.php");
exit();
