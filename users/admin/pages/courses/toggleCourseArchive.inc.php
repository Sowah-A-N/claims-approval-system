<?php
/*
 * Archive / unarchive a course (soft enable/disable).
 * Archived courses no longer appear in the claim form's course dropdown.
 *
 * POST: code, archived (0|1), csrf_token
 * Returns JSON: { success, message }
 */
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';

require_post();
require_role(array('admin', 'Admin'));
csrf_verify();

$code     = validated_str(isset($_POST['code']) ? $_POST['code'] : '', 10);
$archived = (isset($_POST['archived']) && (int) $_POST['archived'] === 1) ? 1 : 0;

if ($code === '') {
    json_response(array('success' => false, 'message' => 'Missing course code.'), 400);
}

$stmt = mysqli_prepare($conn, 'UPDATE course SET archived = ? WHERE code = ?');
if (!$stmt) {
    json_response(array('success' => false, 'message' => 'Database error.'), 500);
}
mysqli_stmt_bind_param($stmt, 'is', $archived, $code);
mysqli_stmt_execute($stmt);
$affected = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

if ($affected >= 0) {
    log_audit($conn, $archived ? 'course.archive' : 'course.unarchive', 'course', null, $code);
    json_response(array('success' => true, 'message' => $archived ? 'Course archived.' : 'Course restored.'));
} else {
    json_response(array('success' => false, 'message' => 'Course not found.'), 404);
}
