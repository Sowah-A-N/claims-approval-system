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
     WHERE cd.completed = 1 AND cd.paid = 0"
);

$CSRF = csrf_token();
?>
<body>

<?php include './assets/partials/sidebar.php'; ?>

<div class="rmu-main">

  <?php include './assets/partials/header.php'; ?>

  <div class="rmu-content">

    <div class="rmu-page-header" style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
      <div>
        <div class="rmu-page-header__title">Finance Dashboard</div>
        <div class="rmu-page-header__sub">Completed claims awaiting payment processing</div>
      </div>
      <?php if ($completedClaimsResult && mysqli_num_rows($completedClaimsResult) > 0): ?>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a class="rmu-btn rmu-btn--secondary" href="exportClaimsCSV.inc.php">
          <i class="ti ti-file-spreadsheet"></i> Export CSV
        </a>
        <a class="rmu-btn rmu-btn--secondary" href="exportPaymentBatch.inc.php">
          <i class="ti ti-building-bank"></i> Payment Batch
        </a>
        <a class="rmu-btn rmu-btn--success" href="downloadAllClaims.inc.php">
          <i class="ti ti-file-zip"></i> Download All Forms
        </a>
      </div>
      <?php endif; ?>
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
                          onclick="markPaid(<?php echo (int) $row['claimId']; ?>, this)">
                    <i class="ti ti-credit-card"></i> Mark Paid
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

<!-- Mark-as-paid modal -->
<div class="rmu-modal-backdrop" id="payBackdrop" role="dialog" aria-modal="true" aria-labelledby="payTitle">
  <div class="rmu-modal" style="max-width:440px;width:calc(100% - 48px);">
    <div class="rmu-modal__header">
      <span class="rmu-modal__title" id="payTitle"><i class="ti ti-credit-card"></i> Mark Claim as Paid</span>
      <button class="rmu-modal__close" onclick="closePayModal()" aria-label="Close"><i class="ti ti-x"></i></button>
    </div>
    <div class="rmu-modal__body">
      <p id="payClaimLabel" style="font-size:.85rem;color:var(--txt-secondary);margin-bottom:14px;"></p>
      <div class="rmu-form-group">
        <label class="rmu-label" for="payRef">Payment Reference <span style="color:var(--txt-muted);">(optional)</span></label>
        <input type="text" class="rmu-input" id="payRef" maxlength="50" placeholder="e.g. bank transfer ref">
      </div>
      <div id="payError" class="rmu-alert rmu-alert--danger" style="display:none;"></div>
      <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:18px;">
        <button type="button" class="rmu-btn rmu-btn--secondary" onclick="closePayModal()">Cancel</button>
        <button type="button" class="rmu-btn rmu-btn--primary" id="payConfirmBtn" onclick="confirmPay()">
          <i class="ti ti-check"></i> Confirm Payment
        </button>
      </div>
    </div>
  </div>
</div>

<script>
var CSRF = <?php echo json_encode($CSRF); ?>;
var _payClaimId = null, _payRow = null, _payTrigger = null;

function downloadClaimPDF(claimId) {
  window.open('downloadClaimPDF.inc.php?claimId=' + encodeURIComponent(claimId), '_blank');
}

function markPaid(claimId, btn) {
  _payClaimId = claimId;
  _payRow     = btn.closest('tr');
  _payTrigger = btn;
  document.getElementById('payClaimLabel').textContent = 'Claim #' + claimId + ' will be marked as paid and removed from the queue.';
  document.getElementById('payRef').value = '';
  var err = document.getElementById('payError'); err.style.display = 'none'; err.textContent = '';
  var cb = document.getElementById('payConfirmBtn'); cb.disabled = false;
  document.getElementById('payBackdrop').classList.add('open');
  document.body.style.overflow = 'hidden';
  setTimeout(function() { document.getElementById('payRef').focus(); }, 60);
}

function closePayModal() {
  document.getElementById('payBackdrop').classList.remove('open');
  document.body.style.overflow = '';
  if (_payTrigger && _payTrigger.focus) _payTrigger.focus();
}

function confirmPay() {
  if (_payClaimId === null) return;
  var cb  = document.getElementById('payConfirmBtn');
  var err = document.getElementById('payError');
  cb.disabled = true;
  err.style.display = 'none';

  var fd = new FormData();
  fd.append('claimId', _payClaimId);
  fd.append('payment_ref', document.getElementById('payRef').value);
  fd.append('csrf_token', CSRF);

  fetch('markPaid.inc.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(res) {
      if (res.success) {
        if (_payRow) _payRow.parentNode.removeChild(_payRow);
        closePayModal();
      } else {
        err.textContent = res.message || 'Could not mark as paid.';
        err.style.display = 'block';
        cb.disabled = false;
      }
    })
    .catch(function() {
      err.textContent = 'Network error. Please try again.';
      err.style.display = 'block';
      cb.disabled = false;
    });
}

document.getElementById('payBackdrop').addEventListener('click', function(e) {
  if (e.target === this) closePayModal();
});
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape' && document.getElementById('payBackdrop').classList.contains('open')) closePayModal();
});
</script>

</body>
</html>
