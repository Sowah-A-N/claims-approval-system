<?php
// require_once '../../includes/conn.inc.php';

// if (isset($_POST['claimId'])) {
//     $claimId = $_POST['claimId'];
//     $programme = $_POST['programme'];
//     $course = $_POST['course'];
//     $start_times = $_POST['start_time'];
//     $end_times = $_POST['end_time'];
//     $periods = $_POST['periods'];

//     // Update the main claim details
//     $updateClaimQuery = "UPDATE saved_claims SET programme = '$programme', course = '$course' WHERE claimTempId = '$claimId'";
//     mysqli_query($conn, $updateClaimQuery);

//     // Delete old claim data
//     $deleteClaimDataQuery = "DELETE FROM claim_data WHERE claimId = '$claimId'";
//     mysqli_query($conn, $deleteClaimDataQuery);

//     // Insert new claim data
//     for ($i = 0; $i < count($start_times); $i++) {
//         $start_time = $start_times[$i];
//         $end_time = $end_times[$i];
//         $period = $periods[$i];

//         $insertClaimDataQuery = "INSERT INTO claim_data (claimId, start_time, end_time, periods) VALUES ('$claimId', '$start_time', '$end_time', '$period')";
//         mysqli_query($conn, $insertClaimDataQuery);
//     }

//     echo "Success";
// } else {
//     echo "Invalid request. Claim ID parameter is missing.";
// }

require_once '../../includes/conn.inc.php';

if (isset($_POST['claimTempId'])) {
    $claimId = $_POST['claimId'];
    $programme = $_POST['programme'];
    $course = $_POST['course'];
    $start_times = $_POST['start_time'];
    $end_times = $_POST['end_time'];
    $periods = $_POST['periods'];

    // Prepare and execute the update query for the main claim details
    $updateClaimQuery = "UPDATE saved_claims SET programme = ?, course = ? WHERE claimTempId = ?";
    if ($stmt = mysqli_prepare($conn, $updateClaimQuery)) {
        mysqli_stmt_bind_param($stmt, 'sss', $programme, $course, $claimId);
        if (mysqli_stmt_execute($stmt)) {
            echo "Claim details updated successfully.<br>";
        } else {
            echo "Error updating claim details: " . mysqli_stmt_error($stmt) . "<br>";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "Error preparing update statement: " . mysqli_error($conn) . "<br>";
    }

    // Prepare and execute the delete query for old claim data
    $deleteClaimDataQuery = "DELETE FROM claim_data WHERE claimId = ?";
    if ($stmt = mysqli_prepare($conn, $deleteClaimDataQuery)) {
        mysqli_stmt_bind_param($stmt, 's', $claimId);
        if (mysqli_stmt_execute($stmt)) {
            echo "Old claim data deleted successfully.<br>";
        } else {
            echo "Error deleting old claim data: " . mysqli_stmt_error($stmt) . "<br>";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "Error preparing delete statement: " . mysqli_error($conn) . "<br>";
    }

    // Prepare and execute the insert queries for new claim data
    $insertClaimDataQuery = "INSERT INTO claim_data (claimId, start_time, end_time, periods) VALUES (?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($conn, $insertClaimDataQuery)) {
        mysqli_stmt_bind_param($stmt, 'ssss', $claimId, $start_time, $end_time, $period);
        
        // Execute the insert query for each set of data
        for ($i = 0; $i < count($start_times); $i++) {
            $start_time = $start_times[$i];
            $end_time = $end_times[$i];
            $period = $periods[$i];
            
            if (mysqli_stmt_execute($stmt)) {
                echo "New claim data inserted successfully.<br>";
            } else {
                echo "Error inserting claim data: " . mysqli_stmt_error($stmt) . "<br>";
            }
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "Error preparing insert statement: " . mysqli_error($conn) . "<br>";
    }

} else {
    echo "Invalid request. Claim ID parameter is missing.";
}

// Close the connection
mysqli_close($conn);

