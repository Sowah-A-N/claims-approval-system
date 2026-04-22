<?php
$pageTitle = 'Finance Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<?php
include './assets/partials/head.php';

$completedClaimsResult = mysqli_query($conn,
    "SELECT cd.claimId,
            cd.department,
            cd.programme,
            cd.course,
            CONCAT(ud.first_name, ' ', ud.last_name) AS full_name
     FROM claim_details cd
     INNER JOIN user_details ud ON cd.userId = ud.userId
     WHERE cd.completed = 1"
);
?>
<body>

<?php include './assets/partials/sidebar.php'; ?>

<div class="rmu-main">

  <?php include './assets/partials/header.php'; ?>

  <div class="rmu-content">

    <div class="rmu-page-header">
      <div class="rmu-page-header__title">Finance Dashboard</div>
      <div class="rmu-page-header__sub">Completed claims awaiting payment processing</div>
    </div>

    <?php if ($completedClaimsResult && mysqli_num_rows($completedClaimsResult) > 0): ?>

    <div class="rmu-card">
      <div class="rmu-card__header">
        <span class="rmu-card__title"><i class="ti ti-circle-check rmu-text-success"></i> Completed Claims</span>
        <span class="rmu-badge rmu-badge--success"><?php echo mysqli_num_rows($completedClaimsResult); ?> records</span>
      </div>
      <div class="rmu-card__body" style="padding:0;">
        <div class="rmu-table-wrap">
          <table class="rmu-table">
            <thead>
              <tr>
                <th>Full Name</th>
                <th>Department</th>
                <th>Programme</th>
                <th>Course</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = mysqli_fetch_assoc($completedClaimsResult)): ?>
              <tr>
                <td><?php echo htmlspecialchars($row['full_name'],   ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['department'],  ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['programme'],   ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['course'],      ENT_QUOTES, 'UTF-8'); ?></td>
                <td><span class="rmu-badge rmu-badge--success">Completed</span></td>
                <td style="white-space:nowrap;">
                  <button class="rmu-btn rmu-btn--secondary rmu-btn--sm"
                          onclick="downloadClaimPDF(<?php echo (int) $row['claimId']; ?>)"
                          style="margin-right:6px;">
                    <i class="ti ti-file-download"></i> PDF
                  </button>
                  <button class="rmu-btn rmu-btn--primary rmu-btn--sm"
                          onclick="approvePayment(<?php echo (int) $row['claimId']; ?>)">
                    <i class="ti ti-credit-card"></i> Process Payment
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

    <div class="rmu-alert rmu-alert--info">
      <i class="ti ti-info-circle"></i> No completed claims found.
    </div>

    <?php endif; ?>

  </div><!-- .rmu-content -->
</div><!-- .rmu-main -->

<script>
function downloadClaimPDF(claimId) {
  window.open('downloadClaimPDF.inc.php?claimId=' + encodeURIComponent(claimId), '_blank');
}

function approvePayment(claimId) {
  alert('Payment approved for Claim ID: ' + claimId);
}
</script>

</body>
</html>
