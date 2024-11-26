<?php
require_once '../../includes/conn.inc.php';

if (isset($_GET['claimId'])) {
    $claimId = $_GET['claimId'];

    // Query to fetch claim details
    $claimsDetailsQuery = "SELECT * FROM saved_claims WHERE claimTempId = '$claimId'";
    $claimsDetailsResult = mysqli_query($conn, $claimsDetailsQuery);

    if ($claimsDetailsResult && mysqli_num_rows($claimsDetailsResult) > 0) {
        $row = mysqli_fetch_assoc($claimsDetailsResult);

        echo '<p id="claimId" name="claimId"><strong>Claim ID</strong> : ' . htmlspecialchars($row['claimTempId']) . '</p>';
        echo '<p id="programme" name="programme"><strong>Programme</strong> :' . htmlspecialchars($row['programme']) . '</p>';
        echo '<p id="course" name="course"><strong>Course</strong> :' . htmlspecialchars($row['course']) . '</p>';

        // Fetch and display additional claim data
        $claimDataQuery = "SELECT * FROM claim_data WHERE claimId = '$claimId'";
        $claimDataResult = mysqli_query($conn, $claimDataQuery);

        // echo '<tbody id="claimDataRows">';
        // if($claimDataResult && mysqli_num_rows($claimDataResult) > 0) {
        //     // Display existing claim data
        //     while ($row = mysqli_fetch_assoc($claimDataResult)) {
        //         echo '<tr>';
        //         echo '<td><input type="time" class="form-control" name="startTime[]" value="' . $row['start_time'] . '" onchange="calculatePeriod()"></td>';
        //         echo '<td><input type="time" class="form-control" name="endTime[]" value="' . $row['end_time'] . '" onchange="calculatePeriod()"></td>';
        //         echo '<td><input type="text" class="form-control" name="period[]" value="' . htmlspecialchars($row['periods']) . '" readonly></td>';
        //         echo '<td><button type="button" class="btn btn-danger btn-sm delete-row">Delete</button></td>';
        //         echo '</tr>';
        //     }
        // } else {
        //     // Display an empty row for new data
        //     echo '<tr>';
        //     echo '<td><input type="time" class="form-control" name="startTime[]" placeholder="Start Time" onchange="calculatePeriod()"></td>';
        //     echo '<td><input type="time" class="form-control" name="endTime[]" placeholder="End Time" onchange="calculatePeriod()"></td>';
        //     echo '<td><input type="text" class="form-control" name="period[]" placeholder="Periods" readonly></td>';
        //     echo '<td><button type="button" class="btn btn-danger btn-sm delete-row">Delete</button></td>';
        //     echo '</tr>';
        // }
        //<!-- Add Row Button -->
        
        
        // echo '</tbody>';
    } else {
        echo "<p>No claim found with ID: $claimId</p>";
    }
} else {
    echo "<p>Invalid request. Claim ID parameter is missing.</p>";
}

