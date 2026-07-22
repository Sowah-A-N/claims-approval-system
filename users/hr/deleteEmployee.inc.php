<?php
/* Remove a single HR employee from the register (#1). JSON endpoint. */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/hr.queries.php';

require_post();
require_role(array('hr', 'HR'));
csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    json_response(array('success' => false, 'message' => 'Invalid employee id.'), 422);
}

if (!db_hr_delete($conn, $id)) {
    json_response(array('success' => false, 'message' => 'Could not remove the employee. Please try again.'), 500);
}

log_audit($conn, 'hr.employee.delete', 'hr_employee', $id);
json_response(array('success' => true, 'message' => 'Employee removed from the register.'));
