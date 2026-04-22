<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';

checkUserRole(['admin', 'Admin']);

// All submitted claims with derived status
$claims_stmt = mysqli_prepare($conn,
    "SELECT cd.claimId,
            CONCAT(ud.first_name, ' ', ud.last_name) AS full_name,
            cd.department,
            cd.programme,
            cd.course,
            cd.time_submitted,
            CASE
                WHEN cd.completed = 1 THEN 'Completed'
                WHEN cd.flagged   = 1 THEN 'Flagged'
                ELSE 'Pending'
            END AS status
     FROM claim_details cd
     JOIN user_details ud ON cd.userId = ud.userId
     ORDER BY cd.time_submitted DESC"
);
mysqli_stmt_execute($claims_stmt);
$claims = mysqli_fetch_all(mysqli_stmt_get_result($claims_stmt), MYSQLI_ASSOC);
mysqli_stmt_close($claims_stmt);

// Distinct filter options sourced from actual claim data
function fetch_distinct_claims($conn, $col) {
    $allowed = ['department' => true, 'programme' => true, 'course' => true];
    if (!isset($allowed[$col])) return [];
    $res = mysqli_query($conn,
        "SELECT DISTINCT `$col` FROM claim_details
         WHERE `$col` IS NOT NULL AND `$col` <> ''
         ORDER BY `$col`");
    return $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
}

$departments = fetch_distinct_claims($conn, 'department');
$programmes  = fetch_distinct_claims($conn, 'programme');
$courses     = fetch_distinct_claims($conn, 'course');

$pageTitle = 'Claims Overview';
?>
<!DOCTYPE html>
<html lang="en">
<?php include '../../assets/partials/head.php'; ?>
<body>

<?php include '../../assets/partials/sidebar.php'; ?>

<div class="page-wrapper" id="main-wrapper">
  <div class="body-wrapper">

    <?php include '../../assets/partials/header.php'; ?>

    <div class="container-fluid">

      <div class="rmu-page-header">
        <div class="rmu-page-header__title">Claims Overview</div>
        <div class="rmu-page-header__sub">All submitted claims across all departments and stages</div>
      </div>

      <!-- Filters -->
      <div class="rmu-card" style="margin-bottom:24px;">
        <div class="rmu-card__body" style="padding:20px 24px;">
          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;align-items:flex-end;">
            <div class="rmu-form-group" style="margin:0;">
              <label class="rmu-label">Department</label>
              <select id="filter-dept" class="rmu-select">
                <option value="">All Departments</option>
                <?php foreach ($departments as $d): ?>
                <option value="<?php echo h(strtolower($d['department'])); ?>"><?php echo h($d['department']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="rmu-form-group" style="margin:0;">
              <label class="rmu-label">Programme</label>
              <select id="filter-prog" class="rmu-select">
                <option value="">All Programmes</option>
                <?php foreach ($programmes as $p): ?>
                <option value="<?php echo h(strtolower($p['programme'])); ?>"><?php echo h($p['programme']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="rmu-form-group" style="margin:0;">
              <label class="rmu-label">Course</label>
              <select id="filter-course" class="rmu-select">
                <option value="">All Courses</option>
                <?php foreach ($courses as $c): ?>
                <option value="<?php echo h(strtolower($c['course'])); ?>"><?php echo h($c['course']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="rmu-form-group" style="margin:0;">
              <label class="rmu-label">Status</label>
              <select id="filter-status" class="rmu-select">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="flagged">Flagged</option>
                <option value="completed">Completed</option>
              </select>
            </div>
            <div>
              <button id="btn-clear" class="rmu-btn rmu-btn--secondary" style="width:100%;">
                <i class="ti ti-x"></i> Clear Filters
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="rmu-card">
        <div class="rmu-card__header">
          <span class="rmu-card__title"><i class="ti ti-files"></i> Claims</span>
          <span class="rmu-badge rmu-badge--neutral" id="row-count"><?php echo count($claims); ?> claim<?php echo count($claims) !== 1 ? 's' : ''; ?></span>
        </div>
        <div class="rmu-card__body" style="padding:0;">
          <div class="rmu-table-wrap">
            <table class="rmu-table" id="claimsTable">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Claim ID</th>
                  <th>Claimant</th>
                  <th>Department</th>
                  <th>Programme</th>
                  <th>Course</th>
                  <th>Date Submitted</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($claims)): ?>
                <tr><td colspan="8" style="text-align:center;color:var(--txt-muted);padding:20px;">No claims found.</td></tr>
                <?php else: $i = 1; foreach ($claims as $cl): ?>
                <tr data-dept="<?php echo h(strtolower($cl['department'])); ?>"
                    data-prog="<?php echo h(strtolower($cl['programme'])); ?>"
                    data-course="<?php echo h(strtolower($cl['course'])); ?>"
                    data-status="<?php echo h(strtolower($cl['status'])); ?>">
                  <td><?php echo $i++; ?></td>
                  <td><?php echo (int)$cl['claimId']; ?></td>
                  <td><?php echo h($cl['full_name']); ?></td>
                  <td><?php echo h($cl['department']); ?></td>
                  <td><?php echo h($cl['programme']); ?></td>
                  <td><?php echo h($cl['course']); ?></td>
                  <td><?php echo h(date('d M Y', strtotime($cl['time_submitted']))); ?></td>
                  <td>
                    <?php
                    $s = $cl['status'];
                    $badge = $s === 'Completed' ? 'rmu-badge--success'
                           : ($s === 'Flagged'  ? 'rmu-badge--danger'
                           :                      'rmu-badge--neutral');
                    echo '<span class="rmu-badge ' . $badge . '">' . h($s) . '</span>';
                    ?>
                  </td>
                </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div><!-- .container-fluid -->
  </div><!-- .body-wrapper -->
</div><!-- .page-wrapper -->

<script>
document.addEventListener('DOMContentLoaded', function() {
  var rows     = Array.from(document.querySelectorAll('#claimsTable tbody tr[data-status]'));
  var rowCount = document.getElementById('row-count');

  function applyFilters() {
    var dept   = document.getElementById('filter-dept').value;
    var prog   = document.getElementById('filter-prog').value;
    var course = document.getElementById('filter-course').value;
    var status = document.getElementById('filter-status').value;
    var vis = 0;
    rows.forEach(function(r) {
      var show = (!dept   || r.dataset.dept   === dept)
              && (!prog   || r.dataset.prog   === prog)
              && (!course || r.dataset.course === course)
              && (!status || r.dataset.status === status);
      r.style.display = show ? '' : 'none';
      if (show) vis++;
    });
    rowCount.textContent = vis + ' claim' + (vis !== 1 ? 's' : '');
  }

  ['filter-dept','filter-prog','filter-course','filter-status'].forEach(function(id) {
    document.getElementById(id).addEventListener('change', applyFilters);
  });

  document.getElementById('btn-clear').addEventListener('click', function() {
    ['filter-dept','filter-prog','filter-course','filter-status'].forEach(function(id) {
      document.getElementById(id).value = '';
    });
    applyFilters();
  });
});
</script>

</body>
</html>
