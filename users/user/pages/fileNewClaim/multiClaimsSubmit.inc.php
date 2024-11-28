<?php
session_start();
include_once '../../includes/conn.inc.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // $rawData = file_get_contents("php://input");
    // $data = json_decode($rawData, true); // Decode the JSON into an associative array

    if (!isset($_POST['department']) || !isset($_POST['programme']) || !isset($_POST['course']) || !isset($_POST['rate']) || !isset($_POST['timeSlots'])) {
        die(json_encode(array('status' => 'error', 'message' => 'Missing required fields')));
    }

    if (empty($_POST['department']) || empty($_POST['programme']) || empty($_POST['course']) || empty($_POST['rate']) || empty($_POST['timeSlots'])) {
        die(json_encode(array('status' => 'error', 'message' => 'Some required fields are empty')));
    }
    // Example inputs from form submission
    $userId = $_SESSION['user_id'] ?? 1;  // Assume this is provided or derived from the session
    $faculty = $_SESSION['faculty'] ?? 'N/A';  // Static or derived elsewhere
    $department = $_POST['department'] ?? null;
    $programme = $_POST['programme'] ?? null;
    $course = $_POST['course'] ?? null;
    $rate = $_POST['rate'] ?? null;
    $timeSlots = $_POST['timeSlots'] ?? [];
    $stage = 1;

    // Validate inputs
    if (!$department || !$programme || !$course || !$rate || empty($timeSlots)) {
        die(json_encode(array('status' => 'error', 'message' => 'Invalid input data!')));
    }

    try {
        // Start inserting into `claim_details`
        $query = "INSERT INTO claim_details (userId, faculty, department, programme, course, rate) 
              VALUES ('$userId', '$faculty', '$department', '$programme', '$course', '$rate')";

        if (!mysqli_query($conn, $query)) {
            throw new Exception('Error inserting into claim_details: ' . mysqli_error($conn));
        }

        // Get the generated claimId
        $claimId = mysqli_insert_id($conn);
        if (!$claimId) {
            throw new Exception('Error retrieving claimId: ' . mysqli_error($conn));
        }

        // Insert into claim_approval_stages
        $stmt = $conn->prepare('INSERT INTO claim_approval_stages (claimId, stage) VALUES (?, ?)');
        $stmt->bind_param('ii', $claimId, $stage);
        $stmt->execute();

        $totaltimeSlot = $AddedDates = 0;

        foreach ($timeSlots as $timeSlot) {
            $startTime = $timeSlot['startTime'] ?? null;
            $endTime = $timeSlot['endTime'] ?? null;
            $periods = $timeSlot['periods'] ?? null;
            $subTotal = $timeSlot['subTotal'] ?? null;
            $fuelComponent = $timeSlot['fuelComponent'] ?? null;
            $dates = $timeSlot['dates'] ?? [];

            // Validate time slot data
            if (!$startTime || !$endTime || !$periods || !$subTotal || !$fuelComponent || empty($dates)) {
                throw new Exception('Invalid time slot data!');
            }

            foreach ($dates as $date) {
                $query = "INSERT INTO claim_data (claimId, date, start_time, end_time, periods, subTotal, fuelComponent) 
                      VALUES ('$claimId', '$date', '$startTime', '$endTime', '$periods', '$subTotal', '$fuelComponent')";

                if (!mysqli_query($conn, $query)) {
                    throw new Exception('Error inserting into claim_data: ' . mysqli_error($conn));
                }
                $AddedDates++;
            }
            $totaltimeSlot++;
        }

        die(json_encode(array('status' => 'success', 'message' => "{$totaltimeSlot} slots and {$AddedDates} dates!")));
    } catch (Exception $e) {
        die(json_encode(array('status' => 'error', 'message' => $e->getMessage())));
    }

    // Prepare data for `claim_data`
    // $startTimes = $_POST['startTime'][$course] ?? []; // Nested arrays indexed by course
    // $endTimes = $_POST['endTime'][$course] ?? [];
    // $dates = $_POST['dates'][$course] ?? [];
    // $periods[] = 0;//$_POST['periods'];
    // $subTotal[] = 0;//$_POST['subTotal'];
    // $fuelComponents = $_POST['fuelComponent'] ?? [];

    // foreach ($startTimes as $index => $startTime) {
    //     $endTime = $endTimes[$index];
    //     $periods = $periods[$index];
    //     $subTotal = $subTotals[$index];
    //     $fuelComponent = isset($fuelComponents[$index]) ? 'Yes' : 'No';

    //     foreach ($dates as $date) {
    //         // Insert each combination of time slot and date
    //         $query = "INSERT INTO claim_data (claimId, date, start_time, end_time, periods, subTotal, fuelComponent)
    //                   VALUES ('$claimId', '$date', '$startTime', '$endTime', '$periods', '$subTotal', '$fuelComponent')";
    //         mysqli_query($conn, $query);
    //     }
    // }
}
