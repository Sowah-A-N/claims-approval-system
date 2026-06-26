<?php
/*
 * Stream a blank CSV template (header row + one sample row) for a bulk import,
 * so admins always upload the correct columns.
 *
 *   downloadTemplate.inc.php?type=users|banks|courses
 */
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';

require_role(array('admin', 'Admin'));

$type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : '';

$templates = array(
    'users' => array(
        'header' => array('first_name', 'last_name', 'other_names', 'phone_number', 'gender', 'email', 'faculty', 'department', 'rank'),
        'sample' => array('John', 'Doe', '', '0244000000', 'Male', 'john.doe@example.com', 'Engineering', 'ICT', 'Lecturer'),
    ),
    'banks' => array(
        'header' => array('bank_name', 'bank_branch', 'branch_code'),
        'sample' => array('GCB BANK LTD', 'ABEKA LAPAZ BRANCH', '1001'),
    ),
    'courses' => array(
        'header' => array('code', 'name', 'department', 'credit_hours', 'contact_hours'),
        'sample' => array('CSE101', 'Introduction to Computer Science', 'ICT', '3', '4'),
    ),
);

if (!isset($templates[$type])) {
    http_response_code(400);
    exit('Unknown template type.');
}

$filename = $type . '_import_template.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
fputcsv($out, $templates[$type]['header'], ',', '"', '');
fputcsv($out, $templates[$type]['sample'], ',', '"', '');
fclose($out);
exit;
