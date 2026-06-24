<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../queries/user.queries.php';

checkUserRole(['admin', 'Admin']);
csrf_token();

$users = db_get_all_users($conn);

$dept_res  = mysqli_query($conn,
    "SELECT DISTINCT department FROM user_details
     WHERE department IS NOT NULL AND department <> ''
     ORDER BY department");
$departments = $dept_res ? mysqli_fetch_all($dept_res, MYSQLI_ASSOC) : [];

$role_res = mysqli_query($conn,
    "SELECT DISTINCT role FROM user_details
     WHERE role IS NOT NULL AND role <> ''
     ORDER BY role");
$roles = $role_res ? mysqli_fetch_all($role_res, MYSQLI_ASSOC) : [];

$pageTitle = 'All Users';
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
        <div class="rmu-page-header__title">All Users</div>
        <div class="rmu-page-header__sub">View and manage all registered system users</div>
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
                <option value="<?php echo h(strtolower($d['department'] ?? '')); ?>"><?php echo h($d['department'] ?? ''); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="rmu-form-group" style="margin:0;">
              <label class="rmu-label">Role</label>
              <select id="filter-role" class="rmu-select">
                <option value="">All Roles</option>
                <?php foreach ($roles as $r): ?>
                <option value="<?php echo h(strtolower($r['role'] ?? '')); ?>"><?php echo h(ucfirst($r['role'] ?? '')); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="rmu-form-group" style="margin:0;">
              <label class="rmu-label">Status</label>
              <select id="filter-status" class="rmu-select">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="disabled">Disabled</option>
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

      <!-- Bulk action bar -->
      <div id="userBulkBar" style="display:none;align-items:center;gap:12px;margin-bottom:16px;
                  background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.25);
                  border-radius:8px;padding:12px 16px;">
        <span id="userBulkCount" style="font-weight:600;">0 selected</span>
        <div style="margin-left:auto;display:flex;gap:8px;">
          <button class="rmu-btn rmu-btn--success rmu-btn--sm" onclick="bulkUserStatus('active')">
            <i class="ti ti-user-check"></i> Activate Selected
          </button>
          <button class="rmu-btn rmu-btn--danger rmu-btn--sm" onclick="bulkUserStatus('disabled')">
            <i class="ti ti-ban"></i> Disable Selected
          </button>
        </div>
      </div>

      <!-- Table -->
      <div class="rmu-card">
        <div class="rmu-card__header">
          <span class="rmu-card__title"><i class="ti ti-users"></i> Users</span>
          <span class="rmu-badge rmu-badge--neutral" id="row-count"><?php echo count($users); ?> user<?php echo count($users) !== 1 ? 's' : ''; ?></span>
        </div>
        <div class="rmu-card__body" style="padding:0;">
          <div class="rmu-table-wrap">
            <table class="rmu-table" id="usersTable">
              <thead>
                <tr>
                  <th style="width:36px;text-align:center;">
                    <input type="checkbox" id="selectAllUsers" onclick="toggleAllUsers(this)"
                           title="Select all" style="cursor:pointer;">
                  </th>
                  <th>#</th>
                  <th>Full Name</th>
                  <th>Email</th>
                  <th>Department</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($users)): ?>
                <tr><td colspan="8" style="text-align:center;color:var(--txt-muted);padding:20px;">No users found.</td></tr>
                <?php else: $i = 1; foreach ($users as $u): ?>
                <tr data-dept="<?php echo h(strtolower($u['department'] ?? '')); ?>"
                    data-role="<?php echo h(strtolower($u['role'] ?? '')); ?>"
                    data-status="<?php echo h(strtolower($u['account_status'] ?? '')); ?>">
                  <td style="text-align:center;">
                    <input type="checkbox" class="u-check" value="<?php echo (int)$u['userId']; ?>"
                           onclick="updateUserSelection()" style="cursor:pointer;">
                  </td>
                  <td><?php echo $i++; ?></td>
                  <td><?php echo h($u['full_name']); ?></td>
                  <td><?php echo h($u['email']); ?></td>
                  <td><?php echo h($u['department']); ?></td>
                  <td><span class="rmu-badge rmu-badge--neutral"><?php echo h(ucfirst($u['role'])); ?></span></td>
                  <td>
                    <?php if ($u['account_status'] === 'active'): ?>
                    <span class="rmu-badge rmu-badge--success">Active</span>
                    <?php else: ?>
                    <span class="rmu-badge rmu-badge--warning">Disabled</span>
                    <?php endif; ?>
                  </td>
                  <td style="white-space:nowrap;">
                    <button class="rmu-btn rmu-btn--secondary rmu-btn--sm" style="margin-right:4px;"
                            onclick="viewUser(<?php echo (int)$u['userId']; ?>)" title="View Details">
                      <i class="ti ti-eye"></i>
                    </button>
                    <button class="rmu-btn rmu-btn--secondary rmu-btn--sm" style="margin-right:4px;"
                            onclick="editUser(<?php echo (int)$u['userId']; ?>)" title="Edit Details">
                      <i class="ti ti-edit"></i>
                    </button>
                    <?php if ($u['account_status'] === 'disabled'): ?>
                    <button class="rmu-btn rmu-btn--success rmu-btn--sm"
                            onclick="setStatus(<?php echo (int)$u['userId']; ?>, 'active')" title="Activate">
                      <i class="ti ti-user-check"></i> Activate
                    </button>
                    <?php else: ?>
                    <button class="rmu-btn rmu-btn--danger rmu-btn--sm"
                            onclick="setStatus(<?php echo (int)$u['userId']; ?>, 'disabled')" title="Disable">
                      <i class="ti ti-ban"></i> Disable
                    </button>
                    <?php endif; ?>
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

<!-- User Details Modal -->
<div class="rmu-modal-backdrop" id="userModal">
  <div class="rmu-modal" style="max-width:680px;width:calc(100% - 48px);">
    <div class="rmu-modal__header">
      <span class="rmu-modal__title" id="modal-title"><i class="ti ti-user" style="margin-right:8px;"></i>User Details</span>
      <button class="rmu-modal__close" onclick="closeModal()"><i class="ti ti-x"></i></button>
    </div>
    <div class="rmu-modal__body">
      <div class="rmu-grid-2">
        <div class="rmu-form-group">
          <label class="rmu-label">First Name</label>
          <input type="text" class="rmu-input" id="md-first" readonly>
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label">Last Name</label>
          <input type="text" class="rmu-input" id="md-last" readonly>
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label">Other Names</label>
          <input type="text" class="rmu-input" id="md-other" readonly>
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label">Gender</label>
          <input type="text" class="rmu-input" id="md-gender" readonly>
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label">Email</label>
          <input type="text" class="rmu-input" id="md-email" readonly>
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label">Phone Number</label>
          <input type="text" class="rmu-input" id="md-phone" readonly>
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label">Department</label>
          <input type="text" class="rmu-input" id="md-dept" readonly>
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label">Role</label>
          <input type="text" class="rmu-input" id="md-role" readonly>
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label">Rank</label>
          <input type="text" class="rmu-input" id="md-rank" readonly>
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label">Rate (GHS)</label>
          <input type="text" class="rmu-input" id="md-rate" readonly>
        </div>
        <div class="rmu-form-group" style="grid-column:1/-1;">
          <label class="rmu-label">Account Status</label>
          <input type="text" class="rmu-input" id="md-status" readonly>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Edit User Modal -->
<div class="rmu-modal-backdrop" id="editModal">
  <div class="rmu-modal" style="max-width:520px;width:calc(100% - 48px);">
    <div class="rmu-modal__header">
      <span class="rmu-modal__title"><i class="ti ti-edit" style="margin-right:8px;"></i>Edit User</span>
      <button class="rmu-modal__close" onclick="closeEditModal()"><i class="ti ti-x"></i></button>
    </div>
    <div class="rmu-modal__body">
      <input type="hidden" id="edit-userId">
      <div class="rmu-form-group">
        <label class="rmu-label">Name</label>
        <input type="text" class="rmu-input" id="edit-name" readonly>
      </div>
      <div class="rmu-grid-2">
        <div class="rmu-form-group">
          <label class="rmu-label">Department</label>
          <input type="text" class="rmu-input" id="edit-dept" maxlength="120">
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label">Rank</label>
          <input type="text" class="rmu-input" id="edit-rank" maxlength="120">
        </div>
        <div class="rmu-form-group" style="grid-column:1/-1;">
          <label class="rmu-label">Rate (GHS per period)</label>
          <input type="number" class="rmu-input" id="edit-rate" min="0" step="0.01">
        </div>
      </div>
    </div>
    <div class="rmu-modal__footer" style="display:flex;justify-content:flex-end;gap:10px;padding:16px 24px;">
      <button class="rmu-btn rmu-btn--secondary" onclick="closeEditModal()">Cancel</button>
      <button class="rmu-btn rmu-btn--primary" id="edit-save-btn" onclick="saveUser()">
        <i class="ti ti-device-floppy"></i> Save Changes
      </button>
    </div>
  </div>
</div>

<script>
const CSRF     = '<?php echo h(csrf_token()); ?>';
const swalOpts = { background: '#0d1b2a', color: '#e2e8f0' };

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Filters ───────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  var rows     = Array.from(document.querySelectorAll('#usersTable tbody tr[data-status]'));
  var rowCount = document.getElementById('row-count');

  function applyFilters() {
    var dept   = document.getElementById('filter-dept').value;
    var role   = document.getElementById('filter-role').value;
    var status = document.getElementById('filter-status').value;
    var vis = 0;
    rows.forEach(function(r) {
      var show = (!dept   || r.dataset.dept   === dept)
              && (!role   || r.dataset.role   === role)
              && (!status || r.dataset.status === status);
      r.style.display = show ? '' : 'none';
      if (show) vis++;
    });
    rowCount.textContent = vis + ' user' + (vis !== 1 ? 's' : '');
  }

  ['filter-dept','filter-role','filter-status'].forEach(function(id) {
    document.getElementById(id).addEventListener('change', applyFilters);
  });

  document.getElementById('btn-clear').addEventListener('click', function() {
    ['filter-dept','filter-role','filter-status'].forEach(function(id) {
      document.getElementById(id).value = '';
    });
    applyFilters();
  });
});

// ── View user ─────────────────────────────────────────────────────────────────
function viewUser(userId) {
  fetch('getUserDetails.inc.php?userId=' + encodeURIComponent(userId))
    .then(function(r) { return r.json(); })
    .then(function(u) {
      if (u.error) {
        Swal.fire(Object.assign({ icon:'error', title:'Not Found', text: u.error }, swalOpts));
        return;
      }
      document.getElementById('modal-title').innerHTML =
        '<i class="ti ti-user" style="margin-right:8px;"></i>' + escHtml(u.full_name || 'User Details');
      document.getElementById('md-first').value  = u.first_name    || '';
      document.getElementById('md-last').value   = u.last_name     || '';
      document.getElementById('md-other').value  = u.other_names   || '';
      document.getElementById('md-gender').value = u.gender        || '';
      document.getElementById('md-email').value  = u.email         || '';
      document.getElementById('md-phone').value  = u.phone_number  || '';
      document.getElementById('md-dept').value   = u.department    || '';
      document.getElementById('md-role').value   = u.role          ? u.role.charAt(0).toUpperCase() + u.role.slice(1) : '';
      document.getElementById('md-rank').value   = u.rank          || '';
      document.getElementById('md-rate').value   = u.rate          ? parseFloat(u.rate).toFixed(2) : '';
      document.getElementById('md-status').value = u.account_status
        ? u.account_status.charAt(0).toUpperCase() + u.account_status.slice(1) : '';
      document.getElementById('userModal').classList.add('open');
      document.body.style.overflow = 'hidden';
    })
    .catch(function() {
      Swal.fire(Object.assign({ icon:'error', title:'Error', text:'Could not load user details.' }, swalOpts));
    });
}

function closeModal() {
  document.getElementById('userModal').classList.remove('open');
  document.body.style.overflow = '';
}

document.getElementById('userModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeModal();
});

// ── Edit user ─────────────────────────────────────────────────────────────────
function editUser(userId) {
  fetch('getUserDetails.inc.php?userId=' + encodeURIComponent(userId))
    .then(function(r) { return r.json(); })
    .then(function(u) {
      if (u.error) {
        Swal.fire(Object.assign({ icon:'error', title:'Not Found', text: u.error }, swalOpts));
        return;
      }
      document.getElementById('edit-userId').value = u.userId || userId;
      document.getElementById('edit-name').value   = u.full_name
        || ((u.last_name || '') + ', ' + (u.first_name || ''));
      document.getElementById('edit-dept').value   = u.department || '';
      document.getElementById('edit-rank').value   = u.rank       || '';
      document.getElementById('edit-rate').value   = (u.rate !== null && u.rate !== undefined)
        ? parseFloat(u.rate).toFixed(2) : '';
      document.getElementById('editModal').classList.add('open');
      document.body.style.overflow = 'hidden';
    })
    .catch(function() {
      Swal.fire(Object.assign({ icon:'error', title:'Error', text:'Could not load user details.' }, swalOpts));
    });
}

function closeEditModal() {
  document.getElementById('editModal').classList.remove('open');
  document.body.style.overflow = '';
}

function saveUser() {
  var btn = document.getElementById('edit-save-btn');
  var fd  = new FormData();
  fd.append('csrf_token', CSRF);
  fd.append('userId',     document.getElementById('edit-userId').value);
  fd.append('department', document.getElementById('edit-dept').value.trim());
  fd.append('rank',       document.getElementById('edit-rank').value.trim());
  fd.append('rate',       document.getElementById('edit-rate').value.trim());

  btn.disabled = true;
  btn.innerHTML = '<i class="ti ti-loader"></i> Saving…';

  fetch('updateUser.inc.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(res) {
      if (res.success) {
        Swal.fire(Object.assign({
          icon:'success', title:'Saved', text: res.message,
          timer: 1600, showConfirmButton: false,
        }, swalOpts)).then(function() { location.reload(); });
      } else {
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-device-floppy"></i> Save Changes';
        Swal.fire(Object.assign({ icon:'error', title:'Failed', text: res.message || 'Update failed.' }, swalOpts));
      }
    })
    .catch(function() {
      btn.disabled = false;
      btn.innerHTML = '<i class="ti ti-device-floppy"></i> Save Changes';
      Swal.fire(Object.assign({ icon:'error', title:'Network Error', text:'Please try again.' }, swalOpts));
    });
}

document.getElementById('editModal').addEventListener('click', function(e) {
  if (e.target === this) closeEditModal();
});

// ── Bulk selection (#2) ─────────────────────────────────────────────────────────
function visibleUserChecks() {
  return Array.prototype.filter.call(document.querySelectorAll('.u-check'),
    function(cb) { return cb.closest('tr').style.display !== 'none'; });
}
function toggleAllUsers(master) {
  visibleUserChecks().forEach(function(cb) { cb.checked = master.checked; });
  updateUserSelection();
}
function selectedUserIds() {
  return visibleUserChecks().filter(function(cb) { return cb.checked; })
                            .map(function(cb) { return cb.value; });
}
function updateUserSelection() {
  var n = selectedUserIds().length;
  var bar = document.getElementById('userBulkBar');
  if (bar) bar.style.display = n > 0 ? 'flex' : 'none';
  var lbl = document.getElementById('userBulkCount');
  if (lbl) lbl.textContent = n + ' selected';
}
function bulkUserStatus(status) {
  var ids = selectedUserIds();
  if (!ids.length) return;
  var activate = status === 'active';
  Swal.fire(Object.assign({
    title: (activate ? 'Activate ' : 'Disable ') + ids.length + ' account(s)?',
    icon: 'question', showCancelButton: true,
    confirmButtonText: activate ? 'Yes, Activate' : 'Yes, Disable',
    confirmButtonColor: activate ? '#22c55e' : '#ef4444',
    cancelButtonColor: 'rgba(255,255,255,0.1)',
  }, swalOpts)).then(function(result) {
    if (!result.isConfirmed) return;
    var fd = new FormData();
    fd.append('status', status);
    fd.append('csrf_token', CSRF);
    ids.forEach(function(id) { fd.append('userIds[]', id); });
    fetch('bulkUserStatus.inc.php', { method: 'POST', body: fd })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        if (res.success) {
          Swal.fire(Object.assign({ icon: 'success', title: 'Done', text: res.message,
            timer: 1800, showConfirmButton: false }, swalOpts)).then(function() { location.reload(); });
        } else {
          Swal.fire(Object.assign({ icon: 'error', title: 'Failed',
            text: res.message || 'Action failed.' }, swalOpts));
        }
      })
      .catch(function() {
        Swal.fire(Object.assign({ icon: 'error', title: 'Network Error',
          text: 'Please try again.' }, swalOpts));
      });
  });
}

// ── Activate / Disable ────────────────────────────────────────────────────────
function setStatus(userId, newStatus) {
  var isActivate = newStatus === 'active';
  Swal.fire(Object.assign({
    title: isActivate ? 'Activate Account?' : 'Disable Account?',
    text:  isActivate ? 'The user will be granted access to the system.'
                      : 'The user will no longer be able to log in.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: isActivate ? 'Yes, Activate' : 'Yes, Disable',
    confirmButtonColor: isActivate ? '#22c55e' : '#ef4444',
    cancelButtonColor: 'rgba(255,255,255,0.1)',
  }, swalOpts)).then(function(result) {
    if (!result.isConfirmed) return;

    var fd = new FormData();
    fd.append('userId', userId);
    fd.append('csrf_token', CSRF);

    var url = isActivate ? 'activateUserAcct.inc.php' : 'disableUserAcct.inc.php';

    fetch(url, { method: 'POST', body: fd })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        if (res.success) {
          Swal.fire(Object.assign({
            icon: 'success',
            title: isActivate ? 'Activated' : 'Disabled',
            text: res.message,
            timer: 1800, showConfirmButton: false,
          }, swalOpts)).then(function() { location.reload(); });
        } else {
          Swal.fire(Object.assign({ icon:'error', title:'Failed', text: res.message || 'Action failed.' }, swalOpts));
        }
      })
      .catch(function() {
        Swal.fire(Object.assign({ icon:'error', title:'Network Error', text:'Please try again.' }, swalOpts));
      });
  });
}
</script>

</body>
</html>
