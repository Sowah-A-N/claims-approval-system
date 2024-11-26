<?php
    //Session Include goes here
    $pageTitle = "Approver Dashboard";

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    include_once '../../includes/conn.inc.php';

    // Retrieve approver's stage from session, defaulting to empty string if not set
    $approverStage = $_SESSION['stage'] ?? "";

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
                                    AND claim_details.flagged = 0;";



    // Prepare the statement
    $approverStageClaimsStmt = $conn->prepare($approverStageClaimsQuery);

    // Bind the parameter
    $approverStageClaimsStmt->bind_param('i', $approverStage);

    // Execute the query
    $approverStageClaimsStmt->execute();

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
    include "./assets/partials/head.php";

        ?>

<body>
    <!--Body Wrapper -->
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
        data-sidebar-position="fixed" data-header-position="fixed">
        
        <?php include './assets/partials/sidebar.php' ?>
        
        <div class="body-wrapper">
            <?php include './assets/partials/header.html'; ?>

            <h5 class="card-title fw-semibold mb-4">Pending Claims</h5>

            <div class="col-lg-12 d-flex align-items-stretch">
				<div class="card">
					<div class="card-body p-8">
						<div class="table-responsive">
							<table class="table text-nowrap mb-0 align-middle">
								<thead class="text-dark fs-4">
									<tr>
										<th class="border-bottom-0">Claim ID</th>
										<th class="border-bottom-0">Claimant</th>
										<th class="border-bottom-0">Department</th>
										<th class="border-bottom-0">Programme</th>
										<th class="border-bottom-0">Course</th>
										<th class="border-bottom-0">Date Submitted</th>
										<th class="border-bottom-0">View Details</th>
									</tr>
								</thead>
								<tbody>
									<?php if (!empty($claims)) : ?>
										<?php foreach ($claims as $claim) : ?>
											<tr id="row_<?php echo $claim['claimId']; ?>">
												<td><?php echo $claim['claimId']; ?></td>
												<td><?php echo htmlspecialchars($claim['full_name']); ?></td>
												<td><?php echo htmlspecialchars($claim['department']); ?></td>
												<td><?php echo htmlspecialchars($claim['programme']); ?></td>
												<td><?php echo htmlspecialchars($claim['course']); ?></td>
												<td><?php echo date('d-m-Y', strtotime($claim['time_submitted'])); ?></td>
												<td>
													<button class="btn btn-info" onclick="viewDetails('<?php echo $claim['claimId']; ?>')">View Details</button>
												</td>
											</tr>
										<?php endforeach; ?>
									<?php else : ?>
										<tr>
											<td colspan="7" class="text-center">No claims found</td>
										</tr>
									<?php endif; ?>
								</tbody>

							</table>
						</div> <!-- Close table-responsive -->
					</div> <!-- Close card-body -->
				</div> <!-- Close card -->
			</div> <!-- Close col-lg-12 -->
        </div> <!-- Close body-wrapper -->
    </div> <!-- Close page-wrapper -->

 <!-- Claim Details Modal -->
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
                <!-- Details for the claim will be dynamically loaded here -->
            </div>
            <div class="modal-footer">
                <form id="manageClaimForm" method="post">
                    <input type="hidden" id="claimId" name="claimId" value="">
                    <button type="button" onclick="approveClaim()" class="btn btn-success btn-lg">Approve</button>
                    <button type="button" onclick="showFlagForm()" class="btn btn-danger btn-lg">Flag</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </form>
            </div>
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
     function viewDetails(claimId) {
    $.ajax({
        url: 'getClaimDetails1.inc.php', // Change this to your PHP script URL
        type: 'GET',
        data: { claimId: claimId },
        success: function(response) {
            // Assuming the response contains HTML content for the modal body
            $('#detailsModal .modal-body').html(response);
            $('#detailsModal').modal('show');

            // Set claimId in a hidden input field for later use
            $('#claimId').val(claimId);
        },
        error: function(xhr, status, error) {
            console.error(error);
            alert('An error occurred while fetching claim details.');
        }
    });
}

function approveClaim() {
    var claimId = $('#claimId').val();
    console.log("Approving claim with ID: " + claimId);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'approveClaim.inc.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.send('claimId=' + encodeURIComponent(claimId));

    xhr.onload = function() {
        if (xhr.status === 200) {
            console.log("Claim approved successfully!");
            alert("Claim approved successfully!");
            $('#detailsModal').modal('hide');
            window.location.reload();
        } else {
            console.log("Error approving claim: " + xhr.statusText);
        }
    };
    xhr.onerror = function() {
        console.error("Network error occurred while trying to approve claim");
        alert("Network error occurred while trying to approve claim");
    };
}

function showFlagForm() {
    var claimId = $('#claimId').val(); // Make sure this is correctly populated
	console.log("Approving claim with ID: " + claimId);

    $('#detailsModal .modal-body').html(`
        <p>Enter reason for flagging this claim:</p>
        <textarea id="flagReason" rows="4" class="form-control"></textarea>
    `);

    $('.modal-footer').html(`
        <button type="button" onclick="flagClaim(${claimId})" class="btn btn-warning btn-lg">Flag</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
    `);
}

function flagClaim(claimId) {
    //var claimId = $('#claimId').val(); // Get the claim ID from the hidden input
	var claimId = claimId;
    var flagReason = $('#flagReason').val();

    if (!claimId || claimId === 'undefined') {
        alert('Invalid claim ID.');
        return;
    }

    if (flagReason.trim() === '') {
        alert('Please enter a reason for flagging.');
        return;
    }

    var formData = new FormData();
    formData.append('claimId', claimId);
    formData.append('flagReason', flagReason);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'flagClaim.inc.php', true);

    xhr.onload = function() {
        if (xhr.status === 200) {
            var response = JSON.parse(xhr.responseText);
            if (response.success) {
                alert(response.message);
                $('#detailsModal').modal('hide');
                window.location.reload();
            } else {
                alert(response.message);
                $('#detailsModal').modal('hide');
            }
        } else {
            alert('An error occurred: ' + xhr.statusText);
            $('#detailsModal').modal('hide');
        }
    };
    xhr.onerror = function() {
        alert('Network error occurred while trying to flag claim');
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