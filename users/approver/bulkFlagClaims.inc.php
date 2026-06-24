<?php
/*
 * Bulk flag claims (#10).
 *
 * Flags every selected claim that is pending at THIS approver's stage with a
 * shared reason, reusing the transactional db_flag_claim logic. Claims not at
 * the approver's stage are skipped.
 *
 * Expected POST: claimIds[] (int array), flagReason, csrf_token
 * Returns JSON: { success, flagged, skipped, failed, message }
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/queries/approval.queries.php';

require_post();
require_role(array('approver', 'Approver'));
csrf_verify();

$session_stage = isset($_SESSION['stage']) ? (int) $_SESSION['stage'] : 0;
if ($session_stage === 0) {
    json_response(array('success' => false, 'message' => 'Approver stage not set in session.'), 403);
}

$reason = validated_str(isset($_POST['flagReason']) ? $_POST['flagReason'] : '');
if ($reason === '') {
    json_response(array('success' => false, 'message' => 'A flag reason is required.'), 400);
}

$ids = isset($_POST['claimIds']) && is_array($_POST['claimIds']) ? $_POST['claimIds'] : array();
if (empty($ids)) {
    json_response(array('success' => false, 'message' => 'No claims selected.'), 400);
}

$flagged = array();
$skipped = 0;
$failed  = array();

foreach ($ids as $raw) {
    $claim_id = filter_var($raw, FILTER_VALIDATE_INT);
    if ($claim_id === false) { $skipped++; continue; }

    $current_stage = db_get_current_stage($conn, $claim_id);
    if ($current_stage === null || $current_stage !== $session_stage) {
        $skipped++;
        continue;
    }

    $error = '';
    if (db_flag_claim($conn, $claim_id, $session_stage, $reason, $error)) {
        log_audit($conn, 'claim.flag', 'claim', $claim_id, 'bulk; ' . $reason);
        $flagged[] = $claim_id;
    } else {
        $failed[] = $claim_id;
    }
}

$n = count($flagged);
json_response(array(
    'success' => $n > 0,
    'flagged' => $flagged,
    'skipped' => $skipped,
    'failed'  => $failed,
    'message' => $n . ' claim(s) flagged'
               . ($skipped ? ', ' . $skipped . ' skipped' : '')
               . (count($failed) ? ', ' . count($failed) . ' failed' : '') . '.',
));
