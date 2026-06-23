<?php
/*
 * Bank payment-batch export (#13).
 *
 * A lean, payment-instruction file (one row per unpaid completed claim) in the
 * shape bank bulk-upload templates expect: beneficiary account, bank/branch,
 * amount and a reference. This is distinct from exportClaimsCSV.inc.php, which
 * is the detailed reconciliation report.
 *
 * NOTE: column order/labels may need adjusting to match a specific bank's
 * template — the data is here; only the header row would change.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role(array('finance', 'Finance'));

$sql =
    "SELECT cd.claimId,
            bd.account_name,
            bd.account_number,
            bd.bank_name,
            bd.bank_branch,
            COALESCE(SUM(cdata.periods), 0) * ud.rate AS amount
     FROM claim_details cd
     JOIN user_details ud           ON cd.userId  = ud.userId
     LEFT JOIN claim_data cdata     ON cd.claimId = cdata.claimId
     LEFT JOIN user_bank_details bd ON ud.userId  = bd.userId
     WHERE cd.completed = 1 AND cd.paid = 0
     GROUP BY cd.claimId
     ORDER BY bd.bank_name, bd.account_name, cd.claimId";

$result = mysqli_query($conn, $sql);
if (!$result) {
    error_log('[exportPaymentBatch] query failed: ' . mysqli_error($conn));
    http_response_code(500);
    exit('Could not generate export.');
}

$filename = 'payment_batch_' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

fputcsv($out, array(
    'Account Name', 'Account Number', 'Bank', 'Branch', 'Amount (GHS)', 'Reference',
));

while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($out, array_map('csv_safe', array(
        $row['account_name'],
        $row['account_number'],
        $row['bank_name'],
        $row['bank_branch'],
        number_format((float) $row['amount'], 2, '.', ''),
        'RMU-CLAIM-' . (int) $row['claimId'],
    )));
}

fclose($out);
exit;
