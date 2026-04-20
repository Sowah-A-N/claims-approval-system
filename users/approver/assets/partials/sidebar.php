<?php
$base_url = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)
    ? '/claims-approval-system/'
    : '/';
?>
<aside class="rmu-sidebar" id="rmu-sidebar">

  <div class="rmu-sidebar__brand">
    <div class="rmu-sidebar__brand-icon">
      <i class="ti ti-shield-check"></i>
    </div>
    <div class="rmu-sidebar__brand-text">
      <span class="rmu-sidebar__brand-name">RMU Claims</span>
      <span class="rmu-sidebar__brand-sub">Approver Portal</span>
    </div>
  </div>

  <nav class="rmu-sidebar__nav">
    <div class="rmu-sidebar__section">Main</div>

    <a class="rmu-sidebar__link" href="<?php echo $base_url; ?>users/approver/">
      <i class="ti ti-layout-dashboard"></i>
      <span>Dashboard</span>
    </a>

    <?php if (isset($_SESSION['stage']) && $_SESSION['stage'] == 1): ?>
    <a class="rmu-sidebar__link" href="<?php echo $base_url; ?>users/approver/new_lect.php">
      <i class="ti ti-user-plus"></i>
      <span>Add Lecturer</span>
    </a>
    <?php endif; ?>

    <a class="rmu-sidebar__link" href="<?php echo $base_url; ?>users/approver/reports.php">
      <i class="ti ti-report"></i>
      <span>Reports</span>
    </a>

    <div class="rmu-sidebar__section">Account</div>

    <a class="rmu-sidebar__link" href="<?php echo $base_url; ?>logout.php">
      <i class="ti ti-logout"></i>
      <span>Logout</span>
    </a>
  </nav>

</aside>
