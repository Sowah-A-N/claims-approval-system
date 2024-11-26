<?php
session_start();
include "../../includes/conn.inc.php";

// Retrieve the current stage and approver ID from the session
$flagged_at_stage = $_SESSION['stage'] ?? 0;
$flagged_by = $_SESSION['approverId'] ?? 0;

// Check if claimId and flagReason are provided in the POST request
if (!isset($_POST['claimId']) || !isset($_POST['flagReason'])) {
    echo json_encode(['success' => false, 'message' => 'Missing claim ID or flag reason.']);
    exit;
}

$claimId = $_POST['claimId'];
$flagged_msg = $_POST['flagReason'];

// Validate claimId and flagReason
if (!is_numeric($claimId)) {
    echo json_encode(['success' => false, 'message' => 'Invalid claim ID.']);
    exit;
}

// Begin a transaction
$conn->begin_transaction();

try {
    // Fetch claim details
    $claimDetailsQuery = "SELECT department, programme, course FROM claim_details WHERE claimId = ?";
    $claimDetailsStmt = $conn->prepare($claimDetailsQuery);
    
    if (!$claimDetailsStmt) {
        throw new Exception("Failed to prepare claim details query: " . $conn->error);
    }

    $claimDetailsStmt->bind_param('i', $claimId);
    $claimDetailsStmt->execute();
    $claimDetailsResult = $claimDetailsStmt->get_result();

    if ($claimDetailsResult->num_rows === 0) {
        throw new Exception("Claim not found.");
    }

    $claimDetails = $claimDetailsResult->fetch_assoc();
    $claimDetailsStmt->close();

    // Update claim_details to flag the claim
    $flagClaimQuery = "UPDATE claim_details SET flagged = 1 WHERE claimId = ?";
    $flagClaimStmt = $conn->prepare($flagClaimQuery);

    if (!$flagClaimStmt) {
        throw new Exception("Failed to prepare flag claim query: " . $conn->error);
    }

    $flagClaimStmt->bind_param('i', $claimId);
    $flagClaimStmt->execute();

    if ($flagClaimStmt->affected_rows <= 0) {
        throw new Exception("Failed to flag the claim or claim was already flagged.");
    }

    $flagClaimStmt->close();

    // Insert details into claim_approval_stages for flagging
    $insertStageQuery = "INSERT INTO claim_approval_stages 
                        (claimId, stage, `status`, time_rejected) 
                        VALUES (?, ?, 'Flagged', NOW())";
    
    $insertStageStmt = $conn->prepare($insertStageQuery);

    if (!$insertStageStmt) {
        throw new Exception("Failed to prepare insert claim approval stages query: " . $conn->error);
    }

    $insertStageStmt->bind_param('ii', $claimId, $flagged_at_stage);
    $insertStageStmt->execute();

    if ($insertStageStmt->affected_rows <= 0) {
        throw new Exception("Failed to record the flagging in claim approval stages.");
    }

    $insertStageStmt->close();

    // Commit the transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Claim successfully flagged and recorded.']);

} catch (Exception $e) {
    // Roll back the transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
