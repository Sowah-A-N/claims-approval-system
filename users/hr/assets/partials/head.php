<?php
require_once __DIR__ . '/../../../../includes/headers.php';
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
// Admins may also manage the HR register (oversight); HR users are the primary owners.
checkUserRole(['hr', 'HR', 'admin', 'Admin']);
?>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') : 'RMU HR'; ?></title>
  <link rel="shortcut icon" type="image/png" href="../../assets/images/logos/favicon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.4.0/dist/tabler-icons.min.css" integrity="sha384-ldmpcx1x0Xzlz3FRdxRDXdddHL6gUAnUo8m6ERvU0MbQIl53rnzI7hCF+Fd8lRsX" crossorigin="anonymous" referrerpolicy="no-referrer">
  <?php $rmu_css = ($pageTitle === 'HR Dashboard') ? '../../assets/css/rmu-glass.css?v=4' : '../../../../assets/css/rmu-glass.css?v=4'; ?>
  <link rel="stylesheet" href="<?php echo $rmu_css; ?>">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25/dist/sweetalert2.all.min.js" integrity="sha384-nLoOnA/BDh8A/jxqtckg4DumuCGOBYUnNJLZdQz/zfYNp3wcjGSoWTAzgko06G/2" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js" integrity="sha384-1H217gwSVyLSIfaLxHbE7dRb3v4mYCKbpQvzx0cegeju1MVsGrX5xXxAvs/HgeFs" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>
