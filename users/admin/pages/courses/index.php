<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';

checkUserRole(['admin', 'Admin']);
csrf_token();

$courses = [];
$cres = mysqli_query($conn,
    "SELECT code, name, department, credit_hours, contact_hours, archived
     FROM course ORDER BY department, name");
if ($cres) $courses = mysqli_fetch_all($cres, MYSQLI_ASSOC);

$departments = [];
$dres = mysqli_query($conn, "SELECT dept_name FROM department ORDER BY dept_name");
while ($dres && $d = mysqli_fetch_row($dres)) $departments[] = $d[0];

$pageTitle = 'Courses';
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
          <div class="rmu-page-header__title">Courses</div>
          <div class="rmu-page-header__sub">Add, edit and archive courses per department</div>
        </div>
        <button class="rmu-btn rmu-btn--primary" onclick="openCreate()">
          <i class="ti ti-plus"></i> Add Course
        </button>
      </div>

      <!-- Filters -->
      <div class="rmu-card" style="margin-bottom:24px;">
        <div class="rmu-card__body" style="padding:20px 24px;">
          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;align-items:flex-end;">
            <div class="rmu-form-group" style="margin:0;">
              <label class="rmu-label">Department</label>
              <select id="filter-dept" class="rmu-select">
                <option value="">All Departments</option>
                <?php foreach ($departments as $d): ?>
                <option value="<?php echo h(strtolower($d)); ?>"><?php echo h($d); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="rmu-form-group" style="margin:0;">
              <label class="rmu-label">Status</label>
              <select id="filter-status" class="rmu-select">
                <option value="">All</option>
                <option value="active">Active</option>
                <option value="archived">Archived</option>
              </select>
            </div>
            <div class="rmu-form-group" style="margin:0;">
              <label class="rmu-label">Search</label>
              <input type="search" id="filter-search" class="rmu-input" placeholder="Code or course name">
            </div>
            <div>
              <button id="btn-clear" class="rmu-btn rmu-btn--secondary" style="width:100%;">
                <i class="ti ti-x"></i> Clear
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="rmu-card">
        <div class="rmu-card__header">
          <span class="rmu-card__title"><i class="ti ti-book"></i> Courses</span>
          <span class="rmu-badge rmu-badge--neutral" id="row-count"><?php echo count($courses); ?> course<?php echo count($courses) !== 1 ? 's' : ''; ?></span>
        </div>
        <div class="rmu-card__body" style="padding:0;">
          <div class="rmu-table-wrap">
            <table class="rmu-table" id="coursesTable">
              <thead>
                <tr>
                  <th>Code</th><th>Course Name</th><th>Department</th>
                  <th>Credit</th><th>Contact</th><th>Status</th><th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($courses)): ?>
                <tr><td colspan="7" style="text-align:center;color:var(--txt-muted);padding:24px;">
                  No courses yet. Click <strong>Add Course</strong> or use Bulk Import.
                </td></tr>
                <?php else: foreach ($courses as $c):
                    $arch = (int)($c['archived'] ?? 0) === 1;
                ?>
                <tr data-dept="<?php echo h(strtolower($c['department'] ?? '')); ?>"
                    data-status="<?php echo $arch ? 'archived' : 'active'; ?>"
                    data-text="<?php echo h(strtolower(($c['code'] ?? '') . ' ' . ($c['name'] ?? ''))); ?>">
                  <td style="font-family:monospace;"><?php echo h($c['code']); ?></td>
                  <td><?php echo h($c['name']); ?></td>
                  <td><?php echo h($c['department']); ?></td>
                  <td><?php echo $c['credit_hours']  !== null ? (int)$c['credit_hours']  : '<span style="color:var(--txt-muted);">—</span>'; ?></td>
                  <td><?php echo $c['contact_hours'] !== null ? (int)$c['contact_hours'] : '<span style="color:var(--txt-muted);">—</span>'; ?></td>
                  <td>
                    <?php if ($arch): ?>
                      <span class="rmu-badge rmu-badge--warning">Archived</span>
                    <?php else: ?>
                      <span class="rmu-badge rmu-badge--success">Active</span>
                    <?php endif; ?>
                  </td>
                  <td style="white-space:nowrap;">
                    <button class="rmu-btn rmu-btn--secondary rmu-btn--sm" title="Edit"
                            data-code="<?php echo h($c['code']); ?>"
                            data-name="<?php echo h($c['name']); ?>"
                            data-dept="<?php echo h($c['department']); ?>"
                            data-credit="<?php echo $c['credit_hours'] !== null ? (int)$c['credit_hours'] : ''; ?>"
                            data-contact="<?php echo $c['contact_hours'] !== null ? (int)$c['contact_hours'] : ''; ?>"
                            onclick="openEdit(this)"><i class="ti ti-edit"></i></button>
                    <?php if ($arch): ?>
                    <button class="rmu-btn rmu-btn--success rmu-btn--sm" onclick="toggleArchive('<?php echo h($c['code']); ?>',0)" title="Restore">
                      <i class="ti ti-restore"></i> Restore
                    </button>
                    <?php else: ?>
                    <button class="rmu-btn rmu-btn--danger rmu-btn--sm" onclick="toggleArchive('<?php echo h($c['code']); ?>',1)" title="Archive">
                      <i class="ti ti-archive"></i> Archive
                    </button>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; endif; ?>
                <tr id="noMatchRow" hidden><td colspan="7" style="text-align:center;color:var(--txt-muted);padding:24px;">No courses match the filters.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Add/Edit Course Modal -->
<div class="rmu-modal-backdrop" id="courseModal" role="dialog" aria-modal="true" aria-labelledby="cm-title">
  <div class="rmu-modal" style="max-width:520px;width:calc(100% - 48px);">
    <div class="rmu-modal__header">
      <span class="rmu-modal__title" id="cm-title"><i class="ti ti-book" style="margin-right:8px;"></i>Add Course</span>
      <button class="rmu-modal__close" onclick="closeModal()" aria-label="Close"><i class="ti ti-x"></i></button>
    </div>
    <div class="rmu-modal__body">
      <input type="hidden" id="cm-mode" value="create">
      <div class="rmu-grid-2">
        <div class="rmu-form-group">
          <label class="rmu-label" for="cm-code">Course Code <span class="required">*</span></label>
          <input type="text" class="rmu-input" id="cm-code" maxlength="10" placeholder="e.g. CSE101">
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label" for="cm-dept">Department <span class="required">*</span></label>
          <select class="rmu-select" id="cm-dept">
            <option value="">— Select —</option>
            <?php foreach ($departments as $d): ?>
            <option value="<?php echo h($d); ?>"><?php echo h($d); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="rmu-form-group" style="grid-column:1/-1;">
          <label class="rmu-label" for="cm-name">Course Name <span class="required">*</span></label>
          <input type="text" class="rmu-input" id="cm-name" maxlength="255" placeholder="e.g. Data Structures">
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label" for="cm-credit">Credit Hours</label>
          <input type="number" class="rmu-input" id="cm-credit" min="0" max="20">
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label" for="cm-contact">Contact Hours</label>
          <input type="number" class="rmu-input" id="cm-contact" min="0" max="40">
        </div>
      </div>
      <div id="cm-error" class="rmu-alert rmu-alert--danger" style="display:none;margin-top:6px;"></div>
      <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:18px;">
        <button class="rmu-btn rmu-btn--secondary" onclick="closeModal()">Cancel</button>
        <button class="rmu-btn rmu-btn--primary" id="cm-save" onclick="saveCourse()"><i class="ti ti-device-floppy"></i> Save</button>
      </div>
    </div>
  </div>
</div>

<script>
const CSRF     = '<?php echo h(csrf_token()); ?>';
const swalOpts = { background: '#ffffff', color: '#0f2744' };

// ── Filters ──────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
  const rows = Array.from(document.querySelectorAll('#coursesTable tbody tr[data-text]'));
  const count = document.getElementById('row-count');
  const noMatch = document.getElementById('noMatchRow');

  function apply() {
    const dept   = document.getElementById('filter-dept').value;
    const status = document.getElementById('filter-status').value;
    const q      = document.getElementById('filter-search').value.trim().toLowerCase();
    let vis = 0;
    rows.forEach(function (r) {
      const show = (!dept   || r.dataset.dept   === dept)
                && (!status || r.dataset.status === status)
                && (!q      || r.dataset.text.includes(q));
      r.style.display = show ? '' : 'none';
      if (show) vis++;
    });
    count.textContent = vis + ' course' + (vis !== 1 ? 's' : '');
    if (noMatch) noMatch.hidden = !(rows.length && vis === 0);
  }
  ['filter-dept', 'filter-status'].forEach(function (id) {
    document.getElementById(id).addEventListener('change', apply);
  });
  document.getElementById('filter-search').addEventListener('input', apply);
  document.getElementById('btn-clear').addEventListener('click', function () {
    document.getElementById('filter-dept').value = '';
    document.getElementById('filter-status').value = '';
    document.getElementById('filter-search').value = '';
    apply();
  });
});

// ── Modal ────────────────────────────────────────────────────────────────────
function _err(msg) {
  const e = document.getElementById('cm-error');
  e.textContent = msg; e.style.display = msg ? 'block' : 'none';
}
function openModal() {
  document.getElementById('courseModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeModal() {
  document.getElementById('courseModal').classList.remove('open');
  document.body.style.overflow = '';
}
function openCreate() {
  document.getElementById('cm-mode').value = 'create';
  document.getElementById('cm-title').innerHTML = '<i class="ti ti-book" style="margin-right:8px;"></i>Add Course';
  document.getElementById('cm-code').value = '';
  document.getElementById('cm-code').readOnly = false;
  document.getElementById('cm-name').value = '';
  document.getElementById('cm-dept').value = '';
  document.getElementById('cm-credit').value = '';
  document.getElementById('cm-contact').value = '';
  _err('');
  openModal();
  setTimeout(function () { document.getElementById('cm-code').focus(); }, 60);
}
function openEdit(btn) {
  document.getElementById('cm-mode').value = 'edit';
  document.getElementById('cm-title').innerHTML = '<i class="ti ti-edit" style="margin-right:8px;"></i>Edit Course';
  document.getElementById('cm-code').value = btn.dataset.code;
  document.getElementById('cm-code').readOnly = true; // code is the key
  document.getElementById('cm-name').value = btn.dataset.name;
  document.getElementById('cm-dept').value = btn.dataset.dept;
  document.getElementById('cm-credit').value = btn.dataset.credit;
  document.getElementById('cm-contact').value = btn.dataset.contact;
  _err('');
  openModal();
}
document.getElementById('courseModal').addEventListener('click', function (e) { if (e.target === this) closeModal(); });
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape' && document.getElementById('courseModal').classList.contains('open')) closeModal();
});

function saveCourse() {
  const code = document.getElementById('cm-code').value.trim();
  const name = document.getElementById('cm-name').value.trim();
  const dept = document.getElementById('cm-dept').value;
  if (!code || !name || !dept) { _err('Code, name and department are required.'); return; }

  const btn = document.getElementById('cm-save');
  btn.disabled = true;
  const fd = new FormData();
  fd.append('csrf_token', CSRF);
  fd.append('mode', document.getElementById('cm-mode').value);
  fd.append('code', code);
  fd.append('name', name);
  fd.append('department', dept);
  fd.append('credit_hours', document.getElementById('cm-credit').value);
  fd.append('contact_hours', document.getElementById('cm-contact').value);

  fetch('saveCourse.inc.php', { method: 'POST', body: fd })
    .then(function (r) { return r.json(); })
    .then(function (res) {
      btn.disabled = false;
      if (res.success) {
        Swal.fire(Object.assign({ icon: 'success', title: 'Saved', text: res.message,
          timer: 1500, showConfirmButton: false }, swalOpts)).then(function () { location.reload(); });
      } else { _err(res.message || 'Could not save.'); }
    })
    .catch(function () { btn.disabled = false; _err('Network error. Please try again.'); });
}

function toggleArchive(code, archived) {
  Swal.fire(Object.assign({
    title: archived ? 'Archive course?' : 'Restore course?',
    text: archived ? 'It will no longer appear in the claim form.' : 'It will appear in the claim form again.',
    icon: 'question', showCancelButton: true,
    confirmButtonText: archived ? 'Yes, Archive' : 'Yes, Restore',
    confirmButtonColor: archived ? '#b45309' : '#047857', cancelButtonColor: '#64748b',
  }, swalOpts)).then(function (result) {
    if (!result.isConfirmed) return;
    const fd = new FormData();
    fd.append('csrf_token', CSRF); fd.append('code', code); fd.append('archived', archived);
    fetch('toggleCourseArchive.inc.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.success) {
          Swal.fire(Object.assign({ icon: 'success', title: 'Done', text: res.message,
            timer: 1400, showConfirmButton: false }, swalOpts)).then(function () { location.reload(); });
        } else { Swal.fire(Object.assign({ icon: 'error', title: 'Error', text: res.message || 'Failed.' }, swalOpts)); }
      })
      .catch(function () { Swal.fire(Object.assign({ icon: 'error', title: 'Network Error', text: 'Please try again.' }, swalOpts)); });
  });
}
</script>

</body>
</html>
