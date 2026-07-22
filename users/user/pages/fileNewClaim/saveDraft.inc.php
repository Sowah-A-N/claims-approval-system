<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../queries/claim.queries.php';

require_post();
require_role(['user', 'claimant']);
csrf_verify();

$userId      = current_user_id();
$claimTempId = (int)($_POST['claimTempId'] ?? 0);
$department  = validated_str($_POST['department'] ?? '');
$programme   = validated_str($_POST['programme']  ?? '');
$course      = validated_str($_POST['course']     ?? '');
$class       = normalize_class_list($_POST['class'] ?? '');  // one or more codes (#5)
$timeSlots   = isset($_POST['timeSlots']) && is_array($_POST['timeSlots']) ? $_POST['timeSlots'] : [];

if (!$department || !$programme || !$course) {
    json_response(['error' => 'Department, programme, and course are required.'], 400);
}

// Reject drafts with an unreasonable number of dates.
$total_draft_dates = 0;
foreach ($timeSlots as $slot) {
    if (isset($slot['dates']) && is_array($slot['dates'])) {
        $total_draft_dates += count($slot['dates']);
    }
}
if ($total_draft_dates > 365) {
    json_response(['error' => 'Too many dates in a single claim (maximum 365).'], 400);
}

mysqli_begin_transaction($conn);
$ok = true;

if ($claimTempId > 0) {
    // Verify ownership before updating
    $chk = mysqli_prepare($conn, 'SELECT claimTempId FROM saved_claims WHERE claimTempId = ? AND userId = ?');
    mysqli_stmt_bind_param($chk, 'ii', $claimTempId, $userId);
    mysqli_stmt_execute($chk);
    $owned = mysqli_num_rows(mysqli_stmt_get_result($chk)) > 0;
    mysqli_stmt_close($chk);

    if (!$owned) {
        mysqli_rollback($conn);
        json_response(['error' => 'Claim not found or permission denied.'], 403);
    }

    $stmt = mysqli_prepare($conn,
        'UPDATE saved_claims SET department=?, programme=?, course=?, class=?, date_saved=NOW() WHERE claimTempId=? AND userId=?');
    mysqli_stmt_bind_param($stmt, 'ssssii', $department, $programme, $course, $class, $claimTempId, $userId);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($ok) {
        $del = mysqli_prepare($conn, 'DELETE FROM claim_data WHERE claimId = ?');
        mysqli_stmt_bind_param($del, 'i', $claimTempId);
        $ok = mysqli_stmt_execute($del);
        mysqli_stmt_close($del);
    }
} else {
    $stmt = mysqli_prepare($conn,
        'INSERT INTO saved_claims (userId, department, programme, course, class) VALUES (?,?,?,?,?)');
    mysqli_stmt_bind_param($stmt, 'issss', $userId, $department, $programme, $course, $class);
    $ok = mysqli_stmt_execute($stmt);
    if ($ok) $claimTempId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
}

// Claims may only be filed for previous months. Enforce the same cut-off the
// submit step uses so a draft can never contain a date it will later refuse to
// submit (which previously left drafts stuck). Out-of-range dates are skipped
// and reported rather than silently saved.
$max_claim_date = date('Y-m-d', strtotime('-1 day', strtotime(date('Y-m-01'))));
$skipped_future = 0;

if ($ok) {
    foreach ($timeSlots as $slot) {
        $start  = validated_str($slot['startTime']     ?? '');
        $end    = validated_str($slot['endTime']       ?? '');
        $periods = (int)($slot['periods']      ?? 0);
        $sub    = (float)($slot['subTotal']    ?? 0);
        $fuel   = (int)($slot['fuelComponent'] ?? 0);
        $dates  = isset($slot['dates']) && is_array($slot['dates']) ? $slot['dates'] : [];

        if (!DateTime::createFromFormat('H:i', $start) || !DateTime::createFromFormat('H:i', $end)) {
            continue; // skip slots with invalid time format
        }

        foreach ($dates as $raw) {
            $date = validated_str($raw);
            if (!$date) continue;
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date > $max_claim_date) {
                $skipped_future++;   // current/future month — not claimable
                continue;
            }
            $ok = db_insert_claim_data_row($conn, $claimTempId, $date, $start, $end, $periods, $sub, $fuel);
            if (!$ok) break 2;
        }
    }
}

if ($ok) {
    mysqli_commit($conn);
    foreach (class_list_to_array($class) as $one_class) {  // remember each code (#5)
        db_upsert_class($conn, $one_class);
    }
    $msg = 'Draft saved successfully.';
    if ($skipped_future > 0) {
        $msg .= ' ' . $skipped_future . ' date(s) in the current/future month were not saved '
              . '(claims are for previous months only; latest allowed '
              . date('d/m/Y', strtotime($max_claim_date)) . ').';
    }
    json_response(['claimTempId' => $claimTempId, 'message' => $msg, 'skippedFuture' => $skipped_future]);
} else {
    mysqli_rollback($conn);
    error_log('[saveDraft] failed: ' . mysqli_error($conn));
    json_response(['error' => 'Failed to save draft. Please try again.'], 500);
}
