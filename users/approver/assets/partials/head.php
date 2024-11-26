<?php
// Configure session settings before starting the session
ini_set('session.gc_maxlifetime', 3600); // Set the garbage collection lifetime to 3600 seconds (1 hour)

session_set_cookie_params([
    'lifetime' => 3600,  // Lifetime of the cookie in seconds
    'path' => '/',       // Path on the server where the cookie will be available
    'domain' => '',      // Domain for which the cookie is available (default is current domain)
    'secure' => false,   // Whether to only send the cookie over secure connections
    'httponly' => true,  // Whether to make the cookie accessible only through the HTTP protocol
]);

// Start the session if it hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID to prevent session fixation attacks
session_regenerate_id(true);

// Function to check if the user is logged in
function isUserLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check user role and redirect if unauthorized
function checkUserRole(array $allowedRole) {
    if (!isUserLoggedIn() || !in_array($_SESSION['role'], $allowedRole)) {
        header("Location: /");
        exit();
    }
}

// Check user role with allowed roles
checkUserRole(['approver', 'Approver']);
?>

<!DOCTYPE html>
<html lang="en">

    
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $pageTitle; ?></title>
    <link rel="shortcut icon" type="image/png" href="../images/logos/favicon.png" />
    <link rel="stylesheet" href="../approver/assets/css/styles.min.css" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="./assets/js/jquery-3.7.1.min.js"></script>
</head>
