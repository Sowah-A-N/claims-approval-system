<?php
    include 'includes/conn.inc.php';
    session_start();

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
    $other_names = sanitize_data($_POST['other_names'] ?? "");
    $phone_number = sanitize_data($_POST['phone_number']);
    $gender = sanitize_data($_POST['gender']);
    $email = sanitize_data($_POST['email']);
    $password = sanitize_data($_POST['password']);
    $department = sanitize_data($_POST['department']); 
    $rank = sanitize_data($_POST['rank']);
    $rate = sanitize_data($_POST['rate']);

    //Bank details to go into user_bank_details table
    $bank_name = sanitize_data($_POST['bank_name']);
    $bank_branch = sanitize_data($_POST['bank_branch']);
    $account_name = sanitize_data($_POST['account_name']);
    $account_number = sanitize_data($_POST['account_number']);



    // Default values for other fields
    $role = 'claimant';    
    $account_status = 'disabled';
    $date_created = date('Y-m-d H:i:s');

    // SQL query to insert data into the user_details table
    $registerSql = "INSERT INTO user_details (first_name, last_name, other_names, phone_number,
                     gender, email, `password`, department, `role`, `rank`, rate, account_status, date_created)
                    VALUES ('$first_name', '$last_name', '$other_names', '$phone_number',
                     '$gender', '$email', '$password', '$department', '$role', '$rank', $rate, '$account_status', '$date_created')";

    if ($conn->query($registerSql) === TRUE) {
        echo "Registration successful";
        echo "<br />";

        $userId = $conn->insert_id;

         // Select columns you want to duplicate to another table
        $detailsForLogin = ['userId', 'email', 'password', 'role', '`rank`'];

        // Construct the query to duplicate values to another table
        $updateLoginSql = "INSERT INTO login_details (" . implode(',', $detailsForLogin) . ")
                             VALUES ('$userId', '$email', '$password', '$role', '$rank')";

        $bankDetailsSql = "INSERT INTO user_bank_details (userId, bank_name, bank_branch, account_name, account_number)
                             VALUES ('$userId', '$bank_name', '$bank_branch', '$account_name', '$account_number')";

        // Execute the query to duplicate values
        if (mysqli_query($conn, $updateLoginSql)) {          
            // echo "Login details updated successfully!";
            // echo "<br />";

        } else {
            echo "Error duplicating values: " . mysqli_error($conn);
        }

        if (mysqli_query($conn, $bankDetailsSql)) {
              // Set success message in session
              $_SESSION['message'] = 'Registration successful! You will be informed when your account is activated.';
            
              // Redirect to login page
              header('Location: ./index.php');
              exit();
            // echo "bank details submitted successfully!";
            // echo "<br />";

        } else {
            echo "Error submitting bank details: " . mysqli_error($conn);
        }
    } else {
        echo "Error: " . $registerSql . "<br>" . $conn->error;
    }

    // Close connection
    $conn->close();
    
