<?php
// Start the session
session_start();

// Set session timeout period (in seconds)
$session_timeout = 1800; // 30 minutes

// Check if the session has expired
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $session_timeout) {
    // Session expired
    session_unset();     // Unset all session variables
    session_destroy();   // Destroy the session
    echo 'Your session has expired. Please <a href="login.php">log in again</a>.';
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Check if user is logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    echo 'Welcome back, ' . htmlspecialchars($_SESSION['username']) . '!<br>';
    echo '<a href="logout.php">Log out</a>';
} else {
    echo 'You are not logged in. Please <a href="login.php">log in</a>.';
}
