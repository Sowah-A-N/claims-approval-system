<?php
    //Session Include goes here
    $pageTitle = "Reports";

    $approverStage;
    include_once '../../includes/conn.inc.php';

	$dateSelectQuery = "SELECT DISTINCT DATE(time_submitted) AS time_submitted FROM `claim_details`; ";

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
							<option value="pending">Pending</option>
							<option value="approved">Approved</option>
						</select>
					</div>					
				</div>

				<div class="col-4">
					<!-- Clear Filters Button -->
					<button type="button" id="clearFiltersBtn" class="btn btn-secondary mt-4">Clear Filters</button>
				</div>

				<div class="container">
					<div id="results"></div>
				</div>
				
			</div>
        </div>
    </div>    

	<script>
		document.addEventListener('DOMContentLoaded', function() {
		// Get the select elements
		const dateSubmitted = document.getElementById('dateSubmitted');
		const action = document.getElementById('action');

		// Function to handle AJAX request
		function fetchResults() {
			const dateValue = dateSubmitted.value;
			const actionValue = action.value;

			// Create a new FormData object to send the data
			const formData = new FormData();
			if (dateValue) formData.append('dateSubmitted', dateValue);
			if (actionValue) formData.append('action', actionValue);

			// If no filters are selected, do nothing
			if (!dateValue && !actionValue) {
				document.getElementById('results').innerHTML = '<p>Please select at least one filter.</p>';
				return;
			}

			// Send the data via AJAX
			fetch('fetchRecords.inc.php', {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				// Display the results in the #results div
				if (data.success) {
  
				// Create the table dynamically
				let html = '<table class="table mt-4">';
				html += '<thead><tr><th>S/N</th><th>Claim ID</th><th>Course</th><th>Claimant</th><th>Current Stage</th><th>Current Status</th><th>Date Submitted</th></tr></thead>';
				html += '<tbody>';

				// Loop through the results and create a table row for each record
				data.results.forEach((result, index) => {
					// Format the 'time_submitted' date (assuming it's in YYYY-MM-DD HH:mm:ss format)
					const dateSubmitted = new Date(result.time_submitted); // Convert to Date object
					const formattedDate = dateSubmitted.toLocaleDateString('en-US'); // Format as MM/DD/YYYY (adjust as needed)
					
					html += `<tr>
								<td>${index + 1}</td>
								<td>${result.claimId}</td>
								<td>${result.course}</td>
								<td>${result.full_name}</td>
								<td>${result.stage}</td>
								<td>${result.status}</td>
								<td>${formattedDate}</td>
							</tr>`;
				});

				html += '</tbody></table>';

				// Append the table to a container in the DOM
				document.getElementById('results').innerHTML = html;
			} else {
				// Handle error or no results scenario
				document.getElementById('results').innerHTML = "<p>No data found.</p>";
			}
		})
			.catch(error => {
				console.error('Error fetching data:', error);
				document.getElementById('results').innerHTML = '<p>There was an error fetching the data.</p>';
			});

		}

		// Add event listeners for both selects
		dateSubmitted.addEventListener('change', fetchResults);
		action.addEventListener('change', fetchResults);
	});

	// Add event listener to the Clear Filters button
    clearFiltersBtn.addEventListener('click', function() {
        // Reset the dropdown values to the default state (the first disabled option)
        dateSubmitted.value = '';
        action.value = '';

        // Clear the results table
        resultsDiv.innerHTML = '';

        // Optionally, show a message that filters have been cleared
        resultsDiv.innerHTML = '<p>Filters have been cleared. Please select your search criteria.</p>';
    });

	</script>
</body>
</html>