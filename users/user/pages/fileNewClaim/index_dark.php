<?php
    // Set the page title
    $pageTitle = "File New Claim";

    // Start the session
    session_start();

    // Include head partial

    include_once "../../assets/partials/head.php"; 


    // Check if claimTempId is set in the URL
    if (isset($_GET['claimTempId'])) {
        $claimTempId = $_GET['claimTempId'];

        // Query to fetch saved claim data
        $savedClaimQuery = "SELECT * FROM saved_claims WHERE claimTempId = ?";
        $stmt = $conn->prepare($savedClaimQuery);
        $stmt->bind_param("i", $claimTempId);
        $stmt->execute();
        $savedClaimResult = $stmt->get_result();

        $savedClaimData = "SELECT * FROM claim_data WHERE claimId = ?";
        $savedClaimDataStmt = $conn->prepare($savedClaimData);
        $savedClaimDataStmt->bind_param("i", $claimTempId);
        $savedClaimDataStmt->execute();
        $savedClaimDataResult = $savedClaimDataStmt->get_result();

        if ($savedClaimResult->num_rows > 0) {
            $claimData = $savedClaimResult->fetch_assoc();

            // Store the retrieved data in session variables
            $_SESSION['claim_data'] = $claimData;
        }
    }

    // Function to output full name stored in the session (if available)
    function outputFullName() {
        if(isset($_SESSION['full_name'])) {
            echo $_SESSION['full_name'];
        }
    }

	$currentRate = $_SESSION['rate'] ?? "";

    // Query to select all programmes from the database
    $programmeSelectQuery = "SELECT * FROM programme;";
    // Execute the query
    $programmeSelectResult = mysqli_query($conn, $programmeSelectQuery);

    // Query to select all departments from the database
    $departmentSelectQuery = "SELECT * FROM department ORDER BY dept_name ASC; ";
    // Execute the query
    $departmentSelectResult = mysqli_query($conn, $departmentSelectQuery);

	// Query to check if the fuel reimbursement option is available
	$fuelComponentStmt = $conn->prepare("SELECT settingValue FROM settings WHERE settingName = 'fuelComponent'");
	$fuelComponentStmt->execute();
	$fuelComponentStmt->bind_result($fuelComponent);
	$fuelComponentStmt->fetch();
	$fuelComponentStmt->close();
	?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?></title>
</head>
<body>

<script>
    // Check if the page was reloaded after a form submission and if there's an error message to display
    window.onload = function() {
        if(performance.navigation.type == 1 && <?php echo isset($_SESSION['formError']) && !empty($_SESSION['formError']) ? 'true' : 'false'; ?>) {
            // Show the error message div
            document.getElementById('errorMessageDiv').style.display = 'block';
        }
    }
</script>

<div class="container-scroller">
    <?php include "../../assets/partials/_sidebar.php" ?>

    <div class="container-fluid page-body-wrapper">
        <?php include "../../assets/partials/_navbar.php"; ?>

        <div class="main-panel">
            <div class="content-wrapper">
                <form class="forms-sample" method="POST" name="newClaimForm" id="newClaimForm">
                    <div id="errorMessageDiv" class="alert alert-danger alert-dismissible fade show" role="alert" style="display: none;"></div>
                    <fieldset>						
                        <legend>Course Information</legend>
                        <!-- Display the rate -->
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="rate">Rate</label>
                            <div class="col-sm-9">
                                <input type="text" name="rate" class="form-control" id="rate" style="width:50%" 
									   value="GH₵ <?php echo $currentRate ?>" readonly>
                            </div>
                        </div>

                        <?php
                            function getClaimFieldValue($field) {
                                return isset($_SESSION['claim_data'][$field]) ? $_SESSION['claim_data'][$field] : '';
                            }
                            ?>

                            <?php
                            //print_r ($_GET);
                            //print_r ($_SESSION['claim_data']);
                            ?>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label" for="department">Department</label>
                                <div class="col-sm-9">
                                    <select class="form-select" name="department" id="department" style="width:75%">
                                        <option value="">--Select Department--</option>
                                        <?php 
                                            while ($row = mysqli_fetch_assoc($departmentSelectResult)) {
                                                $selected = $row['dept_name'] == getClaimFieldValue('department') ? 'selected' : '';
                                                echo "<option value=\"" . $row['dept_name'] ."\" $selected>". $row['dept_name'] ."</option>";
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label" for="programme">Programme</label>
                                <div class="col-sm-9">
                                    <select class="form-select" name="programme" id="programme" style="width:75%">
                                        <option value="">--Select Programme--</option>
                                        <?php 
                                        while ($row = mysqli_fetch_assoc($programmeSelectResult)) {
                                            $selected = $row['name'] == getClaimFieldValue('programme') ? 'selected' : '';
                                            echo "<option value=\"" . $row['name'] ."\" $selected>". $row['name'] ."</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label" for="course">Course</label>
                                <div class="col-sm-9">
                                    <select class="form-select" name="course" id="course" style="width:75%">
                                        <option value="">--Select Department First--</option>
                                        <?php 
                                        // Dynamically populate based on the department selected
                                        ?>
                                    </select>
                                </div>
                            </div>
						
						<?php if ($fuelComponent) : ?>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="fuelComponent">
									Fuel Reimbursement Included?
								</label>
								<div class="col-sm-9">
									<div>
										<input 
											type="checkbox" 
											id="fuelComponent" 
											name="fuelComponent" 
											class="form-check-input" 
											value="1" 
											<?php echo isset($_POST['fuelComponent']) && $_POST['fuelComponent'] == '1' ? 'checked' : ''; ?>
										>
										<label class="form-check-label" for="fuelComponent">Yes</label>
									</div>
								</div>
							</div>
						<?php endif; ?>


                    </fieldset>

                    <fieldset>
                    <legend>Claim Information</legend>  
                        <div id="claimDataRow" class="form-group row"></div>
                        <?php
                            if (isset($savedClaimDataResult) && $savedClaimDataResult->num_rows > 0):   
                                while($savedClaimDataRow = $savedClaimDataResult->fetch_assoc()):                                
                        ?>

                        <!-- Begin of generated row -->
                        <div class="added-div row mb-2">
                            <input type="date" name="date[]" class="form-control col" value="<?php echo htmlspecialchars($savedClaimDataRow['date']); ?>" />
                            
                            <input type="time" name="startTime[]" class="form-control col" value="<?php echo htmlspecialchars($savedClaimDataRow['start_time']); ?>"/>
                            
                            <input type="time" name="endTime[]" class="form-control col" value="<?php echo htmlspecialchars($savedClaimDataRow['end_time']); ?>"/>
                            
                            <input type="text" name="period[]" class="form-control col" value="<?php echo htmlspecialchars($savedClaimDataRow['periods']); ?>" readonly />
                        </div>
                        <!-- End of generated row -->

                        <?php
                            unset($_GET);
                            endwhile;
                        ?>

                        <?php else:?>
                            <!-- Optional: Display a message or generate a default row if no data is found -->
                            <!--div class="alert alert-info" role="alert">
                                No saved claim data found. Please add new rows below.
                            </div-->
                        <?php
                        endif;
                        ?>
                    </fieldset>

                    <fieldset>
                        <!-- Buttons for form actions -->               
                        <div class="form-group row">
                            <div class="col-md-3 col-sm-6 mb-2">
                                <button type='button' id="addNewRow" class="btn btn-secondary btn-rounded btn-block">Add Row</button>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <button type='submit' name='submitBtn' id='saveFormDetails' class="btn btn-primary btn-rounded btn-block">Save Claim Info</button>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <button type='submit' name='submitBtn' id='submitForm' class="btn btn-success btn-rounded btn-block">Submit Claim</button>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <button type='button' name='resetForm' id='resetForm' class="btn btn-danger btn-rounded btn-block" onclick='resetForm()'>Reset Claim</button>
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>
</div>

</body>

<?php include "../../assets/partials/_footer.html"; ?>

<script>
    // Add event listener to the button to add new row dynamically
//     document.getElementById("addNewRow").addEventListener("click", function() {
//     // Create a new div element
//     var newDiv = document.createElement("div");
//     newDiv.className = "added-div"; // Add a class for styling if needed

//     // Create date picker input
//     var datePicker = document.createElement("input");
//     datePicker.setAttribute("type", "date");
//     datePicker.setAttribute("name", "date[]");
//     newDiv.appendChild(datePicker); // Append date picker to the new div

//     // Create first time picker input
//     var timePicker1 = document.createElement("input");
//     timePicker1.setAttribute("type", "time");
//     timePicker1.setAttribute("name", "startTime[]");
//     newDiv.appendChild(timePicker1); // Append first time picker to the new div
//     timePicker1.addEventListener('input', calculatePeriod); // Add event listener to the new time picker

//     // Create second time picker inputprogramme_2_1
//     var timePicker2 = document.createElement("input");
//     timePicker2.setAttribute("type", "time");
//     timePicker2.setAttribute("name", "endTime[]");
//     newDiv.appendChild(timePicker2); // Append second time picker to the new div
//     timePicker2.addEventListener('input', calculatePeriod); // Add event listener to the new time picker

//     // Create text input
//     var textInput = document.createElement("input");
//     textInput.setAttribute("type", "text");
//     textInput.setAttribute("name", "period[]");
//     textInput.setAttribute("readonly", true);programme_2_1
//     newDiv.appendChild(textInput); // Append text input to the new div

//     // Append the new div to the container
//     document.getElementById("newClaimForm").appendChild(newDiv);
// }); 

    
document.getElementById("addNewRow").addEventListener("click", function() {
    // Create a new div element for the row
    var newDiv = document.createElement("div");
    newDiv.className = "row mb-2"; // Add Bootstrap classes for styling

    // Create a container for the inputs
    var inputContainer = document.createElement("div");
    inputContainer.className = "col-12 col-md-8 d-flex"; // Flex container for inputs
    newDiv.appendChild(inputContainer);

    // Create date picker input
    var datePicker = document.createElement("input");
    datePicker.setAttribute("type", "date");
    datePicker.setAttribute("name", "date[]");
    datePicker.className = "form-control me-2"; // Bootstrap form-control and margin-end
    inputContainer.appendChild(datePicker); // Append date picker to the input container

    // Create first time picker input
    var timePicker1 = document.createElement("input");
    timePicker1.setAttribute("type", "time");
    timePicker1.setAttribute("name", "startTime[]");
    timePicker1.className = "form-control me-2"; // Bootstrap form-control and margin-end
    inputContainer.appendChild(timePicker1); // Append first time picker to the input container
    timePicker1.addEventListener('input', function() {
        checkDuplicateDateTime();
        calculatePeriod();
    }); // Add event listener to the new time picker

    // Create second time picker input
    var timePicker2 = document.createElement("input");
    timePicker2.setAttribute("type", "time");
    timePicker2.setAttribute("name", "endTime[]");
    timePicker2.className = "form-control me-2"; // Bootstrap form-control and margin-end
    inputContainer.appendChild(timePicker2); // Append second time picker to the input container
    timePicker2.addEventListener('input', calculatePeriod); // Add event listener to the new time picker

    // Create text input
    var textInput = document.createElement("input");
    textInput.setAttribute("type", "text");
    textInput.setAttribute("name", "period[]");
    textInput.setAttribute("readonly", true);
    textInput.className = "form-control me-2"; // Bootstrap form-control and margin-end
    inputContainer.appendChild(textInput); // Append text input to the input container

    // Create delete button
    var deleteButton = document.createElement("button");
    deleteButton.textContent = "X";
    deleteButton.className = "btn btn-danger btn-block"; // Bootstrap button for styling
    deleteButton.addEventListener('click', function() {
        newDiv.remove(); // Remove the current row
    });
    inputContainer.appendChild(deleteButton); // Append the delete button to the new div

    // Append the new div to the container inside the fieldset
    document.getElementById("claimDataRow").appendChild(newDiv);
});



    // Function to check for duplicate date and time
    function checkDuplicateDateTime() {
        var rows = document.getElementsByName('date[]');
        var startTimes = document.getElementsByName('startTime[]');
        
        var values = {};
        var duplicateFound = false;

        for (var i = 0; i < rows.length; i++) {
            var key = rows[i].value + '-' + startTimes[i].value;
            if (values[key] && values[key] !== rows[i]) {
                duplicateFound = true;
                break;
            } else {
                values[key] = rows[i];
            }
        }

        if (duplicateFound) {
            // You can throw a flag, show an alert, disable form submission, etc.
            alert('Duplicate date and start time found in multiple rows!');
            // Example: disable submit button
            document.getElementById('submitForm').disabled = true;
            document.getElementById('saveFormDetails').disabled = true;
            document.getElementById('addNewRow').disabled = true;


        } else {
            // Enable submit button if no duplicates
            document.getElementById('submitForm').disabled = false;
            document.getElementById('saveFormDetails').disabled = false;
            document.getElementById('addNewRow').disabled = false;
        }    
	};


    // Event listener for time picker changes
    document.querySelectorAll('input[type="time"]').forEach(function(timePicker) {
        timePicker.addEventListener('input', calculatePeriod);
    });

    function calculatePeriod() {
        // Select all start time, end time, and period input fields
        const startTimeInputs = document.querySelectorAll('input[name="startTime[]"]');
        const endTimeInputs = document.querySelectorAll('input[name="endTime[]"]');
        const periodInputs = document.querySelectorAll('input[name="period[]"]');

        // Loop through each pair of start time and end time inputs
        startTimeInputs.forEach((startTimeInput, index) => {
            const endTimeInput = endTimeInputs[index];
            const periodInput = periodInputs[index];

            const startTime = startTimeInput.value;
            const endTime = endTimeInput.value;

            if (startTime && endTime) {
                const startTimeParts = startTime.split(':');
                const endTimeParts = endTime.split(':');

                const startTimeMinutes = parseInt(startTimeParts[0]) * 60 + parseInt(startTimeParts[1]);
                const endTimeMinutes = parseInt(endTimeParts[0]) * 60 + parseInt(endTimeParts[1]);

                const timeDifferenceMinutes = endTimeMinutes - startTimeMinutes;
                const periods = Math.ceil(timeDifferenceMinutes / 50);

                periodInput.value = periods;
            }
        });
    }


    // Event listener for department dropdown change
    document.getElementById("department").addEventListener("change", function() {

        // // Get the select element
        // var selectElement = document.getElementById('department');

        // // Get the selected option
        // var selectedOption = selectElement.options[selectElement.selectedIndex];
        var selectedOption = this.options[this.selectedIndex];

        // // Get the value of the data-value attribute
        // var dataValue = selectedOption.getAttribute('data-value');
        //var departmentDropdownValue = selectedOption.getAttribute('data-value');
        var departmentDropdownValue = selectedOption.value;

        // // Log or use the dataValue as needed
        // console.log('Data Value:', dataValue);
        console.log("Department: ", departmentDropdownValue );

        //var departmentDropdownValue = this.getAttribute("data-value");
        //console.log(departmentDropdownValue);
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "load_data.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                var courseDropdownOptions = JSON.parse(xhr.responseText);
                var courseDropdown = document.getElementById("course");
                // Clear existing options
                courseDropdown.innerHTML = "<option value=''>Select Course</option>";
                // Populate course dropdown with options received from PHP
                courseDropdownOptions.forEach(function(option) {
                    var newOption = document.createElement("option");
                    newOption.value = option.name;
                    newOption.text = option.name;
                    courseDropdown.appendChild(newOption);
                });
            }
        };
        xhr.send("department_dropdown_value=" + encodeURIComponent(departmentDropdownValue));
    });

    // Event listener for form submission
    document.getElementById("newClaimForm").addEventListener("submit", function(e) {
        e.preventDefault(); // Prevent form submission
        
        // Validate form fields
        var department = document.getElementById("department").value;
        var programme = document.getElementById("programme").value;
        var course = document.getElementById("course").value;
        
        if (department.trim() === '' || programme.trim() === '' || course.trim() === '') {
            // Display error message
            document.getElementById('errorMessageDiv').innerHTML = "Please fill out all fields.";
            document.getElementById('errorMessageDiv').style.display = 'block';
        } else {
            // Form data is valid, proceed with AJAX form submission
            var formData = new FormData(this);
            
            // AJAX request to submit form data
            $.ajax({
                type: "POST",
                url: "./index.inc.php",
                data: formData,
                contentType: false,
                processData: false,
                cache: false
            }).done(function (data){
                console.log(data);
                 // Display SweetAlert
                Swal.fire({
                    title: "Claim submitted",
                    timer: 2000, // Adjust the time (in milliseconds) as needed
                    showConfirmButton: false
                }).then(function(){
                    // Redirect or perform any other action after successful submission
                   // window.location.href = "./";
					document.getElementById("newClaimForm").reset();
                });
            });
        }
    });

    //Function to save form details -
    document.getElementById("saveFormDetails").addEventListener("click", function(e){
        e.preventDefault();
        //alert("You just clicked on the save button!");

        const form = document.getElementById("newClaimForm");
        const formData = new FormData(form);

         // AJAX request to save form data
         console.log(formData);
         $.ajax({
                type: "POST",
                url: "./save_claim_data.inc.php",
                data: formData,
                contentType: false,
                processData: false,
                cache: false
            }).done(function (data){
                console.log(data);
                 // Display SweetAlert
                Swal.fire({
                    title: "Claim saved",
                    timer: 2000, // Adjust the time (in milliseconds) as needed
                    showConfirmButton: false
                }).then(function(){
                    // Redirect or perform any other action after successful submission
                    window.location.href = "./";
                });
            });

    })

    // Function to reset the form 
    function resetForm(){
        document.getElementById('newClaimForm').reset();
    }
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
