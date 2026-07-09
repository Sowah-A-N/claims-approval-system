<?php
/* Add or update a single HR employee (#1). JSON endpoint. */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/hr.queries.php';

require_post();
require_role(array('hr', 'HR', 'admin', 'Admin'));
csrf_verify();

$data = array(
    'first_name'   => validated_str($_POST['first_name']   ?? ''),
    'last_name'    => validated_str($_POST['last_name']    ?? ''),
    'other_names'  => validated_str($_POST['other_names']  ?? ''),
    'email'        => validated_str($_POST['email']        ?? ''),
    'phone_number' => validated_str($_POST['phone_number'] ?? ''),
    'gender'       => validated_str($_POST['gender']       ?? ''),
    'department'   => validated_str($_POST['department']   ?? ''),
    'rank'         => validated_str($_POST['rank']         ?? ''),
    'staff_id'     => validated_str($_POST['staff_id']     ?? ''),
);

if ($data['first_name'] === '' || $data['last_name'] === '' ||
    !filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL)) {
    json_response(array('success' => false,
        'message' => 'First name, last name, and a valid email are required.'), 422);
}

$outcome = db_hr_upsert($conn, $data, current_user_id());
if ($outcome === false) {
    json_response(array('success' => false, 'message' => 'Could not save the employee. Please try again.'), 500);
}

log_audit($conn, 'hr.employee.' . $outcome, 'hr_employee', null, hr_normalize_email($data['email']));
json_response(array('success' => true,
    'message' => $outcome === 'updated' ? 'Employee updated.' : 'Employee added to the register.'));
