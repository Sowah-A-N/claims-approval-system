<?php
    $pageTitle = "User Registration";
    include './includes/conn.inc.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $pageTitle ?></title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .container {
      margin-top: 50px;
    }
    .form-group {
      margin-bottom: 20px;
    }
	  .invalid-feedback {
		  display: none;
	  }
	  .is-invalid .invalid-feedback {
		  display: block;
		  color: red;
	  }
  </style>
</head>
<body>

<?php 
    $rankSelectQuery = "SELECT * FROM lecturer_rank_rate;";
    $rankSelectResult = mysqli_query($conn, $rankSelectQuery);
	  // Fetch data from the faculty table
	$facultySelectQuery = "SELECT id, name FROM faculty";
	$facultySelectResult = mysqli_query($conn, $facultySelectQuery);

    $departmentSelectQuery = "SELECT * FROM department;";
    $departmentSelectResult = mysqli_query($conn, $departmentSelectQuery);
?>

<div class="container mt-4">
	 <div class="d-flex justify-content-between align-items-center mb-4">
		 <h2 class="mb-0">RMU Claims System - User Registration Form</h2>
		 <a href="registerApp.php" class="btn btn-primary">Register As Approver</a>
	</div>  <form action="register.inc.php" method="post">
    <div class="form-group">
      <label for="first_name">First Name:</label>
      <input type="text" class="form-control" id="first_name" placeholder="Enter first name" name="first_name" required>
    </div>
    <div class="form-group">
      <label for="last_name">Last Name:</label>
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
      <label for="gender">Gender:</label>
      <select class="form-control" id="gender" name="gender" required>
        <option value="">Select Gender</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
        <option value="Other">Other</option>
      </select>
    </div>
    <div class="form-group">
      <label for="email">Email:</label>
      <input type="email" class="form-control" id="email" placeholder="Enter email" name="email" required>
    </div>
    <div class="form-group">
      <label for="password">Password:</label>
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
          <label for="rank">Rank:</label>
          <select class="form-control" id="rank" name="rank" onchange="updateTextbox()" required>
            <option value="">Select Rank</option>
            <?php 
                if ($rankSelectResult->num_rows > 0) {
                    while ($row = mysqli_fetch_assoc($rankSelectResult)) {
                        echo "<option data-value=\"" . $row['rate'] . "\" value=\"" . $row['rank'] . "\">" . $row['rank'] . "</option>";

                    } 
                } else {
                    echo "<option value=\"\">". "No options available" ."</option>";
                }
            ?>
          </select>
        </div>

        <div class="col-md-6">
          <label for="rate">Rate:</label>
          <input type="text" class="form-control" id="rate" placeholder="Rate is...." name="rate" readonly>
        </div>
      </div>
    </div>

    <div class="form-group">
      <div class="row">
        <div class="col-md-6">
            <label for="bank_name">Bank Name:</label>
            <select class="form-control" id="bank_name" name="bank_name" onchange="updateBranches()" required>
                <option value="">Select Bank</option>
                <?php 
                $sql = "SELECT DISTINCT bank_name FROM `banks_branches` ORDER BY bank_name ";
                $result = mysqli_query($conn, $sql);
                if ($result->num_rows > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<option value=\"" . $row['bank_name'] . "\">" . $row['bank_name'] . "</option>";
                    }
                } else {
                    echo "<option value=\"\">No banks available</option>";
                }
                ?>
            </select>
        </div>

        <div class="col-md-6">
            <label for="bank_branch">Bank Branch:</label>
            <select class="form-control" id="bank_branch" name="bank_branch" required>
                <option value="">Select Branch</option>
                <!-- Options will be dynamically populated based on selected bank -->
            </select>
        </div>
      </div>
    </div>


    <div class="form-group">
      <div class="row">
        <div class="col-md-6">
            <label for="account_name">Account Name:</label>
            <input type="text" class="form-control" id="account_name" name="account_name" required>
        </div>

        <div class="col-md-6">
            <label for="account_number">Account Number:</label>
            <input type="text" class="form-control" id="account_number" name="account_number" required>
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
      var rateTextbox = document.getElementById("rate");
      var selectedOption = rankDropdown.options[rankDropdown.selectedIndex].getAttribute('data-value');
      rateTextbox.value = selectedOption;
    }

    function updateBranches() {
    var bank_name = encodeURIComponent(document.getElementById('bank_name').value); // Get selected bank_name
    var xhr = new XMLHttpRequest(); // Create a new XMLHttpRequest object
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status === 200) {
                var branches = JSON.parse(xhr.responseText); // Parse JSON response
                var branchSelect = document.getElementById('bank_branch');
                branchSelect.innerHTML = ''; // Clear current options
                
                // Populate new options
                if (branches.length > 0) {
                    branches.forEach(function(branch) {
                        var option = document.createElement('option');
                        option.value = branch.bank_branch;
                        option.textContent = branch.bank_branch;
                        branchSelect.appendChild(option);
                    });
                } else {
                    var option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'No branches available';
                    branchSelect.appendChild(option);
                }
            } else {
                // Handle error
                console.error('Error fetching branches: ' + xhr.status);
            }
        }
    };
    
    xhr.open('GET', 'updateBranches.inc.php?bank_name=' + bank_name, true); // Specify the request type and URL
    xhr.send(); // Send the request
}

</script>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
