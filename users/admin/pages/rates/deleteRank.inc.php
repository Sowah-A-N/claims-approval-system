<?php
/*
 * Delete a lecturer rank. Only permitted when no users are currently on it, so
 * we never orphan a user's rate basis.
 * Expected POST: rank (string), csrf_token
 * Returns JSON: { success, message }
 */
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';

require_post();
require_role(array('admin', 'Admin'));
csrf_verify();

$rank = validated_str(isset($_POST['rank']) ? $_POST['rank'] : '', 50);
if ($rank === '') {
    json_response(array('success' => false, 'message' => 'Rank is required.'), 422);
}

// Guard: refuse if any user is on this rank.
$chk = mysqli_prepare($conn, 'SELECT COUNT(*) FROM user_details WHERE `rank` = ?');
if (!$chk) {
    json_response(array('success' => false, 'message' => 'Database error.'), 500);
}
mysqli_stmt_bind_param($chk, 's', $rank);
mysqli_stmt_execute($chk);
$count = (int) mysqli_fetch_row(mysqli_stmt_get_result($chk))[0];
mysqli_stmt_close($chk);

if ($count > 0) {
    json_response(array('success' => false,
        'message' => 'Cannot delete "' . $rank . '" — ' . $count . ' user(s) are on this rank. Reassign them first.'), 409);
}

$stmt = mysqli_prepare($conn, 'DELETE FROM lecturer_rank_rate WHERE `rank` = ?');
if (!$stmt) {
    json_response(array('success' => false, 'message' => 'Database error.'), 500);
}
mysqli_stmt_bind_param($stmt, 's', $rank);
$ok  = mysqli_stmt_execute($stmt);
$aff = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

if (!$ok || $aff < 1) {
    json_response(array('success' => false, 'message' => 'Rank not found or could not be deleted.'), 500);
}

log_audit($conn, 'rank.delete', 'rank', null, $rank);
json_response(array('success' => true, 'message' => 'Rank "' . $rank . '" deleted.'));
