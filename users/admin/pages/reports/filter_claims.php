<?php
include_once "../../includes/conn.inc.php";

// Building the SQL query with filters
$query = "SELECT cd.*, cas.stage, cas.status, cas.time_approved, cas.time_rejected 
          FROM claim_details cd 
          JOIN claim_approval_stages cas ON cd.claimId = cas.claimId 
          WHERE 1=1";

if (!empty($_GET['department'])) {
    $department = $conn->real_escape_string($_GET['department']);
    $query .= " AND cd.department = '$department'";
}

if (!empty($_GET['programme'])) {
    $programme = $conn->real_escape_string($_GET['programme']);
    $query .= " AND cd.programme = '$programme'";
}

if (!empty($_GET['course'])) {
    $course = $conn->real_escape_string($_GET['course']);
    $query .= " AND cd.course = '$course'";
}

if (!empty($_GET['stage'])) {
    $stage = $conn->real_escape_string($_GET['stage']);
    $query .= " AND cas.stage = '$stage'";
}

if (!empty($_GET['status'])) {
    $status = $conn->real_escape_string($_GET['status']);
    $query .= " AND cas.status = '$status'";
}

$result = $conn->query($query);

if ($result->num_rows > 0) {
    echo "<table border='1'>
            <tr>
                <th>Claim ID</th>
                <th>User ID</th>
                <th>Department</th>
                <th>Programme</th>
                <th>Course</th>
                <th>Flagged</th>
                <th>Completed</th>
                <th>Time Submitted</th>
                <th>Stage</th>
                <th>Status</th>
                <th>Time Approved</th>
                <th>Time Rejected</th>
            </tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . htmlspecialchars($row['claimId']) . "</td>
                <td>" . htmlspecialchars($row['userId']) . "</td>
                <td>" . htmlspecialchars($row['department']) . "</td>
                <td>" . htmlspecialchars($row['programme']) . "</td>
                <td>" . htmlspecialchars($row['course']) . "</td>
                <td>" . ($row['flagged'] ? 'Yes' : 'No') . "</td>
                <td>" . ($row['completed'] ? 'Yes' : 'No') . "</td>
                <td>" . htmlspecialchars($row['time_submitted']) . "</td>
                <td>" . htmlspecialchars($row['stage']) . "</td>
                <td>" . htmlspecialchars($row['status']) . "</td>
                <td>" . htmlspecialchars($row['time_approved']) . "</td>
                <td>" . htmlspecialchars($row['time_rejected']) . "</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "No records found.";
}

$conn->close();

