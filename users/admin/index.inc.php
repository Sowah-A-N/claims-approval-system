<?php

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(array('admin', 'Admin'));

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
    csrf_verify();

    if (!isset($_POST['userId'])) {
        http_response_code(400);
        echo "User ID not provided";
        exit;
    }

    $userId = (int) $_POST['userId'];

    $stmt = mysqli_prepare($conn, "UPDATE user_details SET account_status = 'active' WHERE userId = ?");
    mysqli_stmt_bind_param($stmt, 'i', $userId);

    if (mysqli_stmt_execute($stmt)) {
        log_audit($conn, 'user.activate', 'user', $userId);
        http_response_code(200);
        echo json_encode(array('success' => true, 'message' => 'Account activated successfully.'));
    } else {
        error_log('[admin] activateAccount failed: ' . mysqli_error($conn));
        http_response_code(500);
        echo json_encode(array('success' => false, 'message' => 'Account activation failed. Please try again.'));
    }

    mysqli_stmt_close($stmt);
    exit;
}

function changeStage() {
    global $conn;
    csrf_verify();

    if (!isset($_POST['userId']) || !isset($_POST['stage'])) {
        http_response_code(400);
        echo "User ID or stage not provided";
        exit;
    }

    $userId = (int) $_POST['userId'];
    $stage  = (int) $_POST['stage'];

    $stmt = mysqli_prepare($conn, "UPDATE user_details SET stage = ? WHERE userId = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $stage, $userId);

    if (mysqli_stmt_execute($stmt)) {
        log_audit($conn, 'user.change_stage', 'user', $userId, 'stage = ' . $stage);
        http_response_code(200);
        echo json_encode(array('success' => true, 'message' => 'Stage updated successfully.'));
    } else {
        error_log('[admin] changeStage failed: ' . mysqli_error($conn));
        http_response_code(500);
        echo json_encode(array('success' => false, 'message' => 'Stage update failed. Please try again.'));
    }

    mysqli_stmt_close($stmt);
    exit;
}

function viewAccountDetails() {
    global $conn;
    csrf_verify();

    if (!isset($_POST['userId'])) {
        http_response_code(400);
        echo "User ID not provided";
        exit;
    }

    $userId = (int) $_POST['userId'];

    $stmt = mysqli_prepare($conn,
        'SELECT userId, first_name, last_name, other_names, phone_number, gender,
                email, department, faculty, `role`, `rank`, account_status
         FROM user_details WHERE userId = ?'
    );
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row    = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if ($row) {
        http_response_code(200);
        echo json_encode($row);
    } else {
        error_log('[admin] viewAccountDetails failed: ' . mysqli_error($conn));
        http_response_code(500);
        echo json_encode(array('success' => false, 'message' => 'Could not retrieve user details.'));
    }

    exit;
}
