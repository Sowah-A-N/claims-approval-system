<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';

require_post();
require_role(array('user', 'claimant'));
csrf_verify();

$claimId = validated_int(isset($_POST['claimId']) ? $_POST['claimId'] : null, 'claimId');
$userId  = current_user_id();

// Verify the claim belongs to this user and is flagged
$chk = mysqli_prepare($conn,
    'SELECT claimId, department, programme, course FROM claim_details
     WHERE claimId = ? AND userId = ? AND flagged = 1 LIMIT 1');
if (!$chk) {
    json_response(array('success' => false, 'message' => 'Database error.'), 500);
}
mysqli_stmt_bind_param($chk, 'ii', $claimId, $userId);
mysqli_stmt_execute($chk);
$result = mysqli_stmt_get_result($chk);
$claim  = mysqli_fetch_assoc($result);
mysqli_stmt_close($chk);

if (!$claim) {
    json_response(array('success' => false, 'message' => 'Claim not found or is not flagged.'), 404);
}

// Fetch existing claim_data rows to copy
$data_stmt = mysqli_prepare($conn,
    'SELECT date, start_time, end_time, periods, subTotal, fuelComponent
     FROM claim_data WHERE claimId = ? ORDER BY date');
if (!$data_stmt) {
    json_response(array('success' => false, 'message' => 'Database error.'), 500);
}
mysqli_stmt_bind_param($data_stmt, 'i', $claimId);
mysqli_stmt_execute($data_stmt);
$data_rows = mysqli_fetch_all(mysqli_stmt_get_result($data_stmt), MYSQLI_ASSOC);
mysqli_stmt_close($data_stmt);

mysqli_begin_transaction($conn);

// Insert new saved_claims row
$ins_saved = mysqli_prepare($conn,
    'INSERT INTO saved_claims (userId, department, programme, course, date_saved)
     VALUES (?, ?, ?, ?, NOW())');
if (!$ins_saved) {
    mysqli_rollback($conn);
    json_response(array('success' => false, 'message' => 'Could not create draft.'), 500);
}
mysqli_stmt_bind_param($ins_saved, 'isss',
    $userId, $claim['department'], $claim['programme'], $claim['course']);
mysqli_stmt_execute($ins_saved);
$newTempId = (int)mysqli_insert_id($conn);
mysqli_stmt_close($ins_saved);

if ($newTempId === 0) {
    mysqli_rollback($conn);
    json_response(array('success' => false, 'message' => 'Could not create draft.'), 500);
}

// Copy claim_data rows to the new draft
$ins_data = mysqli_prepare($conn,
    'INSERT INTO claim_data (claimId, date, start_time, end_time, periods, subTotal, fuelComponent)
     VALUES (?, ?, ?, ?, ?, ?, ?)');
if (!$ins_data) {
    mysqli_rollback($conn);
    json_response(array('success' => false, 'message' => 'Could not copy claim data.'), 500);
}

foreach ($data_rows as $dr) {
    mysqli_stmt_bind_param($ins_data, 'isssidi',
        $newTempId, $dr['date'], $dr['start_time'], $dr['end_time'],
        $dr['periods'], $dr['subTotal'], $dr['fuelComponent']);
    if (!mysqli_stmt_execute($ins_data)) {
        mysqli_stmt_close($ins_data);
        mysqli_rollback($conn);
        json_response(array('success' => false, 'message' => 'Could not copy claim data.'), 500);
    }
}
mysqli_stmt_close($ins_data);

// Remove the original flagged claim now that a draft has been created.
foreach ([
    'DELETE FROM flagged_claims        WHERE claimId = ?',
    'DELETE FROM claim_approval_stages WHERE claimId = ?',
    'DELETE FROM claim_data            WHERE claimId = ?',
    'DELETE FROM claim_details         WHERE claimId = ? AND userId = ?',
] as $sql) {
    $del = mysqli_prepare($conn, $sql);
    if (!$del) { mysqli_rollback($conn); json_response(array('success' => false, 'message' => 'Database error.'), 500); }
    if (strpos($sql, 'claim_details') !== false) {
        mysqli_stmt_bind_param($del, 'ii', $claimId, $userId);
    } else {
        mysqli_stmt_bind_param($del, 'i', $claimId);
    }
    if (!mysqli_stmt_execute($del)) {
        error_log('[resubmitFlaggedClaim] delete failed: ' . mysqli_error($conn));
        mysqli_stmt_close($del);
        mysqli_rollback($conn);
        json_response(array('success' => false, 'message' => 'Could not remove the original claim.'), 500);
    }
    mysqli_stmt_close($del);
}

mysqli_commit($conn);
json_response(array('success' => true, 'claimTempId' => $newTempId));
