<?php
$pageTitle = 'Multi Claims Trial';

// Start the session
session_start();

// Database connection
// include_once '../../includes/conn.inc.php';
include_once '../../assets/partials/_head.php';

$currentRate = $_SESSION['rate'] ?? '';

// Query to select all programmes from the database
$programmeSelectQuery = 'SELECT * FROM programme;';
// Execute the query
$programmeSelectResult = mysqli_query($conn, $programmeSelectQuery);

// Query to select all departments from the database
$departmentSelectQuery = 'SELECT * FROM department ORDER BY dept_name ASC; ';
// Execute the query
$departmentSelectResult = mysqli_query($conn, $departmentSelectQuery);

$currentDepartment = 'ICT';  // Replace with your test value
$currentProgramme = 'BME';

function outputFullName()
{
    if (isset($_SESSION['full_name'])) {
        echo $_SESSION['full_name'];
    }
}

$currentRate = $_SESSION['rate'] ?? '';

// Query to select all programmes from the database
$programmeSelectQuery = 'SELECT * FROM programme;';
// Execute the query
$programmeSelectResult = mysqli_query($conn, $programmeSelectQuery);

// Query to select all departments from the database
$departmentSelectQuery = 'SELECT * FROM department ORDER BY dept_name ASC; ';
// Execute the query
$departmentSelectResult = mysqli_query($conn, $departmentSelectQuery);

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
        <?php include '../../assets/partials/_navbar.php'; ?>

        <div class="container-fluid page-body-wrapper">
            <?php include '../../assets/partials/_sidebar.php' ?>

            <div class="main-panel">
                <div class="content-wrapper">
                    
                <form action="" id="newClaimForm" name="newClaimForm"></form>
                     <?php
function getClaimFieldValue($field)
{
    return isset($_SESSION['claim_data'][$field]) ? $_SESSION['claim_data'][$field] : '';
}
?>
                         <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="rate">Rate (GH₵)</label>
                            <div class="col-sm-9">
                                <input type="text" name="rate" class="form-control" id="rate" style="width:50%" 
                                        value="<?php echo $currentRate ?>" readonly>
                            </div>
                        </div>		
                    
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="department">Department</label>
                            <div class="col-sm-9">
                                <select class="form-select" name="department" id="department" style="width:75%">
                                    <option value="">--Select Department--</option>
                                    <?php
                                    while ($row = mysqli_fetch_assoc($departmentSelectResult)) {
                                        $selected = $row['dept_name'] == getClaimFieldValue('department') ? 'selected' : '';
                                        echo '<option value="' . $row['dept_name'] . "\" $selected>" . $row['dept_name'] . '</option>';
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
                                        echo '<option value="' . $row['name'] . "\" $selected>" . $row['name'] . '</option>';
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

                        <!-- Container for dynamically added rows -->
                        <div id="courseTimeRows" class="mt-4">
                            <!-- Dynamic rows for times and dates will be appended here -->
                        </div>

                        <!-- Container for other details -->
                        <div id="detailsRowsDiv" class="mt-4">
                            <!-- Details for times and dates will be appended here -->
                        </div>

                        <!-- Buttons for form actions -->               
                        <div class="form-group row">
                            <div class="col-md-3 col-sm-6 mb-2">
                                <button type='button' id="addTimeSlot" class="btn btn-secondary btn-rounded btn-block">Add Timeslot</button>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <button type='submit' id='saveFormDetails' name='submitBtn'  class="btn btn-primary btn-rounded btn-block">Save Claim Info</button>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <button type='submit' id='submitClaim' name='submitBtn'  class="btn btn-success btn-rounded btn-block">Submit Claim</button>
                            </div>                           
                        </div>

                </div>
                <!--Footer goes here -->
            </div>
        </div>
    </div>

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

        // Add a new time slot row
        document.getElementById("addTimeSlot").addEventListener("click", function () {
            const course = document.getElementById("course").value;

            if (!course) {
                alert("Please select a course first.");
                return;
            }

            // Create a container for the time slot
            const timeSlotDiv = document.createElement("div");
            timeSlotDiv.className = "time-slot mb-4 border p-3 rounded bg-light";

            // Create time input fields
            const timeRow = document.createElement("div");
            timeRow.className = "row mb-2";

            const startTimeCol = document.createElement("div");
            startTimeCol.className = "col-md-5";
            const startTimeInput = document.createElement("input");
            startTimeInput.type = "time";
            startTimeInput.name = `startTime[${course}][]`;
            startTimeInput.className = "form-control";
            startTimeInput.placeholder = "Start Time";
            startTimeCol.appendChild(startTimeInput);
            startTimeInput.addEventListener('input', function() {
                checkDuplicateDateTime();
            });

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
            periodsCol.appendChild(periodsInput);
            
            const periodsTooltip = document.createElement("span");
            periodsTooltip.className = "tooltip-text";
            periodsTooltip.textContent = "Number of periods for the course";
            periodsCol.appendChild(periodsTooltip);

            const subTotalCol = document.createElement("div");
            subTotalCol.className = "col-md-4";
            const subTotalInput = document.createElement("input");
            subTotalInput.type = "number";
            subTotalInput.name = `subTotal[${course}][]`;
            subTotalInput.className = "form-control";
            subTotalInput.placeholder = "Sub Total";
            subTotalCol.appendChild(subTotalInput);
            
            const subTotalTooltip = document.createElement("span");
            subTotalTooltip.className = "tooltip-text";
            subTotalTooltip.textContent = "Subtotal cost for this time slot";
            subTotalCol.appendChild(subTotalTooltip);

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
            
            const fuelTooltip = document.createElement("span");
            fuelTooltip.className = "tooltip-text";
            fuelTooltip.textContent = "Check if fuel component applies";
            fuelCol.appendChild(fuelTooltip);

            detailsRow.appendChild(fuelCol);
            <?php endif; ?>

            const detailsDiv = document.getElementById('detailsRow');
            //timeSlotDiv.appendChild(detailsRow);
            detailsRowsDiv.appendChild(detailsRow);

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

                const dateTooltip = document.createElement("span");
                dateTooltip.className = "tooltip-text";
                dateTooltip.textContent = "Select the date for this time slot";
                dateCol.appendChild(dateTooltip);

                dateRow.appendChild(dateCol);
                dateContainer.appendChild(dateRow);
            });

            timeSlotDiv.appendChild(addDateButton);
            timeSlotDiv.appendChild(dateContainer);

            // Append time slot to the container
            document.getElementById("courseTimeRows").appendChild(timeSlotDiv);
        });  

        // Function to check for duplicate date and time
        function checkDuplicateDateTime() {
        var rows = document.getElementsByName('date[]');
        const course = document.getElementById("course").value;
        var startTimes = document.getElementsByName(`startTime[${course}][]`);
        
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
            document.getElementById('addTimeSlot').disabled = true;
            document.getElementById('saveFormDetails').disabled = true;
            document.getElementById('submitClaim').disabled = true;


        } else {
            // Enable submit button if no duplicates
            document.getElementById('addTimeSlot').disabled = false;
            document.getElementById('saveFormDetails').disabled = false;
            document.getElementById('submitClaim').disabled = false;
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



        $(document).ready(function () {
            //Event listener for clicking the submit claim button
            $(document).on("click", "#submitClaim", function (e) {
                $("#newClaimForm").submit(); 
            }); 

            //Handler for parsing and submitting form data
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
                        alert("Claim submitted successfully");
                        window.location.reload();

                    },
                    error: function(xhr, status, error) {
                        console.log(error);
                    }
                })
            });  
        });
    </script>
</body>