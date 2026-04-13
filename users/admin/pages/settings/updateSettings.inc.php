<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';

require_post();
require_role(array('admin', 'Admin'));
csrf_verify();

$fuelComponentValue = isset($_POST['fuelComponent']) ? 1 : 0;

$stmt = mysqli_prepare($conn, "UPDATE settings SET settingValue = ? WHERE settingName = 'fuelComponent'");
if (!$stmt) {
    error_log('[updateSettings] prepare failed: ' . mysqli_error($conn));
    header('Location: ./');
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $fuelComponentValue);

if (!mysqli_stmt_execute($stmt)) {
    error_log('[updateSettings] execute failed: ' . mysqli_error($conn));
}

mysqli_stmt_close($stmt);
header('Location: ./');
exit;
