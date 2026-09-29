<?php
$_approver_name = isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8') : 'Approver';
$_approver_initials = '';
foreach (explode(' ', trim($_approver_name)) as $p) {
    if ($p !== '') { $_approver_initials .= strtoupper($p[0]); }
    if (strlen($_approver_initials) >= 2) break;
}
if ($_approver_initials === '') $_approver_initials = 'A';
?>
<header class="rmu-header" id="rmu-header">

  <div class="rmu-header__left">
    <button class="rmu-header__btn" id="sidebar-toggle" title="Toggle sidebar">
      <i class="ti ti-menu-2"></i>
    </button>
    <span class="rmu-header__title"><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') : 'Approver Dashboard'; ?></span>
  </div>

  <div class="rmu-header__right">
    <div class="rmu-dropdown" id="profile-dropdown">
      <div class="rmu-header__avatar" id="profile-toggle" title="<?php echo $_approver_name; ?>">
        <?php echo $_approver_initials; ?>
      </div>
      <div class="rmu-dropdown__menu">
        <div style="padding:12px 16px 10px;border-bottom:1px solid rgba(255,255,255,0.08);">
          <div style="font-size:.85rem;font-weight:600;color:var(--txt-primary);"><?php echo $_approver_name; ?></div>
          <div style="font-size:.75rem;color:var(--txt-muted);">Approver <?php echo isset($_SESSION['stage']) ? '· Stage '.(int)$_SESSION['stage'] : ''; ?></div>
        </div>
        <a href="" id="logout" class="rmu-dropdown__item">
          <i class="ti ti-logout"></i> Logout
        </a>
      </div>
    </div>
  </div>

</header>

<form id="logout-form" method="post" action="./logout.inc.php" style="display:none;">
  <input type="hidden" name="logout" value="true">
</form>

<script>
(function() {
  document.getElementById('logout').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('logout-form').submit();
  });

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
