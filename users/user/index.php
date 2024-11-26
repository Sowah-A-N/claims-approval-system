<?php 
	$pageTitle = "Dashboard";
    include './assets/partials/_head.php';

	//Assign user id to variable
    $userId = $_SESSION['user_id'] ?? "";

	// Function to output full name stored in the session (if available)
    function outputFullName() {
        echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
    }
?>

<body>    
    <div class="container-scroller">

        <?php
            include './assets/partials/_navbar.php';
        ?>

        <div class="container-fluid page-body-wrapper">

            <?php 
                include './assets/partials/_sidebar.php';
            ?>

            <div class="main-panel">
                
                <div class="content-wrapper">
					<?php
						 // Execute queries
						$userClaimCountQuery = "SELECT COUNT(*) FROM claim_details WHERE userId = '{$userId}';";
						$userClaimCountResult = mysqli_query($conn, $userClaimCountQuery);

						$userFlaggedClaimCount = "SELECT COUNT(*) FROM claim_details WHERE userId = '{$userId}' 																					AND completed = 0 AND flagged = 1;";
						$userFlaggedClaimCountResult = mysqli_query($conn, $userFlaggedClaimCount);

						$userCompletedClaimCount = "SELECT COUNT(*) FROM completed_claims WHERE userId = '{$userId}';";
						$userCompletedClaimCountResult = mysqli_query($conn, $userCompletedClaimCount);

						$userInProgressClaimCount = "SELECT COUNT(*) FROM claim_details WHERE userId = '{$userId}'
													AND completed = 0 AND flagged = 0;";
						$userInProgressClaimCountResult = mysqli_query($conn, $userInProgressClaimCount);

						$userSavedClaimCount = "SELECT COUNT(*) FROM saved_claims WHERE userId = '{$userId}';";
						$userSavedClaimCountResult = mysqli_query($conn, $userSavedClaimCount);

						// Fetch results and handle errors
						$totalClaimsCount = 0;
						if ($userClaimCountResult) {
							$totalClaimsCountResult = mysqli_fetch_array($userClaimCountResult);
							$totalClaimsCount = !empty($totalClaimsCountResult[0]) ? $totalClaimsCountResult[0] : 0;
						}

						$flaggedClaimsCount = 0;
						if ($userFlaggedClaimCountResult) {
							$flaggedClaimsCountResult = mysqli_fetch_array($userFlaggedClaimCountResult);
							$flaggedClaimsCount = !empty($flaggedClaimsCountResult[0]) ? $flaggedClaimsCountResult[0] : 0;
						}

						$completedClaimsCount = 0;
						if ($userCompletedClaimCountResult) {
							$completedClaimsCountResult = mysqli_fetch_array($userCompletedClaimCountResult);
							$completedClaimsCount = !empty($completedClaimsCountResult[0]) ? $completedClaimsCountResult[0] : 0;
						}

						$inProgressClaimsCount = 0;
						if ($userInProgressClaimCountResult) {
							$inProgressClaimsCountResult = mysqli_fetch_array($userInProgressClaimCountResult);
							$inProgressClaimsCount = !empty($inProgressClaimsCountResult[0]) ? $inProgressClaimsCountResult[0] : 0;
						}

						$savedClaimsCount = 0;
						if ($userSavedClaimCountResult) {
							$savedClaimsCountResult = mysqli_fetch_array($userSavedClaimCountResult);
							$savedClaimsCount = !empty($savedClaimsCountResult[0]) ? $savedClaimsCountResult[0] : 0;
						}
					?>
					
					 <div class="row">
						<div class="col-xl col-sm-6 stretch-card transparent">
							<div class="card card-tale">
								<div class="card-body">
									<p class="mb-4">Total Claims</p>
									<p class="fs-30 mb-2"><?php echo $totalClaimsCount; ?></p>
								</div>
							</div>
						</div>
						<div class="col-xl col-sm-6 stretch-card transparent">
							<div class="card card-dark-blue">
								<div class="card-body">
									<p class="mb-4">Saved Claims</p>
									<p class="fs-30 mb-2"><?php echo $savedClaimsCount; ?></p>
								</div>
							</div>
						</div>					
                        <div class="col-xl col-sm-6 stretch-card transparent">
                            <div class="card card-tale">
                                <div class="card-body">
                                    <p class="mb-4">In-Progress Claims</p>
                                    <p class="fs-30 mb-2"><?php echo $inProgressClaimsCount; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl col-sm-6 stretch-card transparent">
                            <div class="card card-light-danger">
                                <div class="card-body">
                                    <p class="mb-4">Flagged Claims</p>
                                    <p class="fs-30 mb-2"><?php echo $flaggedClaimsCount; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
					
               	 </div>
                <!-- content-wrapper ends -->
				
				<!--footer_partial-->
				<?php 
                    include './assets/partials/_footer.php'; 
                ?>
			</div>

                <!-- partial:../../partials/_footer.html -->
                
                
               
                    <!-- partial -->
            </div>
            <!-- main-panel ends -->
            
        </div>
        <!-- page-body-wrapper ends -->

    <?php 
        include './assets/partials/_plugins.php';
    ?>

</body>
