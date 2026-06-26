<?php
/*
 * Create or update a course (admin Course manager).
 *
 * POST: mode (create|edit), code, name, department, credit_hours, contact_hours, csrf_token
 * `code` is the primary key. department must match an existing department name
 * (normalised to the canonical dept_name so it lines up with the claim form).
 * Returns JSON: { success, message }
 */
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';

require_post();
require_role(array('admin', 'Admin'));
csrf_verify();

$mode    = validated_str(isset($_POST['mode']) ? $_POST['mode'] : 'create', 10);
$code    = validated_str(isset($_POST['code']) ? $_POST['code'] : '', 10);
$name    = validated_str(isset($_POST['name']) ? $_POST['name'] : '', 255);
$deptIn  = validated_str(isset($_POST['department']) ? $_POST['department'] : '', 75);
$credit  = (isset($_POST['credit_hours'])  && trim($_POST['credit_hours'])  !== '') ? (int) $_POST['credit_hours']  : null;
$contact = (isset($_POST['contact_hours']) && trim($_POST['contact_hours']) !== '') ? (int) $_POST['contact_hours'] : null;

if ($code === '' || $name === '' || $deptIn === '') {
    json_response(array('success' => false, 'message' => 'Code, name and department are required.'), 400);
}

// Normalise the department to the canonical dept_name (trimmed match).
$dept = null;
$dq = mysqli_prepare($conn, "SELECT dept_name FROM department WHERE TRIM(dept_name) = TRIM(?) LIMIT 1");
mysqli_stmt_bind_param($dq, 's', $deptIn);
mysqli_stmt_execute($dq);
$drow = mysqli_fetch_row(mysqli_stmt_get_result($dq));
mysqli_stmt_close($dq);
if (!$drow) {
    json_response(array('success' => false, 'message' => 'Unknown department: ' . $deptIn . '.'), 400);
}
$dept = $drow[0];

// Does this code already exist?
$chk = mysqli_prepare($conn, 'SELECT code FROM course WHERE code = ? LIMIT 1');
mysqli_stmt_bind_param($chk, 's', $code);
mysqli_stmt_execute($chk);
$exists = mysqli_fetch_row(mysqli_stmt_get_result($chk)) !== null;
mysqli_stmt_close($chk);

if ($mode === 'create') {
    if ($exists) {
        json_response(array('success' => false, 'message' => 'A course with code "' . $code . '" already exists.'), 409);
    }
    $stmt = mysqli_prepare($conn,
        'INSERT INTO course (code, name, department, credit_hours, contact_hours, archived)
         VALUES (?, ?, ?, ?, ?, 0)');
    mysqli_stmt_bind_param($stmt, 'sssii', $code, $name, $dept, $credit, $contact);
} else {
    if (!$exists) {
        json_response(array('success' => false, 'message' => 'Course not found.'), 404);
    }
    $stmt = mysqli_prepare($conn,
        'UPDATE course SET name = ?, department = ?, credit_hours = ?, contact_hours = ? WHERE code = ?');
    mysqli_stmt_bind_param($stmt, 'ssiis', $name, $dept, $credit, $contact, $code);
}

if (!$stmt) {
    json_response(array('success' => false, 'message' => 'Database error.'), 500);
}
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($ok) {
    log_audit($conn, $mode === 'create' ? 'course.create' : 'course.update', 'course', null, $code . ' — ' . $name);
    json_response(array('success' => true, 'message' => 'Course saved.'));
} else {
    json_response(array('success' => false, 'message' => 'Could not save the course. Please try again.'), 500);
}
