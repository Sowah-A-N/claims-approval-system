<?php
/*
 * Create or update a public holiday (admin Holiday Calendar).
 *
 * POST: mode (create|edit), id (edit only), holiday_date (YYYY-MM-DD),
 *       description, csrf_token
 * holiday_date is UNIQUE. Returns JSON: { success, message }
 */
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';

require_post();
require_role(array('admin', 'Admin'));
csrf_verify();

$mode = validated_str(isset($_POST['mode']) ? $_POST['mode'] : 'create', 10);
$id   = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : 0;
$date = validated_str(isset($_POST['holiday_date']) ? $_POST['holiday_date'] : '', 10);
$desc = validated_str(isset($_POST['description']) ? $_POST['description'] : '', 100);

// Validate the date is a real YYYY-MM-DD.
$d = DateTime::createFromFormat('Y-m-d', $date);
if (!$d || $d->format('Y-m-d') !== $date) {
    json_response(array('success' => false, 'message' => 'Please provide a valid date.'), 400);
}
if ($desc === '') {
    json_response(array('success' => false, 'message' => 'Description is required.'), 400);
}

// Reject a date that already belongs to a different holiday.
$chk = mysqli_prepare($conn, 'SELECT id FROM holidays WHERE holiday_date = ? LIMIT 1');
mysqli_stmt_bind_param($chk, 's', $date);
mysqli_stmt_execute($chk);
$dupRow = mysqli_fetch_row(mysqli_stmt_get_result($chk));
mysqli_stmt_close($chk);
$dupId = $dupRow ? (int) $dupRow[0] : 0;

if ($mode === 'edit') {
    if ($id <= 0) {
        json_response(array('success' => false, 'message' => 'Holiday not found.'), 404);
    }
    if ($dupId && $dupId !== $id) {
        json_response(array('success' => false, 'message' => 'Another holiday already falls on ' . $date . '.'), 409);
    }
    $stmt = mysqli_prepare($conn, 'UPDATE holidays SET holiday_date = ?, description = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'ssi', $date, $desc, $id);
} else {
    if ($dupId) {
        json_response(array('success' => false, 'message' => 'A holiday already falls on ' . $date . '.'), 409);
    }
    $stmt = mysqli_prepare($conn, 'INSERT INTO holidays (holiday_date, description) VALUES (?, ?)');
    mysqli_stmt_bind_param($stmt, 'ss', $date, $desc);
}

if (!$stmt) {
    json_response(array('success' => false, 'message' => 'Database error.'), 500);
}
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($ok) {
    log_audit($conn, $mode === 'edit' ? 'holiday.update' : 'holiday.create', 'holiday', null, $date . ' — ' . $desc);
    json_response(array('success' => true, 'message' => 'Holiday saved.'));
}
json_response(array('success' => false, 'message' => 'Could not save the holiday. Please try again.'), 500);
