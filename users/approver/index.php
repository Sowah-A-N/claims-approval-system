<?php	
    //Session Include goes here
    $pageTitle = "Approver Dashboard";

    include "./assets/partials/head.php";

    include_once '../../includes/conn.inc.php';

    // Retrieve approver's stage from session, defaulting to empty string if not set
    $approverStage = $_SESSION['stage'] ?? "";
	$approverDepartment = $_SESSION['dept'] ?? "";

    // Query to fetch claims with user details
    // $approverStageClaimsQuery = "SELECT claim_details.*, CONCAT(user_details.first_name, ' ', user_details.last_name) AS full_name
    //                             FROM claim_details
    //                             INNER JOIN (
    //                                 SELECT claimId, MAX(stage) AS max_stage
    //                                 FROM claim_approval_stages
    //                                 GROUP BY claimId
    //                             ) AS latest_stage ON claim_details.claimId = latest_stage.claimId
    //                             INNER JOIN claim_approval_stages ON claim_details.claimId = claim_approval_stages.claimId
    //                                 AND latest_stage.max_stage = claim_approval_stages.stage
    //                             INNER JOIN user_details ON claim_details.userId = user_details.userId
    //                             WHERE claim_approval_stages.stage = ? 
    //                             AND claim_details.flagged = 0";

    // $approverStageClaimsQuery = "SELECT claim_details.*, CONCAT(user_details.first_name, ' ', user_details.last_name) AS full_name
    //                             FROM claim_details
    //                             INNER JOIN claim_approval_stages ON claim_details.claimId = claim_approval_stages.claimId
    //                             INNER JOIN user_details ON claim_details.userId = user_details.userId
    //                             WHERE claim_approval_stages.stage = ?
    //                             AND claim_details.flagged = 0";  // Exclude flagged claims


	echo '<script>alert("' . $approverStage . ' // ' . $approverDepartment . '");</script>';

	if (isset($approverDepartment) && $approverDepartment !== "") {
		
		$approverStageClaimsQuery = "SELECT claim_details.*, CONCAT(user_details.first_name, ' ', user_details.last_name) AS full_name
                                    FROM claim_details
                                    INNER JOIN (SELECT claimId, MAX(stage) AS max_stage
                                                FROM claim_approval_stages
                                                GROUP BY claimId)
                                    AS latest_stage 
                                    ON claim_details.claimId = latest_stage.claimId
                                    INNER JOIN claim_approval_stages 
                                    ON claim_details.claimId = claim_approval_stages.claimId
                                    AND latest_stage.max_stage = claim_approval_stages.stage
                                    INNER JOIN user_details 
                                    ON claim_details.userId = user_details.userId
                                    WHERE claim_approval_stages.stage = ? 
                                    AND claim_details.flagged = 0
                                    AND claim_details.department = ?;";
	
		// Prepare the statement
		$approverStageClaimsStmt = $conn->prepare($approverStageClaimsQuery);

		// Bind the parameter
		$approverStageClaimsStmt->bind_param('is', $approverStage, $approverDepartment);
		
	} else {
		
		 $approverStageClaimsQuery = /*"SELECT claim_details.*, CONCAT(user_details.first_name, ' ', user_details.last_name) AS full_name
                              FROM claim_details
                              INNER JOIN user_details ON claim_details.userId = user_details.userId
                              WHERE claim_details.flagged = 0";*/
 "SELECT claim_details.*, CONCAT(user_details.first_name, ' ', user_details.last_name) AS full_name
                                    FROM claim_details
                                    INNER JOIN (SELECT claimId, MAX(stage) AS max_stage
                                                FROM claim_approval_stages
                                                GROUP BY claimId)
                                    AS latest_stage 
                                    ON claim_details.claimId = latest_stage.claimId
                                    INNER JOIN claim_approval_stages 
                                    ON claim_details.claimId = claim_approval_stages.claimId
                                    AND latest_stage.max_stage = claim_approval_stages.stage
                                    INNER JOIN user_details 
                                    ON claim_details.userId = user_details.userId
                                    WHERE claim_approval_stages.stage = ? 
                                    AND claim_details.flagged = 0;";
		
		 // Prepare the statement
   		 $approverStageClaimsStmt = $conn->prepare($approverStageClaimsQuery);

		// Bind the parameter
		$approverStageClaimsStmt->bind_param('i', $approverStage);
	
	}
   

    // Execute the query
    $approverStageClaimsStmt->execute();

	if (!$approverStageClaimsStmt->execute()) {
		die("Execution failed: " . $approverStageClaimsStmt->error);
	}

    // Get the result
    $approverStageClaimsResult = $approverStageClaimsStmt->get_result();

    if (!$approverStageClaimsResult) {
        die("Query failed: " . mysqli_error($conn));
    }


    // Fetch all results
    $claims = $approverStageClaimsResult->fetch_all(MYSQLI_ASSOC);

    // Close the statement
    $approverStageClaimsStmt->close();

    // Close the connection
    $conn->close();

    //$approverStageClaimsResult = mysqli_query($conn, $approverStageClaimsQuery);

    // Check if query execution failed
    // if (!$approverStageClaimsResult) {
    //     die("Query failed: " . mysqli_error($conn));
    // }

    // Fetch all claims into an array
    //$claims = mysqli_fetch_all($approverStageClaimsResult, MYSQLI_ASSOC);

    // Include the header and sidebar HTML files

        ?>

<body>
    <!--Body Wrapper -->
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
        data-sidebar-position="fixed" data-header-position="fixed">
        
        <?php include './assets/partials/sidebar.php' ?>
        
        <div class="body-wrapper">
            <?php include './assets/partials/header.html'; ?>
			
			<div class="container-fluid">
						
                <br /><br />
                <h5 class="card-title fw-semibold mb-4">Pending Claims</h5>
                <?php 
                    if ($_SESSION['stage'] && $_SESSION['stage'] !== 1): 
                ?>
                    <div class="mx-auto" style="width: 25%;">
                        <label for="departmentFilter">Filter by Department:</label>
                        <select id="departmentFilter" class="form-select" onchange="filterTable()">
                            <option value="">All Departments</option>
                            <?php
                            // Collect unique departments from claims
                            $departments = array_unique(array_column($claims, 'department'));
                            foreach ($departments as $department) {
                                echo "<option value='" . htmlspecialchars($department) . "'>" . htmlspecialchars($department) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <div class="col-lg-12 d-flex align-items-stretch">
                    <div class="card">
                        <div class="card-body p-8">
                            <div class="table-responsive">
                                <table class="table text-nowrap mb-0 align-middle">
                                    <thead class="text-dark fs-4">
                                        <tr>
                                            <th class="border-bottom-0">S/N</th>
                                            <th class="border-bottom-0">Claimant</th>
                                            <!--th class="border-bottom-0">Department</th>
                                            <th class="border-bottom-0">Programme</th-->
                                            <th class="border-bottom-0">Course</th>
                                            <th class="border-bottom-0">Date Submitted</th>
                                            <th class="border-bottom-0">View Details</th>
                                            <th class="border-bottom-0">Approve</th>
                                            <th class="border-bottom-0">Flag</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($claims)) : 
                                            $index = 1;
                                        ?>
                                        <?php foreach ($claims as $claim) : ?>
                                            <tr data-department="<?php echo htmlspecialchars($claim['department']); ?>" id="<?php echo $claim['claimId']; ?>">                                            
                                                <td><?php echo $index; ?></td>
                                                <td><?php echo htmlspecialchars($claim['full_name']); ?></td>
                                                <!--<td><?php echo htmlspecialchars($claim['department']); ?></td>
                                                <td><?php echo htmlspecialchars($claim['programme']); ?></td>-->
                                                <td><?php echo htmlspecialchars($claim['course']); ?></td>
                                                <td><?php echo date('d-m-Y', strtotime($claim['time_submitted'])); ?></td>
                                                <td>
                                                    <button class="btn btn-info" onclick="viewDetails('<?php echo $claim['claimId']; ?>')">View Details</button>
                                                </td>
                                                <td>
                                                    <button class="btn btn-success" onclick="approve('<?php echo $claim['claimId']; ?>')">Approve</button>
                                                </td>
                                                <td>
                                                    <button class="btn btn-danger" onclick="openFlagModal('<?php echo $claim['claimId']; ?>')">Flag</button>
                                                </td>
                                            </tr>

                                            <!-- Modal for flagging -->
                                            <div id="flagModal_<?php echo $claim['claimId']; ?>" class="modal" tabindex="-1" role="dialog">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Flag Claim</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Enter reason for flagging claim:</p>
                                                            <textarea id="flagReason_<?php echo $claim['claimId']; ?>" rows="4" class="form-control"></textarea>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                            <button type="button" class="btn btn-danger" onclick="flag('<?php echo $claim['claimId']; ?>')">Flag</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php 
                                            $index++;
                                            endforeach; 
                                        ?>

                                        <?php else : ?>
                                            <tr>
                                                <td colspan="9" class="text-center">No claims found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div> <!-- Close table-responsive -->
                        </div> <!-- Close card-body -->
                    </div> <!-- Close card -->
                </div> <!-- Close col-lg-8 -->

                <?php
					echo '<pre>';
                        var_dump($_SESSION);
					echo '</pre>';
					
				?>	

		   </div> <!--Close container-fluid-->
        </div> <!-- Close body-wrapper -->
    </div> <!-- Close page-wrapper -->

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
                    <!--Details for the claim will be shown here from the PHP response -->
                </div>
				<!--div class="container mt-3">
					<form id="approveForm" method="post" action="approveClaim.inc2.php">
						<input type="hidden" name="claimId" value="<?php //echo $claim['claimId']; ?>">
						<button type="submit" name="action" value="approve" class="btn btn-success btn-lg">Approve</button>
						<br /><br />
					</form>
				</div-->

            </div>
        </div>
    </div>

    <!-- Flag Claim Modal -->
    <div id="flagModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Flag Claim</h5>
                    <span type="button" class="close" data-dismiss="modal">&times;</span>
                </div>
                <div class="modal-body">
                    <label for="flagReason">Reason for Flagging:</label>
                    <textarea id="flagReason" class="form-control"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="flagClaim()">Flag</button>
                </div>
            </div>
        </div>
    </div>


    <script>
		
		function filterTable() {
            const filter = document.getElementById('departmentFilter').value;
            const rows = document.querySelectorAll('#claimsTable tbody tr');

            rows.forEach(row => {
                const department = row.getAttribute('data-department');
                if (filter === '' || department === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
		
        function viewDetails(claimId) {
            // AJAX request to fetch additional information about the claim
            $.ajax({
                url: 'getClaimDetails.inc.php', // Change this to your PHP script URL
                type: 'GET',
                data: {
                    claimId: claimId
                },
                success: function(response) {
                    // Assuming the response contains HTML content for the modal body
                    $('#detailsModal .modal-body').html(response);
                    $('#detailsModal').modal('show');
                },
                error: function(xhr, status, error) {
                    console.error(error);
                    alert('An error occurred while fetching claim details.');
                }
            });
        }

        function approve(claimId) {
            console.log("Approving claim with ID: " + claimId);
            
            // Create an AJAX request
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'approveClaim.inc2.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            // Send the claim ID as a POST parameter
            xhr.send('claimId=' + claimId);
            
            // Handle the response
            xhr.onload = function() {
                if (xhr.status === 200) {
                console.log("Claim approved successfully!");
                alert("Claim approved successfully!");
                window.location.reload(); // Refresh the page
                } else {
                console.log("Error approving claim: " + xhr.statusText);
                }
            };
        }
        
        // function flag(claimId) {
        //     console.log("Flag clicked for claim ID: " + claimId);
            
        //     // Create a FormData object to send data
        //     var formData = new FormData();
        //     formData.append('claimId', claimId);

        //     // Create a new XMLHttpRequest object
        //     var xhr = new XMLHttpRequest();

        //     // Configure it: POST-request for the URL 'flagClaim.inc.php'
        //     xhr.open('POST', 'flagClaim.inc.php', true);

        //     // Handle the response
        //     xhr.onload = function() {
        //         if (xhr.responseText.trim() === "Claim flagged successfully!") {
        //             console.log("Claim has been flagged!");
        //             alert("Claim has been flagged!");
        //         } else {
        //             console.log("Error flagging claim: " + xhr.responseText);
        //             alert("Error flagging claim: " + xhr.responseText);
        //         }
        //     };

        //     // Handle network errors
        //     xhr.onerror = function() {
        //         console.error("Network error occurred while trying to flag claim");
        //         alert("Network error occurred while trying to flag claim");
        //     };

        //     // Send the request with the form data
        //     xhr.send(formData);
        // }

        // Function to open flag modal
        function openFlagModal(claimId) {
            $('#flagModal_' + claimId).modal('show');
        }

        // Function to flag claim
        function flag(claimId) {
            var flagReason = $('#flagReason_' + claimId).val();

            // Perform validation if needed
            if (flagReason.trim() === '') {
                alert('Please enter a reason for flagging.');
                return;
            }

            // Create FormData object
            var formData = new FormData();
            formData.append('claimId', claimId);
            formData.append('flagReason', flagReason);

            // Create XMLHttpRequest object
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'flagClaim.inc.php', true);
            xhr.onload = function() {
                if (xhr.responseText.trim() === "Claim flagged successfully!") {
                    console.log("Claim has been flagged!");
                    alert("Claim has been flagged!");
                    $('#flagModal_' + claimId).modal('hide'); // Hide modal after 
                    window.location.reload(); // Refresh the page
                } else {
                    console.log("Error flagging claim: " + xhr.responseText);
                    alert(xhr.responseText);
                    $('#flagModal_' + claimId).modal('hide'); // Hide modal after flagging
                    window.location.reload(); // Refresh the page


                }
            };
            xhr.onerror = function() {
                console.error("Network error occurred while trying to flag claim");
                alert("Network error occurred while trying to flag claim");
            };
            xhr.send(formData);
        }

    </script>

    <script src="./assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="./assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/js/sidebarmenu.js"></script>
    <script src="./assets/js/app.min.js"></script>
    <script src="./assets/libs/simplebar/dist/simplebar.js"></script>
</body>
</html>