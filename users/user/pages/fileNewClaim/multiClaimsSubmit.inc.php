<?php

include_once '../../includes/conn.inc.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	//$rawData = file_get_contents("php://input");
    //$data = json_decode($rawData, true); // Decode the JSON into an associative array
	
	echo "<pre> POST : <br />";
    var_dump($_POST); // View all submitted POST data
    echo "</pre>";
  
	// Debugging: Display the received JSON data
    //var_dump($data);
	
    // Example inputs from form submission
    $userId = $_POST['userId'] ?? 1; // Assume this is provided or derived from the session
    $faculty = $_POST['faculty'] ?? "N/A"; // Static or derived elsewhere
    $department = $_POST['department'];
    $programme = $_POST['programme'];
    $course = $_POST['course'];
    $rate = $_POST['rate'];

    // Start inserting into `claim_details`
    $query = "INSERT INTO claim_details (userId, faculty, department, programme, course, rate) 
              VALUES ('$userId', '$faculty', '$department', '$programme', '$course', '$rate')";
    mysqli_query($conn, $query);

    // Get the generated claimId
    $claimId = mysqli_insert_id($conn);

    // Prepare data for `claim_data`
    $startTimes = $_POST['startTime'][$course] ?? []; // Nested arrays indexed by course
    $endTimes = $_POST['endTime'][$course] ?? [];
    $dates = $_POST['dates'][$course] ?? [];
    $periods = $_POST['periods'];
    $subTotals = $_POST['subTotal'];
    $fuelComponents = $_POST['fuelComponent'] ?? [];

    foreach ($startTimes as $index => $startTime) {
        $endTime = $endTimes[$index];
        $period = $periods[$index];
        $subTotal = $subTotals[$index];
        $fuelComponent = isset($fuelComponents[$index]) ? 'Yes' : 'No';

        foreach ($dates as $date) {
            // Insert each combination of time slot and date
            $query = "INSERT INTO claim_data (claimId, date, start_time, end_time, periods, subTotal, fuelComponent) 
                      VALUES ('$claimId', '$date', '$startTime', '$endTime', '$period', '$subTotal', '$fuelComponent')";
            mysqli_query($conn, $query);
        }
	}
}

