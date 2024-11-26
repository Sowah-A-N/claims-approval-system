<?php if(isset($pageTitle) && $pageTitle == "Admin Dashboard"):?>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo $pageTitle; ?></title>
        <link rel="shortcut icon" type="image/png" href="../images/logos/favicon.png" />
        <link rel="stylesheet" href="../admin/assets/css/styles.min.css" />
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" 
                integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" 
                crossorigin="anonymous">
        </script>
        <?php include_once '../../includes/conn.inc.php'; ?>
    </head>
<?php elseif(isset($pageTitle) && $pageTitle == "Reports"):?>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo $pageTitle; ?></title>
        <link rel="shortcut icon" type="image/png" href="../../assets/images/logos/favicon.png" />
        <link rel="stylesheet" href="../../assets/css/styles.min.css" />
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" 
                integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" 
                crossorigin="anonymous">
        </script>
        <?php include_once '../../includes/conn.inc.php'; ?>
    </head>
    <?php else:?>
<?php
// Configure session settings
    ini_set('session.gc_maxlifetime', 3600); // Set the garbage collection lifetime to 3600 seconds (1 hour)
    
    session_set_cookie_params([
        'lifetime' => 3600,  // Lifetime of the cookie in seconds
        'path' => '/',       // Path on the server where the cookie will be available
        'domain' => '',      // Domain for which the cookie is available (default is current domain)
        'secure' => false,  // Whether to only send the cookie over secure connections
        'httponly' => true, // Whether to make the cookie accessible only through the HTTP protocol
    ]);
    
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
    
    checkUserRole(['approver', 'Approver']);?>
        <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo $pageTitle; ?></title>
        <link rel="shortcut icon" type="image/png" href="../../assets/images/logos/favicon.png" />
        <link rel="stylesheet" href="../../assets/css/styles.min.css" />
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" 
                integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" 
                crossorigin="anonymous">
        </script>
        <?php include_once '../../includes/conn.inc.php'; ?>
    </head>
<?php endif;?>