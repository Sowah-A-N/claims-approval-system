<?php
/*
 * Finance: flag a completed (payment-pending) claim back for correction, per
 * internal regulations (#3). Removes it from the finance queue (completed = 0)
 * and returns it to the claimant as flagged, mirroring the approver flag flow.
 *
 * POST: claimId, flagReason, csrf_token. JSON response.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/mailer.php';

require_post();
require_role(array('finance', 'Finance'));
csrf_verify();

$claim_id = validated_int(isset($_POST['claimId']) ? $_POST['claimId'] : null, 'claimId');
$reason   = validated_str(isset($_POST['flagReason']) ? $_POST['flagReason'] : '');

if ($reason === '') {
    json_response(array('success' => false, 'message' => 'A reason is required to flag a claim.'), 400);
}

mysqli_begin_transaction($conn);

// Only claims currently in the finance queue (completed, unpaid, not flagged).
$s1 = mysqli_prepare($conn,
    'UPDATE claim_details SET flagged = 1, completed = 0
     WHERE claimId = ? AND completed = 1 AND paid = 0 AND flagged = 0');
if (!$s1) { mysqli_rollback($conn); json_response(array('success' => false, 'message' => 'Database error.'), 500); }
mysqli_stmt_bind_param($s1, 'i', $claim_id);
mysqli_stmt_execute($s1);
$affected = mysqli_stmt_affected_rows($s1);
mysqli_stmt_close($s1);

if ($affected <= 0) {
    mysqli_rollback($conn);
    json_response(array('success' => false,
        'message' => 'Claim not found, already paid, or not awaiting payment.'), 409);
}

// Record the flag at the claim's highest reached stage.
$maxStage = 0;
$ms = mysqli_query($conn, 'SELECT COALESCE(MAX(stage),0) FROM claim_approval_stages WHERE claimId = ' . (int) $claim_id);
if ($ms) { $maxStage = (int) mysqli_fetch_row($ms)[0]; }

$s2 = mysqli_prepare($conn,
    "INSERT INTO claim_approval_stages (claimId, stage, status, time_rejected)
     VALUES (?, ?, 'Flagged', NOW())");
mysqli_stmt_bind_param($s2, 'ii', $claim_id, $maxStage);
mysqli_stmt_execute($s2);
mysqli_stmt_close($s2);

$msg = 'Finance: ' . $reason;
$s3 = mysqli_prepare($conn,
    'INSERT INTO flagged_claims (claimId, flagged_at_stage, flagged_msg, date_flagged)
     VALUES (?, ?, ?, NOW())');
mysqli_stmt_bind_param($s3, 'iis', $claim_id, $maxStage, $msg);
mysqli_stmt_execute($s3);
mysqli_stmt_close($s3);

mysqli_commit($conn);

log_audit($conn, 'claim.flag', 'claim', $claim_id, 'finance; ' . $reason);
email_notify_claim_owner($conn, $claim_id, 'claim_flagged', array('reason' => $reason));

json_response(array('success' => true, 'message' => 'Claim flagged and returned to the claimant.'));
