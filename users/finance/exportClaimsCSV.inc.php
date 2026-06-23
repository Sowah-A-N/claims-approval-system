<?php
/*
 * One-click CSV export of payment-ready (completed) claims.
 * Mirrors the data Finance needs for bank submission: claimant, course,
 * computed total, and bank account details. One row per claim.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

checkUserRole(['finance', 'Finance']);

$sql =
    "SELECT cd.claimId,
            CONCAT(ud.last_name, ', ', ud.first_name,
                   CASE WHEN ud.other_names IS NULL OR ud.other_names = '' THEN ''
                        ELSE CONCAT(' ', ud.other_names) END) AS full_name,
            ud.department,
            ud.rank,
            ud.rate,
            cd.programme,
            cd.course,
            COALESCE(SUM(cdata.periods), 0)             AS total_periods,
            COALESCE(SUM(cdata.periods), 0) * ud.rate   AS grand_total,
            bd.bank_name,
            bd.bank_branch,
            bd.account_number,
            bd.account_name
     FROM claim_details cd
     JOIN user_details ud         ON cd.userId  = ud.userId
     LEFT JOIN claim_data cdata   ON cd.claimId = cdata.claimId
     LEFT JOIN user_bank_details bd ON ud.userId = bd.userId
     WHERE cd.completed = 1
     GROUP BY cd.claimId
     ORDER BY ud.last_name, cd.claimId";

$result = mysqli_query($conn, $sql);
if (!$result) {
    error_log('[exportClaimsCSV] query failed: ' . mysqli_error($conn));
    http_response_code(500);
    exit('Could not generate export.');
}

$filename = 'payment_ready_claims_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

// UTF-8 BOM so Excel renders accented names correctly.
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, array(
    'Claim ID', 'Claimant', 'Department', 'Rank', 'Rate (GHS)',
    'Programme', 'Course', 'Total Periods', 'Grand Total (GHS)',
    'Bank Name', 'Branch', 'Account Number', 'Account Name',
));

while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($out, array(
        $row['claimId'],
        $row['full_name'],
        $row['department'],
        $row['rank'],
        number_format((float) $row['rate'], 2, '.', ''),
        $row['programme'],
        $row['course'],
        (int) $row['total_periods'],
        number_format((float) $row['grand_total'], 2, '.', ''),
        $row['bank_name'],
        $row['bank_branch'],
        $row['account_number'],
        $row['account_name'],
    ));
}

fclose($out);
exit;
