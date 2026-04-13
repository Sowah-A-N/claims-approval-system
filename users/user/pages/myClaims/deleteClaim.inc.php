<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../queries/claim.queries.php';

require_post();
require_role(array('user', 'claimant'));

$claim_id = validated_int(isset($_POST['claimId']) ? $_POST['claimId'] : null, 'claimId');
$user_id  = current_user_id();

// Ownership is enforced inside db_delete_saved_claim — returns false if the
// claim does not belong to this user, preventing IDOR deletion.
$deleted = db_delete_saved_claim($conn, $claim_id, $user_id);

if ($deleted) {
    json_response(array('success' => 'Claim deleted successfully.'));
} else {
    json_response(array('error' => 'Claim not found or you do not have permission to delete it.'), 403);
}
