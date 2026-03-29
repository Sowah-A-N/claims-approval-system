<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../queries/claim.queries.php';

require_role(['user', 'claimant']);

$claimId = validated_int($_GET['claimId'] ?? null, 'claimId');
$userId  = current_user_id();

// Ownership enforced in query — null if claimId belongs to another user.
$claim = db_get_saved_claim_by_owner($conn, $claimId, $userId);

if ($claim === null) {
    echo '<p>Claim not found.</p>';
    exit;
}

echo '<p id="claimId"    name="claimId"><strong>Claim ID</strong>: '  . h($claim['claimTempId']) . '</p>';
echo '<p id="programme"  name="programme"><strong>Programme</strong>: ' . h($claim['programme'])  . '</p>';
echo '<p id="course"     name="course"><strong>Course</strong>: '      . h($claim['course'])      . '</p>';
