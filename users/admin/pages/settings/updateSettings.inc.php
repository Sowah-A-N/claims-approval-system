<?php
session_start();
include_once '../../includes/conn.inc.php'; // Ensure you include your database connection

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the value of the checkbox (1 if checked, 0 if not)
    $fuelComponentValue = isset($_POST['fuelComponent']) ? 1 : 0;

    // Prepare the SQL statement to update the settings
    $stmt = $conn->prepare("UPDATE settings SET settingValue = ? WHERE settingName = 'fuelComponent'");

    // Bind the parameter
    $stmt->bind_param('i', $fuelComponentValue);

    // Execute the statement
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Settings updated successfully.']);
		header("Location: ./");
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update settings.']);
    }

    // Close the statement
    $stmt->close();
}

// Close the database connection
$conn->close();
?>
