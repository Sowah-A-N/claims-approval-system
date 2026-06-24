<?php
/*
 * CSV export of processed (paid) payments, honouring the same filters as the
 * Paid Claims page. Includes the optional payment reference, who processed it,
 * and when — for finance reporting and audit trails.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/queries/finance.queries.php';

require_role(array('finance', 'Finance'));

$filters = array(
    'from_date'  => validated_str(isset($_GET['from_date'])  ? $_GET['from_date']  : '', 10),
    'to_date'    => validated_str(isset($_GET['to_date'])    ? $_GET['to_date']    : '', 10),
    'department' => validated_str(isset($_GET['department']) ? $_GET['department'] : ''),
    'search'     => validated_str(isset($_GET['search'])     ? $_GET['search']     : '', 100),
);

$rows = db_get_paid_claims($conn, $filters);

$filename = 'processed_payments_' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

fputcsv($out, array(
    'Claim ID', 'Claimant', 'Department', 'Course',
    'Amount (GHS)', 'Payment Reference', 'Processed By', 'Paid On',
), ',', '"', '');

foreach ($rows as $r) {
    fputcsv($out, array_map('csv_safe', array(
        $r['claimId'],
        $r['full_name'],
        $r['department'],
        $r['course'],
        number_format((float) $r['grand_total'], 2, '.', ''),
        ($r['payment_ref'] === null || $r['payment_ref'] === '') ? '—' : $r['payment_ref'],
        ($r['paid_by_name'] === null) ? '' : trim($r['paid_by_name']),
        $r['time_paid'] ? date('Y-m-d H:i', strtotime($r['time_paid'])) : '',
    )), ',', '"', '');
}
fclose($out);

log_audit($conn, 'finance.export_paid', null, null, count($rows) . ' processed payment(s)');
exit;
