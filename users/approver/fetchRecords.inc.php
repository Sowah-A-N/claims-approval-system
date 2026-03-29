<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role(array('approver', 'Approver'));

$action         = validated_str(isset($_POST['action'])        ? $_POST['action']        : '');
$date_submitted = validated_str(isset($_POST['dateSubmitted']) ? $_POST['dateSubmitted'] : '');

// Use the approver's own department from the session — never from client input.
$dept = isset($_SESSION['dept']) ? (string) $_SESSION['dept'] : '';

$base =
    "SELECT cd.claimId,
            cd.course,
            DATE(cd.time_submitted) AS time_submitted,
            CONCAT(ud.first_name, ' ', ud.last_name) AS full_name,
            cas.stage,
            cas.status
     FROM claim_details cd
     INNER JOIN user_details ud ON cd.userId = ud.userId
     INNER JOIN claim_approval_stages cas ON cd.claimId = cas.claimId
     JOIN (
         SELECT claimId, MAX(stage) AS max_stage
         FROM claim_approval_stages
         GROUP BY claimId
     ) max_stages ON cas.claimId = max_stages.claimId AND cas.stage = max_stages.max_stage
     WHERE cd.department = ?";

$params = array($dept);
$types  = 's';

if ($action !== '') {
    $base    .= ' AND cas.status = ?';
    $params[] = $action;
    $types   .= 's';
}

$stmt = mysqli_prepare($conn, $base);
if (!$stmt) {
    json_response(array('success' => false, 'results' => array(), 'message' => 'Query failed.'), 500);
}

mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result  = mysqli_stmt_get_result($stmt);
$records = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

json_response(array('success' => !empty($records), 'results' => $records));
