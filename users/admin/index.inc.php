<?php

// Include your database connection script or define $conn here
include_once "../../includes/conn.inc.php";

// Handle AJAX request if received
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == 'activateAccount') {
        activateAccount();
    } elseif ($_POST['action'] == 'changeStage') {
        changeStage();
    } elseif ($_POST['action'] == 'viewAccountDetails') {
        viewAccountDetails();
    } else {
        http_response_code(400);
        echo "Invalid action";
    }
}

function activateAccount() {
    global $conn;

    if (!isset($_POST['userId'])) {
        http_response_code(400);
        echo "User ID not provided";
        exit;
    }

    $userId = $_POST['userId'];
    //$stage = $_POST['stage'];

    // Perform the update query
    $sql = "UPDATE user_details SET account_status = 'active' WHERE userId = $userId";

    if ($conn->query($sql) === TRUE) {
        // Update login details stage
        //$updateLoginDetailsSql = "UPDATE login_details SET stage = '$stage' WHERE userId = $userId";

       // if ($conn->query($updateLoginDetailsSql) === TRUE) {
            http_response_code(200);
            echo "Account activated successfully.";
        // } else {
        //     http_response_code(500);
        //     echo "Error updating login details: " . $conn->error;
        //}
    } else {
        http_response_code(500);
        echo "Error activating account: " . $conn->error;
    }

    $conn->close();
    exit;
}

function changeStage() {
    global $conn;

    if (!isset($_POST['userId']) || !isset($_POST['stage'])) {
        http_response_code(400);
        echo "User ID or stage not provided";
        exit;
    }

    $userId = $_POST['userId'];
    $stage = $_POST['stage'];

    // Perform the update query
    $sql = "UPDATE user_details SET stage = '$stage' WHERE userId = $userId";

    if ($conn->query($sql) === TRUE) {
        http_response_code(200);
        echo "Stage changed successfully!";
    } else {
        http_response_code(500);
        echo "Error changing stage: " . $conn->error;
    }

    $conn->close();
    exit;
}

function viewAccountDetails() {
    global $conn;

    if (!isset($_POST['userId'])) {
        http_response_code(400);
        echo "User ID not provided";
        exit;
    }

    $userId = $_POST['userId'];

    // Perform the select query
    $userDetailsSelectQuery = "SELECT * FROM user_details WHERE userId = $userId";

    $result = $conn->query($userDetailsSelectQuery);

    if ($result) {
        $row = $result->fetch_assoc();
        http_response_code(200);
        echo json_encode($row); // Assuming you want to return details as JSON
    } else {
        http_response_code(500);
        echo "Error retrieving user details: " . $conn->error;
    }

    $conn->close();
    exit;
}


