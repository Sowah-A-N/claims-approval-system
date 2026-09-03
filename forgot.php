<?php
// Request a password-reset link. The page itself runs no queries; forgot.inc.php
// handles the lookup + email on submit.
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$notice = '';
$notice_type = 'warning';
if (isset($_SESSION['reset_notice'])) {
    $notice = $_SESSION['reset_notice'];
    $notice_type = isset($_SESSION['reset_notice_type']) ? $_SESSION['reset_notice_type'] : 'info';
    unset($_SESSION['reset_notice'], $_SESSION['reset_notice_type']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Reset your RMU Claims Approval System password.">
  <title>RMU Claims System — Reset Password</title>
  <link rel="icon" type="image/png" href="./login/images/icons/rmu.ico">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.4.0/dist/tabler-icons.min.css" integrity="sha384-ldmpcx1x0Xzlz3FRdxRDXdddHL6gUAnUo8m6ERvU0MbQIl53rnzI7hCF+Fd8lRsX" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="./assets/css/rmu-glass.css?v=7">
  <script src="./assets/js/rmu-ui.js?v=7" defer></script>
</head>
<body>

<div class="rmu-login-page">
  <div class="rmu-login-card">

    <div class="rmu-login-brand">
      <img src="./login/images/rmu.jpg" alt="RMU Logo" class="rmu-login-brand__logo">
      <div class="rmu-login-brand__name">Regional Maritime<br>University</div>
      <div class="rmu-login-brand__tagline">Claims Management &amp; Approval System</div>
    </div>

    <div class="rmu-login-form-wrap">
      <div class="rmu-login-form-wrap__title">Reset your password</div>
      <div class="rmu-login-form-wrap__sub">Enter your account email and we'll send you a reset link.</div>

      <?php if ($notice !== ''): ?>
        <div class="rmu-alert rmu-alert--<?php echo htmlspecialchars($notice_type, ENT_QUOTES, 'UTF-8'); ?>" style="margin-bottom:20px;">
          <?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="forgot.inc.php" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="rmu-login-input-group">
          <label for="reset-email" class="rmu-sr-only">Email address</label>
          <i class="ti ti-mail rmu-login-input-group__icon" aria-hidden="true"></i>
          <input id="reset-email" class="rmu-input" type="email" name="email"
                 placeholder="Email address" required autocomplete="email">
        </div>

        <button type="submit" class="rmu-login-btn">
          <i class="ti ti-send"></i> Send reset link
        </button>
      </form>

      <div class="rmu-login-register">
        <a href="index.php"><i class="ti ti-arrow-left"></i> Back to sign in</a>
      </div>
    </div>

  </div>
</div>

</body>
</html>
