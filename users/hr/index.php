<?php
$pageTitle = 'HR Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<?php
include './assets/partials/head.php';
require_once __DIR__ . '/../../includes/hr.queries.php';

$CSRF     = csrf_token();
$total    = db_hr_count($conn);
$linked   = db_hr_linked_accounts($conn);
$pending  = max(0, $total - $linked);
$employees = db_hr_list($conn);

// Department and rank options come from the database so the Add Employee form
// (and the import guide) always reflect the configured, valid values.
$departments = array();
$dres = mysqli_query($conn, 'SELECT dept_name FROM department ORDER BY dept_name');
while ($dres && $row = mysqli_fetch_row($dres)) $departments[] = $row[0];

$ranks = array();
$rres = mysqli_query($conn, 'SELECT `rank` FROM lecturer_rank_rate ORDER BY `rank`');
while ($rres && $row = mysqli_fetch_row($rres)) $ranks[] = $row[0];
?>
<body>

<?php include './assets/partials/sidebar.php'; ?>

<div class="rmu-main">

  <?php include './assets/partials/header.php'; ?>

  <div class="rmu-content">

    <div class="rmu-page-header" style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
      <div>
        <div class="rmu-page-header__title">Employee Register</div>
        <div class="rmu-page-header__sub">
          People on this list are recognised as bona-fide employees. When someone registers with a
          matching email, their account is <strong>activated automatically</strong> &mdash; no manual approval needed.
        </div>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a class="rmu-btn rmu-btn--secondary" href="templateEmployees.inc.php">
          <i class="ti ti-download"></i> CSV Template
        </a>
        <button class="rmu-btn rmu-btn--secondary" type="button" onclick="openImportModal()">
          <i class="ti ti-file-import"></i> Import CSV
        </button>
        <button class="rmu-btn rmu-btn--primary" type="button" onclick="openAddModal()">
          <i class="ti ti-user-plus"></i> Add Employee
        </button>
      </div>
    </div>

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:20px;">
      <div class="rmu-card"><div class="rmu-card__body">
        <div style="font-size:.78rem;color:var(--txt-muted);text-transform:uppercase;letter-spacing:.05em;">Total employees</div>
        <div style="font-size:1.7rem;font-weight:700;color:var(--txt-primary);"><?php echo (int) $total; ?></div>
      </div></div>
      <div class="rmu-card"><div class="rmu-card__body">
        <div style="font-size:.78rem;color:var(--txt-muted);text-transform:uppercase;letter-spacing:.05em;">Registered</div>
        <div style="font-size:1.7rem;font-weight:700;color:var(--clr-success,#16a34a);"><?php echo (int) $linked; ?></div>
      </div></div>
      <div class="rmu-card"><div class="rmu-card__body">
        <div style="font-size:.78rem;color:var(--txt-muted);text-transform:uppercase;letter-spacing:.05em;">Awaiting registration</div>
        <div style="font-size:1.7rem;font-weight:700;color:var(--txt-primary);"><?php echo (int) $pending; ?></div>
      </div></div>
    </div>

    <div class="rmu-card">
      <div class="rmu-card__header" style="gap:12px;flex-wrap:wrap;">
        <span class="rmu-card__title"><i class="ti ti-users-group"></i> Employees</span>
        <input type="search" id="hrSearch" class="rmu-input" placeholder="Search name, email, department…"
               style="max-width:280px;margin-left:auto;" oninput="filterRows()" aria-label="Search employees">
      </div>
      <div class="rmu-card__body" style="padding:0;">
        <div class="rmu-table-wrap">
          <table class="rmu-table" id="hrTable">
            <thead>
              <tr>
                <th scope="col">Name</th>
                <th scope="col">Email</th>
                <th scope="col">Staff ID</th>
                <th scope="col">Department</th>
                <th scope="col">Rank</th>
                <th scope="col">Status</th>
                <th scope="col" style="text-align:right;">Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php if (empty($employees)): ?>
              <tr id="hrEmptyRow"><td colspan="7" style="text-align:center;padding:28px;color:var(--txt-muted);">
                No employees yet. Add one, or import a CSV to get started.
              </td></tr>
            <?php else: foreach ($employees as $e):
              $full = trim($e['last_name'] . ', ' . $e['first_name'] . ' ' . ($e['other_names'] ?? ''));
              $hay  = strtolower($full . ' ' . $e['email'] . ' ' . ($e['department'] ?? '') . ' ' . ($e['staff_id'] ?? '')); ?>
              <tr data-hay="<?php echo h($hay); ?>">
                <td><?php echo h($full); ?></td>
                <td><?php echo h($e['email']); ?></td>
                <td><?php echo h($e['staff_id'] ?? '') !== '' ? h($e['staff_id']) : '—'; ?></td>
                <td><?php echo h($e['department'] ?? '') !== '' ? h($e['department']) : '—'; ?></td>
                <td><?php echo h($e['rank'] ?? '') !== '' ? h($e['rank']) : '—'; ?></td>
                <td><?php echo $e['registered']
                        ? '<span class="rmu-badge rmu-badge--success">Registered</span>'
                        : '<span class="rmu-badge rmu-badge--neutral">Not yet</span>'; ?></td>
                <td style="text-align:right;">
                  <button class="rmu-btn rmu-btn--danger rmu-btn--sm" type="button"
                          onclick="deleteEmployee(<?php echo (int) $e['id']; ?>, this)"
                          aria-label="Remove <?php echo h($full); ?> from the register">
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

<!-- Add Employee modal -->
<div class="rmu-modal-backdrop" id="addModal">
  <div class="rmu-modal" style="max-width:560px;width:calc(100% - 48px);">
    <div class="rmu-modal__header">
      <span class="rmu-modal__title"><i class="ti ti-user-plus" style="margin-right:8px;"></i>Add Employee</span>
      <button class="rmu-modal__close" type="button" onclick="closeAddModal()" aria-label="Close"><i class="ti ti-x"></i></button>
    </div>
    <div class="rmu-modal__body">
      <div class="rmu-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <div class="rmu-form-group">
          <label class="rmu-label" for="add-first">First name <span class="required">*</span></label>
          <input type="text" class="rmu-input" id="add-first" maxlength="50">
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label" for="add-last">Last name <span class="required">*</span></label>
          <input type="text" class="rmu-input" id="add-last" maxlength="50">
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label" for="add-other">Other names</label>
          <input type="text" class="rmu-input" id="add-other" maxlength="50">
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label" for="add-email">Email <span class="required">*</span></label>
          <input type="email" class="rmu-input" id="add-email" maxlength="120">
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label" for="add-phone">Phone</label>
          <input type="text" class="rmu-input" id="add-phone" maxlength="20">
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label" for="add-gender">Gender</label>
          <select class="rmu-select" id="add-gender">
            <option value="">—</option><option>Male</option><option>Female</option>
          </select>
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label" for="add-dept">Department</label>
          <select class="rmu-select" id="add-dept">
            <option value="">— Select department —</option>
            <?php foreach ($departments as $d): ?>
            <option value="<?php echo h($d); ?>"><?php echo h($d); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label" for="add-rank">Rank</label>
          <select class="rmu-select" id="add-rank">
            <option value="">— Select rank —</option>
            <?php foreach ($ranks as $r): ?>
            <option value="<?php echo h($r); ?>"><?php echo h($r); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="rmu-form-group" style="grid-column:1/-1;">
          <label class="rmu-label" for="add-staff">Staff ID</label>
          <input type="text" class="rmu-input" id="add-staff" maxlength="40">
        </div>
      </div>
      <div class="rmu-form-hint" style="margin-top:4px;">The email is what registration is checked against, so make sure it is exact.</div>
    </div>
    <div class="rmu-modal__footer" style="display:flex;justify-content:flex-end;gap:10px;padding:16px 24px;">
      <button class="rmu-btn rmu-btn--secondary" type="button" onclick="closeAddModal()">Cancel</button>
      <button class="rmu-btn rmu-btn--primary" type="button" id="add-save-btn" onclick="saveEmployee()">
        <i class="ti ti-device-floppy"></i> Save
      </button>
    </div>
  </div>
</div>

<!-- Import CSV modal -->
<div class="rmu-modal-backdrop" id="importModal">
  <div class="rmu-modal" style="max-width:520px;width:calc(100% - 48px);">
    <div class="rmu-modal__header">
      <span class="rmu-modal__title"><i class="ti ti-file-import" style="margin-right:8px;"></i>Import Employees (CSV)</span>
      <button class="rmu-modal__close" type="button" onclick="closeImportModal()" aria-label="Close"><i class="ti ti-x"></i></button>
    </div>
    <div class="rmu-modal__body">
      <p style="font-size:.85rem;color:var(--txt-secondary);margin-bottom:12px;">
        Download the <a href="templateEmployees.inc.php">CSV template</a>, fill it in, and upload it here.
        Existing emails are <strong>updated</strong>, not duplicated.
      </p>

      <!-- Valid-values guide (accurate: pulled from the database) -->
      <details open style="margin-bottom:14px;border:1px solid var(--divider);border-radius:8px;background:var(--surface-2);">
        <summary style="cursor:pointer;padding:10px 14px;font-weight:600;font-size:.85rem;">
          <i class="ti ti-info-circle"></i> Accepted columns &amp; valid values
        </summary>
        <div style="padding:4px 14px 14px;font-size:.8rem;color:var(--txt-secondary);">
          <table class="rmu-table" style="font-size:.78rem;">
            <thead><tr><th scope="col">Column</th><th scope="col">Required</th><th scope="col">Accepted values</th></tr></thead>
            <tbody>
              <tr><td><code>first_name</code></td><td>Yes</td><td>Any text</td></tr>
              <tr><td><code>last_name</code></td><td>Yes</td><td>Any text</td></tr>
              <tr><td><code>email</code></td><td>Yes</td><td>A valid email — this is what registration is matched against</td></tr>
              <tr><td><code>other_names</code></td><td>No</td><td>Any text</td></tr>
              <tr><td><code>phone_number</code></td><td>No</td><td>Digits, e.g. <code>0244000000</code></td></tr>
              <tr><td><code>gender</code></td><td>No</td><td><code>Male</code>, <code>Female</code>, or <code>Other</code></td></tr>
              <tr><td><code>department</code></td><td>No</td><td><?php echo $departments ? h(implode(', ', $departments)) : '<em>none configured yet</em>'; ?></td></tr>
              <tr><td><code>rank</code></td><td>No</td><td><?php echo $ranks ? h(implode(', ', $ranks)) : '<em>none configured yet</em>'; ?></td></tr>
              <tr><td><code>staff_id</code></td><td>No</td><td>Any text, e.g. <code>RMU-0001</code></td></tr>
            </tbody>
          </table>
          <p style="margin-top:8px;">Column order doesn’t matter; the header row is matched by name (case-insensitive). Rows missing a name or a valid email are skipped and reported.</p>
        </div>
      </details>

      <div class="rmu-form-group">
        <label class="rmu-label" for="import-file">CSV file <span class="required">*</span></label>
        <input type="file" class="rmu-input" id="import-file" accept=".csv,text/csv" aria-label="CSV file">
      </div>
    </div>
    <div class="rmu-modal__footer" style="display:flex;justify-content:flex-end;gap:10px;padding:16px 24px;">
      <button class="rmu-btn rmu-btn--secondary" type="button" onclick="closeImportModal()">Cancel</button>
      <button class="rmu-btn rmu-btn--primary" type="button" id="import-btn" onclick="doImport()">
        <i class="ti ti-upload"></i> Import
      </button>
    </div>
  </div>
</div>

<script>
const CSRF     = '<?php echo h($CSRF); ?>';
const swalOpts = { background: '#ffffff', color: '#0f2744' };

function toast(icon, title, text) {
  if (typeof Swal !== 'undefined') Swal.fire(Object.assign({ icon, title, text }, swalOpts));
  else alert(title + (text ? '\n' + text : ''));
}

// ── Search filter ───────────────────────────────────────────────────────────
function filterRows() {
  const q = document.getElementById('hrSearch').value.trim().toLowerCase();
  document.querySelectorAll('#hrTable tbody tr[data-hay]').forEach(function(tr) {
    tr.style.display = (!q || tr.getAttribute('data-hay').indexOf(q) !== -1) ? '' : 'none';
  });
}

// ── Add employee ──────────────────────────────────────────────────────────────
function openAddModal()  { document.getElementById('addModal').classList.add('open'); setTimeout(function(){ document.getElementById('add-first').focus(); }, 60); }
function closeAddModal() { document.getElementById('addModal').classList.remove('open'); }

function saveEmployee() {
  const g = function(id){ return document.getElementById(id).value.trim(); };
  const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!g('add-first') || !g('add-last') || !emailRe.test(g('add-email'))) {
    toast('error', 'Missing details', 'First name, last name and a valid email are required.');
    return;
  }
  const btn = document.getElementById('add-save-btn'); btn.disabled = true;
  const fd = new URLSearchParams({
    csrf_token: CSRF,
    first_name: g('add-first'), last_name: g('add-last'), other_names: g('add-other'),
    email: g('add-email'), phone_number: g('add-phone'), gender: g('add-gender'),
    department: g('add-dept'), rank: g('add-rank'), staff_id: g('add-staff')
  });
  fetch('./addEmployee.inc.php', { method:'POST', body: fd, credentials:'include' })
    .then(function(r){ return r.json(); })
    .then(function(j){
      if (j.success) { toast('success', 'Saved', j.message); setTimeout(function(){ location.reload(); }, 900); }
      else { btn.disabled = false; toast('error', 'Could not save', j.message || 'Please try again.'); }
    })
    .catch(function(){ btn.disabled = false; toast('error', 'Network error', 'Please try again.'); });
}

// ── Import CSV ──────────────────────────────────────────────────────────────
function openImportModal()  { document.getElementById('importModal').classList.add('open'); }
function closeImportModal() { document.getElementById('importModal').classList.remove('open'); }

function doImport() {
  const f = document.getElementById('import-file').files[0];
  if (!f) { toast('error', 'No file', 'Please choose a CSV file.'); return; }
  const btn = document.getElementById('import-btn'); btn.disabled = true;
  const fd = new FormData();
  fd.append('csrf_token', CSRF);
  fd.append('csv', f);
  fetch('./importEmployees.inc.php', { method:'POST', body: fd, credentials:'include' })
    .then(function(r){ return r.json(); })
    .then(function(j){
      btn.disabled = false;
      const detail = (j.errors && j.errors.length) ? ('\n' + j.errors.slice(0,6).join('\n')) : '';
      toast(j.success ? 'success' : 'warning', 'Import complete', (j.message || '') + detail);
      if (j.success && (j.inserted || j.updated)) setTimeout(function(){ location.reload(); }, 1200);
    })
    .catch(function(){ btn.disabled = false; toast('error', 'Network error', 'Please try again.'); });
}

// ── Delete ──────────────────────────────────────────────────────────────────
function deleteEmployee(id, btn) {
  const doDelete = function() {
    const fd = new URLSearchParams({ csrf_token: CSRF, id: String(id) });
    fetch('./deleteEmployee.inc.php', { method:'POST', body: fd, credentials:'include' })
      .then(function(r){ return r.json(); })
      .then(function(j){
        if (j.success) { const tr = btn.closest('tr'); if (tr) tr.remove(); toast('success', 'Removed', j.message); }
        else toast('error', 'Could not remove', j.message || 'Please try again.');
      })
      .catch(function(){ toast('error', 'Network error', 'Please try again.'); });
  };
  if (typeof Swal !== 'undefined') {
    Swal.fire(Object.assign({
      icon: 'warning', title: 'Remove employee?',
      text: 'They will no longer be auto-activated on registration.',
      showCancelButton: true, confirmButtonText: 'Remove', confirmButtonColor: '#dc2626'
    }, swalOpts)).then(function(res){ if (res.isConfirmed) doDelete(); });
  } else if (confirm('Remove this employee?')) doDelete();
}

// Close modals on backdrop click / Escape.
document.querySelectorAll('.rmu-modal-backdrop').forEach(function(bd){
  bd.addEventListener('click', function(e){ if (e.target === bd) bd.classList.remove('open'); });
});
document.addEventListener('keydown', function(e){
  if (e.key === 'Escape') document.querySelectorAll('.rmu-modal-backdrop.open').forEach(function(bd){ bd.classList.remove('open'); });
});
</script>

</body>
</html>
