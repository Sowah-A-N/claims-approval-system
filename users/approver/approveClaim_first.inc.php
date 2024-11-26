<?php
session_start();

include_once '../../includes/conn.inc.php';

// Get the current approver's stage from the session
$stage = $_SESSION['stage'] ?? null;

// Get the claim ID from the POST request
$claimId = $_POST['claimId'] ?? null;

// Validate input
if ($claimId === null || !filter_var($claimId, FILTER_VALIDATE_INT)) {
    echo json_encode(['success' => false, 'message' => 'Invalid claim ID.']);
    exit;
}

$conn->begin_transaction();

try {
    // Fetch the current stage
    $stmt = $conn->prepare(
        'SELECT `stage` 
         FROM `claim_approval_stages` 
         WHERE `claimId` = ? 
         ORDER BY `stageId` DESC 
         LIMIT 1'
    );

    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    $stmt->bind_param('i', $claimId);
    $stmt->execute();
    $stmt->bind_result($currentStage);
    $stmt->fetch();
    $stmt->close();

    // Validate the current stage
    if ($currentStage === null) {
        throw new Exception('Claim ID not found or invalid stage.');
    }
    if ($currentStage >= 5) {
        throw new Exception('Claim is already fully approved.');
    }

    // Update the status at the current stage
    $stmt = $conn->prepare(
        'UPDATE `claim_approval_stages`
         SET `status` = ?
         WHERE `claimId` = ? AND `stage` = ?'
    );

    $status = 'Approved';
    $stmt->bind_param('sii', $status, $claimId, $currentStage);

    if (!$stmt->execute() || $stmt->affected_rows <= 0) {
        throw new Exception('Failed to update the status at the current stage.');
    }

    $stmt->close();

    // Calculate the next stage
    $newStage = $currentStage + 1;

    // Insert the new stage into the approval stages
    $stmt = $conn->prepare(
        'INSERT INTO `claim_approval_stages` 
         (`claimId`, `stage`, `status`, `time_updated`) 
         VALUES (?, ?, ?, NOW())'
    );

    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    $status = 'Pending';
    $stmt->bind_param('iis', $claimId, $newStage, $status);
    $stmt->execute();

    if ($stmt->affected_rows <= 0) {
        throw new Exception('Failed to insert the new stage.');
    }

    $stmt->close();

    // Commit the transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Status updated and new stage inserted successfully.']);

} catch (Exception $e) {
    // Roll back the transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
