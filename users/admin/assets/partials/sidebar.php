<?php
$base_url = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)
    ? '/claims-approval-system/'
    : '/';
?>
<aside class="rmu-sidebar" id="rmu-sidebar">

  <div class="rmu-sidebar__brand">
    <div class="rmu-sidebar__brand-icon">
      <i class="ti ti-shield"></i>
    </div>
    <div class="rmu-sidebar__brand-text">
      <span class="rmu-sidebar__brand-name">RMU Claims</span>
      <span class="rmu-sidebar__brand-sub">Admin Portal</span>
    </div>
  </div>

  <nav class="rmu-sidebar__nav">
    <div class="rmu-sidebar__section">Home</div>

    <a class="rmu-sidebar__link" href="<?php echo $base_url; ?>users/admin">
      <i class="ti ti-layout-dashboard"></i>
      <span>Dashboard</span>
    </a>

    <div class="rmu-sidebar__section">Overview</div>

    <a class="rmu-sidebar__link" href="<?php echo $base_url; ?>users/admin/pages/allUsers">
      <i class="ti ti-users"></i>
      <span>Users</span>
    </a>

    <a class="rmu-sidebar__link" href="<?php echo $base_url; ?>users/admin/pages/import">
      <i class="ti ti-file-import"></i>
      <span>Bulk Import</span>
    </a>

    <a class="rmu-sidebar__link" href="<?php echo $base_url; ?>users/admin/pages/allClaims">
      <i class="ti ti-cards"></i>
      <span>Claims</span>
    </a>

    <a class="rmu-sidebar__link" href="<?php echo $base_url; ?>users/admin/pages/logs">
      <i class="ti ti-history"></i>
      <span>Logs</span>
    </a>

    <a class="rmu-sidebar__link" href="<?php echo $base_url; ?>users/admin/pages/reports">
      <i class="ti ti-file-description"></i>
      <span>Reports</span>
    </a>

    <div class="rmu-sidebar__section">System</div>

    <a class="rmu-sidebar__link" href="<?php echo $base_url; ?>users/admin/pages/settings">
      <i class="ti ti-settings"></i>
      <span>Settings</span>
    </a>

    <a class="rmu-sidebar__link" href="<?php echo $base_url; ?>logout.php">
      <i class="ti ti-logout"></i>
      <span>Logout</span>
    </a>
  </nav>

</aside>
