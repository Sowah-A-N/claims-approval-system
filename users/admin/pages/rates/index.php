<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';

checkUserRole(['admin', 'Admin']);
csrf_token();

// Rank rates, with a live count of users currently on each rank.
$rows = array();
$res  = mysqli_query($conn,
    "SELECT lrr.`rank`, lrr.rate,
            (SELECT COUNT(*) FROM user_details ud WHERE ud.`rank` = lrr.`rank`) AS user_count
     FROM lecturer_rank_rate lrr
     ORDER BY lrr.`rank`");
if ($res) $rows = mysqli_fetch_all($res, MYSQLI_ASSOC);

$pageTitle = 'Rank Rates';
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
        <div class="rmu-page-header__title">Rank Rates</div>
        <div class="rmu-page-header__sub">
          Update the rate for a rank — saving propagates it to every user on that rank
        </div>
      </div>

      <div class="rmu-card" style="max-width:720px;">
        <div class="rmu-card__body" style="padding:0;">
          <div class="rmu-table-wrap">
            <table class="rmu-table">
              <thead>
                <tr>
                  <th>Rank</th>
                  <th>Users</th>
                  <th>Rate (GHS)</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($rows)): ?>
                <tr><td colspan="4" style="text-align:center;color:var(--txt-muted);padding:20px;">
                  No ranks configured.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                <tr>
                  <td><?php echo h($r['rank']); ?></td>
                  <td><span class="rmu-badge rmu-badge--neutral"><?php echo (int)$r['user_count']; ?></span></td>
                  <td>
                    <input type="number" step="0.01" min="0" class="rmu-input rate-input"
                           data-rank="<?php echo h($r['rank']); ?>"
                           value="<?php echo h(number_format((float)$r['rate'], 2, '.', '')); ?>"
                           style="width:140px;">
                  </td>
                  <td>
                    <button class="rmu-btn rmu-btn--primary rmu-btn--sm" onclick="saveRate(this)">
                      <i class="ti ti-device-floppy"></i> Save &amp; Propagate
                    </button>
                  </td>
                </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
const CSRF     = '<?php echo h(csrf_token()); ?>';
const swalOpts = { background: '#0d1b2a', color: '#e2e8f0' };

function saveRate(btn) {
  var row   = btn.closest('tr');
  var input = row.querySelector('.rate-input');
  var rank  = input.getAttribute('data-rank');
  var rate  = input.value;

  Swal.fire(Object.assign({
    title: 'Update rate for "' + rank + '"?',
    text:  'This overwrites the rate for every user on this rank.',
    icon:  'question', showCancelButton: true,
    confirmButtonText: 'Yes, Save', confirmButtonColor: '#3b82f6',
    cancelButtonColor: 'rgba(255,255,255,0.1)',
  }, swalOpts)).then(function(result) {
    if (!result.isConfirmed) return;
    btn.disabled = true;
    var fd = new FormData();
    fd.append('rank', rank);
    fd.append('rate', rate);
    fd.append('csrf_token', CSRF);
    fetch('updateRate.inc.php', { method: 'POST', body: fd })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        btn.disabled = false;
        Swal.fire(Object.assign({
          icon: res.success ? 'success' : 'error',
          title: res.success ? 'Saved' : 'Failed',
          text: res.message || '', timer: res.success ? 2200 : undefined,
          showConfirmButton: !res.success,
        }, swalOpts));
      })
      .catch(function() {
        btn.disabled = false;
        Swal.fire(Object.assign({ icon: 'error', title: 'Network Error',
          text: 'Please try again.' }, swalOpts));
      });
  });
}
</script>

</body>
</html>
