<?php
/* Download a CSV template for the HR employee bulk import (#1). */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role(array('hr', 'HR', 'admin', 'Admin'));

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="hr_employees_template.csv"');
header('Pragma: no-cache');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
fputcsv($out, array('first_name', 'last_name', 'other_names', 'email', 'phone_number', 'gender', 'department', 'rank', 'staff_id'), ',', '"', '');
fputcsv($out, array('Ama', 'Mensah', '', 'ama.mensah@rmu.edu.gh', '0240000000', 'Female', 'ICT', 'Lecturer', 'RMU-0001'), ',', '"', '');
fputcsv($out, array('Kofi', 'Boateng', 'Kwame', 'kofi.boateng@rmu.edu.gh', '0270000000', 'Male', 'Nautical Science', 'Senior Lecturer', 'RMU-0002'), ',', '"', '');
fclose($out);
exit;
