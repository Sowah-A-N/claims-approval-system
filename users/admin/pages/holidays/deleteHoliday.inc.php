<?php
/*
 * Delete a public holiday (admin Holiday Calendar).
 * POST: id, csrf_token. Returns JSON: { success, message }
 */
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';

require_post();
require_role(array('admin', 'Admin'));
csrf_verify();

$id = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : 0;
if ($id <= 0) {
    json_response(array('success' => false, 'message' => 'Holiday not found.'), 404);
}

// Capture the label for the audit trail before deleting.
$label = '';
$sel = mysqli_prepare($conn, 'SELECT holiday_date, description FROM holidays WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($sel, 'i', $id);
mysqli_stmt_execute($sel);
if ($r = mysqli_fetch_row(mysqli_stmt_get_result($sel))) {
    $label = $r[0] . ' — ' . $r[1];
}
mysqli_stmt_close($sel);

$stmt = mysqli_prepare($conn, 'DELETE FROM holidays WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $id);
$ok = mysqli_stmt_execute($stmt);
$affected = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

if ($ok && $affected > 0) {
    log_audit($conn, 'holiday.delete', 'holiday', null, $label);
    json_response(array('success' => true, 'message' => 'Holiday removed.'));
}
json_response(array('success' => false, 'message' => 'Holiday not found or already removed.'), 404);
