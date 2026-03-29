<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../queries/claim.queries.php';

require_post();
require_role(['user', 'claimant']);

$userId     = current_user_id();
$rate       = (float) ($_SESSION['rate']    ?? 0);
$faculty    = (string) ($_SESSION['faculty'] ?? '');

$department = validated_str($_POST['department'] ?? '');
$programme  = validated_str($_POST['programme']  ?? '');
$course     = validated_str($_POST['course']     ?? '');

if ($department === '' || $programme === '' || $course === '') {
    json_response(['error' => 'Department, programme, and course are required.'], 400);
}

$conn->begin_transaction();

try {
    $claimId = db_insert_claim($conn, $userId, $faculty, $department, $programme, $course, $rate);
    db_insert_initial_stage($conn, $claimId);

    if (isset($_POST['date']) && is_array($_POST['date'])) {
        $dates      = $_POST['date'];
        $startTimes = $_POST['startTime']     ?? [];
        $endTimes   = $_POST['endTime']       ?? [];
        $periods    = $_POST['period']        ?? [];
        $subTotals  = $_POST['subTotal']      ?? [];
        $fuels      = $_POST['fuelComponent'] ?? [];

        foreach ($dates as $i => $rawDate) {
            $date         = validated_str($rawDate);
            $startTime    = validated_str($startTimes[$i] ?? '');
            $endTime      = validated_str($endTimes[$i]   ?? '');
            $periodVal    = (int) ($periods[$i]   ?? 0);
            $subTotalVal  = (float) ($subTotals[$i] ?? 0.0);
            $fuelVal      = isset($fuels[$i]) ? 1 : 0;

            db_insert_claim_data_row(
                $conn, $claimId, $date, $startTime, $endTime,
                $periodVal, $subTotalVal, $fuelVal
            );
        }
    }

    $conn->commit();
    json_response(['success' => 'Claim submitted successfully.']);

} catch (Exception $e) {
    $conn->rollback();
    error_log('[fileNewClaim] submit failed: ' . $e->getMessage());
    json_response(['error' => 'Failed to submit claim. Please try again.'], 500);
}
