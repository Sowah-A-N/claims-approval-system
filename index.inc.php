<?php
// Start session
session_start();

// Include database connection
require './includes/conn.inc.php';

// SQL statement to query database
$query = "SELECT * FROM login_details WHERE email = ? AND password = ?";

// Prepare statement for DB
$stmt = mysqli_prepare($conn, $query);

// Check if the prepared statement is successful
if (!$stmt) {
    die("Error in preparing statement: " . mysqli_error($conn));
}

// Get the login email and password from the POST request
$login_email = $_POST['email'] ?? '';
$login_pw = $_POST['pw'] ?? '';

// Bind the login email and password to the prepared statement
mysqli_stmt_bind_param($stmt, "ss", $login_email, $login_pw);

// Execute the prepared statement
mysqli_stmt_execute($stmt);

// Get the result of the query
$query_result = mysqli_stmt_get_result($stmt);

if (!$query_result) {
    
    die("Error in executing query: " . mysqli_error($conn));

}  elseif (mysqli_num_rows($query_result) > 0) {    // Check if any rows were returned
    // Fetch the account status from user_details table
    $acctStatusSql = "SELECT * FROM user_details WHERE email = ?";
    $acctStatusStmt = mysqli_prepare($conn, $acctStatusSql);
    mysqli_stmt_bind_param($acctStatusStmt, "s", $login_email);
    mysqli_stmt_execute($acctStatusStmt);
    $acctStatusResult = mysqli_stmt_get_result($acctStatusStmt);

    if ($acctStatusResult) {
        $row = mysqli_fetch_assoc($acctStatusResult);
        $accountStatus = $row['account_status'];
        $_SESSION['full_name'] = ($row['last_name'] ?? "") . ", " . ($row['first_name'] ?? "");
		$_SESSION['rate'] = $row['rate'] ?? "";
		$_SESSION['dept'] = ($row['department'] ?? "");
		$_SESSION['stage'] = ($row['stage'] ?? "");



        if ($accountStatus == "disabled") {
            // Account is disabled, handle accordingly
            echo "Your account is disabled. Please contact support for assistance.";
            session_unset();
            session_destroy();
            exit(); // Stop execution if account is disabled
        }
    } else {
        die("Error in determining account status: " . mysqli_error($conn));
    }

    // Fetch user details
    $row = mysqli_fetch_assoc($query_result);
    $_SESSION['role'] = ($row['role'] ?? "");
    $_SESSION['user_id'] = ($row['userId'] ?? "");
    $_SESSION['stage'] = ($row['stage'] ?? "");



    switch ($_SESSION['role']) {
        case 'user':
        case 'claimant':
            $redirect_url = './users/user/';
            break;

        case 'approver':
            $_SESSION['approverId'] = $_SESSION['user_id'];
            $redirect_url = './users/approver/';
            break;

        case 'admin':
        case 'Admin':
            $redirect_url = './users/admin';
            break;
			
		case 'finance':
		case 'Finance':
			$redirect_url = './users/finance';
			break;
            
        default:
            // Display an error message if the credentials are invalid
            echo "Invalid credentials. Please try again.";
            exit(); // Stop execution if invalid credentials
    }
    

    // Redirect user
    header("Location: $redirect_url");
    exit();
} else {
    // No user found with the provided email
    echo "No user found with the provided email.";
}

// Close the database connection
mysqli_close($conn);
