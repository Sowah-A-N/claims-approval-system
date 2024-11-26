<?php
    //Session Include goes here
    $pageTitle = "Reports";

    $approverStage;
    include_once '../../includes/conn.inc.php';

	$dateSelectQuery = "SELECT DISTINCT time_submitted FROM `claim_details`; ";

	// Execute the query
	$dateSelectResult = $conn->query($dateSelectQuery);

	// Check if the query was successful
	if ($dateSelectResult) {
		// Fetch the distinct dates
		$dates = [];
		while ($row = $dateSelectResult->fetch_assoc()) {
			// Assuming time_submitted is a valid datetime format
			$formattedDate = date('d/m/Y', strtotime($row['time_submitted']));
			$dates[] = $formattedDate;
		}
	} else {
		// Handle query error
		echo "Error: " . $conn->error;
	};

	// Optionally, you can print or use the dates array
	// echo '<pre>'; print_r($dates); echo '</pre>';
	
?>

<!DOCTYPE html>
<html lang="en">

<?php
    include "./assets/partials/head.php";
?>

<body>
    <!--Body Wrapper -->
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
        data-sidebar-position="fixed" data-header-position="fixed">

        <?php include './assets/partials/sidebar.php' ?>

        <div class="body-wrapper">
			
		<?php include './assets/partials/header.html'; ?>	

			
			<div class="container-fluid">

				
				<div class="row">
					<div class="col-4 me-2">
						<label for="dateSubmitted" class="form-label">Date Submitted</label>
						<select name="dateSubmitted" id="dateSubmitted" class="form-select">
							<option value=""selected disabled>--Select an option--</option>
							<?php foreach ($dates as $date): ?>
								<option value="<?php echo $date; ?>"><?php echo $date; ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="col-4 me-2">
						<label for="action" class="form-label">Action</label>
						<select name="action" id="action" class="form-select">
							<option value="" selected disabled>--Select an option--</option>
							<option value="flagged">Flagged</option>
							<option value="approved">Approved</option>
						</select>
					</div>
					
				</div>


			
				
			</div>
        </div>
    </div>    
</body>
</html>