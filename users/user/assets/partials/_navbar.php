<?php
$base_url = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)
    ? '/claims-approval-system/'
    : '/';

$full_name    = isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8') : 'User';
$initials     = '';
$parts = explode(' ', trim($full_name));
foreach ($parts as $p) { if ($p !== '') { $initials .= strtoupper($p[0]); } if (strlen($initials) >= 2) break; }
if ($initials === '') $initials = 'U';
?>
<header class="rmu-header" id="rmu-header">

  <div class="rmu-header__left">
    <!-- Sidebar toggle (mobile) -->
    <button class="rmu-header__btn" id="sidebar-toggle" title="Toggle sidebar">
      <i class="ti ti-menu-2"></i>
    </button>
    <span class="rmu-header__title"><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') : 'Dashboard'; ?></span>
  </div>

  <div class="rmu-header__right">
    <div class="rmu-dropdown" id="profile-dropdown">
      <div class="rmu-header__avatar" id="profile-toggle" title="<?php echo $full_name; ?>">
        <?php echo $initials; ?>
      </div>
      <div class="rmu-dropdown__menu">
        <div style="padding:12px 16px 10px; border-bottom:1px solid var(--divider);">
          <div style="font-size:.85rem;font-weight:600;color:var(--txt-primary);"><?php echo $full_name; ?></div>
          <div style="font-size:.75rem;color:var(--txt-muted);">Claimant</div>
        </div>
        <a class="rmu-dropdown__item" href="<?php echo $base_url; ?>users/user/pages/settings/">
          <i class="ti ti-settings"></i> Settings
        </a>
        <div class="rmu-dropdown__divider"></div>
        <a class="rmu-dropdown__item" href="<?php echo $base_url; ?>logout.php">
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
