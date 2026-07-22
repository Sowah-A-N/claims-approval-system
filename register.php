<?php
// Streamlined registration collects identity + credentials only, so the page
// itself runs no queries (register.inc.php handles the DB write on submit).
require_once __DIR__ . '/includes/auth.php';
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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.4.0/dist/tabler-icons.min.css" integrity="sha384-ldmpcx1x0Xzlz3FRdxRDXdddHL6gUAnUo8m6ERvU0MbQIl53rnzI7hCF+Fd8lRsX" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="./assets/css/rmu-glass.css?v=4">
  <script src="./assets/js/rmu-ui.js?v=1" defer></script>
</head>
<body>

<div class="rmu-register-page">
  <div class="rmu-register-container" style="max-width:640px;">

    <!-- Page header -->
    <div class="rmu-register-header">
      <div>
        <div class="rmu-register-header__title">
          <i class="ti ti-user-plus rmu-text-primary"></i> Create your account
        </div>
        <div style="font-size:.82rem;color:var(--txt-secondary);margin-top:4px;">
          Just the essentials to get started.
        </div>
      </div>
      <a href="registerApp.php" class="rmu-btn rmu-btn--secondary">
        <i class="ti ti-shield-check"></i> Register as Approver
      </a>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
      <div class="rmu-alert rmu-alert--warning">
        <?php echo htmlspecialchars($_SESSION['message'], ENT_QUOTES, 'UTF-8');
              unset($_SESSION['message']); ?>
      </div>
    <?php endif; ?>

    <div class="rmu-alert rmu-alert--info" style="margin-bottom:16px;">
      <i class="ti ti-info-circle"></i>
      If your email is on the HR register your account is activated automatically; otherwise an
      administrator reviews it. Your department, rank and bank details are added after activation.
    </div>

    <form action="register.inc.php" method="post" novalidate>
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

      <div class="rmu-card rmu-mb-3">
        <div class="rmu-card__header">
          <span class="rmu-card__title"><i class="ti ti-user rmu-text-primary"></i> Personal Information</span>
        </div>
        <div class="rmu-card__body">
          <div class="rmu-grid-2">
            <div class="rmu-form-group">
              <label class="rmu-label" for="first_name">First Name <span class="required">*</span></label>
              <input type="text" class="rmu-input" id="first_name" name="first_name" placeholder="First name" required>
            </div>
            <div class="rmu-form-group">
              <label class="rmu-label" for="last_name">Last Name <span class="required">*</span></label>
              <input type="text" class="rmu-input" id="last_name" name="last_name" placeholder="Last name" required>
            </div>
          </div>
          <div class="rmu-grid-2">
            <div class="rmu-form-group">
              <label class="rmu-label" for="other_names">Other Names</label>
              <input type="text" class="rmu-input" id="other_names" name="other_names" placeholder="Middle / other names">
            </div>
            <div class="rmu-form-group">
              <label class="rmu-label" for="gender">Gender <span class="required">*</span></label>
              <select class="rmu-select" id="gender" name="gender" required>
                <option value="">Select gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
              </select>
            </div>
          </div>
          <div class="rmu-grid-2">
            <div class="rmu-form-group">
              <label class="rmu-label" for="phone_number">Phone Number <span class="required">*</span></label>
              <input type="tel" class="rmu-input" id="phone_number" name="phone_number"
                     placeholder="0XXXXXXXXX" required>
              <div class="rmu-form-error" id="phone-error">Enter a valid 10-digit phone number starting with 0.</div>
              <div class="rmu-form-hint">Format: 0XXXXXXXXX (10 digits)</div>
            </div>
            <div class="rmu-form-group">
              <label class="rmu-label" for="email">Email Address <span class="required">*</span></label>
              <input type="email" class="rmu-input" id="email" name="email" placeholder="you@example.com" required>
              <div class="rmu-form-hint">Use the email your employer/HR has on file.</div>
            </div>
          </div>
          <div class="rmu-form-group" style="max-width:340px;">
            <label class="rmu-label" for="password">Password <span class="required">*</span></label>
            <input type="password" class="rmu-input" id="password" name="password" placeholder="Choose a strong password" required>
          </div>
        </div>
      </div>

      <div class="d-flex" style="justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <a href="index.php" class="rmu-btn rmu-btn--secondary">
          <i class="ti ti-arrow-left"></i> Back to Login
        </a>
        <button type="submit" class="rmu-btn rmu-btn--primary" style="min-width:160px;">
          <i class="ti ti-user-check"></i> Create Account
        </button>
      </div>

    </form>
  </div>
</div>

<script>
/* Phone validation (soft — the server is authoritative) */
document.getElementById('phone_number').addEventListener('input', function() {
  var err = document.getElementById('phone-error');
  if (/^0\d{9}$/.test(this.value)) {
    this.classList.remove('is-invalid');
    err.style.display = 'none';
  } else {
    this.classList.add('is-invalid');
    err.style.display = 'block';
  }
});
</script>

</body>
</html>
