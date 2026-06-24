<?php
/*
 * Bulk approve claims (#10).
 *
 * Approves every selected claim that is genuinely pending at THIS approver's
 * stage, reusing the same transactional, stage-ownership-checked logic as the
 * single approve. Claims not at the approver's stage are skipped, never forced.
 *
 * Expected POST: claimIds[] (int array), csrf_token
 * Returns JSON: { success, approved, skipped, failed, message }
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

$ids = isset($_POST['claimIds']) && is_array($_POST['claimIds']) ? $_POST['claimIds'] : array();
if (empty($ids)) {
    json_response(array('success' => false, 'message' => 'No claims selected.'), 400);
}

$approved = array();
$skipped  = 0;
$failed   = array();

foreach ($ids as $raw) {
    $claim_id = filter_var($raw, FILTER_VALIDATE_INT);
    if ($claim_id === false) { $skipped++; continue; }

    $current_stage = db_get_current_stage($conn, $claim_id);
    if ($current_stage === null || $current_stage !== $session_stage) {
        $skipped++; // not found, or not at this approver's stage
        continue;
    }

    $error     = '';
    $completed = false;
    if (db_advance_claim_stage($conn, $claim_id, $current_stage, $error, $completed)) {
        log_audit($conn, 'claim.approve', 'claim', $claim_id,
            'bulk; stage ' . $current_stage . ($completed ? ' (completed)' : ''));
        $approved[] = $claim_id;
    } else {
        $failed[] = $claim_id;
    }
}

$n = count($approved);
json_response(array(
    'success'  => $n > 0,
    'approved' => $approved,
    'skipped'  => $skipped,
    'failed'   => $failed,
    'message'  => $n . ' claim(s) approved'
                . ($skipped ? ', ' . $skipped . ' skipped' : '')
                . (count($failed) ? ', ' . count($failed) . ' failed' : '') . '.',
));
