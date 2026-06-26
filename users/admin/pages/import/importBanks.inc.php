<?php
/*
 * Bulk bank/branch import from CSV (#18).
 *
 * Expected header row (case-insensitive, order-independent):
 *   bank_name, bank_branch, branch_code
 *
 * branch_code is the unique key, so re-importing is safe (duplicates skipped).
 * Returns JSON: { success, inserted, skipped, message }
 */
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';

require_post();
require_role(array('admin', 'Admin'));
csrf_verify();

if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
    json_response(array('success' => false, 'message' => 'No CSV file uploaded or upload failed.'), 400);
}
if ($_FILES['csv']['size'] > 2 * 1024 * 1024) {
    json_response(array('success' => false, 'message' => 'File too large (max 2 MB).'), 400);
}

$fh = fopen($_FILES['csv']['tmp_name'], 'r');
if (!$fh) {
    json_response(array('success' => false, 'message' => 'Could not read the uploaded file.'), 500);
}

$header = fgetcsv($fh, 0, ',', '"', '');
if (!$header) {
    fclose($fh);
    json_response(array('success' => false, 'message' => 'The CSV is empty.'), 400);
}
$col = array();
foreach ($header as $i => $name) {
    $col[strtolower(trim($name))] = $i;
}
foreach (array('bank_name', 'bank_branch', 'branch_code') as $r) {
    if (!isset($col[$r])) {
        fclose($fh);
        json_response(array('success' => false, 'message' => 'Missing required column: ' . $r . '.'), 400);
    }
}

$ins = mysqli_prepare($conn,
    'INSERT IGNORE INTO banks_branches (branch_code, bank_name, bank_branch) VALUES (?, ?, ?)');
if (!$ins) {
    fclose($fh);
    json_response(array('success' => false, 'message' => 'Database error.'), 500);
}

$inserted = 0;
$skipped  = 0;
$line     = 1;
$MAX      = 5000;

while (($row = fgetcsv($fh, 0, ',', '"', '')) !== false) {
    $line++;
    if ($line - 1 > $MAX) break;
    if (count(array_filter($row, function ($v) { return trim($v) !== ''; })) === 0) continue;

    $code   = isset($row[$col['branch_code']])  ? filter_var(trim($row[$col['branch_code']]), FILTER_VALIDATE_INT) : false;
    $name   = validated_str(isset($row[$col['bank_name']])   ? $row[$col['bank_name']]   : '', 75);
    $branch = validated_str(isset($row[$col['bank_branch']]) ? $row[$col['bank_branch']] : '', 50);

    if ($code === false || $name === '' || $branch === '') {
        $skipped++;
        continue;
    }

    mysqli_stmt_bind_param($ins, 'iss', $code, $name, $branch);
    mysqli_stmt_execute($ins);
    if (mysqli_stmt_affected_rows($ins) > 0) $inserted++;
    else $skipped++; // duplicate branch_code or no-op
}
mysqli_stmt_close($ins);
fclose($fh);

if ($inserted > 0) {
    log_audit($conn, 'bank.import', 'bank', null, $inserted . ' branch(es) imported');
}

json_response(array(
    'success'  => $inserted > 0,
    'inserted' => $inserted,
    'skipped'  => $skipped,
    'message'  => $inserted . ' branch(es) imported, ' . $skipped . ' skipped.',
));
