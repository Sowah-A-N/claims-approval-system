<?php
require_once __DIR__ . '/../../../../includes/db.php';

header('Content-Type: application/json');

if (!isset($_GET['bank_name'])) {
    echo json_encode([]);
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT bank_branch FROM banks_branches WHERE bank_name = ?");
mysqli_stmt_bind_param($stmt, 's', $_GET['bank_name']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$branches = [];
while ($row = mysqli_fetch_assoc($result)) {
    $branches[] = $row['bank_branch'];
}
mysqli_stmt_close($stmt);

echo json_encode($branches);
