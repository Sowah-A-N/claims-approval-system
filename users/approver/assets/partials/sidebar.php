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

    <a class="rmu-sidebar__link" href="./">
      <i class="ti ti-layout-dashboard"></i>
      <span>Dashboard</span>
    </a>

    <?php if (isset($_SESSION['stage']) && $_SESSION['stage'] == 1): ?>
    <a class="rmu-sidebar__link" href="new_lect.php">
      <i class="ti ti-user-plus"></i>
      <span>Add Lecturer</span>
    </a>
    <?php endif; ?>

    <a class="rmu-sidebar__link" href="reports.php">
      <i class="ti ti-report"></i>
      <span>Reports</span>
    </a>

    <div class="rmu-sidebar__section">Account</div>

    <a class="rmu-sidebar__link" href="logout.inc.php" id="approver-logout-link">
      <i class="ti ti-logout"></i>
      <span>Logout</span>
    </a>
  </nav>

</aside>

<form id="approver-logout-form" method="post" action="logout.inc.php" style="display:none;">
  <input type="hidden" name="logout" value="true">
</form>

<script>
(function() {
  var link = document.getElementById('approver-logout-link');
  if (link) {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      document.getElementById('approver-logout-form').submit();
    });
  }
})();
</script>
