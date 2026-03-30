<?php
require_once __DIR__ . '/includes/db.php';

// Public endpoint — no auth required (called from registration before login).
// Input is fully parameterised; no SQL injection possible.

$bank_name = isset($_GET['bank_name']) ? trim($_GET['bank_name']) : '';

header('Content-Type: application/json');

if ($bank_name === '') {
    echo json_encode(array());
    exit;
}

$stmt = mysqli_prepare($conn, 'SELECT bank_branch FROM banks_branches WHERE bank_name = ? ORDER BY bank_branch');
if (!$stmt) {
    echo json_encode(array());
    exit;
}

mysqli_stmt_bind_param($stmt, 's', $bank_name);
mysqli_stmt_execute($stmt);
$result   = mysqli_stmt_get_result($stmt);
$branches = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

echo json_encode($branches);
