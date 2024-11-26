<?php
// disableUserAccount.php

include_once '../../../../includes/conn.inc.php';

// Check if userId is provided in GET or POST request
if (!isset($_REQUEST['userId'])) {
    http_response_code(400);
    echo "User ID not provided";
    exit;
}

$userId = $_REQUEST['userId']; // Using $_REQUEST to handle both GET and POST

// Perform the update query to disable the user account
$sql = "UPDATE user_details SET account_status = 'disabled' WHERE userId = $userId";

if ($conn->query($sql) === TRUE) {
    http_response_code(200);
    echo "Account disabled successfully."; 
} else {
    http_response_code(500);
    echo "Error disabling account: " . $conn->error;
}

$conn->close();
exit;
