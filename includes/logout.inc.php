<?php
// logout_handler.php

session_start();

// Function to handle user logout
function logout() {
    // Unset all of the session variables
    $_SESSION = array();

    // If it's desired to delete the session cookie as well
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Finally, destroy the session
    session_destroy();
}

// Call the logout function
logout();

// Redirect to the login page or homepage
header("Location: ../../index.php"); 
exit();
