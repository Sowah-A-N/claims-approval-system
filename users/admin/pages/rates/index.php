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

      <div class="rmu-page-header" style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
        <div>
          <div class="rmu-page-header__title">Rank Rates</div>
          <div class="rmu-page-header__sub">
            Add ranks, and set each rate — saving a rate propagates it to every user on that rank
          </div>
        </div>
        <button class="rmu-btn rmu-btn--primary" type="button" onclick="openAddRank()">
          <i class="ti ti-plus"></i> Add Rank
        </button>
      </div>

      <div class="rmu-card" style="max-width:760px;">
        <div class="rmu-card__body" style="padding:0;">
          <div class="rmu-table-wrap">
            <table class="rmu-table">
              <thead>
                <tr>
                  <th scope="col">Rank</th>
                  <th scope="col">Users</th>
                  <th scope="col">Rate (GHS)</th>
                  <th scope="col" style="text-align:right;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($rows)): ?>
                <tr id="ranksEmpty"><td colspan="4" style="text-align:center;color:var(--txt-muted);padding:20px;">
                  No ranks configured yet. Use <strong>Add Rank</strong> to create one.</td></tr>
                <?php else: foreach ($rows as $r): $uc = (int) $r['user_count']; ?>
                <tr>
                  <td><?php echo h($r['rank']); ?></td>
                  <td><span class="rmu-badge rmu-badge--neutral"><?php echo $uc; ?></span></td>
                  <td>
                    <input type="number" step="0.01" min="0" class="rmu-input rate-input"
                           data-rank="<?php echo h($r['rank']); ?>"
                           value="<?php echo h(number_format((float)$r['rate'], 2, '.', '')); ?>"
                           style="width:140px;" aria-label="Rate for <?php echo h($r['rank']); ?>">
                  </td>
                  <td style="text-align:right;white-space:nowrap;">
                    <button class="rmu-btn rmu-btn--primary rmu-btn--sm" onclick="saveRate(this)">
                      <i class="ti ti-device-floppy"></i> Save &amp; Propagate
                    </button>
                    <button class="rmu-btn rmu-btn--danger rmu-btn--sm" onclick="deleteRank(this)"
                            data-rank="<?php echo h($r['rank']); ?>"
                            <?php echo $uc > 0 ? 'disabled title="Reassign the ' . $uc . ' user(s) on this rank before deleting"' : 'title="Delete rank"'; ?>
                            aria-label="Delete <?php echo h($r['rank']); ?>">
                      <i class="ti ti-trash"></i>
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

<!-- Add Rank modal -->
<div class="rmu-modal-backdrop" id="addRankModal">
  <div class="rmu-modal" style="max-width:440px;width:calc(100% - 48px);">
    <div class="rmu-modal__header">
      <span class="rmu-modal__title"><i class="ti ti-plus" style="margin-right:8px;"></i>Add Rank</span>
      <button class="rmu-modal__close" type="button" onclick="closeAddRank()" aria-label="Close"><i class="ti ti-x"></i></button>
    </div>
    <div class="rmu-modal__body">
      <div class="rmu-form-group">
        <label class="rmu-label" for="new-rank">Rank name <span class="required">*</span></label>
        <input type="text" class="rmu-input" id="new-rank" maxlength="50" placeholder="e.g. Professor" autocomplete="off">
      </div>
      <div class="rmu-form-group">
        <label class="rmu-label" for="new-rate">Rate (GHS per period) <span class="required">*</span></label>
        <input type="number" class="rmu-input" id="new-rate" min="0" step="0.01" placeholder="0.00">
      </div>
      <div class="rmu-form-hint">New ranks start with no users; assign them from a user's profile.</div>
    </div>
    <div class="rmu-modal__footer" style="display:flex;justify-content:flex-end;gap:10px;padding:16px 24px;">
      <button class="rmu-btn rmu-btn--secondary" type="button" onclick="closeAddRank()">Cancel</button>
      <button class="rmu-btn rmu-btn--primary" type="button" id="add-rank-btn" onclick="saveNewRank()">
        <i class="ti ti-device-floppy"></i> Add Rank
      </button>
    </div>
  </div>
</div>

<script>
const CSRF     = '<?php echo h(csrf_token()); ?>';
const swalOpts = { background: '#ffffff', color: '#0f2744' };

function openAddRank() {
  document.getElementById('new-rank').value = '';
  document.getElementById('new-rate').value = '';
  document.getElementById('addRankModal').classList.add('open');
  setTimeout(function(){ document.getElementById('new-rank').focus(); }, 60);
}
function closeAddRank() { document.getElementById('addRankModal').classList.remove('open'); }

function saveNewRank() {
  var rank = document.getElementById('new-rank').value.trim();
  var rate = document.getElementById('new-rate').value.trim();
  if (!rank) { Swal.fire(Object.assign({ icon:'error', title:'Rank required', text:'Please enter a rank name.' }, swalOpts)); return; }
  if (rate === '' || isNaN(parseFloat(rate)) || parseFloat(rate) < 0) {
    Swal.fire(Object.assign({ icon:'error', title:'Invalid rate', text:'Rate must be a non-negative number.' }, swalOpts)); return;
  }
  var btn = document.getElementById('add-rank-btn'); btn.disabled = true;
  var fd = new FormData(); fd.append('csrf_token', CSRF); fd.append('rank', rank); fd.append('rate', rate);
  fetch('addRank.inc.php', { method:'POST', body: fd })
    .then(function(r){ return r.json(); })
    .then(function(res){
      btn.disabled = false;
      if (res.success) {
        Swal.fire(Object.assign({ icon:'success', title:'Added', text:res.message, timer:1500, showConfirmButton:false }, swalOpts))
          .then(function(){ location.reload(); });
      } else { Swal.fire(Object.assign({ icon:'error', title:'Could not add', text:res.message || 'Please try again.' }, swalOpts)); }
    })
    .catch(function(){ btn.disabled = false; Swal.fire(Object.assign({ icon:'error', title:'Network Error', text:'Please try again.' }, swalOpts)); });
}

function deleteRank(btn) {
  var rank = btn.getAttribute('data-rank');
  Swal.fire(Object.assign({
    icon:'warning', title:'Delete rank "' + rank + '"?',
    text:'This removes the rank and its rate. It has no users.',
    showCancelButton:true, confirmButtonText:'Delete', confirmButtonColor:'#dc2626', cancelButtonColor:'#64748b',
  }, swalOpts)).then(function(result){
    if (!result.isConfirmed) return;
    var fd = new FormData(); fd.append('csrf_token', CSRF); fd.append('rank', rank);
    fetch('deleteRank.inc.php', { method:'POST', body: fd })
      .then(function(r){ return r.json(); })
      .then(function(res){
        if (res.success) {
          var tr = btn.closest('tr'); if (tr) tr.remove();
          Swal.fire(Object.assign({ icon:'success', title:'Deleted', text:res.message, timer:1400, showConfirmButton:false }, swalOpts));
        } else { Swal.fire(Object.assign({ icon:'error', title:'Cannot delete', text:res.message || 'Please try again.' }, swalOpts)); }
      })
      .catch(function(){ Swal.fire(Object.assign({ icon:'error', title:'Network Error', text:'Please try again.' }, swalOpts)); });
  });
}

document.querySelectorAll('.rmu-modal-backdrop').forEach(function(bd){
  bd.addEventListener('click', function(e){ if (e.target === bd) bd.classList.remove('open'); });
});
document.addEventListener('keydown', function(e){
  if (e.key === 'Escape') document.querySelectorAll('.rmu-modal-backdrop.open').forEach(function(bd){ bd.classList.remove('open'); });
});

function saveRate(btn) {
  var row   = btn.closest('tr');
  var input = row.querySelector('.rate-input');
  var rank  = input.getAttribute('data-rank');
  var rate  = input.value;

  Swal.fire(Object.assign({
    title: 'Update rate for "' + rank + '"?',
    text:  'This overwrites the rate for every user on this rank.',
    icon:  'question', showCancelButton: true,
    confirmButtonText: 'Yes, Save', confirmButtonColor: '#1d4ed8',
    cancelButtonColor: '#64748b',
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
