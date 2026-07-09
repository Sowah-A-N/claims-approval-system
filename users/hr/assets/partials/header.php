<?php
$base_url = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)
    ? '/claims-approval-system/'
    : '/';
$_hr_name = isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8') : 'HR';
$_hr_initials = '';
foreach (explode(' ', trim($_hr_name)) as $p) {
    if ($p !== '') { $_hr_initials .= strtoupper($p[0]); }
    if (strlen($_hr_initials) >= 2) break;
}
if ($_hr_initials === '') $_hr_initials = 'H';
$_hr_role_label = (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin')
    ? 'Administrator' : 'HR Officer';
?>
<header class="rmu-header" id="rmu-header">

  <div class="rmu-header__left">
    <button class="rmu-header__btn" id="sidebar-toggle" title="Toggle sidebar">
      <i class="ti ti-menu-2"></i>
    </button>
    <span class="rmu-header__title"><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') : 'HR Dashboard'; ?></span>
  </div>

  <div class="rmu-header__right">
    <div class="rmu-dropdown" id="profile-dropdown">
      <div class="rmu-header__avatar" id="profile-toggle" title="<?php echo $_hr_name; ?>">
        <?php echo $_hr_initials; ?>
      </div>
      <div class="rmu-dropdown__menu">
        <div style="padding:12px 16px 10px;border-bottom:1px solid var(--divider);">
          <div style="font-size:.85rem;font-weight:600;color:var(--txt-primary);"><?php echo $_hr_name; ?></div>
          <div style="font-size:.75rem;color:var(--txt-muted);"><?php echo $_hr_role_label; ?></div>
        </div>
        <a href="<?php echo $base_url; ?>logout.php" class="rmu-dropdown__item">
          <i class="ti ti-logout"></i> Logout
        </a>
      </div>
    </div>
  </div>

</header>

<script>
(function() {
  var toggle = document.getElementById('profile-toggle');
  var dd     = document.getElementById('profile-dropdown');
  if (toggle && dd) {
    toggle.addEventListener('click', function(e) {
      e.stopPropagation();
      dd.classList.toggle('open');
    });
    document.addEventListener('click', function() { dd.classList.remove('open'); });
  }

  var sidebarBtn = document.getElementById('sidebar-toggle');
  var sidebar    = document.getElementById('rmu-sidebar');
  if (sidebarBtn && sidebar) {
    sidebarBtn.addEventListener('click', function() {
      sidebar.classList.toggle('open');
    });
  }
})();
</script>
