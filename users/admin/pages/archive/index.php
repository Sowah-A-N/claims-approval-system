<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../../../includes/archive.php';

checkUserRole(['admin', 'Admin']);
$CSRF = csrf_token();

archive_ensure_schema($conn);

$sections = archive_sections();
$sectionKeys = array_keys($sections);
$current = isset($_GET['section']) && isset($sections[$_GET['section']]) ? $_GET['section'] : $sectionKeys[0];
$cfg = $sections[$current];

$active   = archive_list($conn, $current, 'active');
$archived = archive_list($conn, $current, 'archived');
$activeCount   = archive_count($conn, $current, 'active');
$archivedCount = archive_count($conn, $current, 'archived');

$pageTitle = 'Archive';

/* Render one data cell value (dates get dd/mm/yyyy). */
function arch_cell($col, $val) {
    if ($val === null || $val === '') return '<span style="color:var(--txt-muted);">—</span>';
    if (preg_match('/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2})/', (string) $val)) {
        return h(date('d/m/Y H:i', strtotime($val)));
    }
    return h($val);
}
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
        <div class="rmu-page-header__title">Archive</div>
        <div class="rmu-page-header__sub">
          Move records out of the live database into the separate archive store, per section.
          Archived records disappear from the app but stay recoverable here.
        </div>
      </div>

      <!-- Section selector -->
      <div class="rmu-card" style="margin-bottom:20px;">
        <div class="rmu-card__body" style="padding:18px 24px;display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap;">
          <div class="rmu-form-group" style="margin:0;min-width:220px;">
            <label class="rmu-label" for="sectionSel">Section</label>
            <select id="sectionSel" class="rmu-select" onchange="location.href='?section='+encodeURIComponent(this.value)">
              <?php foreach ($sections as $k => $s): ?>
              <option value="<?php echo h($k); ?>" <?php echo $k === $current ? 'selected' : ''; ?>><?php echo h($s['label']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="display:flex;gap:10px;align-items:center;">
            <span class="rmu-badge rmu-badge--info"><?php echo (int) $activeCount; ?> active</span>
            <span class="rmu-badge rmu-badge--neutral"><?php echo (int) $archivedCount; ?> archived</span>
          </div>
        </div>
      </div>

      <!-- Active records -->
      <div class="rmu-card" style="margin-bottom:24px;">
        <div class="rmu-card__header" style="gap:12px;flex-wrap:wrap;">
          <span class="rmu-card__title"><i class="ti ti-database"></i> Active &mdash; <?php echo h($cfg['label']); ?></span>
          <button class="rmu-btn rmu-btn--warning rmu-btn--sm" style="margin-left:auto;" type="button"
                  id="archiveSelBtn" onclick="bulk('archive','active')" disabled>
            <i class="ti ti-archive"></i> Archive selected
          </button>
        </div>
        <div class="rmu-card__body" style="padding:0;">
          <div class="rmu-table-wrap">
            <table class="rmu-table">
              <thead><tr>
                <th style="width:36px;"><input type="checkbox" id="selAllActive" onclick="toggleAll('active',this.checked)" aria-label="Select all active"></th>
                <?php foreach ($cfg['columns'] as $c): ?><th scope="col"><?php echo h($c[1]); ?></th><?php endforeach; ?>
                <th scope="col" style="text-align:right;">Action</th>
              </tr></thead>
              <tbody>
              <?php if (empty($active)): ?>
                <tr><td colspan="<?php echo count($cfg['columns']) + 2; ?>" style="text-align:center;padding:26px;color:var(--txt-muted);">Nothing here.</td></tr>
              <?php else: foreach ($active as $row): ?>
                <tr>
                  <td><input type="checkbox" class="active-cb" value="<?php echo h($row['__id']); ?>" onclick="refreshBtns()" aria-label="Select record"></td>
                  <?php foreach ($cfg['columns'] as $c): ?><td><?php echo arch_cell($c[0], $row[$c[0]] ?? null); ?></td><?php endforeach; ?>
                  <td style="text-align:right;">
                    <button class="rmu-btn rmu-btn--warning rmu-btn--sm" type="button"
                            onclick="single('archive', '<?php echo h($row['__id']); ?>')">
                      <i class="ti ti-archive"></i> Archive
                    </button>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Archived records -->
      <div class="rmu-card">
        <div class="rmu-card__header" style="gap:12px;flex-wrap:wrap;">
          <span class="rmu-card__title"><i class="ti ti-archive"></i> Archived &mdash; <?php echo h($cfg['label']); ?></span>
          <div style="margin-left:auto;display:flex;gap:8px;">
            <button class="rmu-btn rmu-btn--success rmu-btn--sm" type="button" id="restoreSelBtn" onclick="bulk('restore','archived')" disabled>
              <i class="ti ti-restore"></i> Restore selected
            </button>
            <button class="rmu-btn rmu-btn--danger rmu-btn--sm" type="button" id="purgeSelBtn" onclick="bulk('purge','archived')" disabled>
              <i class="ti ti-trash"></i> Delete selected
            </button>
          </div>
        </div>
        <div class="rmu-card__body" style="padding:0;">
          <div class="rmu-table-wrap">
            <table class="rmu-table">
              <thead><tr>
                <th style="width:36px;"><input type="checkbox" id="selAllArchived" onclick="toggleAll('archived',this.checked)" aria-label="Select all archived"></th>
                <?php foreach ($cfg['columns'] as $c): ?><th scope="col"><?php echo h($c[1]); ?></th><?php endforeach; ?>
                <th scope="col">Archived</th>
                <th scope="col" style="text-align:right;">Actions</th>
              </tr></thead>
              <tbody>
              <?php if (empty($archived)): ?>
                <tr><td colspan="<?php echo count($cfg['columns']) + 3; ?>" style="text-align:center;padding:26px;color:var(--txt-muted);">No archived records.</td></tr>
              <?php else: foreach ($archived as $row): ?>
                <tr>
                  <td><input type="checkbox" class="archived-cb" value="<?php echo h($row['__id']); ?>" onclick="refreshBtns()" aria-label="Select record"></td>
                  <?php foreach ($cfg['columns'] as $c): ?><td><?php echo arch_cell($c[0], $row[$c[0]] ?? null); ?></td><?php endforeach; ?>
                  <td><?php echo arch_cell('archived_at', $row['archived_at'] ?? null); ?></td>
                  <td style="text-align:right;white-space:nowrap;">
                    <button class="rmu-btn rmu-btn--success rmu-btn--sm" type="button"
                            onclick="single('restore', '<?php echo h($row['__id']); ?>')">
                      <i class="ti ti-restore"></i> Restore
                    </button>
                    <button class="rmu-btn rmu-btn--danger rmu-btn--sm" type="button"
                            onclick="single('purge', '<?php echo h($row['__id']); ?>')">
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

<script>
const CSRF     = '<?php echo h($CSRF); ?>';
const SECTION  = '<?php echo h($current); ?>';
const swalOpts = { background: '#ffffff', color: '#0f2744' };

function toggleAll(scope, checked) {
  document.querySelectorAll('.' + scope + '-cb').forEach(function(cb){ cb.checked = checked; });
  refreshBtns();
}
function selected(scope) {
  return Array.from(document.querySelectorAll('.' + scope + '-cb:checked')).map(function(cb){ return cb.value; });
}
function refreshBtns() {
  document.getElementById('archiveSelBtn').disabled = selected('active').length === 0;
  document.getElementById('restoreSelBtn').disabled = selected('archived').length === 0;
  document.getElementById('purgeSelBtn').disabled   = selected('archived').length === 0;
}

const LABELS = {
  archive: { title: 'Archive selected?', text: 'They will be moved out of the live database into the archive.', btn: 'Archive', color: '#b45309' },
  restore: { title: 'Restore selected?', text: 'They will be moved back into the live database.', btn: 'Restore', color: '#047857' },
  purge:   { title: 'Delete permanently?', text: 'This cannot be undone — the records leave the archive for good.', btn: 'Delete', color: '#dc2626' }
};

function post(action, ids) {
  const fd = new FormData();
  fd.append('csrf_token', CSRF);
  fd.append('section', SECTION);
  fd.append('action', action);
  ids.forEach(function(id){ fd.append('ids[]', id); });
  return fetch('archiveAction.inc.php', { method:'POST', body: fd, credentials:'include' }).then(function(r){ return r.json(); });
}

function run(action, ids) {
  if (!ids.length) return;
  const L = LABELS[action];
  const go = function() {
    post(action, ids).then(function(j){
      if (j.success) {
        Swal.fire(Object.assign({ icon:'success', title:'Done', text:j.message, timer:1400, showConfirmButton:false }, swalOpts))
          .then(function(){ location.reload(); });
      } else {
        Swal.fire(Object.assign({ icon:'error', title:'Nothing changed', text:j.message || 'Please try again.' }, swalOpts));
      }
    }).catch(function(){ Swal.fire(Object.assign({ icon:'error', title:'Network error', text:'Please try again.' }, swalOpts)); });
  };
  if (typeof Swal !== 'undefined') {
    Swal.fire(Object.assign({ icon: action === 'purge' ? 'warning' : 'question', title:L.title, text:L.text,
      showCancelButton:true, confirmButtonText:L.btn, confirmButtonColor:L.color, cancelButtonColor:'#64748b' }, swalOpts))
      .then(function(res){ if (res.isConfirmed) go(); });
  } else if (confirm(L.title)) go();
}

function single(action, id) { run(action, [String(id)]); }
function bulk(action)       { run(action, selected(action === 'archive' ? 'active' : 'archived')); }
</script>

</body>
</html>
