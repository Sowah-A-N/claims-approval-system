<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/claim_form_pdf.php';

checkUserRole(['finance', 'Finance']);

$claim_id = isset($_GET['claimId']) ? (int) $_GET['claimId'] : 0;

if ($claim_id <= 0) {
    http_response_code(400);
    exit('Invalid claim ID.');
}

$stmt = mysqli_prepare($conn,
    'SELECT cd.claimId,
            ud.first_name,
            ud.last_name,
            ud.other_names,
            ud.phone_number,
            ud.department  AS user_department,
            ud.rank,
            ud.rate,
            cd.programme,
            cd.course,
            cd.class,
            cdata.date     AS claim_date,
            cdata.start_time,
            cdata.end_time,
            cdata.periods,
            bd.bank_name,
            bd.bank_branch,
            bd.account_number,
            bd.account_name
     FROM claim_details cd
     JOIN user_details      ud    ON cd.userId  = ud.userId
     JOIN claim_data        cdata ON cd.claimId = cdata.claimId
     JOIN user_bank_details bd    ON ud.userId  = bd.userId
     WHERE cd.claimId = ? AND cd.completed = 1'
);

if (!$stmt) {
    http_response_code(500);
    exit('Database error.');
}

mysqli_stmt_bind_param($stmt, 'i', $claim_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$rows   = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

if (empty($rows)) {
    http_response_code(404);
    exit('Claim not found.');
}

render_rmu_claim_form($rows);

