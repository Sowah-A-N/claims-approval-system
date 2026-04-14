<?php
require_once __DIR__ . '/../../../../includes/headers.php';
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.4.0/dist/tabler-icons.min.css">
  <?php $rmu_css = ($pageTitle === 'Admin Dashboard') ? '../../assets/css/rmu-glass.css' : '../../../../assets/css/rmu-glass.css'; ?>
  <link rel="stylesheet" href="<?php echo $rmu_css; ?>">
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <?php if (!empty($include_charts)): ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
  <?php endif; ?>
</head>
