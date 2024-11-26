<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Approver Registration</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<?php 
    $pageTitle = "Approver Registration";
    include './includes/conn.inc.php';

    $rankSelectQuery = "SELECT * FROM approver_ranks;";
    $rankSelectResult = mysqli_query($conn, $rankSelectQuery);
	
	// Fetch data from the faculty table
	$facultySelectQuery = "SELECT id, name FROM faculty";
	$facultySelectResult = mysqli_query($conn, $facultySelectQuery);

    $departmentSelectQuery = "SELECT * FROM department;";
    $departmentSelectResult = mysqli_query($conn, $departmentSelectQuery);
?>

<div class="container mt-5">
	 <div class="d-flex justify-content-between align-items-center mb-4">
		 <h2 class="mb-0">RMU Claims System - Approver Registration Form</h2>
		 <a href="register.php" class="btn btn-primary">Register As Claimant</a>
	</div>
	<form action="registerApp.inc.php" method="post">
    <div class="form-group">
      <label for="first_name">First Name<span class="text-danger"> * </span></label>
      <input type="text" class="form-control" id="first_name" placeholder="Enter first name" name="first_name" required>
    </div>
    <div class="form-group">
      <label for="last_name">Last Name<span class="text-danger"> * </span></label>
      <input type="text" class="form-control" id="last_name" placeholder="Enter last name" name="last_name" required>
    </div>
    <div class="form-group">
      <label for="other_names">Other Names:</label>
      <input type="text" class="form-control" id="other_names" placeholder="Enter other names" name="other_names">
    </div>
    
    <div class="form-group">
      <label for="phone_number">Phone Number:</label>
      <input type="tel" class="form-control" id="phone_number" placeholder="Enter phone number" name="phone_number" required>
		 <div class="invalid-feedback">
			 Please enter a valid 10-digit phone number.
		</div>
		<small class="form-text text-muted">Please enter exactly 10 digits.</small>            
    </div>
    <div class="form-group">
      <label for="gender">Gender<span class="text-danger"> * </span></label>
      <select class="form-control" id="gender" name="gender" required>
        <option value="">Select Gender</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
        <option value="Other">Other</option>
      </select>
    </div>
    <div class="form-group">
      <label for="email">Email<span class="text-danger"> * </span></label>
      <input type="email" class="form-control" id="email" placeholder="Enter email" name="email" required>
    </div>
    <div class="form-group">
      <label for="password">Password<span class="text-danger"> * </span></label>
      <input type="password" class="form-control" id="password" placeholder="Enter password" name="password" required>
    </div>
    <div class="form-group">
	   <label for="faculty">Faculty<span class="text-danger"> * </span></label>
	   <select class="form-control" id="faculty" name="faculty" required>
		   <option value="" disabled selected>Select a faculty</option>
		   <?php
		   if ($facultySelectResult->num_rows > 0) {
			   // Output data of each row
			   while($row = $facultySelectResult->fetch_assoc()) {
				   echo "<option value='" . $row["name"] . "'>" . htmlspecialchars($row["name"]) . "</option>";
			   }
		   } else {
			   echo "<option value='' disabled>No faculties available</option>";
		   }
		   ?>
	   </select>
	</div>
    <div class="form-group">
      <label for="department">Department:</label>
      <select class="form-control" id="department" placeholder="Enter department" name="department" required>
        <option value="">Select Department</option>
        <?php
                // Check if the query returned results
                if ($departmentSelectResult && $departmentSelectResult->num_rows > 0) {
                    // Loop through the results and generate <option> elements
                    while ($row = mysqli_fetch_assoc($departmentSelectResult)) {
                        echo "<option value=\"" . htmlspecialchars($row['dept_name']) . "\">" . htmlspecialchars($row['dept_name']) . "</option>";
                    }
                } else {
                    echo "<option value=\"\">No departments available</option>";
                }
                ?>
      </select>
    </div>
    <div class="form-group">
      <div class="row">
        <div class="col-md-6">
          <label for="rank">Rank<span class="text-danger"> * </span></label>
          <select class="form-control" id="rank" name="rank" onchange="updateTextbox()" required>
            <option value="">Select Rank</option>
            <?php 
                if ($rankSelectResult->num_rows > 0) {
                    while ($row = mysqli_fetch_assoc($rankSelectResult)) {
                        //echo "<option data-value=\"" . $row['stage'] . "\" value=\"" . $row['rank'] . "\">" . $row['name'] . "</option>";
                        echo "<option data-value=\"" . htmlspecialchars($row['stage']) . "\" value=\"" . htmlspecialchars($row['rank']) . "\">" . htmlspecialchars($row['name']) . "</option>";


                    } 
                } else {
                    echo "<option value=\"\">". "No options available" ."</option>";
                }
            ?>
          </select>
        </div>
        <div class="col-md-6">
          <label for="stage">Stage:</label>
          <input type="text" class="form-control" id="stage" placeholder="Stage is...." name="stage" readonly>
        </div>
      </div>
    </div>
    <button type="submit" class="btn btn-primary">Submit</button>
  </form>
</div>

<script>
	document.addEventListener('DOMContentLoaded', function() {
            const phoneInput = document.getElementById('phone_number');

            phoneInput.addEventListener('input', function() {
                const phoneValue = phoneInput.value;
                const regex = /^0\d{9}$/;

                if (regex.test(phoneValue)) {
                    phoneInput.classList.remove('is-invalid');
                } else {
                    phoneInput.classList.add('is-invalid');
                }
            });
        });
	
    function updateTextbox() {
        var rankDropdown = document.getElementById("rank");
        var stageTextbox = document.getElementById("stage");
        var selectedOption = rankDropdown.options[rankDropdown.selectedIndex].getAttribute('data-value');
        stageTextbox.value = selectedOption;
    }
</script>

</body>
</html>
