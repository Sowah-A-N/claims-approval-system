<?php
require_once '../../includes/conn.inc.php';

if (isset($_POST['claimId'])) {
    $claimId = $_POST['claimId'];
    $programme = $_POST['programme'];
    $course = $_POST['course'];
    $start_times = $_POST['start_time'];
    $end_times = $_POST['end_time'];
    $periods = $_POST['periods'];

    // Save or update the claim details first
    // (same as in saveClaimDetails.inc.php)

    // Additional logic for submitting the claim
    $submitClaimQuery = "UPDATE saved_claims SET status = 'submitted' WHERE claimTempId = '$claimId'";
    mysqli_query($conn, $submitClaimQuery);

    echo "Claim submitted successfully";
} else {
    echo "Invalid request. Claim ID parameter is missing.";
}
?>
