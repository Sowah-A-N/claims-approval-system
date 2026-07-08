<?php
require_once __DIR__ . '/../../../../vendor/autoload.php';
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../queries/claim.queries.php';

use PhpOffice\PhpWord\TemplateProcessor;

require_post();
require_role(array('user', 'claimant'));

$claim_id = validated_int(isset($_POST['claimId']) ? $_POST['claimId'] : null, 'claimId');
$user_id  = current_user_id();

// Ownership enforced in query — returns empty array if claim belongs to another user.
$claim_data_list = db_get_claim_download_data($conn, $claim_id, $user_id);

if (empty($claim_data_list)) {
    echo 'Claim not found or you do not have permission to download it.';
    exit;
}

$template_path = __DIR__ . '/../../../../uploads/claim_form_template.docx';
if (!file_exists($template_path)) {
    echo 'Claim template file not found. Please contact the administrator.';
    exit;
}

$tp        = new TemplateProcessor($template_path);
$first_row = $claim_data_list[0];

$tp->setValue('first_name',      $first_row['first_name']);
$tp->setValue('last_name',       $first_row['last_name']);
$tp->setValue('other_names',     $first_row['other_names']);
$tp->setValue('phone_number',    $first_row['phone_number']);
$tp->setValue('user_department', $first_row['user_department']);
$tp->setValue('rank',            $first_row['rank']);
$tp->setValue('rate',            $first_row['rate']);
$tp->setValue('programme',       $first_row['programme']);
$tp->setValue('course',          $first_row['course']);
$tp->setValue('class',           isset($first_row['class']) ? $first_row['class'] : '');
$tp->setValue('bank_name',       $first_row['bank_name']);
$tp->setValue('bank_branch',     $first_row['bank_branch']);
$tp->setValue('account_number',  $first_row['account_number']);
$tp->setValue('account_name',    $first_row['account_name']);

$grand_total = 0;
$tp->cloneBlock('claim_data_block', 0, true, false, $claim_data_list);

foreach ($claim_data_list as $i => $row) {
    $result       = (float) $row['periods'] * (float) $first_row['rate'];
    $grand_total += $result;
    $n            = $i + 1;

    $tp->setValue('claim_date#' . $n,  $row['claim_date']);
    $tp->setValue('start_time#' . $n,  $row['start_time']);
    $tp->setValue('end_time#' . $n,    $row['end_time']);
    $tp->setValue('periods#' . $n,     $row['periods']);
    $tp->setValue('result#' . $n,      number_format($result, 2));
}

$tp->setValue('grand_total', number_format($grand_total, 2));

$output_path = tempnam(sys_get_temp_dir(), 'claim_') . '.docx';
$tp->saveAs($output_path);

// Build a safe filename from DB values — no raw user input in headers.
$last_name  = preg_replace('/[^A-Za-z0-9_\-]/', '_', $first_row['last_name']);
$first_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $first_row['first_name']);
$course     = preg_replace('/[^A-Za-z0-9_\-]/', '_', $first_row['course']);
$filename   = $last_name . '_' . $first_name . '-' . $course . '.docx';

header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($output_path));
ob_clean();
flush();
readfile($output_path);
unlink($output_path);
exit;
