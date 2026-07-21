<?php
/*
 * Add a new lecturer rank with its rate (#rank management).
 * Expected POST: rank (string), rate (numeric >= 0), csrf_token
 * Returns JSON: { success, message }
 */
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';

require_post();
require_role(array('admin', 'Admin'));
csrf_verify();

$rank     = validated_str(isset($_POST['rank']) ? $_POST['rank'] : '', 50);
$rate_raw = isset($_POST['rate']) ? trim($_POST['rate']) : '';

if ($rank === '') {
    json_response(array('success' => false, 'message' => 'Please enter a rank name.'), 422);
}
if ($rate_raw === '' || !is_numeric($rate_raw) || (float) $rate_raw < 0) {
    json_response(array('success' => false, 'message' => 'Rate must be a non-negative number.'), 422);
}
$rate = round((float) $rate_raw, 2);

// Reject duplicates (case-insensitive) with a clear message.
$dup = mysqli_prepare($conn, 'SELECT 1 FROM lecturer_rank_rate WHERE LOWER(`rank`) = LOWER(?) LIMIT 1');
if ($dup) {
    mysqli_stmt_bind_param($dup, 's', $rank);
    mysqli_stmt_execute($dup);
    $exists = mysqli_fetch_row(mysqli_stmt_get_result($dup)) !== null;
    mysqli_stmt_close($dup);
    if ($exists) {
        json_response(array('success' => false, 'message' => 'A rank named "' . $rank . '" already exists.'), 409);
    }
}

$stmt = mysqli_prepare($conn, 'INSERT INTO lecturer_rank_rate (`rank`, rate) VALUES (?, ?)');
if (!$stmt) {
    json_response(array('success' => false, 'message' => 'Database error.'), 500);
}
mysqli_stmt_bind_param($stmt, 'sd', $rank, $rate);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$ok) {
    json_response(array('success' => false, 'message' => 'Could not add the rank. Please try again.'), 500);
}

log_audit($conn, 'rank.add', 'rank', null, $rank . ' = ' . number_format($rate, 2));
json_response(array('success' => true, 'message' => 'Rank "' . $rank . '" added at GHS ' . number_format($rate, 2) . '.'));
