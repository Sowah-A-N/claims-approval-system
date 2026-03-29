<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../queries/claim.queries.php';

require_post();
require_role(array('user', 'claimant'));

$user_id    = current_user_id();
$faculty    = isset($_SESSION['faculty']) ? (string) $_SESSION['faculty'] : '';

$department = validated_str(isset($_POST['department']) ? $_POST['department'] : '');
$programme  = validated_str(isset($_POST['programme'])  ? $_POST['programme']  : '');
$course     = validated_str(isset($_POST['course'])     ? $_POST['course']     : '');
$rate       = (float) (isset($_POST['rate'])            ? $_POST['rate']       : 0);
$time_slots = isset($_POST['timeSlots']) && is_array($_POST['timeSlots']) ? $_POST['timeSlots'] : array();

if ($department === '' || $programme === '' || $course === '' || empty($time_slots)) {
    json_response(array('status' => 'error', 'message' => 'Missing required fields.'), 400);
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

        if ($start_time === '' || $end_time === '' || $periods === 0 || empty($dates)) {
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

if ($ok) {
    mysqli_commit($conn);
    json_response(array('status' => 'success', 'message' => $total_slots . ' slot(s) and ' . $total_dates . ' date(s) submitted.'));
} else {
    mysqli_rollback($conn);
    error_log('[multiClaimsSubmit] failed: ' . mysqli_error($conn));
    json_response(array('status' => 'error', 'message' => 'Submission failed. Please try again.'), 500);
}
