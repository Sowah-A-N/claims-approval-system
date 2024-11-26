<?php
    //Session include goes here
    $pageTitle = "Reports";
?>

<!DOCTYPE html>
<html lang="en">

<?php
    include "../../assets/partials/head.php";
?>

<body>
    <?php   
        include '../../assets/partials/sidebar.php';
        include '../../assets/partials/header.php';

    ?>

    <!--Body Wrapper -->
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
        data-sidebar-position="fixed" data-header-position="fixed">

        <div class="body-wrapper">

            <div class="container-fluid">
             
                   <form method="GET" action="">
					 <div class="row form-group">
                        <!-- Department Filter -->
                        <div class="col-md-3">
                        <label for="department">Department:</label>
                        <select name="department" id="department" class="form-control">
                            <option value="">Select Department</option>
                            <?php
                            // Fetch unique departments from claim_details
                            $query = "SELECT DISTINCT department FROM claim_details";
                            $result = $conn->query($query);
                            while ($row = $result->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($row['department']) . '">' . 			htmlspecialchars($row['department']) . '</option>';
                            }
                            ?>
                        </select>
                        </div>

                        <!-- Programme Filter -->
                        <div class="col-md-3">
                        <label for="programme">Programme:</label>
                        <select name="programme" id="programme" class="form-control">
                            <option value="">Select Programme</option>
                            <?php
                            // Fetch unique programmes from claim_details
                            $query = "SELECT DISTINCT programme FROM claim_details";
                            $result = $conn->query($query);
                            while ($row = $result->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($row['programme']) . '">' . htmlspecialchars($row['programme']) . '</option>';
                            }
                            ?>
                        </select>
                        </div>

                        <!-- Course Filter -->
                        <div class="col-md-3">
                        <label for="course">Course:</label>
                        <select name="course" id="course" class="form-control">
                            <option value="">Select Course</option>
                            <?php
                            // Fetch unique courses from claim_details
                            $query = "SELECT DISTINCT course FROM claim_details";
                            $result = $conn->query($query);
                            while ($row = $result->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($row['course']) . '">' . htmlspecialchars($row['course']) . '</option>';
                            }
                            ?>
                        </select>
                        </div>

                        <!-- Stage Filter -->
                        <div class="col-md-3">
                        <label for="stage">Stage:</label>
                        <select name="stage" id="stage" class="form-control">
                            <option value="">Select Stage</option>
                            <?php
                            // Fetch unique stages from claim_approval_stages
                            $query = "SELECT DISTINCT stage FROM claim_approval_stages";
                            $result = $conn->query($query);
                            while ($row = $result->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($row['stage']) . '">' . htmlspecialchars($row['stage']) . '</option>';
                            }
                            ?>
                        </select>
                        </div>

                        <!-- Status Filter -->
                         <div class="col-md-3">
                         <label for="status">Status:</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">Select Status</option>
                                <option value="Pending" <?php if (isset($_GET['status']) && $_GET['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
								<option value="Completed" <?php if (isset($_GET['status']) && $_GET['status'] == 'Completed') echo 'selected'; ?>>Completed</option>
                                <option value="Approved" <?php if (isset($_GET['status']) && $_GET['status'] == 'Approved') echo 'selected'; ?>>Approved</option>
                                <option value="Flagged" <?php if (isset($_GET['status']) && $_GET['status'] == 'Flagged') echo 'selected'; ?>>Flagged</option>
                            </select>
                        </div>

                        <!-- Date Range Filters -->
                        <div class="col-md-3">
                            <label for="start_date">Start Date:</label>
                            <input type="date" name="start_date" id="start_date" class="form-control"
								   value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : ''; ?>">
						 </div>
                            
						<div class="col-md-3">
                            <label for="end_date">End Date:</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" 
								   value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : ''; ?>">
                        </div>

                        <!-- Filter Button -->
						<div class="col-md-3">						 
                       		 <br /><input class="btn btn-info" type="submit" value="Filter">
						 </div>
					   </div>	 
                    </form>
                </div>

                <?php
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
                
                    // Date range filters
                    if (!empty($_GET['start_date'])) {   $start_date = $conn->real_escape_string($_GET['start_date']);
                        $query .= " AND cd.time_submitted >= '$start_date'";
                    }
                
                    if (!empty($_GET['end_date'])) {
                        $end_date = $conn->real_escape_string($_GET['end_date']);
                        $query .= " AND cd.time_submitted <= '$end_date'";
                    }
                
                    $result = $conn->query($query);
                
                    
                    if ($result->num_rows > 0) {
						echo '<div class="container">';
                        echo '<table class="table table-bordered table-striped">';
                        echo '<thead class="thead-dark">';
                        echo '<tr>';
                        echo '<th scope="col">Claim ID</th>';
                        //echo '<th scope="col">User ID</th>';
                        echo '<th scope="col">Department</th>';
                        echo '<th scope="col">Programme</th>';
                        echo '<th scope="col">Course</th>';
                        echo '<th scope="col">Flagged</th>';
                        echo '<th scope="col">Completed</th>';
                        //echo '<th scope="col">Time Submitted</th>';
                        echo '<th scope="col">Stage</th>';
                        echo '<th scope="col">Status</th>';
                        echo '<th scope="col">Time Approved</th>';
                        echo '<th scope="col">Time Rejected</th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';
                        
                        while ($row = $result->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['claimId']) . '</td>';
                            //echo '<td>' . htmlspecialchars($row['userId']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['department']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['programme']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['course']) . '</td>';
                            echo '<td>' . ($row['flagged'] ? 'Yes' : 'No') . '</td>';
                            echo '<td>' . ($row['completed'] ? 'Yes' : 'No') . '</td>';
                            //echo '<td>' . htmlspecialchars($row['time_submitted']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['stage']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['status']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['time_approved']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['time_rejected']) . '</td>';
                            echo '</tr>';
                        }

                    } else {
                        echo '<div class="alert alert-warning" role="alert">No records found.</div>';
                    }			
			
                        echo '</tbody>';
                        echo '</table>';
						echo '</div>';
                
                    $conn->close();
                ?>
                <?php ?>
                <?php ?>
            </div>
        </div>
    </div>
    
    
    <script src="../../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sidebarmenu.js"></script>
    <script src="../../assets/js/app.min.js"></script>
    <script src="../../assets/libs/simplebar/dist/simplebar.js"></script>
</body>

<?php

?>



</html>
