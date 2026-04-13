<?php
$pageTitle = 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<?php
include './assets/partials/head.php';

// Dashboard counts
$totalUsers    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM user_details"))['n'] ?? 0;
$activeUsers   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM user_details WHERE account_status = 'active'"))['n'] ?? 0;
$disabledUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM user_details WHERE account_status = 'disabled'"))['n'] ?? 0;
$totalClaims   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM claim_details"))['n'] ?? 0;
$flaggedClaims = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM claim_details WHERE flagged = 1"))['n'] ?? 0;

$disabledUserResult = mysqli_query($conn, "SELECT * FROM user_details WHERE account_status = 'disabled'");
?>
<body>

<?php include './assets/partials/sidebar.php'; ?>

<div class="rmu-main">

  <?php include './assets/partials/header.php'; ?>

  <div class="rmu-content">

    <div class="rmu-page-header">
      <div class="rmu-page-header__title">Admin Dashboard</div>
      <div class="rmu-page-header__sub">System overview and pending actions</div>
    </div>

    <!-- Stats -->
    <div class="rmu-stats">
      <div class="rmu-stat-card rmu-stat-card--primary">
        <div class="rmu-stat-card__icon rmu-stat-card__icon--primary"><i class="ti ti-users"></i></div>
        <div class="rmu-stat-card__value"><?php echo (int) $totalUsers; ?></div>
        <div class="rmu-stat-card__label">Total Users</div>
      </div>
      <div class="rmu-stat-card rmu-stat-card--success">
        <div class="rmu-stat-card__icon rmu-stat-card__icon--success"><i class="ti ti-user-check"></i></div>
        <div class="rmu-stat-card__value"><?php echo (int) $activeUsers; ?></div>
        <div class="rmu-stat-card__label">Active Users</div>
      </div>
      <div class="rmu-stat-card rmu-stat-card--warning">
        <div class="rmu-stat-card__icon rmu-stat-card__icon--warning"><i class="ti ti-user-x"></i></div>
        <div class="rmu-stat-card__value"><?php echo (int) $disabledUsers; ?></div>
        <div class="rmu-stat-card__label">Disabled Users</div>
      </div>
      <div class="rmu-stat-card rmu-stat-card--secondary">
        <div class="rmu-stat-card__icon rmu-stat-card__icon--secondary"><i class="ti ti-files"></i></div>
        <div class="rmu-stat-card__value"><?php echo (int) $totalClaims; ?></div>
        <div class="rmu-stat-card__label">Total Claims</div>
      </div>
      <div class="rmu-stat-card rmu-stat-card--danger">
        <div class="rmu-stat-card__icon rmu-stat-card__icon--danger"><i class="ti ti-flag"></i></div>
        <div class="rmu-stat-card__value"><?php echo (int) $flaggedClaims; ?></div>
        <div class="rmu-stat-card__label">Flagged Claims</div>
      </div>
    </div>

    <!-- Disabled users table -->
    <?php if ($disabledUserResult && mysqli_num_rows($disabledUserResult) > 0): ?>
    <div class="rmu-card">
      <div class="rmu-card__header">
        <span class="rmu-card__title"><i class="ti ti-user-x rmu-text-warning"></i> Pending Account Activations</span>
        <span class="rmu-badge rmu-badge--warning"><?php echo (int) $disabledUsers; ?> pending</span>
      </div>
      <div class="rmu-card__body" style="padding:0;">
        <div class="rmu-table-wrap">
          <table class="rmu-table">
            <thead>
              <tr>
                <th>User ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Role</th>
                <th>Activate</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = mysqli_fetch_assoc($disabledUserResult)): ?>
              <tr id="user-row-<?php echo (int) $row['userId']; ?>">
                <td><?php echo (int) $row['userId']; ?></td>
                <td><?php echo htmlspecialchars($row['first_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['last_name'],  ENT_QUOTES, 'UTF-8'); ?></td>
                <td><span class="rmu-badge rmu-badge--neutral"><?php echo htmlspecialchars($row['role'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                <td>
                  <button class="rmu-btn rmu-btn--success rmu-btn--sm"
                          onclick="activateAccount(<?php echo (int) $row['userId']; ?>)"
                          id="activate-btn-<?php echo (int) $row['userId']; ?>">
                    <i class="ti ti-user-check"></i> Activate
                  </button>
                </td>
                <td>
                  <button class="rmu-btn rmu-btn--secondary rmu-btn--sm"
                          onclick="viewAcctDetails(<?php echo (int) $row['userId']; ?>)">
                    <i class="ti ti-eye"></i> View
                  </button>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div class="rmu-alert rmu-alert--success">
      <i class="ti ti-circle-check"></i> No accounts pending activation.
    </div>
    <?php endif; ?>

  </div><!-- .rmu-content -->
</div><!-- .rmu-main -->

<!-- User Details Modal -->
<div class="rmu-modal-backdrop" id="userDetailsModal">
  <div class="rmu-modal">
    <div class="rmu-modal__header">
      <span class="rmu-modal__title">User Details</span>
      <button class="rmu-modal__close" onclick="document.getElementById('userDetailsModal').classList.remove('open')">
        <i class="ti ti-x"></i>
      </button>
    </div>
    <div class="rmu-modal__body">
      <div class="rmu-grid-2">
        <div class="rmu-form-group">
          <label class="rmu-label">First Name</label>
          <input type="text" class="rmu-input" id="ud_first_name" readonly>
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label">Last Name</label>
          <input type="text" class="rmu-input" id="ud_last_name" readonly>
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label">Phone Number</label>
          <input type="text" class="rmu-input" id="ud_phone_number" readonly>
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label">Gender</label>
          <input type="text" class="rmu-input" id="ud_gender" readonly>
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label">Email</label>
          <input type="text" class="rmu-input" id="ud_email" readonly>
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label">Department</label>
          <input type="text" class="rmu-input" id="ud_department" readonly>
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label">Role</label>
          <input type="text" class="rmu-input" id="ud_role" readonly>
        </div>
        <div class="rmu-form-group">
          <label class="rmu-label">Rank</label>
          <input type="text" class="rmu-input" id="ud_rank" readonly>
        </div>
        <div class="rmu-form-group" style="grid-column:1/-1;">
          <label class="rmu-label">Account Status</label>
          <input type="text" class="rmu-input" id="ud_account_status" readonly>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function activateAccount(userId) {
  var xhr = new XMLHttpRequest();
  xhr.open('POST', 'index.inc.php', true);
  xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
  xhr.onload = function() {
    if (xhr.status === 200) {
      window.location.reload();
    } else {
      alert('Error activating account.');
    }
  };
  xhr.send('action=activateAccount&userId=' + userId);
}

function viewAcctDetails(userId) {
  $.ajax({
    url: 'index.inc.php',
    type: 'POST',
    data: { action: 'viewAccountDetails', userId: userId },
    success: function(response) {
      var u = JSON.parse(response);
      document.getElementById('ud_first_name').value    = u.first_name    || '';
      document.getElementById('ud_last_name').value     = u.last_name     || '';
      document.getElementById('ud_phone_number').value  = u.phone_number  || '';
      document.getElementById('ud_gender').value        = u.gender        || '';
      document.getElementById('ud_email').value         = u.email         || '';
      document.getElementById('ud_department').value    = u.department    || '';
      document.getElementById('ud_role').value          = u.role          || '';
      document.getElementById('ud_rank').value          = u.rank          || '';
      document.getElementById('ud_account_status').value= u.account_status|| '';
      document.getElementById('userDetailsModal').classList.add('open');
    },
    error: function() { alert('An error occurred while fetching user details.'); }
  });
}
</script>

</body>
</html>
