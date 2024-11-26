<?php
session_start(); 
include 'includes/conn.inc.php';

// Function to sanitize input data
function sanitize_data($data) {
    $data = trim($data); // Remove leading/trailing whitespace
    $data = stripslashes($data); // Remove backslashes
    $data = htmlspecialchars($data); // Convert special characters to HTML entities
    return $data;
}

// Retrieve and sanitize form data
$first_name = sanitize_data($_POST['first_name']);
$last_name = sanitize_data($_POST['last_name']);
$other_names = sanitize_data($_POST['other_names']) ?? "";
$phone_number = sanitize_data($_POST['phone_number']);
$gender = sanitize_data($_POST['gender']);
$email = sanitize_data($_POST['email']);
$password = sanitize_data($_POST['password']);
$department = sanitize_data($_POST['department']); 
$rank = sanitize_data($_POST['rank']);

// Default values for other fields
$role = 'approver';
$rate = 0;
$account_status = 'disabled';
$date_created = date('Y-m-d H:i:s');

// Begin transaction
mysqli_begin_transaction($conn);

try {
    // SQL query to insert data into the user_details table
    $registerSql = "INSERT INTO user_details (first_name, last_name, other_names, phone_number, gender, email, 
                                `password`, department, `role`, `rank`, `rate`, account_status, date_created)
                    VALUES ('$first_name', '$last_name', '$other_names', '$phone_number', '$gender', '$email',
                             '$password', '$department', '$role', '$rank', $rate, '$account_status', '$date_created')";

    if (!mysqli_query($conn, $registerSql)) {
        throw new Exception("Error inserting user details: " . mysqli_error($conn));
    }

    $userId = mysqli_insert_id($conn);

    // SQL query to insert data into the login_details table
    $updateLoginSql = "INSERT INTO login_details (userId, email, `password`, `role`, `rank`)
                        VALUES ($userId, '$email', '$password', '$role', '$rank')";

    if (!mysqli_query($conn, $updateLoginSql)) {
        throw new Exception("Error inserting login details: " . mysqli_error($conn));
    }

    // Commit transaction
    mysqli_commit($conn);

    // Set success message in session
    $_SESSION['message'] = 'Registration successful! You will be informed when your account is activated.';
    header('Location: ./index.php');
    exit();

} catch (Exception $e) {
    // Rollback transaction in case of error
    mysqli_rollback($conn);
    echo "Error: " . $e->getMessage();
}

// Close connection
mysqli_close($conn);
