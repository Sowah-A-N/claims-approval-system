<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$rankResult    = mysqli_query($conn, 'SELECT `rank`, rate FROM lecturer_rank_rate ORDER BY `rank`');
$facultyResult = mysqli_query($conn, 'SELECT id, name FROM faculty ORDER BY name');
$deptResult    = mysqli_query($conn, 'SELECT dept_name FROM department ORDER BY dept_name');
$bankResult    = mysqli_query($conn, "SELECT DISTINCT bank_name FROM banks_branches ORDER BY bank_name");

$ranks     = $rankResult    ? mysqli_fetch_all($rankResult,    MYSQLI_ASSOC) : array();
$faculties = $facultyResult ? mysqli_fetch_all($facultyResult, MYSQLI_ASSOC) : array();
$depts     = $deptResult    ? mysqli_fetch_all($deptResult,    MYSQLI_ASSOC) : array();
$banks     = $bankResult    ? mysqli_fetch_all($bankResult,    MYSQLI_ASSOC) : array();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>RMU Claims System — Register</title>
  <link rel="icon" type="image/png" href="./login/images/icons/rmu.ico">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.4.0/dist/tabler-icons.min.css">
  <link rel="stylesheet" href="./assets/css/rmu-glass.css">
</head>
<body>

<div class="rmu-register-page">
  <div class="rmu-register-container">

    <!-- Page header -->
    <div class="rmu-register-header">
      <div>
        <div class="rmu-register-header__title">
          <i class="ti ti-user-plus rmu-text-primary"></i> User Registration
        </div>
        <div style="font-size:.82rem;color:var(--txt-secondary);margin-top:4px;">
          Create your claimant account — an admin will activate it shortly.
        </div>
      </div>
      <a href="register.php?type=approver" class="rmu-btn rmu-btn--secondary">
        <i class="ti ti-shield-check"></i> Register as Approver
      </a>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
      <div class="rmu-alert rmu-alert--warning">
        <?php echo htmlspecialchars($_SESSION['message'], ENT_QUOTES, 'UTF-8');
              unset($_SESSION['message']); ?>
      </div>
    <?php endif; ?>

    <form action="register.inc.php" method="post" novalidate>

      <!-- Personal Information -->
      <div class="rmu-card rmu-mb-3">
        <div class="rmu-card__header">
          <span class="rmu-card__title"><i class="ti ti-user rmu-text-primary"></i> Personal Information</span>
        </div>
        <div class="rmu-card__body">
          <div class="rmu-grid-3">
            <div class="rmu-form-group">
              <label class="rmu-label">First Name <span class="required">*</span></label>
              <input type="text" class="rmu-input" name="first_name" placeholder="First name" required>
            </div>
            <div class="rmu-form-group">
              <label class="rmu-label">Last Name <span class="required">*</span></label>
              <input type="text" class="rmu-input" name="last_name" placeholder="Last name" required>
            </div>
            <div class="rmu-form-group">
              <label class="rmu-label">Other Names</label>
              <input type="text" class="rmu-input" name="other_names" placeholder="Middle / other names">
            </div>
          </div>
          <div class="rmu-grid-3">
            <div class="rmu-form-group">
              <label class="rmu-label">Phone Number <span class="required">*</span></label>
              <input type="tel" class="rmu-input" id="phone_number" name="phone_number"
                     placeholder="0XXXXXXXXX" required>
              <div class="rmu-form-error" id="phone-error">Enter a valid 10-digit phone number starting with 0.</div>
              <div class="rmu-form-hint">Format: 0XXXXXXXXX (10 digits)</div>
            </div>
            <div class="rmu-form-group">
              <label class="rmu-label">Gender <span class="required">*</span></label>
              <select class="rmu-select" name="gender" required>
                <option value="">Select gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="rmu-form-group">
              <label class="rmu-label">Email Address <span class="required">*</span></label>
              <input type="email" class="rmu-input" name="email" placeholder="you@example.com" required>
            </div>
          </div>
          <div class="rmu-form-group" style="max-width:340px;">
            <label class="rmu-label">Password <span class="required">*</span></label>
            <input type="password" class="rmu-input" name="password" placeholder="Choose a strong password" required>
          </div>
        </div>
      </div>

      <!-- Academic Details -->
      <div class="rmu-card rmu-mb-3">
        <div class="rmu-card__header">
          <span class="rmu-card__title"><i class="ti ti-school rmu-text-primary"></i> Academic Details</span>
        </div>
        <div class="rmu-card__body">
          <div class="rmu-grid-3">
            <div class="rmu-form-group">
              <label class="rmu-label">Faculty <span class="required">*</span></label>
              <select class="rmu-select" name="faculty" required>
                <option value="">Select faculty</option>
                <?php foreach ($faculties as $f): ?>
                  <option value="<?php echo htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="rmu-form-group">
              <label class="rmu-label">Department <span class="required">*</span></label>
              <select class="rmu-select" name="department" required>
                <option value="">Select department</option>
                <?php foreach ($depts as $d): ?>
                  <option value="<?php echo htmlspecialchars($d['dept_name'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($d['dept_name'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="rmu-form-group">
              <label class="rmu-label">Academic Rank <span class="required">*</span></label>
              <select class="rmu-select" id="rank" name="rank" required>
                <option value="">Select rank</option>
                <?php foreach ($ranks as $r): ?>
                  <option value="<?php echo htmlspecialchars($r['rank'], ENT_QUOTES, 'UTF-8'); ?>"
                          data-rate="<?php echo (float) $r['rate']; ?>">
                    <?php echo htmlspecialchars($r['rank'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="rmu-form-group" style="max-width:260px;">
            <label class="rmu-label">Hourly Rate (GH&#8373;)</label>
            <input type="text" class="rmu-input" id="rate" name="rate" placeholder="Auto-filled from rank" readonly>
            <div class="rmu-form-hint">Filled automatically when you select a rank.</div>
          </div>
        </div>
      </div>

      <!-- Banking Details -->
      <div class="rmu-card rmu-mb-3">
        <div class="rmu-card__header">
          <span class="rmu-card__title"><i class="ti ti-building-bank rmu-text-primary"></i> Banking Details</span>
        </div>
        <div class="rmu-card__body">
          <div class="rmu-grid-2">
            <div class="rmu-form-group">
              <label class="rmu-label">Bank <span class="required">*</span></label>
              <select class="rmu-select" id="bank_name" name="bank_name" required>
                <option value="">Select bank</option>
                <?php foreach ($banks as $b): ?>
                  <option value="<?php echo htmlspecialchars($b['bank_name'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($b['bank_name'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="rmu-form-group">
              <label class="rmu-label">Branch <span class="required">*</span></label>
              <select class="rmu-select" id="bank_branch" name="bank_branch" required>
                <option value="">Select branch</option>
              </select>
            </div>
          </div>
          <div class="rmu-grid-2">
            <div class="rmu-form-group">
              <label class="rmu-label">Account Name <span class="required">*</span></label>
              <input type="text" class="rmu-input" name="account_name" placeholder="Name on account" required>
            </div>
            <div class="rmu-form-group">
              <label class="rmu-label">Account Number <span class="required">*</span></label>
              <input type="text" class="rmu-input" name="account_number" placeholder="Account number" required>
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex" style="justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <a href="index.php" class="rmu-btn rmu-btn--secondary">
          <i class="ti ti-arrow-left"></i> Back to Login
        </a>
        <button type="submit" class="rmu-btn rmu-btn--primary" style="min-width:160px;">
          <i class="ti ti-user-check"></i> Submit Registration
        </button>
      </div>

    </form>
  </div>
</div>

<script>
/* Phone validation */
document.getElementById('phone_number').addEventListener('input', function() {
  var el = this;
  var err = document.getElementById('phone-error');
  if (/^0\d{9}$/.test(el.value)) {
    el.classList.remove('is-invalid');
    err.style.display = 'none';
  } else {
    el.classList.add('is-invalid');
    err.style.display = 'block';
  }
});

/* Rank → rate auto-fill */
document.getElementById('rank').addEventListener('change', function() {
  var opt = this.options[this.selectedIndex];
  document.getElementById('rate').value = opt.getAttribute('data-rate') || '';
});

/* Dynamic branch loading */
document.getElementById('bank_name').addEventListener('change', function() {
  var bankName = encodeURIComponent(this.value);
  var branchSel = document.getElementById('bank_branch');
  branchSel.innerHTML = '<option value="">Loading...</option>';
  if (!bankName) { branchSel.innerHTML = '<option value="">Select branch</option>'; return; }

  var xhr = new XMLHttpRequest();
  xhr.onreadystatechange = function() {
    if (xhr.readyState !== XMLHttpRequest.DONE) return;
    branchSel.innerHTML = '';
    if (xhr.status === 200) {
      var branches = JSON.parse(xhr.responseText);
      if (branches.length === 0) {
        branchSel.innerHTML = '<option value="">No branches available</option>';
      } else {
        branches.forEach(function(b) {
          var opt = document.createElement('option');
          opt.value = b.bank_branch;
          opt.textContent = b.bank_branch;
          branchSel.appendChild(opt);
        });
      }
    } else {
      branchSel.innerHTML = '<option value="">Error loading branches</option>';
    }
  };
  xhr.open('GET', 'updateBranches.inc.php?bank_name=' + bankName, true);
  xhr.send();
});
</script>

</body>
</html>
