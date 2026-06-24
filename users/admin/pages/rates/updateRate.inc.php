<?php
/*
 * Update a lecturer rank's rate and propagate it to every user on that rank (#17).
 *
 * Expected POST: rank (string), rate (numeric >= 0), csrf_token
 * Returns JSON: { success, propagated, message }
 */
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';

require_post();
require_role(array('admin', 'Admin'));
csrf_verify();

$rank = validated_str(isset($_POST['rank']) ? $_POST['rank'] : '', 50);
$rate_raw = isset($_POST['rate']) ? trim($_POST['rate']) : '';

if ($rank === '') {
    json_response(array('success' => false, 'message' => 'Rank is required.'), 400);
}
if ($rate_raw === '' || !is_numeric($rate_raw) || (float) $rate_raw < 0) {
    json_response(array('success' => false, 'message' => 'Rate must be a non-negative number.'), 400);
}
$rate = round((float) $rate_raw, 2);

mysqli_begin_transaction($conn);

// 1. Update the rank's canonical rate.
$r1 = mysqli_prepare($conn, 'UPDATE lecturer_rank_rate SET rate = ? WHERE `rank` = ?');
if (!$r1) {
    mysqli_rollback($conn);
    json_response(array('success' => false, 'message' => 'Database error.'), 500);
}
mysqli_stmt_bind_param($r1, 'ds', $rate, $rank);
$ok = mysqli_stmt_execute($r1);
$rank_rows = mysqli_stmt_affected_rows($r1);
mysqli_stmt_close($r1);

if (!$ok || $rank_rows < 0) {
    mysqli_rollback($conn);
    json_response(array('success' => false, 'message' => 'Could not update the rank rate.'), 500);
}

// 2. Propagate to every user currently on that rank.
$r2 = mysqli_prepare($conn, 'UPDATE user_details SET rate = ? WHERE `rank` = ?');
if (!$r2) {
    mysqli_rollback($conn);
    json_response(array('success' => false, 'message' => 'Database error.'), 500);
}
mysqli_stmt_bind_param($r2, 'ds', $rate, $rank);
mysqli_stmt_execute($r2);
$propagated = mysqli_stmt_affected_rows($r2);
mysqli_stmt_close($r2);

mysqli_commit($conn);

log_audit($conn, 'rate.update', 'rank', null,
    $rank . ' = ' . number_format($rate, 2) . '; ' . max(0, $propagated) . ' user(s) updated');

json_response(array(
    'success'    => true,
    'propagated' => max(0, (int) $propagated),
    'message'    => 'Rate updated. ' . max(0, (int) $propagated) . ' user(s) on "' . $rank . '" updated.',
));
