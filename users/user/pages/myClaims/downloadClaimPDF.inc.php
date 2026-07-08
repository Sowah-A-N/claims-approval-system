<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../queries/claim.queries.php';

require_role(array('user', 'claimant'));

$claim_id = isset($_GET['claimId']) ? (int) $_GET['claimId'] : 0;
$user_id  = current_user_id();

if ($claim_id <= 0) {
    http_response_code(400);
    exit('Invalid claim ID.');
}

$rows = db_get_claim_download_data($conn, $claim_id, $user_id);

if (empty($rows)) {
    http_response_code(404);
    exit('Claim not found or access denied.');
}

$first       = $rows[0];
$grand_total = 0;
foreach ($rows as $row) {
    $grand_total += (float) $row['periods'] * (float) $first['rate'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Claim #<?php echo $claim_id; ?> &mdash; <?php echo h($first['last_name'] . ', ' . $first['first_name']); ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, Helvetica, sans-serif; font-size: 11pt; color: #111; background: #fff; padding: 28px 32px; }
h1 { font-size: 15pt; text-align: center; margin-bottom: 4px; }
h2 { font-size: 11pt; text-align: center; margin-bottom: 22px; color: #444; font-weight: normal; }
.section-title {
    font-size: 10pt; font-weight: bold; margin: 16px 0 7px;
    text-transform: uppercase; letter-spacing: .04em;
    border-bottom: 1.5px solid #999; padding-bottom: 3px; color: #333;
}
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 5px 32px; margin-bottom: 4px; }
.info-row { display: flex; gap: 6px; font-size: 10.5pt; line-height: 1.5; }
.info-label { font-weight: bold; white-space: nowrap; min-width: 130px; }
table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 10pt; }
th, td { border: 1px solid #bbb; padding: 5px 9px; }
th { background: #ebebeb; font-weight: bold; text-align: left; }
.total-row td { font-weight: bold; background: #f3f3f3; }
.footer-note { margin-top: 28px; font-size: 8.5pt; color: #777; border-top: 1px solid #ddd; padding-top: 8px; }
.no-print { text-align: right; margin-bottom: 18px; }
.btn-print {
    padding: 8px 18px; cursor: pointer;
    background: #2563eb; color: #fff;
    border: none; border-radius: 4px; font-size: 10.5pt; margin-left: 6px;
}
.btn-close-win {
    padding: 8px 18px; cursor: pointer;
    background: #6b7280; color: #fff;
    border: none; border-radius: 4px; font-size: 10.5pt; margin-left: 6px;
}
@media print {
    .no-print { display: none; }
    body { padding: 0; }
    @page { margin: 15mm; }
}
</style>
</head>
<body>

<div class="no-print">
  <button class="btn-print" onclick="window.print()"><i>&#128438;</i> Print / Save as PDF</button>
  <button class="btn-close-win" onclick="window.close()">Close</button>
</div>

<h1>Regional Maritime University</h1>
<h2>Part-Time Teaching Claim Form</h2>

<div class="section-title">Personal Details</div>
<div class="info-grid">
  <div class="info-row"><span class="info-label">Name:</span><span><?php echo h($first['last_name'] . ', ' . $first['first_name'] . ($first['other_names'] ? ' ' . $first['other_names'] : '')); ?></span></div>
  <div class="info-row"><span class="info-label">Phone Number:</span><span><?php echo h($first['phone_number']); ?></span></div>
  <div class="info-row"><span class="info-label">Department:</span><span><?php echo h($first['user_department']); ?></span></div>
  <div class="info-row"><span class="info-label">Rank:</span><span><?php echo h($first['rank']); ?></span></div>
</div>

<div class="section-title">Course Details</div>
<div class="info-grid">
  <div class="info-row"><span class="info-label">Programme:</span><span><?php echo h($first['programme']); ?></span></div>
  <div class="info-row"><span class="info-label">Course:</span><span><?php echo h($first['course']); ?></span></div>
  <div class="info-row"><span class="info-label">Class(es):</span><span><?php echo !empty($first['class']) ? h($first['class']) : '—'; ?></span></div>
  <div class="info-row"><span class="info-label">Rate per Period:</span><span>GHS <?php echo number_format((float) $first['rate'], 2); ?></span></div>
</div>

<div class="section-title">Bank Details</div>
<div class="info-grid">
  <div class="info-row"><span class="info-label">Bank:</span><span><?php echo h($first['bank_name']); ?></span></div>
  <div class="info-row"><span class="info-label">Branch:</span><span><?php echo h($first['bank_branch']); ?></span></div>
  <div class="info-row"><span class="info-label">Account Number:</span><span><?php echo h($first['account_number']); ?></span></div>
  <div class="info-row"><span class="info-label">Account Name:</span><span><?php echo h($first['account_name']); ?></span></div>
</div>

<div class="section-title">Teaching Hours</div>
<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Date</th>
      <th>Start Time</th>
      <th>End Time</th>
      <th>Periods</th>
      <th>Amount (GHS)</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $i => $row):
        $amount = (float) $row['periods'] * (float) $first['rate'];
    ?>
    <tr>
      <td><?php echo $i + 1; ?></td>
      <td><?php echo h(date('d/m/Y', strtotime($row['claim_date']))); ?></td>
      <td><?php echo h($row['start_time']); ?></td>
      <td><?php echo h($row['end_time']); ?></td>
      <td><?php echo (int) $row['periods']; ?></td>
      <td><?php echo number_format($amount, 2); ?></td>
    </tr>
    <?php endforeach; ?>
    <tr class="total-row">
      <td colspan="5" style="text-align:right;">Grand Total</td>
      <td>GHS <?php echo number_format($grand_total, 2); ?></td>
    </tr>
  </tbody>
</table>

<div class="footer-note">Claim #<?php echo $claim_id; ?> &mdash; Generated on <?php echo date('d/m/Y, H:i'); ?> (<?php echo date('T'); ?>)</div>

<script>window.onload = function() { window.print(); };</script>
</body>
</html>
