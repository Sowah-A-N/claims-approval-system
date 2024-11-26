<?php
include '../../../../includes/conn.inc.php';

// Check if the department dropdown value is set
if(isset($_POST['department_dropdown_value'])) {
    $departmentDropdownValue = $_POST['department_dropdown_value'];

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM course WHERE department = ?");
    $stmt->bind_param("s", $departmentDropdownValue);
    $stmt->execute();
    $result = $stmt->get_result();

    $courses = [];
    if($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
    }

    // Output JSON
    echo json_encode($courses);

    // Close the statement and connection
    $stmt->close();
    $conn->close();
} else {
    // If department dropdown value is not set, return an empty array
    echo json_encode([]);
}
?>
