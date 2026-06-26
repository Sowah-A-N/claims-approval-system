<?php
/*
 * Bulk course import from CSV.
 *
 * Expected header row (case-insensitive, order-independent):
 *   code, name, department   (required)
 *   credit_hours, contact_hours   (optional)
 *
 * `code` is the primary key, so re-importing updates the existing course.
 * `department` is normalised to the matching department.dept_name (trimmed)
 * so courses always line up with the file-claim department dropdown; rows whose
 * department doesn't exist are skipped and reported.
 *
 * Returns JSON: { success, inserted, updated, skipped, skippedRows, message }
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
foreach (array('code', 'name', 'department') as $r) {
    if (!isset($col[$r])) {
        fclose($fh);
        json_response(array('success' => false, 'message' => 'Missing required column: ' . $r . '.'), 400);
    }
}

// Canonical department names (trimmed-key -> exact dept_name).
$deptMap = array();
$dres = mysqli_query($conn, "SELECT dept_name FROM department");
while ($dres && $d = mysqli_fetch_row($dres)) {
    $deptMap[strtolower(trim($d[0]))] = $d[0];
}

$ins = mysqli_prepare($conn,
    'INSERT INTO course (code, name, department, credit_hours, contact_hours, archived)
     VALUES (?, ?, ?, ?, ?, 0)
     ON DUPLICATE KEY UPDATE
        name = VALUES(name), department = VALUES(department),
        credit_hours = VALUES(credit_hours), contact_hours = VALUES(contact_hours), archived = 0');
if (!$ins) {
    fclose($fh);
    json_response(array('success' => false, 'message' => 'Database error.'), 500);
}

$inserted = 0; $updated = 0; $skipped = 0; $skippedRows = array();
$line = 1; $MAX = 5000;

while (($row = fgetcsv($fh, 0, ',', '"', '')) !== false) {
    $line++;
    if ($line - 1 > $MAX) break;
    if (count(array_filter($row, function ($v) { return trim($v) !== ''; })) === 0) continue;

    $code   = validated_str(isset($row[$col['code']]) ? $row[$col['code']] : '', 10);
    $name   = validated_str(isset($row[$col['name']]) ? $row[$col['name']] : '', 255);
    $deptIn = validated_str(isset($row[$col['department']]) ? $row[$col['department']] : '', 75);
    $credit  = (isset($col['credit_hours'])  && isset($row[$col['credit_hours']])  && trim($row[$col['credit_hours']])  !== '') ? (int) $row[$col['credit_hours']]  : null;
    $contact = (isset($col['contact_hours']) && isset($row[$col['contact_hours']]) && trim($row[$col['contact_hours']]) !== '') ? (int) $row[$col['contact_hours']] : null;

    if ($code === '' || $name === '' || $deptIn === '') {
        $skipped++; $skippedRows[] = array('row' => $line, 'code' => $code, 'reason' => 'missing code, name or department');
        continue;
    }
    $key = strtolower(trim($deptIn));
    if (!isset($deptMap[$key])) {
        $skipped++; $skippedRows[] = array('row' => $line, 'code' => $code, 'reason' => 'unknown department "' . $deptIn . '"');
        continue;
    }
    $dept = $deptMap[$key]; // canonical name

    mysqli_stmt_bind_param($ins, 'sssii', $code, $name, $dept, $credit, $contact);
    if (!mysqli_stmt_execute($ins)) {
        $skipped++; $skippedRows[] = array('row' => $line, 'code' => $code, 'reason' => 'database error');
        continue;
    }
    // affected_rows: 1 = insert, 2 = update (ON DUPLICATE), 0 = no change
    $aff = mysqli_stmt_affected_rows($ins);
    if ($aff === 1) $inserted++; else $updated++;
}
mysqli_stmt_close($ins);
fclose($fh);

if ($inserted + $updated > 0) {
    log_audit($conn, 'course.import', 'course', null, $inserted . ' new, ' . $updated . ' updated');
}

json_response(array(
    'success'     => ($inserted + $updated) > 0,
    'inserted'    => $inserted,
    'updated'     => $updated,
    'skipped'     => $skipped,
    'skippedRows' => array_slice($skippedRows, 0, 50),
    'message'     => $inserted . ' added, ' . $updated . ' updated, ' . $skipped . ' skipped.',
));
