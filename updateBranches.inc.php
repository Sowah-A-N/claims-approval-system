<?php
include_once "./includes/conn.inc.php";

// Check if bank_name is set and not empty
if (isset($_GET['bank_name']) && !empty($_GET['bank_name'])) {
    $bank_name = $_GET['bank_name'];
    
    // SQL query to select branches for the selected bank
    $sql = "SELECT bank_branch FROM `banks_branches` WHERE bank_name = '$bank_name' ORDER BY bank_branch";
    $result = mysqli_query($conn, $sql);
    
    if ($result->num_rows > 0) {
        $branches = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $branches[] = $row;
        }
        echo json_encode($branches); // Output JSON encoded branches
    } else {
        echo json_encode(array()); // Output an empty array if no branches found
    }
} else {
    echo json_encode(array()); // Output an empty array if bank_name parameter is not set
}
