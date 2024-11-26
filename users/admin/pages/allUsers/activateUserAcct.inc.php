<?php
include_once '../../../../includes/conn.inc.php';

// Check if userId is provided in GET request
if (!isset($_GET['userId'])) {
    http_response_code(400);
    echo "User ID not provided";
    exit;
}

$userId = $_GET['userId'];

// Perform the update query to activate the user account
$sql = "UPDATE user_details SET account_status = 'active' WHERE userId = $userId";

if ($conn->query($sql) === TRUE) {
    http_response_code(200);
    echo "Account activated successfully."; 
} else {
    http_response_code(500);
    echo "Error activating account: " . $conn->error;
}

$conn->close();
exit;

