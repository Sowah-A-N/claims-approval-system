<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../queries/claim.queries.php';

require_post();
require_role(['user', 'claimant']);

$userId    = current_user_id();
$faculty   = (string) ($_SESSION['faculty'] ?? '');

$department = validated_str($_POST['department'] ?? '');
$programme  = validated_str($_POST['programme']  ?? '');
$course     = validated_str($_POST['course']     ?? '');
$rate       = (float) ($_POST['rate']            ?? 0);
$timeSlots  = $_POST['timeSlots'] ?? [];

if ($department === '' || $programme === '' || $course === '' || empty($timeSlots)) {
    json_response(['status' => 'error', 'message' => 'Missing required fields.'], 400);
}

$conn->begin_transaction();

try {
    $claimId = db_insert_claim($conn, $userId, $faculty, $department, $programme, $course, $rate);
    db_insert_initial_stage($conn, $claimId);

    $totalSlots = 0;
    $totalDates = 0;

    foreach ($timeSlots as $slot) {
        $startTime    = validated_str($slot['startTime']     ?? '');
        $endTime      = validated_str($slot['endTime']       ?? '');
        $periods      = (int)   ($slot['periods']      ?? 0);
        $subTotal     = (float) ($slot['subTotal']     ?? 0.0);
        $fuelComponent = (int)  ($slot['fuelComponent'] ?? 0);
        $dates        = $slot['dates'] ?? [];

        if ($startTime === '' || $endTime === '' || $periods === 0 || empty($dates)) {
            throw new InvalidArgumentException('Incomplete time slot data.');
        }

        foreach ($dates as $rawDate) {
            $date = validated_str($rawDate);
            db_insert_claim_data_row(
                $conn, $claimId, $date, $startTime, $endTime,
                $periods, $subTotal, $fuelComponent
            );
            $totalDates++;
        }
        $totalSlots++;
    }

    $conn->commit();
    json_response([
        'status'  => 'success',
        'message' => "{$totalSlots} slot(s) and {$totalDates} date(s) submitted.",
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log('[multiClaimsSubmit] failed: ' . $e->getMessage());
    json_response(['status' => 'error', 'message' => $e->getMessage()], 500);
}
