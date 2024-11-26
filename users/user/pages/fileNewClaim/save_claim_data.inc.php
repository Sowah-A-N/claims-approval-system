<?php

    session_start();
    // Include the database connection file
    include '../../../../includes/conn.inc.php';

    // Function to sanitize input data to prevent SQL injection
    function sanitizeInput($conn, $input) {
        return htmlspecialchars(mysqli_real_escape_string($conn, $input));
    }

    $userId = $_SESSION['user_id'] ?? "";

    $submitClaimBtn = isset($_POST['submitBtn']) ?? "";

    // Sanitize and set default values for input variables
    $department = sanitizeInput($conn, $_POST['department'] ?? "");
    $programme = sanitizeInput($conn, $_POST['programme'] ?? "");
    $course = sanitizeInput($conn, $_POST['course'] ?? "");

    // Get date, start time, end time, and period data if available
    if (isset($_POST['date'])) {
        $date = array();
        $startTime = array();
        $endTime = array();
        $period = array();
        foreach ($_POST['date'] as $index => $value) {
            $date[] = sanitizeInput($conn, $value) ?? "";
            $startTime[] = sanitizeInput($conn, $_POST['startTime'][$index]) ?? "";
            $endTime[] = sanitizeInput($conn, $_POST['endTime'][$index]) ?? "";
            $period[] = sanitizeInput($conn, $_POST['period'][$index]) ?? "";
        }
    }

    // Prepare and execute database query to insert claim details
    $stmt = $conn->prepare("INSERT INTO saved_claims (userId, department, programme, course) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $department, $programme, $course);
    $result = $stmt->execute();

    // Check for errors in claim details insertion
    if ($result === false) {
        echo json_encode(array("error" => "Error saving claim: " . $conn->error));
        exit;
    } else {
        echo json_encode(array("success" => "Claim saved successfully!"));
    }

    // Get the claim ID
    $claim_id = $conn->insert_id;

    // Prepare and execute database queries for claim data insertion
    if (isset($date)) {
        for ($i = 0; $i < count($date); $i++) {
            $stmt = $conn->prepare("INSERT INTO claim_data (claimId, date, start_time, end_time, periods) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssi", $claim_id, $date[$i], $startTime[$i], $endTime[$i], $period[$i]);
            $result = $stmt->execute();

            // Check for errors in claim data insertion
            if ($result === false) {
                echo json_encode(array("error" => "Error saving claim data: " . $conn->error));
                exit;
            } else {
                echo json_encode(array("success" => "Claim data saved successfully!"));
            }
        }
    }
