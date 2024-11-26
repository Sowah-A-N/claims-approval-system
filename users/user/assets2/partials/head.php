<?php

if (session_status() === PHP_SESSION_NONE) {
// Configure session settings
ini_set('session.gc_maxlifetime', 3600); // Set the garbage collection lifetime to 3600 seconds (1 hour)

session_set_cookie_params([
    'lifetime' => 3600,  // Lifetime of the cookie in seconds
    'path' => '/',       // Path on the server where the cookie will be available
    'domain' => '',      // Domain for which the cookie is available (default is current domain)
    'secure' => false,  // Whether to only send the cookie over secure connections
    'httponly' => true, // Whether to make the cookie accessible only through the HTTP protocol
]);

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

checkUserRole(['user', 'claimant']);

// Get dashboard link and text based on user role
//$userRole = $_SESSION['role'];

include_once '../../includes/conn.inc.php';

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
    header("Location: ../../");
    exit;
}

// Call the logout function when the logout button is clicked
if(isset($_POST['logout'])) {
    logout();
}

?> 

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.11.0/dist/sweetalert2.all.min.js" 
            integrity="sha256-4lhPGIWv8kmCP7JRGJE4IdRod2IdQEZPui6f0uICZ6w=" 
            crossorigin="anonymous">

        </script>

    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.11.0/dist/sweetalert2.min.css"
          integrity="sha256-h2Gkn+H33lnKlQTNntQyLXMWq7/9XI2rlPCsLsVcUBs="
          crossorigin="anonymous">


    <?php 
        if($pageTitle !== "User Dashboard"):
    ?>      
        <!----Custom toasts for SweetAlert files---->
        <script src="../js/toast.js"></script>

        <!-- Custom fonts for this template-->
      
        <!--link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" 
        rel="stylesheet"--> 

        <!-- Custom styles for this template-->
        <link rel="stylesheet" href="../../assets/vendors/mdi/css/materialdesignicons.min.css">
        <link rel="stylesheet" href="../../assets/vendors/ti-icons/css/themify-icons.css">
        <link rel="stylesheet" href="../../assets/vendors/css/vendor.bundle.base.css">
        <link rel="stylesheet" href="../../assets/vendors/font-awesome/css/font-awesome.min.css">
    
        <!-- endinject -->
        <!-- Plugin css for this page -->
        <!-- End Plugin css for this page -->
        <!-- inject:css -->
        <!-- endinject -->
        <!-- Layout styles -->
        <link rel="stylesheet" href="../../assets/css/style.css">
        <!-- End layout styles -->
        <link rel="shortcut icon" href="../../images/favicon.png" />
        <title><?php echo $pageTitle ?? "New Page" ?></title>

    <?php else: ?> 
        <!----Custom toasts for SweetAlert files---->
        <script src="../js/toast.js"></script>

        <!--link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" 
        rel="stylesheet"--> 

        <!-- Custom styles for this template-->
        <link rel="stylesheet" href="./assets/vendors/mdi/css/materialdesignicons.min.css">
        <link rel="stylesheet" href="./assets/vendors/ti-icons/css/themify-icons.css">
        <link rel="stylesheet" href="./assets/vendors/css/vendor.bundle.base.css">
        <link rel="stylesheet" href="./assets/vendors/font-awesome/css/font-awesome.min.css">

    
        <!-- endinject -->
        <!-- Plugin css for this page -->
        <!-- End Plugin css for this page -->
        <!-- inject:css -->
        <!-- endinject -->
        <!-- Layout styles -->
        <link rel="stylesheet" href="./assets/css/style.css">
        <!-- End layout styles -->
        <link rel="shortcut icon" href="../images/favicon.png" />
        <title><?php echo $pageTitle ?? "New Page" ?></title>

    <?php endif; ?>    
</head>