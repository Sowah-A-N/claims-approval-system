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
function checkUserRole(array $allowedRoles) {
    if (!isUserLoggedIn() || !in_array($_SESSION['role'], $allowedRoles)) {
        header("Location: /");
        exit();
    }
}

// Default favicon and stylesheet paths
$faviconPath = '../../assets/images/logos/favicon.png';
$stylesheetPath = '../../assets/css/styles.min.css';

// Page-specific configurations
$scripts = [
    'jquery' => 'https://code.jquery.com/jquery-3.7.1.min.js',
    'bootstrap' => 'https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js',
    'bootstrap_integrity' => 'sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa'
];

switch ($pageTitle) {
    case 'Admin Dashboard':
        $faviconPath = '../images/logos/favicon.png';
        $stylesheetPath = '../admin/assets/css/styles.min.css';
        break;

    case 'Reports':
        $scripts['chart'] = 'https://cdn.jsdelivr.net/npm/chart.js';
        $scripts['chart_version'] = 'https://cdn.jsdelivr.net/npm/chart.js@3.7.0';
        $scripts['moment'] = 'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js';
        break;
    
    default:
        checkUserRole(['admin', 'Admin']);
					
        break;
}

?>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') : 'Default Title'; ?></title>
    <link rel="shortcut icon" type="image/png" href="<?php echo $faviconPath; ?>" />
    <link rel="stylesheet" href="<?php echo $stylesheetPath; ?>" />
    <script src="<?php echo $scripts['jquery']; ?>"></script>
    <?php if (isset($scripts['chart'])): ?>
        <script src="<?php echo $scripts['chart']; ?>"></script>
        <script src="<?php echo $scripts['chart_version']; ?>"></script>
        <script src="<?php echo $scripts['moment']; ?>"></script>
    <?php endif; ?>
    <script src="<?php echo $scripts['bootstrap']; ?>" integrity="<?php echo $scripts['bootstrap_integrity']; ?>" crossorigin="anonymous"></script>
    <?php include_once '../../includes/conn.inc.php'; ?>
</head>
