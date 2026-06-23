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

$stmt = mysqli_prepare($conn,
    'INSERT INTO user_bank_details (userId, bank_name, bank_branch, account_number, account_name)
     VALUES (?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
         bank_name      = VALUES(bank_name),
         bank_branch    = VALUES(bank_branch),
         account_number = VALUES(account_number),
         account_name   = VALUES(account_name)'
);
if (!$stmt) {
    error_log('[saveBankDetails] prepare failed: ' . mysqli_error($conn));
    json_response(array('ok' => false, 'message' => 'Database error.'), 500);
}
mysqli_stmt_bind_param($stmt, 'issss', $user_id, $bank_name, $bank_branch, $account_number, $account_name);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($ok) {
    json_response(array('ok' => true, 'message' => 'Bank details saved successfully.'));
} else {
    error_log('[saveBankDetails] execute failed: ' . mysqli_error($conn));
    json_response(array('ok' => false, 'message' => 'Failed to save bank details. Please try again.'), 500);
}
