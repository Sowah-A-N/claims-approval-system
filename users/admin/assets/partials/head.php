<?php
require_once __DIR__ . '/../../../../includes/headers.php';
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
checkUserRole(['admin', 'Admin']);

if (isset($pageTitle) && $pageTitle === 'Reports') {
    $include_charts = true;
}
?>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') : 'RMU Admin'; ?></title>
  <link rel="shortcut icon" type="image/png" href="<?php echo ($pageTitle === 'Admin Dashboard') ? '../images/logos/favicon.png' : '../../assets/images/logos/favicon.png'; ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.4.0/dist/tabler-icons.min.css" integrity="sha384-ldmpcx1x0Xzlz3FRdxRDXdddHL6gUAnUo8m6ERvU0MbQIl53rnzI7hCF+Fd8lRsX" crossorigin="anonymous" referrerpolicy="no-referrer">
  <?php $rmu_css = ($pageTitle === 'Admin Dashboard') ? '../../assets/css/rmu-glass.css?v=4' : '../../../../assets/css/rmu-glass.css?v=4'; ?>
  <link rel="stylesheet" href="<?php echo $rmu_css; ?>">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25/dist/sweetalert2.all.min.js" integrity="sha384-nLoOnA/BDh8A/jxqtckg4DumuCGOBYUnNJLZdQz/zfYNp3wcjGSoWTAzgko06G/2" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js" integrity="sha384-1H217gwSVyLSIfaLxHbE7dRb3v4mYCKbpQvzx0cegeju1MVsGrX5xXxAvs/HgeFs" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <?php if (!empty($include_charts)): ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js" integrity="sha384-4mFQWIqZrcfYZkFPEgIoT//zTEU64gEoH2tGV72Koyhoa9Fxz43YgroFUGj4/RAx" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js" integrity="sha384-Uz1UHyakAAz121kPY0Nx6ZGzYeUTy9zAtcpdwVmFCEwiTGPA2K6zSGgkKJEQfMhK" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <?php endif; ?>
</head>
