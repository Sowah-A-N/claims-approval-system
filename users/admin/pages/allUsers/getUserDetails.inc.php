<?php
// getUserDetails.php

// Include your database connection file
include '../../../../includes/conn.inc.php'; // Adjust the path as necessary

// Check if userId parameter exists in GET request
if(isset($_GET['userId'])) {
    // Sanitize userId input
    $userId = mysqli_real_escape_string($conn, $_GET['userId']);

    // Query to fetch user details
    $userDetailsQuery = "SELECT *, CONCAT(first_name, ' ', last_name) AS full_name
                         FROM user_details WHERE userId = $userId";
    $userDetailsResult = mysqli_query($conn, $userDetailsQuery);

    if($userDetailsResult) {
        // Fetch user details as associative array
        $userDetails = mysqli_fetch_assoc($userDetailsResult);

        // Check if user details were found
        if($userDetails) {
            // Return user details as JSON response
            header('Content-Type: application/json');
            echo json_encode($userDetails);
        } else {
            // User not found
            http_response_code(404);
            echo json_encode(array('error' => 'User not found'));
        }
    } else {
        // Query execution error
        http_response_code(500);
        echo json_encode(array('error' => 'Error executing query'));
    }

    // Free result set
    mysqli_free_result($userDetailsResult);
} else {
    // userId parameter not provided
    http_response_code(400);
    echo json_encode(array('error' => 'userId parameter is required'));
}

// Close database connection
mysqli_close($conn);

