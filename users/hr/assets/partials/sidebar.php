<?php
$base_url = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)
    ? '/claims-approval-system/'
    : '/';
?>
<aside class="rmu-sidebar" id="rmu-sidebar">

  <div class="rmu-sidebar__brand">
    <div class="rmu-sidebar__brand-icon">
      <i class="ti ti-id-badge-2"></i>
    </div>
    <div class="rmu-sidebar__brand-text">
      <span class="rmu-sidebar__brand-name">RMU Claims</span>
      <span class="rmu-sidebar__brand-sub">HR Portal</span>
    </div>
  </div>

  <nav class="rmu-sidebar__nav">
    <div class="rmu-sidebar__section">Main</div>

    <a class="rmu-sidebar__link" href="<?php echo $base_url; ?>users/hr">
      <i class="ti ti-users-group"></i>
      <span>Employee Register</span>
    </a>

    <div class="rmu-sidebar__section">Account</div>

    <a class="rmu-sidebar__link" href="<?php echo $base_url; ?>logout.php">
      <i class="ti ti-logout"></i>
      <span>Logout</span>
    </a>
  </nav>

</aside>
