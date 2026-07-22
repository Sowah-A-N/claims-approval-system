<?php
/* Bulk-import HR employees from an uploaded CSV (#1). JSON endpoint. */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/hr.queries.php';

require_post();
require_role(array('hr', 'HR'));
csrf_verify();

if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
    json_response(array('success' => false, 'message' => 'Please choose a CSV file to upload.'), 422);
}

$tmp  = $_FILES['csv']['tmp_name'];
$name = (string) $_FILES['csv']['name'];
if (!is_uploaded_file($tmp)) {
    json_response(array('success' => false, 'message' => 'Invalid upload.'), 422);
}
$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
if ($ext !== 'csv' && $ext !== 'txt') {
    json_response(array('success' => false, 'message' => 'Please upload a .csv file.'), 422);
}
if ($_FILES['csv']['size'] > 5 * 1024 * 1024) {
    json_response(array('success' => false, 'message' => 'File too large (max 5 MB).'), 422);
}

$res = hr_import_csv($conn, $tmp, current_user_id());

$processed = $res['inserted'] + $res['updated'];
log_audit($conn, 'hr.employee.import', 'hr_employee', null,
    $res['inserted'] . ' added, ' . $res['updated'] . ' updated, ' . $res['skipped'] . ' skipped');

$msg = $res['inserted'] . ' added, ' . $res['updated'] . ' updated'
     . ($res['skipped'] ? ', ' . $res['skipped'] . ' skipped' : '') . '.';

json_response(array(
    'success'  => ($processed > 0 || $res['skipped'] === 0),
    'message'  => $msg,
    'inserted' => $res['inserted'],
    'updated'  => $res['updated'],
    'skipped'  => $res['skipped'],
    'errors'   => $res['errors'],
));
