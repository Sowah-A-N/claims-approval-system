<?php
/*
 * Mark a completed claim as paid (#15).
 *
 * Clears the claim from the payment queue and stamps who paid it and when,
 * replacing the old client-side alert() stub. One claim per call.
 *
 * Expected POST: claimId (int), payment_ref (optional), csrf_token
 * Returns JSON: { success: bool, message: string }
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

require_post();
require_role(array('finance', 'Finance'));
csrf_verify();

$claim_id    = validated_int(isset($_POST['claimId']) ? $_POST['claimId'] : null, 'claimId');
$payment_ref = validated_str(isset($_POST['payment_ref']) ? $_POST['payment_ref'] : '', 50);
$finance_id  = current_user_id();
$payment_ref = ($payment_ref === '') ? null : $payment_ref;

// Only a completed, not-yet-paid claim can be marked paid. The WHERE clause
// enforces that, so a double-submit affects zero rows.
$stmt = mysqli_prepare($conn,
    'UPDATE claim_details
        SET paid = 1, time_paid = NOW(), paid_by = ?, payment_ref = ?
      WHERE claimId = ? AND completed = 1 AND paid = 0'
);
if (!$stmt) {
    json_response(array('success' => false, 'message' => 'Database error.'), 500);
}
mysqli_stmt_bind_param($stmt, 'isi', $finance_id, $payment_ref, $claim_id);
mysqli_stmt_execute($stmt);
$affected = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

if ($affected > 0) {
    log_audit($conn, 'claim.paid', 'claim', $claim_id,
        $payment_ref !== null ? ('ref ' . $payment_ref) : null);
    json_response(array('success' => true, 'message' => 'Claim marked as paid.'));
} else {
    json_response(array('success' => false,
        'message' => 'Claim not found, not completed, or already paid.'), 409);
}
