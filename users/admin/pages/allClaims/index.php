<?php
    // Include session handling (assuming it's needed)
    // include "session.php";

    // Set the page title
    $pageTitle = "Claims Overview";

    // Include head section
    include "../../assets/partials/head.php";

    // Database connection assuming $conn is already established

    // Query to fetch claims from different tables
    $claimSelectQuery = "
        SELECT claimId, department, programme, course, 'COMPLETED' AS status FROM completed_claims
        UNION ALL 
        SELECT claimId, department, programme, course, 'FLAGGED' AS status FROM flagged_claims
        UNION ALL 
        SELECT claimTempId AS claimId,  department, programme, course, 'SAVED' AS status FROM saved_claims
        UNION ALL
        SELECT claimId AS claimId,  department, programme, course, 'IN PROGRESS' AS status 
        FROM claim_details 
        WHERE flagged <> 1;";

        $claimSelectResult = mysqli_query($conn, $claimSelectQuery);

        // Fetch all claims as associative array
        $claims = mysqli_fetch_all($claimSelectResult, MYSQLI_ASSOC);

    // Query to fetch all department names from the 'department' table
    $departmentSelectQuery = "SELECT dept_name FROM department";
    $result = $conn->query($departmentSelectQuery);

    // Array to store department names
    $departments = [];

    if ($result->num_rows > 0) {
        // Fetching department names and storing them in the array
        while ($row = $result->fetch_assoc()) {
            $departments[] = $row['dept_name'];
        }
    }
    
    $courseSelectQuery = "SELECT name FROM course";
    $result = $conn->query($courseSelectQuery);

    // Array to store course names
    $courses = [];

    if ($result->num_rows > 0) {
        // Fetching course names and storing them in the array
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row['name'];
        }
    }    

    $programmeSelectQuery = "SELECT name FROM programme";
    $result = $conn->query($programmeSelectQuery);

    // Array to store department names
    $programmes = [];

    if ($result->num_rows > 0) {
        // Fetching department names and storing them in the array
        while ($row = $result->fetch_assoc()) {
            $programmes[] = $row['name'];
        }
    }    

    // Include sidebar
    include '../../assets/partials/sidebar.php';

    // Include header
    include '../../assets/partials/header.php';
?>

<!-- Body Wrapper -->
<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    <div class="body-wrapper">
        <div class="container-fluid">
            <h3>Claims Overview</h3>

            <div class="row form-group">
                <div class="col-md-3">
                    <label for="department-filter">Department</label>
                    <select name="department-filter" id="department-filter" class="form-control">
                        <option value="">Select an option</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="programme-filter">Programme</label>
                    <select name="programme-filter" id="programme-filter" class="form-control">
                        <option value="">Select an option</option>
                        <?php foreach ($programmes as $prog): ?>
                            <option value="<?php echo htmlspecialchars($prog); ?>"><?php echo htmlspecialchars($prog); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="courses-filter">Course</label>
                    <select name="courses-filter" id="courses-filter" class="form-control">
                        <option value="">Select an option</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo htmlspecialchars($course); ?>"><?php echo htmlspecialchars($course); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="status-filter">Status</label>
                    <select name="status-filter" id="status-filter" class="form-control">
                        <option value="">Select an option</option>
                        <option value="IN PROGRESS">In-Progress</option>
                        <option value="SAVED">Saved</option>
                        <option value="FLAGGED">Flagged</option>
                        <option value="COMPLETED">Completed</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped" id="claimsTable">
                    <thead>
                        <tr>
                            <th>Claim ID</th>
                            <th>Department</th>
                            <th>Programme</th>
                            <th>Course</th>
                            <th>Status</th>
                            <!-- Removed unnecessary empty headers -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($claims as $claim): ?>
                        <tr id="<?php echo $claim['claimId']; ?>">
                            <td><?php echo $claim['claimId']; ?></td>
                            <td><?php echo $claim['department']; ?></td>
                            <td><?php echo $claim['programme']; ?></td>
                            <td><?php echo $claim['course']; ?></td>
                            <td><?php echo $claim['status']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// JavaScript for filtering table
document.addEventListener("DOMContentLoaded", function() {
        const departmentFilter = document.getElementById('department-filter');
        const programmeFilter = document.getElementById('programme-filter');
        const coursesFilter = document.getElementById('courses-filter');
        const statusFilter = document.getElementById('status-filter');
        const tableRows = document.querySelectorAll('#claimsTable tbody tr');

        function applyFilters() {
            const departmentValue = departmentFilter.value.trim().toLowerCase();
            const programmeValue = programmeFilter.value.trim().toLowerCase();
            const coursesValue = coursesFilter.value.trim().toLowerCase();
            const statusValue = statusFilter.value.trim().toLowerCase();

            tableRows.forEach(row => {
                const departmentText = row.children[1].textContent.trim().toLowerCase();
                const programmeText = row.children[2].textContent.trim().toLowerCase();
                const coursesText = row.children[3].textContent.trim().toLowerCase();
                const statusText = row.children[4].textContent.trim().toLowerCase();

                const departmentMatch = departmentValue === '' || departmentText === departmentValue;
                const programmeMatch = programmeValue === '' || programmeText === programmeValue;
                const coursesMatch = coursesValue === '' || coursesText === coursesValue;
                const statusMatch = statusValue === '' || statusText === statusValue;

                if (departmentMatch && programmeMatch && coursesMatch && statusMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        departmentFilter.addEventListener('change', applyFilters);
        programmeFilter.addEventListener('change', applyFilters);
        coursesFilter.addEventListener('change', applyFilters);
        statusFilter.addEventListener('change', applyFilters);
    });
</script>

<!-- JavaScript imports -->
<script src="../../assets/libs/jquery/dist/jquery.min.js"></script>
<script src="../../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/sidebarmenu.js"></script>
<script src="../../assets/js/app.min.js"></script>
<script src="../../assets/libs/simplebar/dist/simplebar.js"></script>
</body>
</html>
