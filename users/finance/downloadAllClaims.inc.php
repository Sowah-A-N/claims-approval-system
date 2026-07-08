<?php
/*
 * Batch claim-form download (#14).
 *
 * Generates the official claim-form .docx for every completed, unpaid claim
 * and streams them back as a single .zip — replacing the one-at-a-time PDF
 * download. Uses the same PhpWord template as the claimant download.
 */
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

use PhpOffice\PhpWord\TemplateProcessor;

require_role(array('finance', 'Finance'));

$template_path = __DIR__ . '/../../uploads/claim_form_template.docx';
if (!file_exists($template_path)) {
    http_response_code(500);
    exit('Claim template file not found. Please contact the administrator.');
}
if (!class_exists('ZipArchive')) {
    http_response_code(500);
    exit('ZIP support is not available on this server.');
}

// All completed, unpaid claims with claimant + bank details.
$claims_sql =
    "SELECT cd.claimId, cd.programme, cd.course, cd.class, ud.rate,
            ud.first_name, ud.last_name, ud.other_names, ud.phone_number,
            ud.department AS user_department, ud.rank,
            bd.bank_name, bd.bank_branch, bd.account_number, bd.account_name
     FROM claim_details cd
     JOIN user_details ud           ON cd.userId = ud.userId
     LEFT JOIN user_bank_details bd ON ud.userId = bd.userId
     WHERE cd.completed = 1 AND cd.paid = 0
     ORDER BY ud.last_name, cd.claimId";
$claims = mysqli_query($conn, $claims_sql);
if (!$claims || mysqli_num_rows($claims) === 0) {
    http_response_code(404);
    exit('No completed claims awaiting payment.');
}

// Prepared statement reused for each claim's teaching-session rows.
$rows_stmt = mysqli_prepare($conn,
    'SELECT date, start_time, end_time, periods FROM claim_data WHERE claimId = ? ORDER BY date');
if (!$rows_stmt) {
    http_response_code(500);
    exit('Database error.');
}

$zip_path = tempnam(sys_get_temp_dir(), 'claims_') . '.zip';
$zip = new ZipArchive();
if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    exit('Could not create archive.');
}

$temp_files = array();
$count      = 0;

while ($c = mysqli_fetch_assoc($claims)) {
    $claim_id = (int) $c['claimId'];
    $rate     = (float) $c['rate'];

    mysqli_stmt_bind_param($rows_stmt, 'i', $claim_id);
    mysqli_stmt_execute($rows_stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($rows_stmt), MYSQLI_ASSOC);
    if (empty($rows)) continue;

    $tp = new TemplateProcessor($template_path);
    $tp->setValue('first_name',      $c['first_name']);
    $tp->setValue('last_name',       $c['last_name']);
    $tp->setValue('other_names',     $c['other_names']);
    $tp->setValue('phone_number',    $c['phone_number']);
    $tp->setValue('user_department', $c['user_department']);
    $tp->setValue('rank',            $c['rank']);
    $tp->setValue('rate',            $rate);
    $tp->setValue('programme',       $c['programme']);
    $tp->setValue('course',          $c['course']);
    $tp->setValue('class',           isset($c['class']) ? $c['class'] : '');
    $tp->setValue('bank_name',       $c['bank_name']);
    $tp->setValue('bank_branch',     $c['bank_branch']);
    $tp->setValue('account_number',  $c['account_number']);
    $tp->setValue('account_name',    $c['account_name']);

    $grand_total = 0;
    $tp->cloneBlock('claim_data_block', 0, true, false, $rows);
    foreach ($rows as $i => $r) {
        $amount       = (float) $r['periods'] * $rate;
        $grand_total += $amount;
        $n            = $i + 1;
        $tp->setValue('claim_date#' . $n, $r['date']);
        $tp->setValue('start_time#' . $n, $r['start_time']);
        $tp->setValue('end_time#' . $n,   $r['end_time']);
        $tp->setValue('periods#' . $n,    $r['periods']);
        $tp->setValue('result#' . $n,     number_format($amount, 2));
    }
    $tp->setValue('grand_total', number_format($grand_total, 2));

    $out = tempnam(sys_get_temp_dir(), 'claim_') . '.docx';
    $tp->saveAs($out);
    $temp_files[] = $out;

    $ln    = preg_replace('/[^A-Za-z0-9_\-]/', '_', $c['last_name']);
    $fn    = preg_replace('/[^A-Za-z0-9_\-]/', '_', $c['first_name']);
    $crs   = preg_replace('/[^A-Za-z0-9_\-]/', '_', $c['course']);
    $entry = $ln . '_' . $fn . '-' . $crs . '-' . $claim_id . '.docx';
    $zip->addFile($out, $entry);
    $count++;
}
mysqli_stmt_close($rows_stmt);
$zip->close();

if ($count === 0) {
    foreach ($temp_files as $f) @unlink($f);
    @unlink($zip_path);
    http_response_code(404);
    exit('No claim forms could be generated.');
}

log_audit($conn, 'claim.batch_download', 'claim', null, $count . ' claim form(s)');

$download_name = 'claim_forms_' . date('Y-m-d') . '.zip';
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $download_name . '"');
header('Content-Length: ' . filesize($zip_path));
header('Pragma: public');
ob_clean();
flush();
readfile($zip_path);

// Cleanup temp artefacts.
foreach ($temp_files as $f) @unlink($f);
@unlink($zip_path);
exit;
