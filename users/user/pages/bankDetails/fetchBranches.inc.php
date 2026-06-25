<?php
/* Return the distinct branches for a given bank as a JSON array. */
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';

require_role(array('user', 'claimant'));

header('Content-Type: application/json');

$bank = isset($_GET['bank_name']) ? $_GET['bank_name'] : '';
if ($bank === '') { echo json_encode([]); exit; }

$stmt = mysqli_prepare($conn,
    "SELECT DISTINCT bank_branch FROM banks_branches
     WHERE bank_name = ? AND bank_branch IS NOT NULL AND bank_branch <> ''
     ORDER BY bank_branch");
if (!$stmt) { echo json_encode([]); exit; }
mysqli_stmt_bind_param($stmt, 's', $bank);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$out = [];
while ($row = mysqli_fetch_row($res)) { $out[] = $row[0]; }
mysqli_stmt_close($stmt);

echo json_encode($out);
