<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>RMU Claims System — Login</title>
  <link rel="icon" type="image/png" href="./login/images/icons/rmu.ico">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.4.0/dist/tabler-icons.min.css" integrity="sha384-ldmpcx1x0Xzlz3FRdxRDXdddHL6gUAnUo8m6ERvU0MbQIl53rnzI7hCF+Fd8lRsX" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="./assets/css/rmu-glass.css?v=4">
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

      <?php if (isset($_SESSION['message'])): ?>
        <div class="rmu-alert rmu-alert--warning" style="margin-bottom:20px;">
          <?php echo htmlspecialchars($_SESSION['message'], ENT_QUOTES, 'UTF-8');
                unset($_SESSION['message']); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="index.inc.php" autocomplete="off">
        <div class="rmu-login-input-group">
          <i class="ti ti-mail rmu-login-input-group__icon"></i>
          <input
            class="rmu-input"
            type="email"
            name="email"
            placeholder="Email address"
            required
            autocomplete="email"
          >
        </div>

        <div class="rmu-login-input-group">
          <i class="ti ti-lock rmu-login-input-group__icon"></i>
          <input
            class="rmu-input"
            type="password"
            name="pw"
            placeholder="Password"
            required
            autocomplete="current-password"
          >
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
