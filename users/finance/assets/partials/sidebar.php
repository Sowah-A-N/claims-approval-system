<?php
$base_url = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)
    ? '/claims-approval-system/'
    : '/';
?>
<aside class="rmu-sidebar" id="rmu-sidebar">

  <div class="rmu-sidebar__brand">
    <div class="rmu-sidebar__brand-icon">
      <i class="ti ti-building-bank"></i>
    </div>
    <div class="rmu-sidebar__brand-text">
      <span class="rmu-sidebar__brand-name">RMU Claims</span>
      <span class="rmu-sidebar__brand-sub">Finance Portal</span>
    </div>
  </div>

  <nav class="rmu-sidebar__nav">
    <div class="rmu-sidebar__section">Main</div>

    <a class="rmu-sidebar__link" href="<?php echo $base_url; ?>users/finance">
      <i class="ti ti-layout-dashboard"></i>
      <span>Dashboard</span>
    </a>

    <a class="rmu-sidebar__link" href="<?php echo $base_url; ?>users/finance/pages/paidClaims">
      <i class="ti ti-receipt"></i>
      <span>Paid Claims</span>
    </a>

    <div class="rmu-sidebar__section">Account</div>

    <a class="rmu-sidebar__link" href="<?php echo $base_url; ?>logout.php">
      <i class="ti ti-logout"></i>
      <span>Logout</span>
    </a>
  </nav>

</aside>
