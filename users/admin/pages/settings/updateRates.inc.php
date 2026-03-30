<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';

require_post();
require_role(array('admin', 'Admin'));
csrf_verify();

$stmt = mysqli_prepare($conn, 'UPDATE lecturer_rank_rate SET rate = ? WHERE rankId = ?');
if (!$stmt) {
    error_log('[updateRates] prepare failed: ' . mysqli_error($conn));
    header('Location: ./');
    exit;
}

foreach ($_POST as $key => $value) {
    if (strpos($key, 'rate_') === 0) {
        $rank_id = (int) str_replace('rate_', '', $key);
        $rate    = (int) $value;
        if ($rank_id <= 0 || $rate < 0) continue;
        mysqli_stmt_bind_param($stmt, 'ii', $rate, $rank_id);
        if (!mysqli_stmt_execute($stmt)) {
            error_log('[updateRates] failed for rankId ' . $rank_id . ': ' . mysqli_error($conn));
        }
    }
}

mysqli_stmt_close($stmt);
header('Location: ./');
exit;
