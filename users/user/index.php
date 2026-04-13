<?php
$pageTitle = 'Dashboard';
include './assets/partials/_head.php';

$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

// Dashboard counts using prepared statements
function dash_count($conn, $sql, $param_types, $params) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return 0;
    if ($params) {
        mysqli_stmt_bind_param($stmt, $param_types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return (int) $count;
}

$totalClaims     = dash_count($conn, 'SELECT COUNT(*) FROM claim_details WHERE userId = ?',    'i', [$userId]);
$savedClaims     = dash_count($conn, 'SELECT COUNT(*) FROM saved_claims WHERE userId = ?',     'i', [$userId]);
$inProgressClaims= dash_count($conn, 'SELECT COUNT(*) FROM claim_details WHERE userId = ? AND completed = 0 AND flagged = 0', 'i', [$userId]);
$flaggedClaims   = dash_count($conn, 'SELECT COUNT(*) FROM claim_details WHERE userId = ? AND completed = 0 AND flagged = 1', 'i', [$userId]);
$completedClaims = dash_count($conn, 'SELECT COUNT(*) FROM completed_claims WHERE userId = ?', 'i', [$userId]);
?>

<body>
<div id="app-wrapper">

  <?php include './assets/partials/_sidebar.php'; ?>

  <div class="rmu-main">

    <?php include './assets/partials/_navbar.php'; ?>

    <div class="rmu-content">

      <div class="rmu-page-header">
        <div class="rmu-page-header__title">Dashboard</div>
        <div class="rmu-page-header__sub">
          Welcome back, <?php echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8') : 'Claimant'; ?>
        </div>
      </div>

      <!-- Stat cards -->
      <div class="rmu-stats">

        <div class="rmu-stat-card rmu-stat-card--primary">
          <div class="rmu-stat-card__icon rmu-stat-card__icon--primary">
            <i class="ti ti-files"></i>
          </div>
          <div class="rmu-stat-card__value"><?php echo $totalClaims; ?></div>
          <div class="rmu-stat-card__label">Total Claims</div>
        </div>

        <div class="rmu-stat-card rmu-stat-card--secondary">
          <div class="rmu-stat-card__icon rmu-stat-card__icon--secondary">
            <i class="ti ti-device-floppy"></i>
          </div>
          <div class="rmu-stat-card__value"><?php echo $savedClaims; ?></div>
          <div class="rmu-stat-card__label">Saved Drafts</div>
        </div>

        <div class="rmu-stat-card rmu-stat-card--info">
          <div class="rmu-stat-card__icon rmu-stat-card__icon--info">
            <i class="ti ti-clock"></i>
          </div>
          <div class="rmu-stat-card__value"><?php echo $inProgressClaims; ?></div>
          <div class="rmu-stat-card__label">In Progress</div>
        </div>

        <div class="rmu-stat-card rmu-stat-card--warning">
          <div class="rmu-stat-card__icon rmu-stat-card__icon--warning">
            <i class="ti ti-flag"></i>
          </div>
          <div class="rmu-stat-card__value"><?php echo $flaggedClaims; ?></div>
          <div class="rmu-stat-card__label">Flagged</div>
        </div>

        <div class="rmu-stat-card rmu-stat-card--success">
          <div class="rmu-stat-card__icon rmu-stat-card__icon--success">
            <i class="ti ti-circle-check"></i>
          </div>
          <div class="rmu-stat-card__value"><?php echo $completedClaims; ?></div>
          <div class="rmu-stat-card__label">Completed</div>
        </div>

      </div>

      <!-- Quick actions -->
      <div class="rmu-card">
        <div class="rmu-card__header">
          <span class="rmu-card__title">Quick Actions</span>
        </div>
        <div class="rmu-card__body" style="display:flex;gap:12px;flex-wrap:wrap;">
          <a href="./pages/fileNewClaim" class="rmu-btn rmu-btn--primary">
            <i class="ti ti-file-plus"></i> File New Claim
          </a>
          <a href="./pages/myClaims" class="rmu-btn rmu-btn--secondary">
            <i class="ti ti-files"></i> View My Claims
          </a>
          <a href="./pages/settings" class="rmu-btn rmu-btn--secondary">
            <i class="ti ti-settings"></i> Account Settings
          </a>
        </div>
      </div>

    </div><!-- .rmu-content -->
  </div><!-- .rmu-main -->

</div><!-- #app-wrapper -->
</body>
</html>
