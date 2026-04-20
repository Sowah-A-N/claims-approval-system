<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';

require_post();
require_role(['user', 'claimant']);

$claimId = (int) ($_POST['claimId'] ?? 0);
$userId  = current_user_id();

if ($claimId === 0) {
    json_response(['error' => 'Invalid claim ID.'], 400);
}

$stmt = mysqli_prepare($conn,
    "UPDATE saved_claims SET status = 'submitted' WHERE claimTempId = ? AND userId = ?");
mysqli_stmt_bind_param($stmt, 'ii', $claimId, $userId);
mysqli_stmt_execute($stmt);
$affected = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

if ($affected > 0) {
    json_response(['success' => 'Claim submitted successfully.']);
} else {
    json_response(['error' => 'Claim not found or permission denied.'], 403);
}
