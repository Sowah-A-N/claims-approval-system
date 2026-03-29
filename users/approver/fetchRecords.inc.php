<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/queries/approval.queries.php';

require_role(['approver', 'Approver']);

$action        = validated_str($_POST['action']        ?? '');
$dateSubmitted = validated_str($_POST['dateSubmitted'] ?? '');

// Use the approver's department from the session — never from client input.
$dept          = (string) ($_SESSION['dept'] ?? '');

$base = 'SELECT cd.claimId, cd.course,
                DATE(cd.time_submitted) AS time_submitted,
                CONCAT(ud.first_name, \' \', ud.last_name) AS full_name,
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
         WHERE cd.department = ?';

$params = [$dept];
$types  = 's';

if ($action !== '') {
    $base    .= ' AND cas.status = ?';
    $params[] = $action;
    $types   .= 's';
}

$stmt = $conn->prepare($base);
if ($stmt === false) {
    json_response(['success' => false, 'results' => [], 'message' => 'Query prepare failed.'], 500);
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

json_response(['success' => !empty($records), 'results' => $records]);
