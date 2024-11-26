<?php
// Include the database connection file
include '../../../../includes/conn.inc.php';

// Check if claimId is provided in POST data
if (isset($_POST['claimId'])) {
    // Sanitize claimId to prevent SQL injection
    $claimId = htmlspecialchars(mysqli_real_escape_string($conn, $_POST['claimId']));

    // Prepare and execute database query to delete claim from saved_claims table
    $stmt = $conn->prepare("DELETE FROM saved_claims WHERE claimTempId = ?");
    $stmt->bind_param("i", $claimId);
    $result = $stmt->execute();

    // Check for errors in deletion
    if ($result === false) {
        echo json_encode(array("error" => "Error deleting claim: " . $conn->error));
    } else {
        // Also delete associated claim data from claim_data table
        $stmt = $conn->prepare("DELETE FROM claim_data WHERE claimId = ?");
        $stmt->bind_param("i", $claimId);
        $result = $stmt->execute();

        // Check for errors in claim data deletion
        if ($result === false) {
            echo json_encode(array("error" => "Error deleting claim data: " . $conn->error));
        } else {
            echo json_encode(array("success" => "Claim and associated data deleted successfully!"));
        }
    }
} else {
    echo json_encode(array("error" => "ClaimId not provided."));
}

// Close database connection
$conn->close();

