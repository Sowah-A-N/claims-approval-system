<?php
include_once '../../includes/conn.inc.php';

// Check if claimId parameter is set in the request
if(isset($_GET['claimId'])) {
    $claimId = $_GET['claimId'];

    // Perform SELECT query to fetch claim details based on claimId
    $claimsDetailsQuery = "SELECT * FROM saved_claims WHERE claimTempId = '$claimId'";
    $claimsDetailsResult = mysqli_query($conn, $claimsDetailsQuery);

    // Check if the query was successful
    if($claimsDetailsResult) {
        // Check if any rows were returned
        if(mysqli_num_rows($claimsDetailsResult) > 0) {
            // Fetch the claim details
            $row = mysqli_fetch_assoc($claimsDetailsResult);

            // Output the claim details in HTML format
            echo "<p><strong>Claim ID:</strong> {$row['claimTempId']}</p>";
            echo "<p><strong>Programme:</strong> {$row['programme']}</p>";
            echo "<p><strong>Course:</strong> {$row['course']}</p>";
            // Add more details as needed

            // Query to fetch additional claim data
            $claimDataQuery = "SELECT * FROM claim_data WHERE claimId = '$claimId'";
            $claimDataResult = mysqli_query($conn, $claimDataQuery);

            // Check if the query was successful
            if($claimDataResult) {
                echo '<table class="table table-bordered table-striped">';
                echo '<thead class="thead-light">';
                echo '<tr>';
                echo '<th>Start Time</th>'; 
                echo '<th>End Time</th>';
                echo '<th>Periods</th>';
                echo '<th></th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';

                // Check if any rows were returned
                if(mysqli_num_rows($claimDataResult) > 0) {
                    // Fetch and output the additional claim data
                    while ($row = mysqli_fetch_assoc($claimDataResult)) {
                        echo '<tr>';
                        echo '<td><input type="time" class="form-control" value="' . $row['start_time'] . '"></td>';
                        echo '<td><input type="time" class="form-control" value="' . $row['end_time'] . '"></td>';
                        echo '<td>' . htmlspecialchars($row['periods']) . '</td>';
                        echo '<td><button type="button" class="btn btn-danger btn-sm delete-row">Delete</button></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">No claim data available</td></tr>';
                }

                echo '</tbody>';
                echo '</table>';
                echo '<br><button type="button" id="modalNewRow" class="btn btn-primary"><span class="mdi mdi-plus-thick"></span> Add Row</button><br>';
                echo '<br><div class="row">';
                echo '<div class="col-sm-3"><button type="button" class="btn btn-primary btn-block">Save</button></div>';
                echo '<div class="col-sm-3"><button type="button" class="btn btn-success btn-block">Submit</button></div>';
                echo '</div>';


            } else {
                echo "<p>Error retrieving additional claim data: " . mysqli_error($conn) . "</p>";
            }
        } else {
            echo "<p>No claim found with ID: $claimId</p>";
        }
    } else {
        echo "<p>Error retrieving claim details: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>Invalid request. Claim ID parameter is missing.</p>";
}

