<?php
session_start();
// Ensure a CSRF token exists for the login form (same session key the rest of
// the app uses via csrf_token()).
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Sign in to the RMU Claims Approval System.">
  <title>RMU Claims System — Login</title>
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

    <!-- Brand panel -->
    <div class="rmu-login-brand">
      <img src="./login/images/rmu.jpg" alt="RMU Logo" class="rmu-login-brand__logo">
      <div class="rmu-login-brand__name">Regional Maritime<br>University</div>
      <div class="rmu-login-brand__tagline">Claims Management &amp; Approval System</div>
    </div>

    <!-- Form panel -->
    <div class="rmu-login-form-wrap">
      <div class="rmu-login-form-wrap__title">Welcome back</div>
      <div class="rmu-login-form-wrap__sub">Sign in to your account to continue</div>

      <?php
        // Surface the state the app signals via query string on redirect —
        // otherwise a timed-out or disabled user lands on a blank login page
        // with no explanation. Session messages take precedence.
        $login_notice = '';
        if (isset($_SESSION['message'])) {
            $login_notice = $_SESSION['message'];
            unset($_SESSION['message']);
        } elseif (isset($_GET['timeout'])) {
            $login_notice = 'Your session expired after a period of inactivity. Please sign in again.';
        } elseif (isset($_GET['disabled'])) {
            $login_notice = 'This account is not active. Please contact an administrator.';
        } elseif (isset($_GET['loggedout'])) {
            $login_notice = 'You have been signed out.';
        }
      ?>
      <?php if ($login_notice !== ''): ?>
        <div class="rmu-alert rmu-alert--warning" style="margin-bottom:20px;">
          <?php echo htmlspecialchars($login_notice, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="index.inc.php" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <div class="rmu-login-input-group">
          <label for="login-email" class="rmu-sr-only">Email address</label>
          <i class="ti ti-mail rmu-login-input-group__icon" aria-hidden="true"></i>
          <input
            id="login-email"
            class="rmu-input"
            type="email"
            name="email"
            placeholder="Email address"
            required
            autocomplete="email"
          >
        </div>

        <div class="rmu-login-input-group">
          <label for="login-pw" class="rmu-sr-only">Password</label>
          <i class="ti ti-lock rmu-login-input-group__icon" aria-hidden="true"></i>
          <input
            id="login-pw"
            class="rmu-input"
            type="password"
            name="pw"
            placeholder="Password"
            required
            autocomplete="current-password"
          >
        </div>

        <div style="text-align:right;margin:-4px 0 16px;">
          <a href="forgot.php" style="font-size:.85rem;">Forgot password?</a>
        </div>

        <button type="submit" class="rmu-login-btn">
          <i class="ti ti-login"></i> Sign In
        </button>
      </form>

      <div class="rmu-login-register">
        Don't have an account?
        <a href="register.php">Register here</a>
      </div>
    </div>

  </div>
</div>

</body>
</html>
