<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../queries/claim.queries.php';

require_post();
require_role(array('user', 'claimant'));
csrf_verify();

$user_id      = current_user_id();
$faculty      = isset($_SESSION['faculty']) ? (string) $_SESSION['faculty'] : '';
$claim_temp_id = isset($_POST['claimTempId']) ? (int)$_POST['claimTempId'] : 0;

$department = validated_str(isset($_POST['department']) ? $_POST['department'] : '');
$programme  = validated_str(isset($_POST['programme'])  ? $_POST['programme']  : '');
$course     = validated_str(isset($_POST['course'])     ? $_POST['course']     : '');
$rate       = (float) (isset($_POST['rate'])            ? $_POST['rate']       : 0);
$time_slots = isset($_POST['timeSlots']) && is_array($_POST['timeSlots']) ? $_POST['timeSlots'] : array();

if ($department === '' || $programme === '' || $course === '' || empty($time_slots)) {
    json_response(array('status' => 'error', 'message' => 'Missing required fields.'), 400);
}

// Reject submissions with an unreasonable number of dates.
$total_dates_submitted = 0;
foreach ($time_slots as $slot) {
    if (isset($slot['dates']) && is_array($slot['dates'])) {
        $total_dates_submitted += count($slot['dates']);
    }
}
if ($total_dates_submitted > 365) {
    json_response(array('status' => 'error', 'message' => 'Too many dates in a single claim (maximum 365).'), 400);
}

mysqli_begin_transaction($conn);
$ok = true;

$claim_id = db_insert_claim($conn, $user_id, $faculty, $department, $programme, $course, $rate);
if (!$claim_id) {
    $ok = false;
}

if ($ok) {
    $ok = db_insert_initial_stage($conn, $claim_id);
}

$total_slots = 0;
$total_dates = 0;

if ($ok) {
    foreach ($time_slots as $slot) {
        $start_time     = validated_str(isset($slot['startTime'])     ? $slot['startTime']     : '');
        $end_time       = validated_str(isset($slot['endTime'])       ? $slot['endTime']       : '');
        $periods        = (int)   (isset($slot['periods'])      ? $slot['periods']      : 0);
        $sub_total      = (float) (isset($slot['subTotal'])     ? $slot['subTotal']     : 0.0);
        $fuel_component = (int)   (isset($slot['fuelComponent']) ? $slot['fuelComponent'] : 0);
        $dates          = isset($slot['dates']) && is_array($slot['dates']) ? $slot['dates'] : array();

        $valid_start = DateTime::createFromFormat('H:i', $start_time);
        $valid_end   = DateTime::createFromFormat('H:i', $end_time);

        if ($start_time === '' || $end_time === '' || !$valid_start || !$valid_end || $periods === 0 || empty($dates)) {
            $ok = false;
            break;
        }

        foreach ($dates as $raw_date) {
            $date = validated_str($raw_date);
            $ok   = db_insert_claim_data_row($conn, $claim_id, $date, $start_time, $end_time, $periods, $sub_total, $fuel_component);
            if (!$ok) break 2;
            $total_dates++;
        }
        $total_slots++;
    }
}

// If this was submitted from a saved draft, delete the draft inside the same transaction.
if ($ok && $claim_temp_id > 0) {
    $del_data = mysqli_prepare($conn, 'DELETE FROM claim_data WHERE claimId = ?');
    if ($del_data) {
        mysqli_stmt_bind_param($del_data, 'i', $claim_temp_id);
        mysqli_stmt_execute($del_data);
        mysqli_stmt_close($del_data);
    }
    $del_saved = mysqli_prepare($conn, 'DELETE FROM saved_claims WHERE claimTempId = ? AND userId = ?');
    if ($del_saved) {
        mysqli_stmt_bind_param($del_saved, 'ii', $claim_temp_id, $user_id);
        mysqli_stmt_execute($del_saved);
        mysqli_stmt_close($del_saved);
    }
}

if ($ok) {
    mysqli_commit($conn);
    json_response(array('status' => 'success', 'message' => $total_slots . ' slot(s) and ' . $total_dates . ' date(s) submitted.'));
} else {
    mysqli_rollback($conn);
    error_log('[multiClaimsSubmit] failed: ' . mysqli_error($conn));
    json_response(array('status' => 'error', 'message' => 'Submission failed. Please try again.'), 500);
}
