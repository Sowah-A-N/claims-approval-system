<?php

session_start();
// Include the database connection file
include '../../../../includes/conn.inc.php';

// Function to sanitize input data to prevent SQL injection
function sanitizeInput($conn, $input) {
    return htmlspecialchars(mysqli_real_escape_string($conn, $input));
}

$userId = $_SESSION['user_id'] ?? "";
$currentUserRate = $_SESSION['rate'];

$submitClaimBtn = isset($_POST['submitBtn']) ?? "";

// Sanitize and set default values for input variables
$department = sanitizeInput($conn, $_POST['department'] ?? "");
$programme = sanitizeInput($conn, $_POST['programme'] ?? "");
$course = sanitizeInput($conn, $_POST['course'] ?? "");
$stage = 1;


// Start a transaction
$conn->begin_transaction();

try {
    // Insert into claim_details
    $stmt = $conn->prepare("INSERT INTO claim_details (userId, department, programme, course, rate) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssi", $userId, $department, $programme, $course, $currentUserRate);
    $stmt->execute();

    // Get the claim ID
    $claim_id = $conn->insert_id;

    // Insert into claim_approval_stages
    $stmt = $conn->prepare("INSERT INTO claim_approval_stages (claimId, stage) VALUES (?, ?)");
    $stmt->bind_param("ii", $claim_id, $stage);
    $stmt->execute();

    // Get date, start time, end time, and period data if available
    if (isset($_POST['date'])) {
        $date = array();
        $startTime = array();
        $endTime = array();
        $period = array();
		$fuelComponent = array();
		
        foreach ($_POST['date'] as $index => $value) {
            $date[] = sanitizeInput($conn, $value) ?? "";
            $startTime[] = sanitizeInput($conn, $_POST['startTime'][$index]) ?? "";
            $endTime[] = sanitizeInput($conn, $_POST['endTime'][$index]) ?? "";
            $period[] = sanitizeInput($conn, $_POST['period'][$index]) ?? "";
			 // Check if the fuel component checkbox is set for this index
			$fuelComponent[] = isset($_POST['fuelComponent'][$index]) ? 1 : 0; // 1 if checked, 0 if not
			$subTotal[] = sanitizeInput($conn, $_POST['subTotal'][$index]) ?? "";

        }

        // Insert claim data for each period
        for ($i = 0; $i < count($date); $i++) {
            $stmt = $conn->prepare("INSERT INTO claim_data (claimId, date, start_time, end_time, periods, subTotal, fuelComponent) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssisi", $claim_id, $date[$i], $startTime[$i], $endTime[$i], $period[$i], $subTotal[$i], $fuelComponent[$i]);
            $stmt->execute();
        }
    }

    // Commit the transaction
    $conn->commit();
    echo json_encode(array("success" => "Claim and claim data submitted successfully!"));

} catch (Exception $e) {
    // Rollback the transaction if any query fails
    $conn->rollback();
    echo json_encode(array("error" => "Error submitting claim: " . $e->getMessage()));
}



