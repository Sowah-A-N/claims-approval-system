<?php
/*
 * Admin: update a user's editable profile fields (rate, rank, department)
 * after registration. Auth tables stay in sync — rank lives in both
 * user_details and login_details.
 */
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';

require_post();
require_role(array('admin', 'Admin'));
csrf_verify();

$userId     = validated_int(isset($_POST['userId']) ? $_POST['userId'] : null, 'userId');
$rank       = validated_str(isset($_POST['rank'])       ? $_POST['rank']       : '', 120);
$department = validated_str(isset($_POST['department']) ? $_POST['department'] : '', 120);
$rate_raw   = isset($_POST['rate']) ? $_POST['rate'] : '';

$rate = filter_var($rate_raw, FILTER_VALIDATE_FLOAT);
if ($rate === false || $rate < 0) {
    json_response(array('success' => false, 'message' => 'Rate must be a non-negative number.'), 400);
}

mysqli_begin_transaction($conn);

$s1 = mysqli_prepare($conn,
    'UPDATE user_details SET rate = ?, `rank` = ?, department = ? WHERE userId = ?');
if (!$s1) {
    mysqli_rollback($conn);
    json_response(array('success' => false, 'message' => 'Database error.'), 500);
}
mysqli_stmt_bind_param($s1, 'dssi', $rate, $rank, $department, $userId);
$ok = mysqli_stmt_execute($s1);
mysqli_stmt_close($s1);

if ($ok) {
    // Mirror rank into login_details so the two auth tables don't drift.
    $s2 = mysqli_prepare($conn, 'UPDATE login_details SET `rank` = ? WHERE userId = ?');
    if ($s2) {
        mysqli_stmt_bind_param($s2, 'si', $rank, $userId);
        mysqli_stmt_execute($s2);
        mysqli_stmt_close($s2);
    }
}

if ($ok) {
    mysqli_commit($conn);
    json_response(array('success' => true, 'message' => 'User details updated successfully.'));
} else {
    mysqli_rollback($conn);
    error_log('[updateUser] failed: ' . mysqli_error($conn));
    json_response(array('success' => false, 'message' => 'Update failed. Please try again.'), 500);
}
