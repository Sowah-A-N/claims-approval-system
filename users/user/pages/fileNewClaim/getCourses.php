<?php
require '../../includes/conn.inc.php'; // Replace with your DB connection file

if (isset($_GET['department'])) {
    $department = mysqli_real_escape_string($conn, $_GET['department']);
    $query = "SELECT name FROM course WHERE department = '$department'";
    $result = mysqli_query($conn, $query);

    $courses = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $courses[] = $row;
    }

    echo json_encode($courses);
}
?>
