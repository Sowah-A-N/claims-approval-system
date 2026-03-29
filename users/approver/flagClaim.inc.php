<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/queries/approval.queries.php';

require_post();
require_role(['approver', 'Approver']);

$claimId      = validated_int($_POST['claimId']    ?? null, 'claimId');
$flagReason   = validated_str($_POST['flagReason'] ?? '');
$sessionStage = (int) ($_SESSION['stage'] ?? 0);

if ($flagReason === '') {
    json_response(['success' => false, 'message' => 'A flag reason is required.'], 400);
}

if ($sessionStage === 0) {
    json_response(['success' => false, 'message' => 'Approver stage not set in session.'], 403);
}

// Stage ownership check: claim must be at this approver's stage.
$currentStage = db_get_current_stage($conn, $claimId);

if ($currentStage === null) {
    json_response(['success' => false, 'message' => 'Claim not found.'], 404);
}

if ($currentStage !== $sessionStage) {
    json_response([
        'success' => false,
        'message' => 'You are not authorised to flag this claim at its current stage.',
    ], 403);
}

try {
    db_flag_claim($conn, $claimId, $sessionStage, $flagReason);
    json_response(['success' => true, 'message' => 'Claim flagged successfully.']);
} catch (RuntimeException $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 409);
}
