<?php
require_once __DIR__ . '/../../../includes/headers.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/db.php';

// Admin Dashboard has its own role check path via the switch below;
// all other admin pages enforce the role here.
checkUserRole(['admin', 'Admin']);

// Default favicon and stylesheet paths
$faviconPath = '../../assets/images/logos/favicon.png';
$stylesheetPath = '../../assets/css/styles.min.css';

// Page-specific configurations
$scripts = [
    'jquery'              => 'https://code.jquery.com/jquery-3.7.1.min.js',
    'bootstrap'           => 'https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js',
    'bootstrap_integrity' => 'sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa',
];

switch ($pageTitle) {
    case 'Admin Dashboard':
        $faviconPath    = '../images/logos/favicon.png';
        $stylesheetPath = '../admin/assets/css/styles.min.css';
        break;

    case 'Reports':
        $scripts['chart']         = 'https://cdn.jsdelivr.net/npm/chart.js';
        $scripts['chart_version'] = 'https://cdn.jsdelivr.net/npm/chart.js@3.7.0';
        $scripts['moment']        = 'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js';
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
</head>
