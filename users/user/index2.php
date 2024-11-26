<?php
    $pageTitle = "User Dashboard";

    // Include head partial
    include_once "./assets/partials/head.php"; 

	//Assign user id to variable
    $userId = $_SESSION['user_id'] ?? "";

	// Function to output full name stored in the session (if available)
    function outputFullName() {
        echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
    }

	//echo '<script>alert('. $userId .')</script>';
?>


<body>
    <div class="container-scroller">
        <?php include "./assets/partials/_sidebar.php" ?>


        <div class="container-fluid page-body-wrapper">
            <?php include "./assets/partials/_navbar.php"; ?>

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
					<div class="col-xl col-sm-6 grid-margin stretch-card">
						<div class="card">
							<div class="card-body">
								<div class="row">
									<div class="col-9">
										<div class="d-flex align-items-center align-self-start">
											<h2 class="mb-0"><?php echo $completedClaimsCount; ?></h2>
										</div>
									</div>                    
								</div>
								<h6 class="text-white font-weight-normal text-white">Completed Claims</h6>
							</div>
						</div>
					</div>


					<div class="col-xl col-sm-6 grid-margin stretch-card">
						<div class="card">
							<div class="card-body">
								<div class="row">
									<div class="col-9">
										<div class="d-flex align-items-center align-self-start">
											<h2 class="mb-0"><?php echo $inProgressClaimsCount; ?></h2>
										</div>
									</div>                    
								</div>
								<h6 class="text-white font-weight-normal text-white">In-Progress Claims</h6>
							</div>
						</div>
					</div>


					<div class="col-xl col-sm-6 grid-margin stretch-card">
						<div class="card">
							<div class="card-body">
								<div class="row">
									<div class="col-9">
										<div class="d-flex align-items-center align-self-start">
											<h2 class="mb-0"><?php echo $savedClaimsCount; ?></h2>
										</div>
									</div>                    
								</div>
								<h6 class="text-white font-weight-normal text-white">Saved Claims</h6>
							</div>
						</div>
					</div>


					<div class="col-xl col-sm-6 grid-margin stretch-card">
						<div class="card">
							<div class="card-body">
								<div class="row">
									<div class="col-9">
										<div class="d-flex align-items-center align-self-start">
											<h2 class="mb-0"><?php echo $totalClaimsCount; ?></h2>
										</div>
									</div>                    
								</div>
								<h6 class="text-white font-weight-normal text-white">Total Claims</h6>
							</div>
						</div>
					</div>


					<div class="col-xl col-sm-6 grid-margin stretch-card">
						<div class="card">
							<div class="card-body">
								<div class="row">
									<div class="col-9">
										<div class="d-flex align-items-center align-self-start">
											<h2 class="mb-0"><?php echo $flaggedClaimsCount; ?></h2>
										</div>
									</div>                    
								</div>
								<h6 class="text-white font-weight-normal">Flagged Claims</h6>
							</div>
						</div>
					</div>
				</div>
					<div class="col-xl col-sm-6 grid-margin stretch-card">
						<?php 
							//print_r($_SESSION);
						?>
					</div>


                </div>
            </div>
        </div>
    </div>
<?php include "./assets/partials/_footer.html"; ?>


<?php include "./assets/partials/_plugins.php"; ?>
</body>
</html>
