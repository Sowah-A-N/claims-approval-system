<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';

checkUserRole(['admin', 'Admin']);
csrf_token();

// All holidays, newest year first then chronological within the year.
$holidays = [];
$res = mysqli_query($conn, "SELECT id, holiday_date, description FROM holidays ORDER BY holiday_date");
if ($res) $holidays = mysqli_fetch_all($res, MYSQLI_ASSOC);

// Distinct years for the filter.
$years = [];
foreach ($holidays as $hh) { $years[substr($hh['holiday_date'], 0, 4)] = true; }
$years = array_keys($years);
rsort($years);
$thisYear = date('Y');

$pageTitle = 'Holiday Calendar';
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
          <div class="rmu-page-header__title">Holiday Calendar</div>
          <div class="rmu-page-header__sub">Public holidays are skipped when generating recurring teaching dates</div>
        </div>
        <button class="rmu-btn rmu-btn--primary" onclick="openCreate()">
          <i class="ti ti-plus"></i> Add Holiday
        </button>
      </div>

      <!-- Filters -->
      <div class="rmu-card" style="margin-bottom:24px;">
        <div class="rmu-card__body" style="padding:20px 24px;">
          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;align-items:flex-end;">
            <div class="rmu-form-group" style="margin:0;">
              <label class="rmu-label">Year</label>
              <select id="filter-year" class="rmu-select">
                <option value="">All Years</option>
                <?php foreach ($years as $y): ?>
                <option value="<?php echo h($y); ?>" <?php echo ($y === $thisYear) ? 'selected' : ''; ?>><?php echo h($y); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="rmu-form-group" style="margin:0;">
              <label class="rmu-label">Search</label>
              <input type="search" id="filter-search" class="rmu-input" placeholder="Holiday name">
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
          <span class="rmu-card__title"><i class="ti ti-calendar-event"></i> Holidays</span>
          <span class="rmu-badge rmu-badge--neutral" id="row-count"><?php echo count($holidays); ?> holiday<?php echo count($holidays) !== 1 ? 's' : ''; ?></span>
        </div>
        <div class="rmu-card__body" style="padding:0;">
          <div class="rmu-table-wrap">
            <table class="rmu-table" id="holidaysTable">
              <thead>
                <tr>
                  <th>Date</th><th>Day</th><th>Holiday</th><th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($holidays)): ?>
                <tr><td colspan="4" style="text-align:center;color:var(--txt-muted);padding:24px;">
                  No holidays yet. Click <strong>Add Holiday</strong>.
                </td></tr>
                <?php else: foreach ($holidays as $hh):
                    $ts   = strtotime($hh['holiday_date']);
                    $year = substr($hh['holiday_date'], 0, 4);
                    $isWeekend = in_array(date('N', $ts), ['6', '7']);
                ?>
                <tr data-year="<?php echo h($year); ?>"
                    data-text="<?php echo h(strtolower($hh['description'])); ?>">
                  <td style="white-space:nowrap;font-weight:500;"><?php echo h(date('d/m/Y', $ts)); ?></td>
                  <td style="color:<?php echo $isWeekend ? 'var(--txt-muted)' : 'inherit'; ?>;"><?php echo h(date('l', $ts)); ?></td>
                  <td><?php echo h($hh['description']); ?></td>
                  <td style="white-space:nowrap;">
                    <button class="rmu-btn rmu-btn--secondary rmu-btn--sm" title="Edit"
                            data-id="<?php echo (int) $hh['id']; ?>"
                            data-date="<?php echo h($hh['holiday_date']); ?>"
                            data-desc="<?php echo h($hh['description']); ?>"
                            onclick="openEdit(this)"><i class="ti ti-edit"></i></button>
                    <button class="rmu-btn rmu-btn--danger rmu-btn--sm" title="Delete"
                            onclick="deleteHoliday(<?php echo (int) $hh['id']; ?>, '<?php echo h(addslashes($hh['description'])); ?>')">
                      <i class="ti ti-trash"></i>
                    </button>
                  </td>
                </tr>
                <?php endforeach; endif; ?>
                <tr id="noMatchRow" hidden><td colspan="4" style="text-align:center;color:var(--txt-muted);padding:24px;">No holidays match the filters.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Add/Edit Holiday Modal -->
<div class="rmu-modal-backdrop" id="holidayModal" role="dialog" aria-modal="true" aria-labelledby="hm-title">
  <div class="rmu-modal" style="max-width:460px;width:calc(100% - 48px);">
    <div class="rmu-modal__header">
      <span class="rmu-modal__title" id="hm-title"><i class="ti ti-calendar-plus" style="margin-right:8px;"></i>Add Holiday</span>
      <button class="rmu-modal__close" onclick="closeModal()" aria-label="Close"><i class="ti ti-x"></i></button>
    </div>
    <div class="rmu-modal__body">
      <input type="hidden" id="hm-mode" value="create">
      <input type="hidden" id="hm-id" value="">
      <div class="rmu-form-group">
        <label class="rmu-label" for="hm-date">Date <span class="required">*</span></label>
        <input type="date" class="rmu-input" id="hm-date">
      </div>
      <div class="rmu-form-group">
        <label class="rmu-label" for="hm-desc">Holiday name <span class="required">*</span></label>
        <input type="text" class="rmu-input" id="hm-desc" maxlength="100" placeholder="e.g. Founders' Day">
      </div>
      <div id="hm-error" class="rmu-alert rmu-alert--danger" style="display:none;margin-top:6px;"></div>
      <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:18px;">
        <button class="rmu-btn rmu-btn--secondary" onclick="closeModal()">Cancel</button>
        <button class="rmu-btn rmu-btn--primary" id="hm-save" onclick="saveHoliday()"><i class="ti ti-device-floppy"></i> Save</button>
      </div>
    </div>
  </div>
</div>

<script>
const CSRF     = '<?php echo h(csrf_token()); ?>';
const swalOpts = { background: '#ffffff', color: '#0f2744' };

// ── Filters ──────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
  const rows = Array.from(document.querySelectorAll('#holidaysTable tbody tr[data-text]'));
  const count = document.getElementById('row-count');
  const noMatch = document.getElementById('noMatchRow');

  function apply() {
    const year = document.getElementById('filter-year').value;
    const q    = document.getElementById('filter-search').value.trim().toLowerCase();
    let vis = 0;
    rows.forEach(function (r) {
      const show = (!year || r.dataset.year === year)
                && (!q    || r.dataset.text.includes(q));
      r.style.display = show ? '' : 'none';
      if (show) vis++;
    });
    count.textContent = vis + ' holiday' + (vis !== 1 ? 's' : '');
    if (noMatch) noMatch.hidden = !(rows.length && vis === 0);
  }
  document.getElementById('filter-year').addEventListener('change', apply);
  document.getElementById('filter-search').addEventListener('input', apply);
  document.getElementById('btn-clear').addEventListener('click', function () {
    document.getElementById('filter-year').value = '';
    document.getElementById('filter-search').value = '';
    apply();
  });
  apply(); // default to current year
});

// ── Modal ────────────────────────────────────────────────────────────────────
function _err(msg) {
  const e = document.getElementById('hm-error');
  e.textContent = msg; e.style.display = msg ? 'block' : 'none';
}
function openModal() {
  document.getElementById('holidayModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeModal() {
  document.getElementById('holidayModal').classList.remove('open');
  document.body.style.overflow = '';
}
function openCreate() {
  document.getElementById('hm-mode').value = 'create';
  document.getElementById('hm-id').value = '';
  document.getElementById('hm-title').innerHTML = '<i class="ti ti-calendar-plus" style="margin-right:8px;"></i>Add Holiday';
  document.getElementById('hm-date').value = '';
  document.getElementById('hm-desc').value = '';
  _err('');
  openModal();
  setTimeout(function () { document.getElementById('hm-date').focus(); }, 60);
}
function openEdit(btn) {
  document.getElementById('hm-mode').value = 'edit';
  document.getElementById('hm-id').value = btn.dataset.id;
  document.getElementById('hm-title').innerHTML = '<i class="ti ti-edit" style="margin-right:8px;"></i>Edit Holiday';
  document.getElementById('hm-date').value = btn.dataset.date;
  document.getElementById('hm-desc').value = btn.dataset.desc;
  _err('');
  openModal();
}
document.getElementById('holidayModal').addEventListener('click', function (e) { if (e.target === this) closeModal(); });
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape' && document.getElementById('holidayModal').classList.contains('open')) closeModal();
});

function saveHoliday() {
  const date = document.getElementById('hm-date').value.trim();
  const desc = document.getElementById('hm-desc').value.trim();
  if (!date || !desc) { _err('Date and holiday name are required.'); return; }

  const btn = document.getElementById('hm-save');
  btn.disabled = true;
  const fd = new FormData();
  fd.append('csrf_token', CSRF);
  fd.append('mode', document.getElementById('hm-mode').value);
  fd.append('id', document.getElementById('hm-id').value);
  fd.append('holiday_date', date);
  fd.append('description', desc);

  fetch('saveHoliday.inc.php', { method: 'POST', body: fd })
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

function deleteHoliday(id, name) {
  Swal.fire(Object.assign({
    title: 'Remove holiday?',
    text: name + ' will no longer be skipped in recurring dates.',
    icon: 'warning', showCancelButton: true,
    confirmButtonText: 'Yes, remove',
    confirmButtonColor: '#d62828', cancelButtonColor: '#64748b',
  }, swalOpts)).then(function (result) {
    if (!result.isConfirmed) return;
    const fd = new FormData();
    fd.append('csrf_token', CSRF); fd.append('id', id);
    fetch('deleteHoliday.inc.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.success) {
          Swal.fire(Object.assign({ icon: 'success', title: 'Removed', text: res.message,
            timer: 1400, showConfirmButton: false }, swalOpts)).then(function () { location.reload(); });
        } else { Swal.fire(Object.assign({ icon: 'error', title: 'Error', text: res.message || 'Failed.' }, swalOpts)); }
      })
      .catch(function () { Swal.fire(Object.assign({ icon: 'error', title: 'Network Error', text: 'Please try again.' }, swalOpts)); });
  });
}
</script>

</body>
</html>
