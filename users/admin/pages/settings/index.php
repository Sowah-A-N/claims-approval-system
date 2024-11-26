<?php
    $pageTitle = "Settings";
?>

<!DOCTYPE html>
<html lang="en">

<?php
    include "../../assets/partials/head.php";
	
	// Function to handle errors
	function handleError($error) {
		echo "<div class='alert alert-danger' role='alert'>" . htmlspecialchars($error) . "</div>";
	}
	
	// Handle adding a new bank with at least one branch
	if (isset($_POST['add_bank'])) {
		$new_bank_name = $conn->real_escape_string($_POST['new_bank_name']);
		$branch_code = $conn->real_escape_string($_POST['branch_code']);
		$branch_name = $conn->real_escape_string($_POST['branch_name']);

		// Check if the bank name already exists
		$checkBankQuery = "SELECT COUNT(*) AS count FROM banks_branches WHERE bank_name = '$new_bank_name'";
		$checkBankResult = $conn->query($checkBankQuery);
		$row = $checkBankResult->fetch_assoc();

		if ($row['count'] > 0) {
			handleError("Bank name already exists.");
		} else {
			// Check if the branch code already exists
			$checkBranchCodeQuery = "SELECT COUNT(*) AS count FROM banks_branches WHERE branch_code = '$branch_code'";
			$checkBranchCodeResult = $conn->query($checkBranchCodeQuery);
			$row = $checkBranchCodeResult->fetch_assoc();

			if ($row['count'] > 0) {
				handleError("Branch code already exists.");
			} else {
				// Add the new bank (simulated) and the branch
				$sql = "INSERT INTO banks_branches (branch_code, bank_name, bank_branch) VALUES ('$branch_code', '$new_bank_name', '$branch_name')";
				if ($conn->query($sql) === TRUE) {
					echo "<div class='alert alert-success' role='alert'>New bank and branch added successfully.</div>";
				} else {
					handleError($conn->error);
				}
			}
		}
	}

	// Fetch all branches
	$branchesQuery = "SELECT * FROM banks_branches";
	$branchesResult = $conn->query($branchesQuery);

	// Handle adding a branch
	if (isset($_POST['add_branch'])) {
		$branch_code = $conn->real_escape_string($_POST['branch_code']);
		$bank_name = $conn->real_escape_string($_POST['bank_name']);
		$bank_branch = $conn->real_escape_string($_POST['bank_branch']);

		// Simulated bank name - no actual bank_id field in schema
		$sql = "INSERT INTO banks_branches (branch_code, bank_name, bank_branch) 
				VALUES ('$branch_code', '$bank_name', '$bank_branch')";
		if ($conn->query($sql) === TRUE) {
			echo "<div class='alert alert-success' role='alert'>New branch added successfully.</div>";
		} else {
			handleError($conn->error);
		}
	}

	// Handle removing a branch
	if (isset($_POST['remove_branch'])) {
		$bank_branch_id = intval($_POST['bank_branch_id']); // Use intval to ensure it's an integer

		if ($bank_branch_id > 0) {
			$sql = "DELETE FROM banks_branches WHERE bank_branch_id = $bank_branch_id";
			if ($conn->query($sql) === TRUE) {
				echo "<div class='alert alert-success' role='alert'>Branch removed successfully.</div>";
			} else {
				handleError($conn->error);
			}
		} else {
			handleError("Invalid branch ID.");
		}
	}

	// Fetch unique bank names for dropdown (simulation)
	$uniqueBanksQuery = "SELECT DISTINCT bank_name FROM banks_branches";
	$uniqueBanksResult = $conn->query($uniqueBanksQuery);

	$selected_bank_name = isset($_POST['bank_name']) ? $_POST['bank_name'] : '';

	// Fetch branches for the selected bank
	$branchesQuery = "SELECT * FROM banks_branches WHERE bank_name = '$selected_bank_name'";
	$branchesResult = $conn->query($branchesQuery);
	
	//Fuel Component Setting
	// Initialize the variable
	$fuelComponent = 0; // Default value

	// Fetch the current value from the database
	$fuelComponentQuery = "SELECT settingValue FROM settings WHERE settingName = 'fuelComponent'"; // Added quotes around 'fuelComponent'
	$fuelComponentResult = $conn->query($fuelComponentQuery);

	if ($fuelComponentResult && $fuelComponentResult->num_rows > 0) {
		$row = $fuelComponentResult->fetch_assoc();
		$fuelComponent = (int) $row['settingValue']; // Cast to integer for safety
	}
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
				<h1>Update Lecturer Claim Rates</h1>
				<form id="updateForm" method="post" action="updateRates.inc.php">
					<div class="row">
						<?php
						$sql = "SELECT * FROM lecturer_rank_rate";
						$result = $conn->query($sql);

						if ($result->num_rows > 0) {
							while($row = $result->fetch_assoc()) {
								echo '<div class="col-md-3 form-group mb-3">';
								echo '<label class="form-label">' . htmlspecialchars($row['rank']) . ':</label>';
								echo '<input type="text" name="rate_' . htmlspecialchars($row['rankId']) . '" class="form-control" value="' . htmlspecialchars($row['rate']) . '" />';
								echo '<input type="hidden" name="id_' . htmlspecialchars($row['rankId']) . '" value="' . htmlspecialchars($row['rankId']) . '" />';
								echo '</div>';
							}
						} else {
							echo '<div class="col-12">No results found</div>';
						}
						
						$conn->close();
						
						?>
						<div class="col-md-3 form-group">
							<button type="submit" class="btn btn-outline-info mt-4">Update Rates</button>
						</div>
					</div>
				</form>
				<br /><br />
				
				<?php 
					
					?>

					<!-- Other Settings for Claims -->
				<h2>General Settings</h2>
					<form method="post" class="mb-4" action="updateSettings.inc.php">
						<div class="row align-items-end">
							<div class="col-md-3 form-check">
								<input type="checkbox" class="form-check-input" name="fuelComponent" id="fuelComponent" value="1" <?php echo $fuelComponent == 1 ? 'checked' : ''; ?>>
								<label class="form-check-label" for="fuelComponent">Fuel for Weekend and Part-Timers</label>
							</div>
							<input type="hidden" name="fuelComponentHidden" value="0"> <!-- Hidden input with a different name -->
							<div class="col-md-3 form-group">
								<button type="submit" class="btn btn-outline-info mt-2">Update Settings</button>
							</div>
						</div>
					</form>
					<br /><br />


				
				<!-- Add New Bank and Branch Form -->
				<h2>Add New Bank and Branch</h2>
				<form method="post" class="mb-4">
					<div class="row align-items-end">
						<div class="col-md-3 form-group">
							<label for="new_bank_name">Bank Name:</label>
							<input type="text" class="form-control" id="new_bank_name" name="new_bank_name" oninput="toUpperCase(this)" required>
						</div>
						<div class="col-md-3 form-group">
							<label for="branch_code">Branch Code:</label>
							<input type="text" class="form-control" id="branch_code" name="branch_code" oninput="toUpperCase(this)" required>
						</div>
						<div class="col-md-3 form-group">
							<label for="branch_name">Branch Name:</label>
							<input type="text" class="form-control" id="branch_name" name="branch_name" oninput="toUpperCase(this)" required>
						</div>
						<div class="col-md-3 form-group">
							<button type="submit" name="add_bank" class="btn btn-primary mt-4">Add Bank and Branch</button>
						</div>
					</div>
				</form>

        
				<!-- Bank Selection Form -->
				<form method="post" class="mb-4">
					<div class="form-group">
						<label for="bank_name">Select Bank:</label>
						<select id="bank_name" name="bank_name" class="form-control" onchange="this.form.submit()">
							<option value="">-- Select a Bank --</option>
							<?php
							if ($uniqueBanksResult->num_rows > 0) {
								while($row = $uniqueBanksResult->fetch_assoc()) {
									$selected = ($selected_bank_name == $row['bank_name']) ? 'selected' : '';
									echo "<option value='" . htmlspecialchars($row['bank_name']) . "' $selected>" . htmlspecialchars($row['bank_name']) . "</option>";
								}
							}
							?>
						</select>
					</div>
				</form>
				
				<?php if ($selected_bank_name): ?>
				<h2>Branches for Selected Bank</h2>
				<table class="table table-bordered table-striped">
					<thead class="thead-dark">
						<tr>
							<th>ID</th>
							<th>Branch Code</th>
							<th>Bank Name</th>
							<th>Branch</th>
							<th>Action</th>
						</tr>
					</thead>
					<tbody>
						<?php
						if ($branchesResult) {
							if ($branchesResult->num_rows > 0) {
								while($row = $branchesResult->fetch_assoc()) {
									echo "<tr>";
									echo "<td>" . htmlspecialchars($row["bank_branch_id"]) . "</td>";
									echo "<td>" . htmlspecialchars($row["branch_code"]) . "</td>";
									echo "<td>" . htmlspecialchars($row["bank_name"]) . "</td>";
									echo "<td>" . htmlspecialchars($row["bank_branch"]) . "</td>";
									echo "<td>
											<form method='post' style='display:inline;'>
												<input type='hidden' name='bank_branch_id' value='" . htmlspecialchars($row["bank_branch_id"]) . "'>
												<button type='submit' name='remove_branch' class='btn btn-danger btn-sm'>Remove</button>
											</form>
										  </td>";
									echo "</tr>";
								}
							} else {
								echo "<tr><td colspan='5'>No branches found for the selected bank</td></tr>";
							}
						} else {
							handleError($conn->error);
						}
						?>
					</tbody>
				</table>

				<h2 class="my-4">Add New Branch</h2>
				<form method="post">
					<input type="hidden" name="bank_name" value="<?php echo htmlspecialchars($selected_bank_name); ?>">
					<div class="form-group">
						<label for="branch_code">Branch Code:</label>
						<input type="text" class="form-control" id="branch_code" name="branch_code" oninput="toUpperCase(this)" required>
					</div>
					<div class="form-group">
						<label for="bank_branch">Branch Name:</label>
						<input type="text" class="form-control" id="bank_branch" name="bank_branch" oninput="toUpperCase(this)" required>
					</div>
					<button type="submit" name="add_branch" class="btn btn-primary">Add Branch</button>
				</form>
			<?php endif; ?>
				
			</div>
		</div>

			<?php ?>
			<?php ?>
			<?php ?>
        </div>
    
	<script>
		 // Convert input to uppercase
        function toUpperCase(element) {
            element.value = element.value.toUpperCase();
        }
	</script>
    <script src="../../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sidebarmenu.js"></script>
    <script src="../../assets/js/app.min.js"></script>
    <script src="../../assets/libs/simplebar/dist/simplebar.js"></script>
</body>
</html>
