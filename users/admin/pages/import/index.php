<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';

checkUserRole(['admin', 'Admin']);
csrf_token();

$pageTitle = 'Bulk Import';
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
        <div class="rmu-page-header__title">Bulk Import</div>
        <div class="rmu-page-header__sub">Import users and bank branches from CSV files</div>
      </div>

      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:24px;">

        <!-- Users import -->
        <div class="rmu-card">
          <div class="rmu-card__header">
            <span class="rmu-card__title"><i class="ti ti-users-plus"></i> Import Users</span>
          </div>
          <div class="rmu-card__body">
            <p style="font-size:.82rem;color:var(--txt-muted);margin-bottom:14px;">
              CSV columns: <code>first_name, last_name, other_names, phone_number, gender,
              email, faculty, department, rank</code>. Accounts are created
              <strong>disabled</strong> as claimants, with the rate auto-filled from rank.
              A temporary password is generated for each.
            </p>
            <form id="usersForm">
              <input type="file" name="csv" accept=".csv,text/csv" class="rmu-input" required
                     style="margin-bottom:12px;">
              <button type="submit" class="rmu-btn rmu-btn--primary">
                <i class="ti ti-upload"></i> Upload &amp; Import
              </button>
            </form>
            <div id="usersResult" style="margin-top:16px;"></div>
          </div>
        </div>

        <!-- Banks import -->
        <div class="rmu-card">
          <div class="rmu-card__header">
            <span class="rmu-card__title"><i class="ti ti-building-bank"></i> Import Bank Branches</span>
          </div>
          <div class="rmu-card__body">
            <p style="font-size:.82rem;color:var(--txt-muted);margin-bottom:14px;">
              CSV columns: <code>bank_name, bank_branch, branch_code</code>.
              <code>branch_code</code> is the unique key, so re-importing is safe.
            </p>
            <form id="banksForm">
              <input type="file" name="csv" accept=".csv,text/csv" class="rmu-input" required
                     style="margin-bottom:12px;">
              <button type="submit" class="rmu-btn rmu-btn--primary">
                <i class="ti ti-upload"></i> Upload &amp; Import
              </button>
            </form>
            <div id="banksResult" style="margin-top:16px;"></div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
const CSRF = '<?php echo h(csrf_token()); ?>';

function esc(s) {
  return String(s == null ? '' : s)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function setBusy(form, busy) {
  var btn = form.querySelector('button[type=submit]');
  if (btn) btn.disabled = busy;
}

document.getElementById('usersForm').addEventListener('submit', function(e) {
  e.preventDefault();
  var box = document.getElementById('usersResult');
  var fd  = new FormData(this);
  fd.append('csrf_token', CSRF);
  setBusy(this, true);
  box.innerHTML = '<span style="color:var(--txt-muted);">Importing…</span>';

  fetch('importUsers.inc.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(res) {
      setBusy(document.getElementById('usersForm'), false);
      var html = '<div class="rmu-alert rmu-alert--' + (res.success ? 'success' : 'warning') + '">'
               + esc(res.message || 'Done.') + '</div>';
      if (res.created && res.created.length) {
        html += '<div style="font-size:.78rem;color:var(--txt-muted);margin:8px 0;">'
              + 'Distribute these temporary passwords securely:</div>';
        html += '<div class="rmu-table-wrap"><table class="rmu-table"><thead><tr>'
              + '<th>Name</th><th>Email</th><th>Temp Password</th></tr></thead><tbody>';
        res.created.forEach(function(c) {
          html += '<tr><td>' + esc(c.name) + '</td><td>' + esc(c.email)
                + '</td><td style="font-family:monospace;">' + esc(c.temp_password) + '</td></tr>';
        });
        html += '</tbody></table></div>';
      }
      if (res.skipped && res.skipped.length) {
        html += '<details style="margin-top:10px;"><summary style="cursor:pointer;">'
              + res.skipped.length + ' skipped</summary><ul style="margin:8px 0 0 18px;font-size:.8rem;">';
        res.skipped.forEach(function(s) {
          html += '<li>Row ' + esc(s.row) + ' ' + esc(s.email) + ' — ' + esc(s.reason) + '</li>';
        });
        html += '</ul></details>';
      }
      box.innerHTML = html;
    })
    .catch(function() {
      setBusy(document.getElementById('usersForm'), false);
      box.innerHTML = '<div class="rmu-alert rmu-alert--warning">Network error. Please try again.</div>';
    });
});

document.getElementById('banksForm').addEventListener('submit', function(e) {
  e.preventDefault();
  var box = document.getElementById('banksResult');
  var fd  = new FormData(this);
  fd.append('csrf_token', CSRF);
  setBusy(this, true);
  box.innerHTML = '<span style="color:var(--txt-muted);">Importing…</span>';

  fetch('importBanks.inc.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(res) {
      setBusy(document.getElementById('banksForm'), false);
      box.innerHTML = '<div class="rmu-alert rmu-alert--' + (res.success ? 'success' : 'warning') + '">'
                    + esc(res.message || 'Done.') + '</div>';
    })
    .catch(function() {
      setBusy(document.getElementById('banksForm'), false);
      box.innerHTML = '<div class="rmu-alert rmu-alert--warning">Network error. Please try again.</div>';
    });
});
</script>

</body>
</html>
