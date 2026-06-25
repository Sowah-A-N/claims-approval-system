<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';

require_post();
require_role(array('user', 'claimant'));
csrf_verify();

$user_id        = current_user_id();
$bank_name      = validated_str(isset($_POST['bank_name'])      ? $_POST['bank_name']      : '');
$bank_branch    = validated_str(isset($_POST['bank_branch'])    ? $_POST['bank_branch']    : '');
$account_number = validated_str(isset($_POST['account_number']) ? $_POST['account_number'] : '');
$account_name   = validated_str(isset($_POST['account_name'])   ? $_POST['account_name']   : '');

if ($bank_name === '' || $account_number === '' || $account_name === '') {
    json_response(array('ok' => false, 'message' => 'Bank name, account number, and account name are required.'), 400);
}

// Explicit update-or-insert by userId. Doesn't rely on a UNIQUE constraint, so
// it persists correctly even where that migration hasn't been applied yet.
$chk = mysqli_prepare($conn, 'SELECT user_bank_details_id FROM user_bank_details WHERE userId = ? LIMIT 1');
mysqli_stmt_bind_param($chk, 'i', $user_id);
mysqli_stmt_execute($chk);
$existing = mysqli_fetch_row(mysqli_stmt_get_result($chk));
mysqli_stmt_close($chk);

if ($existing) {
    $stmt = mysqli_prepare($conn,
        'UPDATE user_bank_details
            SET bank_name = ?, bank_branch = ?, account_number = ?, account_name = ?
          WHERE userId = ?');
    if (!$stmt) {
        error_log('[saveBankDetails] prepare failed: ' . mysqli_error($conn));
        json_response(array('ok' => false, 'message' => 'Database error.'), 500);
    }
    mysqli_stmt_bind_param($stmt, 'ssssi', $bank_name, $bank_branch, $account_number, $account_name, $user_id);
} else {
    $stmt = mysqli_prepare($conn,
        'INSERT INTO user_bank_details (userId, bank_name, bank_branch, account_number, account_name)
         VALUES (?, ?, ?, ?, ?)');
    if (!$stmt) {
        error_log('[saveBankDetails] prepare failed: ' . mysqli_error($conn));
        json_response(array('ok' => false, 'message' => 'Database error.'), 500);
    }
    mysqli_stmt_bind_param($stmt, 'issss', $user_id, $bank_name, $bank_branch, $account_number, $account_name);
}
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($ok) {
    json_response(array('ok' => true, 'message' => 'Bank details saved successfully.'));
} else {
    error_log('[saveBankDetails] execute failed: ' . mysqli_error($conn));
    json_response(array('ok' => false, 'message' => 'Failed to save bank details. Please try again.'), 500);
}
