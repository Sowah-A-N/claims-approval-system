<?php

include_once "../../includes/conn.inc.php";

// Prepare an SQL statement for updating rates
$stmt = $conn->prepare("UPDATE lecturer_rank_rate SET rate = ? WHERE rankId = ?");

// Loop through POST data and update each record
foreach ($_POST as $key => $value) {
    if (strpos($key, 'rate_') === 0) {
        $id = str_replace('rate_', '', $key);
        $rate = intval($value);
        
        $stmt->bind_param("ii", $rate, $id);
        if (!$stmt->execute()) {
            echo "Error updating record with ID $id: " . $stmt->error;
        }
    }
}

$stmt->close();
$conn->close();

echo "Rates updated successfully.";
?>