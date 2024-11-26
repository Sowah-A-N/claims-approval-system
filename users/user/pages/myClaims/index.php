<?php
  session_start();

$pageTitle = "My Claims";
$userId = $_SESSION['user_id'] ?? "";
// Include head partial
include_once "../../assets/partials/_head.php"; 

// Function to output full name stored in the session (if available)
function outputFullName() {
    echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
}

// Query strings
$queries = [
    'inProgressClaims' => "SELECT * FROM claim_details WHERE userId = '{$userId}'",
    'savedClaims' => "SELECT *, 'Saved' AS status FROM saved_claims WHERE userId = '{$userId}'",
    'flaggedClaims' => "SELECT *, 'Flagged' AS status FROM claim_details WHERE userId = '{$userId}' AND flagged = 1",
    'pendingClaims' => "SELECT * FROM claim_details 
						WHERE userId = '{$userId}' 
						AND flagged <> 1 
						AND completed <> 1  
						ORDER BY claimId DESC;
						",
    'completedClaims' => "SELECT *, 'Forwarded to Finance' AS status FROM claim_details WHERE userId = '{$userId}' AND completed = 1"
];

$results = [];

foreach ($queries as $key => $query) {
    $results[$key] = $conn->query($query);

    if (!$results[$key]) {
        die("Query failed: " . $conn->error);
    }
}
?>

<body>
    <div class="container-scroller">
            <?php include "../../assets/partials/_navbar.php"; ?>

        <div class="container-fluid page-body-wrapper">
			<?php include "../../assets/partials/_sidebar.php"; ?>


            <div class="main-panel">
                <div class="content-wrapper">
                    <h2>My Claims</h2>

                    <?php
					
					// Display Flagged Claims
                    echo '<div class="col-lg-12 stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Flagged Claims</h4>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-contextual">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Department</th>
                                                    <th>Programme</th>
                                                    <th>Course</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>';

                    if ($results['flaggedClaims']->num_rows > 0) {
                        while ($row = $results['flaggedClaims']->fetch_assoc()) {
                            echo '<tr id="' . $row['claimId'] . '">';
                            echo '<td>' . $row['claimId'] . '</td>';
                            echo '<td>' . $row['department'] . '</td>';
                            echo '<td>' . $row['programme'] . '</td>';
                            echo '<td>' . $row['course'] . '</td>';
                            echo '<td>' . $row['status'] . '</td>';
                            echo '<td>
                                    <span class="mdi mdi-eye-outline" style="font-size:1.8rem; cursor:pointer;" onclick="viewClaimDetails(' . $row['claimId'] . ')"></span>
                                  </td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="6">No Flagged Claims Found</td></tr>';
                    }

                    echo '   </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div><br />';
					
					 // Display Pending Claims
                    echo '<div class="col-lg-12 stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Pending Claims</h4>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-contextual">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Department</th>
                                                    <th>Programme</th>
                                                    <th>Course</th>
													<th>Date Submitted</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>';

                    if ($results['pendingClaims']->num_rows > 0){ 
						$index = 1;
                        while ($row = $results['pendingClaims']->fetch_assoc()) {
                            echo '<tr id="' . $row['claimId'] . '">';
                            echo '<td>' . $index . '</td>';
                            echo '<td>' . $row['department'] . '</td>';
                            echo '<td>' . $row['programme'] . '</td>';
                            echo '<td>' . $row['course'] . '</td>';
							echo '<td>' . date('d/m/Y', strtotime($row['time_submitted'])) . '</td>';
							echo '<td>
                                    <span class="mdi mdi-eye-outline" style="font-size:1.8rem; cursor:pointer;" 			onclick="viewClaimDetails(' . $row['claimId'] . ')"></span>
                                  </td>';
                            echo '</tr>';
							$index++;
                        }
                    } else {
                        echo '<tr><td colspan="6">No Pending Claims Found</td></tr>';
                    }

                    echo '   </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div><br />';
						
					// Display Saved Claims
					echo '<div class="col-lg-12 stretch-card">
								<div class="card">
									<div class="card-body">
										<h4 class="card-title">Saved Claims</h4>
										<div class="table-responsive">
											<table class="table table-bordered table-contextual">
												<thead>
													<tr>
														<th>#</th>
														<th>Department</th>
														<th>Programme</th>
														<th>Course</th>
														<th>Date Saved</th>
														<th>Status</th>
														<th>Actions</th>
													</tr>
												</thead>
												<tbody>';
			
					if ($results['savedClaims']->num_rows > 0) {
						while ($row = $results['savedClaims']->fetch_assoc()) {
							echo '<tr id="' . $row['claimTempId'] . '">';
							echo '<td>' . $row['claimTempId'] . '</td>';
							echo '<td>' . $row['department'] . '</td>';
							echo '<td>' . $row['programme'] . '</td>';
							echo '<td>' . $row['course'] . '</td>';
							echo '<td>' . date('d-m-Y', strtotime($row['date_saved'])) . '</td>';
							echo '<td>' . $row['status'] . '</td>';
							echo '<td>
									<span class="mdi mdi-file-edit-outline" style="font-size:2rem; cursor:pointer;" onclick="editClaim(' . $row['claimTempId'] . ')"></span>
									<span class="mdi mdi-delete" style="font-size:2rem; cursor:pointer;" onclick="deleteClaim(' . $row['claimTempId'] . ')"></span>
								  </td>';
							echo '</tr>';
						}
						
					} else {
						echo '<tr><td colspan="6">No Saved Claims Found</td><tr>';
					}
					
					echo '   </tbody>
								</table>
							</div>
						</div>
					</div>
					</div><br />';

					// Display Completed Claims
					echo '<div class="col-lg-12 stretch-card">
							<div class="card">
								<div class="card-body">
									<h4 class="card-title">Completed Claims</h4>
									<div class="table-responsive">
										<table class="table table-bordered table-contextual">
											<thead>
												<tr>
													<th>#</th>
													<th>Department</th>
													<th>Programme</th>
													<th>Course</th>
													<th>Status</th>
													<th>Date Completed</th>
													<th>Actions</th>
												</tr>
											</thead>
											<tbody>';

					if ($results['completedClaims']->num_rows > 0) {
						while ($row = $results['completedClaims']->fetch_assoc()) {
							echo '<tr id="' . $row['claimId'] . '">';
							echo '<td>' . $row['claimId'] . '</td>';
							echo '<td>' . $row['department'] . '</td>';
							echo '<td>' . $row['programme'] . '</td>';
							echo '<td>' . $row['course'] . '</td>';
							echo '<td>' . $row['status'] . '</td>';
							echo '<td>' . date('d/m/Y', strtotime($row['time_submitted'])) . '</td>';
							echo '<td>
									<span class="mdi mdi-eye-outline" style="font-size:2rem; cursor:pointer;" onclick="viewClaimDetails(' . $row['claimId'] . ')"></span>
									<span class="mdi mdi-download" style="font-size:2rem; cursor:pointer;" onclick="downloadClaimDetails(' . $row['claimId'] . ')"></span>
								  </td>';
							echo '</tr>';
						}
					} else {
						echo '<tr><td colspan="6">No Completed Claims Found</td></tr>';
					}

					echo '   </tbody>
							</table>
						</div>
					</div>
					</div>
					</div><br />';


                   

                    ;
                    ?>

                    <!--Claim Details Modal -->
                    <div class="modal fade" id="detailsModal" tabindex="-1" role="dialog" aria-labelledby="detailsModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="detailsModalLabel">Claim Details</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <!--div class="form-group">
                                        <label for="claimId" class="col-form-label">Claim ID:</label>
                                        <input type="text" class="form-control" id="claimId" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="programme" class="col-form-label">Programme:</label>
                                        <input type="text" class="form-control" id="programme" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="course" class="col-form-label">Course:</label>
                                        <input type="course" class="form-control" id="course" readonly>
									</div-->
                                    <!-- Add more input fields for additional user details -->
                                </div>
                            </div>
                        </div>
                    </div>

						<div>
					</div>
				</div>
			</div>
		</div>
					<?php include "../../assets/partials/_footer.php"; ?>

	</div>



<script>
   function editClaim(claimId) {
        // $.ajax({
        //     url: 'editClaimDetails.inc.php',
        //     type: 'GET',
        //     data: { claimId: claimId },
        //     success: function(response) {
        //         $('#detailsModal .modal-body').html(response);
        //         $('#detailsModal').modal('show');
        //     },
        //     error: function(xhr, status, error) {
        //         console.error(error);
        //         alert('An error occurred while fetching claim details.');
        //     }
        // });
        //console.log(window.location.pathname);
        window.location.assign("../fileNewClaim/index.php?claimTempId=" + claimId);
    }

    function addNewRow() {
        const newRow = `
            <tr>
                <td><input type="time" class="form-control" name="start_time[]"></td>
                <td><input type="time" class="form-control" name="end_time[]"></td>
                <td><input type="text" class="form-control" name="periods[]"></td>
                <td><button type="button" class="btn btn-danger btn-sm delete-row">Delete</button></td>
            </tr>`;
        $('#claimDataRows').append(newRow);
    }

    function saveClaimDetails() {
        $.ajax({
            url: 'saveClaimDetails.inc.php',
            type: 'POST',
            data: $('#claimDetailsForm').serialize(),
            success: function(response) {
                alert("Changes saved successfully!");
                $('#detailsModal').modal('hide');
            },
            error: function(xhr, status, error) {
                alert("An error occurred while saving the claim details.");
            }
        });
    }

    function submitClaimDetails() {
        $.ajax({
            url: 'submitClaimDetails.inc.php',
            type: 'POST',
            data: $('#claimDetailsForm').serialize(),
            success: function(response) {
                alert("Claim submitted successfully!");
                $('#detailsModal').modal('hide');
            },
            error: function(xhr, status, error) {
                alert("An error occurred while submitting the claim.");
            }
        });
    }

    //Function to view claim details
    function viewClaimDetails(claimId){
        console.log("Viewing claim details...");
        $.ajax({
            url: 'viewClaimDetails.inc.php',
            type: 'GET',
            data: { claimId: claimId },
            success: function(response) {
                $('#detailsModal .modal-body').html(response);
                $('#detailsModal').modal('show');
            },
            error: function(xhr, status, error) {
                console.error(error);
                alert('An error occurred while fetching claim details.');
            }
        });
    }

   //Function to delete a claim
   function deleteClaim(claimId) {
        // Ask for confirmation before deleting the claim
        var confirmation = confirm("Are you sure you want to delete this claim?");

        if (confirmation) {
            $.ajax({
                url: 'deleteClaim.inc.php',
                type: 'POST',
                dataType: 'json',
                data: { claimId: claimId },
                success: function(response) {
                    alert(response.success); // or handle success message
                },
                error: function(xhr, status, error) {
                    alert("Error deleting claim: " + xhr.responseText); // or handle error
                }
            });
        } else {
            alert("Claim deletion canceled.");
        }
    }
   
    //Function to download claim as filled out document
    function downloadClaimDetails(claimId) {
    $.ajax({
        url: 'downloadClaim.inc.php', // Your PHP script URL
        type: 'POST',
        data: { claimId: claimId },
        xhrFields: {
            responseType: 'blob' // Ensure the response is treated as a binary file
        },
        success: function(response, status, xhr) {
            var filename = xhr.getResponseHeader('Content-Disposition').split('filename=')[1].split(';')[0];
            var blob = new Blob([response], { type: xhr.getResponseHeader('Content-Type') });
            var link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = filename;
            link.click();
        },
        error: function(xhr, status, error) {
            alert("There was an error. Please try again later!");
        }
    });
}


   document.getElementById('modalNewRow').addEventListener("click", function(e){
    e.preventDefault();
    alert("New row added in modal...");
   })


</script>


    <!-- plugins:js -->
    <script src="../../assets/vendors/js/vendor.bundle.base.js"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <script src="../../assets/vendors/progressbar.js/progressbar.min.js"></script>
    <script src="../../assets/vendors/jvectormap/jquery-jvectormap.min.js"></script>
    <script src="../../assets/vendors/jvectormap/jquery-jvectormap-world-mill-en.js"></script>
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="../../assets/js/off-canvas.js"></script>
    <script src="../../assets/js/misc.js"></script>
    <script src="../../assets/js/settings.js"></script>
    <script src="../../assets/js/todolist.js"></script>
    <!-- endinject -->
    <!-- Custom js for this page -->


    
</body>
</html>

    
