<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/queries/approval.queries.php';

require_post();
require_role(array('approver', 'Approver'));

$claim_id      = validated_int(isset($_POST['claimId'])    ? $_POST['claimId']    : null, 'claimId');
$flag_reason   = validated_str(isset($_POST['flagReason']) ? $_POST['flagReason'] : '');
$session_stage = isset($_SESSION['stage']) ? (int) $_SESSION['stage'] : 0;

if ($flag_reason === '') {
    json_response(array('success' => false, 'message' => 'A flag reason is required.'), 400);
}

if ($session_stage === 0) {
    json_response(array('success' => false, 'message' => 'Approver stage not set in session.'), 403);
}

// Stage ownership check.
$current_stage = db_get_current_stage($conn, $claim_id);

if ($current_stage === null) {
    json_response(array('success' => false, 'message' => 'Claim not found.'), 404);
}

if ($current_stage !== $session_stage) {
    json_response(array(
        'success' => false,
        'message' => 'You are not authorised to flag this claim at its current stage.',
    ), 403);
}

$error = '';
$ok    = db_flag_claim($conn, $claim_id, $session_stage, $flag_reason, $error);

if ($ok) {
    json_response(array('success' => true, 'message' => 'Claim flagged successfully.'));
} else {
    json_response(array('success' => false, 'message' => $error), 409);
}
