<?php
/*
 * Return a past claim as a reusable template (#7): the header details
 * (department / programme / course / class) plus the distinct session times.
 * Dates are intentionally NOT returned — reuse starts with fresh dates.
 *
 * GET: claimId. Ownership is enforced. Returns JSON.
 */
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';

require_role(array('user', 'claimant'));

$claimId = validated_int(isset($_GET['claimId']) ? $_GET['claimId'] : null, 'claimId');
$userId  = current_user_id();

$chk = mysqli_prepare($conn,
    'SELECT department, programme, course, class
     FROM claim_details WHERE claimId = ? AND userId = ? LIMIT 1');
if (!$chk) {
    json_response(array('success' => false, 'message' => 'Database error.'), 500);
}
mysqli_stmt_bind_param($chk, 'ii', $claimId, $userId);
mysqli_stmt_execute($chk);
$claim = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));
mysqli_stmt_close($chk);

if (!$claim) {
    json_response(array('success' => false, 'message' => 'Claim not found.'), 404);
}

// Distinct session time windows on the source claim (HH:MM), earliest first.
$slots = array();
$sres = mysqli_prepare($conn,
    'SELECT DISTINCT start_time, end_time FROM claim_data WHERE claimId = ? ORDER BY start_time');
if ($sres) {
    mysqli_stmt_bind_param($sres, 'i', $claimId);
    mysqli_stmt_execute($sres);
    $r = mysqli_stmt_get_result($sres);
    while ($row = mysqli_fetch_assoc($r)) {
        $slots[] = array(
            'startTime' => substr((string) $row['start_time'], 0, 5),
            'endTime'   => substr((string) $row['end_time'], 0, 5),
        );
    }
    mysqli_stmt_close($sres);
}

json_response(array(
    'success'    => true,
    'department' => $claim['department'],
    'programme'  => $claim['programme'],
    'course'     => $claim['course'],
    'class'      => isset($claim['class']) ? $claim['class'] : '',
    'slots'      => $slots,
));
