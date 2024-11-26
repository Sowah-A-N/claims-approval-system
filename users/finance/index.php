<?php
    //Session include goes here
    $pageTitle = "Finance Dashboard";
?>

<!DOCTYPE html>
<html lang="en">

<?php
    include "./assets/partials/head.php";

	$completedClaimsQuery = "SELECT 
								cd.claimId,
								cd.department, 
								cd.programme, 
								cd.course, 
								'COMPLETED' AS status, 
								CONCAT(user_details.first_name, ' ', user_details.last_name) AS full_name 
							FROM 
								claim_details cd 
							INNER JOIN 
								user_details ON cd.userId = user_details.userId 
							WHERE 
								cd.completed = 1;
						";
	$completedClaimsResult = $conn->query($completedClaimsQuery);
?>

<body>
    <?php include './assets/partials/sidebar.php' ?>

    <!--Body Wrapper -->
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
        data-sidebar-position="fixed" data-header-position="fixed">


        <div class="body-wrapper">
			<?php
				include './assets/partials/header.php';
			?>
			<div class="container-fluid">

                <?php
					if ($completedClaimsResult->num_rows > 0) {
						// Start the HTML table
						echo '<div class="table-responsive">';
						echo '<table class="table table-striped table-bordered">';
						echo '<thead class="thead-light">';
						echo '<tr>';
						echo '<th>Full Name</th>';
						echo '<th>Department</th>';
						echo '<th>Programme</th>';
						echo '<th>Course</th>';
						echo '<th>Status</th>';	
						echo '<th>Payment Approval</th>';
						echo '</tr>';
						echo '</thead>';
						echo '<tbody>';

						// Fetch each row and output it
						while ($row = $completedClaimsResult->fetch_assoc()) {
							echo '<tr>';
							echo '<td>' . htmlspecialchars($row['full_name']) . '</td>';
							echo '<td>' . htmlspecialchars($row['department']) . '</td>';
							echo '<td>' . htmlspecialchars($row['programme']) . '</td>';
							echo '<td>' . htmlspecialchars($row['course']) . '</td>';
							echo '<td>' . htmlspecialchars($row['status']) . '</td>';	
							
							/* Approve Payment button
							echo '<td>';
							echo '<form method="post" action="approvePayment.inc.php">'; // Replace with your action
							echo '<input type="hidden" name="claimId" value="' . htmlspecialchars($row['claimId']) . '">'; // Hidden input to send the claimId
							echo '<button type="submit" class="btn btn-success">Approve Payment</button>';
							echo '</form>';
							echo '</td>';
							*/
							
							// Approve Payment button
							echo '<td>';
							echo '<button type="button" class="btn btn-success" onclick="approvePayment(' . htmlspecialchars($row['claimId']) . ')">Process</button>';
							echo '</td>';
							echo '</tr>';
						}

						echo '</tbody>';
						echo '</table>';
						echo '</div>'; // Close the table-responsive div
					} else {
						echo '<div class="alert alert-warning" role="alert">No completed claims found.</div>';
					}

					// Close the database connection
					$conn->close();

				?>
                <?php ?>
                <?php ?>
				
			</div>
        </div>
    </div>
	
	
	<script>
	function approvePayment(claimId) {
		alert("Payment approved for Claim ID: " + claimId);
	}
	</script>
    
    
    <script src="./assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="./assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/js/sidebarmenu.js"></script>
    <script src="./assets/js/app.min.js"></script>
    <script src="./assets/libs/simplebar/dist/simplebar.js"></script>
</body>

<?php

?>



</html>
