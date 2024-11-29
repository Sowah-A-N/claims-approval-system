<?php

    session_start();
    include_once './includes/conn.inc.php';
    // Get the POST data
    $dateSubmitted = isset($_POST['dateSubmitted']) ? $_POST['dateSubmitted'] : '';
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $dept = "ELECTRICAL ENGINEERING";//$_SESSION['department'] ?? "";

    // Base query
    //$query = "SELECT *, DATE(time_updated) AS date FROM claim_approval_stages WHERE 1=1"; // 1=1 is just a placeholder for the WHERE clause
    $query = "SELECT cd.claimId, cd.course, DATE(cd.time_submitted) AS time_submitted,
                    CONCAT(ud.first_name, ' ', ud.last_name) AS full_name,
                    cas.stage, cas.status
                FROM 
                    claim_details cd
                INNER JOIN 
                    user_details ud ON cd.userId = ud.userId
                INNER JOIN 
                    claim_approval_stages cas ON cd.claimId = cas.claimId
                JOIN (
                    SELECT 
                        claimId,
                        MAX(stage) AS max_stage
                    FROM 
                        claim_approval_stages
                    GROUP BY 
                        claimId
                ) AS max_stages
                ON 
                    cas.claimId = max_stages.claimId
                    AND cas.stage = max_stages.max_stage
                WHERE 
                    cd.department = '{$dept}' AND 1 = 1";

   // Array to hold query parameters
    $params = [];

    // Add conditions to the query based on provided inputs
    if (!empty($dateSubmitted)) {
       // $query .= " AND time_updated = ?";//--> This is the date selected in the dropdown
       // $params[] = $dateSubmitted; // Add dateSubmitted to params
    }
    if (!empty($action)) {
        $query .= " AND cas.status = ?"; //--> This is the action selected in the dropdown
        $params[] = $action; // Add action to params
    }

    // Prepare the statement
    $stmt = $conn->prepare($query);

    // Check if the statement was prepared successfully
    if ($stmt === false) {
        // Handle error, e.g., log it or display a message
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }

    // Dynamically bind the parameters based on the number of inputs
    if (count($params) > 0) {
        $types = str_repeat('s', count($params)); // 's' for string type
        $stmt->bind_param($types, ...$params);
    }

    // Execute the query
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch the records
    $records = [];
    while ($row = $result->fetch_assoc()) {
        // Format the date to 'd/m/Y'
        //$row['date'] = date('d/m/Y', strtotime($row['time_submitted']));
        // Add the formatted record to the results array
        $records[] = $row;
    }

    // Close the database connection
    $stmt->close();
    $conn->close();

    // Return the results as JSON
    echo json_encode([
        'success' => !empty($records),
        'results' => $records
]);
