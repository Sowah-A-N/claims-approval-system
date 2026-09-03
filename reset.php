<?php
// Landing page for a password-reset link. Validates the token for display;
// reset.inc.php re-validates authoritatively on submit.
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$token = isset($_GET['token']) ? preg_replace('/[^a-f0-9]/', '', (string) $_GET['token']) : '';
$valid = false;

if ($token !== '' && strlen($token) === 64) {
    $token_hash = hash('sha256', $token);
    $stmt = mysqli_prepare($conn,
        'SELECT id FROM password_resets
         WHERE token_hash = ? AND used = 0 AND expires_at > NOW() LIMIT 1');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $token_hash);
        mysqli_stmt_execute($stmt);
        $valid = mysqli_fetch_row(mysqli_stmt_get_result($stmt)) !== null;
        mysqli_stmt_close($stmt);
    }
}

$notice = '';
if (isset($_SESSION['reset_notice'])) {
    $notice = $_SESSION['reset_notice'];
    unset($_SESSION['reset_notice'], $_SESSION['reset_notice_type']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Choose a new password for your RMU Claims account.">
  <title>RMU Claims System — New Password</title>
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
      <div class="rmu-login-form-wrap__title">Choose a new password</div>

      <?php if ($notice !== ''): ?>
        <div class="rmu-alert rmu-alert--warning" style="margin-bottom:20px;">
          <?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <?php if (!$valid): ?>
        <div class="rmu-login-form-wrap__sub">This reset link is invalid or has expired.</div>
        <div class="rmu-alert rmu-alert--warning" style="margin:16px 0;">
          Reset links expire after one hour and can be used only once.
        </div>
        <a href="forgot.php" class="rmu-login-btn" style="text-decoration:none;text-align:center;">
          <i class="ti ti-refresh"></i> Request a new link
        </a>
      <?php else: ?>
        <div class="rmu-login-form-wrap__sub">Enter a new password for your account.</div>
        <form method="POST" action="reset.inc.php" autocomplete="off" style="margin-top:8px;">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

          <div class="rmu-login-input-group">
            <label for="new-password" class="rmu-sr-only">New password</label>
            <i class="ti ti-lock rmu-login-input-group__icon" aria-hidden="true"></i>
            <input id="new-password" class="rmu-input" type="password" name="password"
                   placeholder="New password" minlength="8" required autocomplete="new-password"
                   aria-describedby="reset-hint">
          </div>
          <div class="rmu-login-input-group">
            <label for="confirm-password" class="rmu-sr-only">Confirm new password</label>
            <i class="ti ti-lock-check rmu-login-input-group__icon" aria-hidden="true"></i>
            <input id="confirm-password" class="rmu-input" type="password" name="confirm_password"
                   placeholder="Confirm new password" minlength="8" required autocomplete="new-password">
          </div>
          <div id="reset-hint" style="font-size:.8rem;color:var(--txt-muted);margin:-6px 0 16px;">
            At least 8 characters, including a letter and a number.
          </div>

          <button type="submit" class="rmu-login-btn">
            <i class="ti ti-check"></i> Update password
          </button>
        </form>
      <?php endif; ?>

      <div class="rmu-login-register">
        <a href="index.php"><i class="ti ti-arrow-left"></i> Back to sign in</a>
      </div>
    </div>

  </div>
</div>

</body>
</html>
