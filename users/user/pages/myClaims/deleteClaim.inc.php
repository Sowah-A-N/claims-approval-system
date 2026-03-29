<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../queries/claim.queries.php';

require_post();
require_role(['user', 'claimant']);

$claimId = validated_int($_POST['claimId'] ?? null, 'claimId');
$userId  = current_user_id();

// Ownership is enforced inside db_delete_saved_claim — returns false if the
// claim does not belong to this user, preventing IDOR deletion.
$deleted = db_delete_saved_claim($conn, $claimId, $userId);

if ($deleted) {
    json_response(['success' => 'Claim deleted successfully.']);
} else {
    json_response(['error' => 'Claim not found or you do not have permission to delete it.'], 403);
}
