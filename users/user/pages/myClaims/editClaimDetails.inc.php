<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../queries/claim.queries.php';

require_role(array('user', 'claimant'));

$claim_id = validated_int(isset($_GET['claimId']) ? $_GET['claimId'] : null, 'claimId');
$user_id  = current_user_id();

// Ownership enforced in query — returns null if claimId belongs to another user.
$claim = db_get_saved_claim_by_owner($conn, $claim_id, $user_id);

if ($claim === null) {
    echo '<p>Claim not found.</p>';
    exit;
}

echo '<p id="claimId"   name="claimId"><strong>Claim ID</strong>: '   . h($claim['claimTempId']) . '</p>';
echo '<p id="programme" name="programme"><strong>Programme</strong>: ' . h($claim['programme'])  . '</p>';
echo '<p id="course"    name="course"><strong>Course</strong>: '       . h($claim['course'])      . '</p>';
