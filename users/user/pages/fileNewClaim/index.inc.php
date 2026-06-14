<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../queries/claim.queries.php';

require_post();
require_role(array('user', 'claimant'));
csrf_verify();

$user_id    = current_user_id();
$rate       = isset($_SESSION['rate'])    ? (float) $_SESSION['rate']    : 0.0;
$faculty    = isset($_SESSION['faculty']) ? (string) $_SESSION['faculty'] : '';

$department = validated_str(isset($_POST['department']) ? $_POST['department'] : '');
$programme  = validated_str(isset($_POST['programme'])  ? $_POST['programme']  : '');
$course     = validated_str(isset($_POST['course'])     ? $_POST['course']     : '');

if ($department === '' || $programme === '' || $course === '') {
    json_response(array('error' => 'Department, programme, and course are required.'), 400);
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

if ($ok && isset($_POST['date']) && is_array($_POST['date'])) {
    $dates      = $_POST['date'];
    $start_times = isset($_POST['startTime'])     ? $_POST['startTime']     : array();
    $end_times   = isset($_POST['endTime'])        ? $_POST['endTime']       : array();
    $periods_arr = isset($_POST['period'])         ? $_POST['period']        : array();
    $sub_totals  = isset($_POST['subTotal'])       ? $_POST['subTotal']      : array();
    $fuels       = isset($_POST['fuelComponent'])  ? $_POST['fuelComponent'] : array();

    foreach ($dates as $i => $raw_date) {
        $date       = validated_str($raw_date);
        $start_time = validated_str(isset($start_times[$i]) ? $start_times[$i] : '');
        $end_time   = validated_str(isset($end_times[$i])   ? $end_times[$i]   : '');
        $period     = (int)   (isset($periods_arr[$i]) ? $periods_arr[$i] : 0);
        $sub_total  = (float) (isset($sub_totals[$i])  ? $sub_totals[$i]  : 0.0);
        $fuel       = isset($fuels[$i]) ? 1 : 0;

        $ok = db_insert_claim_data_row($conn, $claim_id, $date, $start_time, $end_time, $period, $sub_total, $fuel);
        if (!$ok) break;
    }
}

if ($ok) {
    mysqli_commit($conn);
    json_response(array('success' => 'Claim submitted successfully.'));
} else {
    mysqli_rollback($conn);
    error_log('[fileNewClaim] submit failed: ' . mysqli_error($conn));
    json_response(array('error' => 'Failed to submit claim. Please try again.'), 500);
}
