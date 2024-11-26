<?php
    // Include database connection
    include_once '../../../../includes/conn.inc.php'; // Adjust the path as needed

    // Check if 'bank_name' is set in the request
    if (isset($_GET['bank_name'])) {
        $bankName = mysqli_real_escape_string($conn, $_GET['bank_name']);
        
        // Query to fetch branches based on the bank name
        $branchesQuery = "SELECT bank_branch FROM banks_branches WHERE bank_name = \"$bankName\";";
        $branchesResult = mysqli_query($conn, $branchesQuery);
        
        // Prepare the response
        $branches = [];
        while ($row = mysqli_fetch_assoc($branchesResult)) {
            $branches[] = $row['bank_branch'];
        }
        
        // Return the JSON response
        echo json_encode($branches);
    } else {
        // If 'bank_name' is not set, return an empty array
        echo json_encode([]);
    }

    // Close the database connection
    mysqli_close($conn);
