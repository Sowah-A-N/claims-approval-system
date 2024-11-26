<?php
// Start the session
session_start();

// Function to log out the user
function logout() {
    // Unset all of the session variables
    $_SESSION = array();

    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destroy the session
    session_destroy();

    // Redirect to the login page or any other appropriate page
    header("Location: ../");
    exit;
}

// Call the logout function when the logout button is clicked
if(isset($_POST['logout'])) {
    logout();
}
?>