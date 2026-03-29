<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../vendor/autoload.php';
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../queries/claim.queries.php';

use PhpOffice\PhpWord\TemplateProcessor;

require_post();
require_role(['user', 'claimant']);

$claimId = validated_int($_POST['claimId'] ?? null, 'claimId');
$userId  = current_user_id();

// Ownership enforced in query — empty array if claim belongs to another user.
$claimDataList = db_get_claim_download_data($conn, $claimId, $userId);

if (empty($claimDataList)) {
    echo 'Claim not found or you do not have permission to download it.';
    exit;
}

$templatePath = __DIR__ . '/../../../../uploads/claim_form_template.docx';
if (!file_exists($templatePath)) {
    echo 'Claim template file not found. Please contact the administrator.';
    exit;
}

$templateProcessor = new TemplateProcessor($templatePath);
$firstRow          = $claimDataList[0];

$templateProcessor->setValue('first_name',      $firstRow['first_name']);
$templateProcessor->setValue('last_name',       $firstRow['last_name']);
$templateProcessor->setValue('other_names',     $firstRow['other_names']);
$templateProcessor->setValue('phone_number',    $firstRow['phone_number']);
$templateProcessor->setValue('user_department', $firstRow['user_department']);
$templateProcessor->setValue('rank',            $firstRow['rank']);
$templateProcessor->setValue('rate',            $firstRow['rate']);
$templateProcessor->setValue('programme',       $firstRow['programme']);
$templateProcessor->setValue('course',          $firstRow['course']);
$templateProcessor->setValue('bank_name',       $firstRow['bank_name']);
$templateProcessor->setValue('bank_branch',     $firstRow['bank_branch']);
$templateProcessor->setValue('account_number',  $firstRow['account_number']);
$templateProcessor->setValue('account_name',    $firstRow['account_name']);

$grandTotal = 0;
$templateProcessor->cloneBlock('claim_data_block', 0, true, false, $claimDataList);

foreach ($claimDataList as $index => $claimData) {
    $result      = (float) $claimData['periods'] * (float) $firstRow['rate'];
    $grandTotal += $result;
    $n           = $index + 1;

    $templateProcessor->setValue("claim_date#$n",  $claimData['claim_date']);
    $templateProcessor->setValue("start_time#$n",  $claimData['start_time']);
    $templateProcessor->setValue("end_time#$n",    $claimData['end_time']);
    $templateProcessor->setValue("periods#$n",     $claimData['periods']);
    $templateProcessor->setValue("result#$n",      number_format($result, 2));
}

$templateProcessor->setValue('grand_total', number_format($grandTotal, 2));

$outputPath = tempnam(sys_get_temp_dir(), 'claim_') . '.docx';
$templateProcessor->saveAs($outputPath);

// Build a safe filename from DB values — no user-supplied string in headers.
$lastName  = preg_replace('/[^A-Za-z0-9_\-]/', '_', $firstRow['last_name']);
$firstName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $firstRow['first_name']);
$course    = preg_replace('/[^A-Za-z0-9_\-]/', '_', $firstRow['course']);
$filename  = "{$lastName}_{$firstName}-{$course}.docx";

header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($outputPath));
ob_clean();
flush();
readfile($outputPath);
unlink($outputPath);
exit;
