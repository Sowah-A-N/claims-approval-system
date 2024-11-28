<?php
	$pageTitle = "Multi Claims Trial";

    //Start the session
    session_start();

	// Database connection
	//include_once '../../includes/conn.inc.php';
    include_once "../../assets/partials/_head.php"; 

    $currentRate = $_SESSION['rate'] ?? "";

    // Query to select all programmes from the database
    $programmeSelectQuery = "SELECT * FROM programme;";
    // Execute the query
    $programmeSelectResult = mysqli_query($conn, $programmeSelectQuery);

    // Query to select all departments from the database
    $departmentSelectQuery = "SELECT * FROM department ORDER BY dept_name ASC; ";
    // Execute the query
    $departmentSelectResult = mysqli_query($conn, $departmentSelectQuery);

	$currentDepartment = 'ICT'; // Replace with your test value
	$currentProgramme = 'BME';

    
	// Query to check if the fuel reimbursement option is available
	$fuelComponentStmt = $conn->prepare("SELECT settingValue FROM settings WHERE settingName = 'fuelComponent'");
	$fuelComponentStmt->execute();
	$fuelComponentStmt->bind_result($fuelComponent);
	$fuelComponentStmt->fetch();
	$fuelComponentStmt->close();

	// Check if fuelComponent is set to 1
	if ($fuelComponent == 1) {
		// Query to fetch the value of another component
		$fuelValueStmt = $conn->prepare("SELECT settingValue FROM settings WHERE settingName = 'fuelAmount'");
		$fuelValueStmt->execute();
		$fuelValueStmt->bind_result($fuelValue);
		$fuelValueStmt->fetch();
		$fuelValueStmt->close();
	}
?>

<html>
	<body>
    <div class="container-scroller">
	<?php include "../../assets/partials/_navbar.php"; ?>

    <div class="container-fluid page-body-wrapper">
		<?php include "../../assets/partials/_sidebar.php" ?>


        <div class="main-panel">
            <div class="content-wrapper">

            <?php print_r($_SESSION); ?>
	
		<form method="POST" id="newClaimForm" name="newClaimForm">
            <!-- Display the rate -->
            <div class="form-group row">
                <label class="col-sm-3 col-form-label" for="rate">Rate (GH₵)</label>
                <div class="col-sm-9">
                    <input type="text" name="rate" class="form-control" id="rate" style="width:50%" 
                            value="<?php echo $currentRate ?>" readonly>
                </div>
            </div>
		
            <div class="form-group row">
                <label class="col-sm-3 col-form-label" for="programme">Programme</label>
                <div class="col-sm-9">
                    <select class="form-select" name="programme" id="programme" style="width:75%">
                        <option value="">--Select Programme--</option>
                        <?php 
                        
                        while ($row = mysqli_fetch_assoc($programmeSelectResult)) {
                                $programmeName = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
                                $selected = $programme === $currentProgramme ? 'selected' : '';
                                echo "<option value=\"$programmeName\" $selected>$programmeName</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <label class="col-sm-3 col-form-label" for="department">Department</label>
                <div class="col-sm-9">
                    <select class="form-select" name="department" id="department" style="width:25%">
                        <option value="">--Select Department--</option>
                        <?php 
                        while ($row = mysqli_fetch_assoc($departmentSelectResult)) {
                                $deptName = htmlspecialchars($row['dept_name'], ENT_QUOTES, 'UTF-8');
                                $selected = $deptName === $currentDepartment ? 'selected' : '';
                                echo "<option value=\"$deptName\" $selected>$deptName</option>";
                            }
                        ?>
                    </select>
                </div>
            </div>
            <br /><br />
            
            <div class="form-group row">
                <label class="col-sm-3 col-form-label" for="course">Course</label>
                <div class="col-sm-9">
                    <select class="form-select" name="course" id="course" style="width:25%">
                        <option value="">--Select Department First--</option>
                    </select>
                </div>		
            </div>
            <br >
            
            <div class="row">
                <div class="col-12 text-end">
                    <button type="button" id="submitClaim" class="btn btn-success btn-lg">Submit Claim</button>
                </div>
            </div>

            <!-- Container for dynamically added rows -->
            <div id="courseTimeRows" class="mt-4">
                <!-- Dynamic rows for times and dates will be appended here -->
            </div>

            <!-- Button to add a new time slot -->
            <div class="col-md-3 col-sm-6 mb-2">
                <button type="button" id="addTimeSlot" class="btn btn-secondary btn-rounded btn-block" >Add Time Slot</button>
            </div>
		</form>

        
        <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
        <script>
            $(document).ready(function () {

                $(document).on("click", "#submitClaim", function (e) {
                    $("#newClaimForm").submit(); 
                }); 

                $(document).on("submit", "#newClaimForm", function (e) {
                    e.preventDefault();

                    const department = document.getElementById("department").value;
                    const programme = document.getElementById("programme").value;
                    const course = document.getElementById("course").value;
                    const rate = document.getElementById("rate").value;

                    // Validate department, programme, and course are selected
                    if (!department || !programme || !course) {
                        alert("Please select department, programme, and course before submitting.");
                        return;
                    }

                    // Gather form data
                    const claimData = {
                        department: department,
                        programme: programme,
                        course: course,
                        rate: rate, // Assuming there's a rate field
                        //rate: 35,
                        timeSlots: [],
                    };

                    // Iterate through each time slot row
                    const timeSlotDivs = document.querySelectorAll(".time-slot");
                    timeSlotDivs.forEach((slotDiv) => {
                        const startTime = slotDiv.querySelector('input[name^="startTime"]').value;
                        const endTime = slotDiv.querySelector('input[name^="endTime"]').value;
                        const periods = 30;//slotDiv.querySelector('input[name^="period"]').value;
                        const subTotal = 450; //slotDiv.querySelector('input[name^="subTotal"]').value;
                        const fuelComponent = slotDiv.querySelector('input[name^="fuelComponent"]')?.checked ? "Yes" : "No";

                        // Collect dates for this time slot
                        const dates = Array.from(slotDiv.querySelectorAll('input[name^="dates"]')).map(
                            (dateInput) => dateInput.value
                        );

                        // Push the time slot data with its dates
                        claimData.timeSlots.push({
                            startTime,
                            endTime,
                            periods,
                            subTotal,
                            fuelComponent,
                            dates,
                        });
                    });

                    // Validate at least one time slot is present
                    if (claimData.timeSlots.length === 0) {
                        alert("Please add at least one time slot with valid details before submitting.");
                        return;
                    }

                     // Convert claimData into FormData
                    const formData = new FormData();
                    formData.append("department", claimData.department);
                    formData.append("programme", claimData.programme);
                    formData.append("course", claimData.course);
                    formData.append("rate", claimData.rate);

                    claimData.timeSlots.forEach((slot, index) => {
                        formData.append(`timeSlots[${index}][startTime]`, slot.startTime);
                        formData.append(`timeSlots[${index}][endTime]`, slot.endTime);
                        formData.append(`timeSlots[${index}][periods]`, slot.periods);
                        formData.append(`timeSlots[${index}][subTotal]`, slot.subTotal);
                        formData.append(`timeSlots[${index}][fuelComponent]`, slot.fuelComponent);

                        slot.dates.forEach((date, dateIndex) => {
                            formData.append(`timeSlots[${index}][dates][${dateIndex}]`, date);
                        });
                    });		
                    
                    $.ajax({
                        type: "POST",
                        url: "multiClaimsSubmit.inc.php",
                        data: formData,
                        processData: false,
                        contentType: false, 
                        cache:false,
                        success:function(result) {
                            console.log(result);
                        },
                        error: function(xhr, status, error) {
                            console.log(error);
                        }
                    })
                });  
            });
        </script>
		
		<script>
			document.getElementById("department").addEventListener("change", function () {
				const department = this.value;
				const courseDropdown = document.getElementById("course");

				// Reset course dropdown and disable initially
				courseDropdown.innerHTML = `<option value="">--Select Department First--</option>`;
				courseDropdown.disabled = !department;
	
				if (department) {
					// Make an AJAX call to fetch courses for the selected department
					fetch(`getCourses.php?department=${encodeURIComponent(department)}`)
						.then(response => response.json())
						.then(courses => {
							// Populate the courses dropdown
							courseDropdown.innerHTML = `<option value="">--Select Course--</option>`;
							courses.forEach(course => {
								const option = document.createElement("option");
								option.value = course.name;
								option.textContent = course.name;
								courseDropdown.appendChild(option);
							});
						});
				}
			});
			
// Enable the "Add Time Slot" button when a course is selected
document.getElementById("course").addEventListener("change", function () {
    const course = this.value;
    document.getElementById("addTimeSlot").disabled = !course;
});

// Add a new time slot row
document.getElementById("addTimeSlot").addEventListener("click", function () {
    const course = document.getElementById("course").value;

    if (!course) {
        alert("Please select a course first.");
        return;
    }

    // Create a container for the time slot
    const timeSlotDiv = document.createElement("div");
    timeSlotDiv.className = "time-slot mb-4";

    // Create time input fields
    const timeRow = document.createElement("div");
    timeRow.className = "row mb-2";

    const startTimeCol = document.createElement("div");
    startTimeCol.className = "col-md-5";
    const startTimeInput = document.createElement("input");
    startTimeInput.type = "time";
    startTimeInput.name = `startTime[${course}][]`;
    startTimeInput.className = "form-control";
    startTimeCol.appendChild(startTimeInput);

    const endTimeCol = document.createElement("div");
    endTimeCol.className = "col-md-5";
    const endTimeInput = document.createElement("input");
    endTimeInput.type = "time";
    endTimeInput.name = `endTime[${course}][]`;
    endTimeInput.className = "form-control";
    endTimeCol.appendChild(endTimeInput);

    timeRow.appendChild(startTimeCol);
    timeRow.appendChild(endTimeCol);

    timeSlotDiv.appendChild(timeRow);

    // Add fields for `periods`, `subTotal`, and `fuelComponent`
    const detailsRow = document.createElement("div");
    detailsRow.className = "row mb-2";

    const periodsCol = document.createElement("div");
    periodsCol.className = "col-md-4";
    const periodsInput = document.createElement("input");
    periodsInput.type = "number";
    periodsInput.name = `period[${course}][]`;
    periodsInput.className = "form-control";
    periodsInput.placeholder = "Periods";
    detailsRow.appendChild(periodsCol);

    const subTotalCol = document.createElement("div");
    subTotalCol.className = "col-md-4";
    const subTotalInput = document.createElement("input");
    subTotalInput.type = "number";
    subTotalInput.name = `subTotal[${course}][]`;
    subTotalInput.className = "form-control";
    subTotalInput.placeholder = "Sub Total";
    detailsRow.appendChild(subTotalCol);

    <?php if($fuelComponent): ?>

    const fuelCol = document.createElement("div");
    fuelCol.className = "col-md-4";
    const fuelLabel = document.createElement("label");
    fuelLabel.className = "form-check-label me-2";
    fuelLabel.textContent = "Fuel Component";
    const fuelInput = document.createElement("input");
    fuelInput.type = "checkbox";
    fuelInput.name = `fuelComponent[${course}][]`;
    fuelInput.className = "form-check-input";
    fuelCol.appendChild(fuelLabel);
    fuelCol.appendChild(fuelInput);

    detailsRow.appendChild(fuelCol);
    <?php endif; ?>

    timeSlotDiv.appendChild(detailsRow);

    // Add dates under the time slot
    const dateContainer = document.createElement("div");
    dateContainer.className = "date-container";

    const addDateButton = document.createElement("button");
    addDateButton.type = "button";
    addDateButton.textContent = "Add Date";
    addDateButton.className = "btn btn-primary mb-2";
    addDateButton.addEventListener("click", function () {
        const dateRow = document.createElement("div");
        dateRow.className = "row mb-2";

        const dateCol = document.createElement("div");
        dateCol.className = "col-md-10";
        const dateInput = document.createElement("input");
        dateInput.type = "date";
        dateInput.name = `dates[${course}][]`;
        dateInput.className = "form-control";
        dateCol.appendChild(dateInput);

        dateRow.appendChild(dateCol);
        dateContainer.appendChild(dateRow);
    });

    timeSlotDiv.appendChild(addDateButton);
    timeSlotDiv.appendChild(dateContainer);

    // Append time slot to the container
    document.getElementById("courseTimeRows").appendChild(timeSlotDiv);
});
		</script>
        </div>
        </div>
        </div>
        </div>
	</body>
</html>