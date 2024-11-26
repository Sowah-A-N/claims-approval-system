<?php
    // Include session handling (assuming it's needed)
    // include "session.php";

    // Set the page title
    $pageTitle = "All Users";

    // Include head section
    include "../../assets/partials/head.php";

    // Database connection assuming $conn is already established

    // Query to fetch all user details
    $userSelectQuery = "SELECT *, CONCAT(first_name,' ', last_name) 
                        AS full_name 
                        FROM user_details";
    $userSelectResult = mysqli_query($conn, $userSelectQuery);

    // Fetch all rows (if needed) - assuming there are multiple users
    $users = mysqli_fetch_all($userSelectResult, MYSQLI_ASSOC);

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
            <h3>All Users</h3>

            <div class="row form-group">

                <div class="col-md-3">
                    <label for="department-filter">Department</label>
                    <select name="department-filter" id="department-filter" class="form-control">
                        <option value="">Select an option</option>
                        <option value="ICT">ICT</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="role-filter">Role</label>
                    <select name="role-filter" id="role-filter" class="form-control">
                        <option value="">Select an option</option>
                        <option value="claimant">Claimant</option>
                        <option value="approver">Approver</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="status-filter">Account Status</label>
                    <select name="status-filter" id="status-filter" class="form-control">
                        <option value="">Select an option</option>
                        <option value="active">Active</option>
                        <option value="disabled">Disabled</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table id="userDetailsTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Department</th>
                            <th>Phone No.</th>
                            <th>Role</th>
                            <th>Account Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr id="<?php echo $user['userId']?>">
                            <!-- Display user details in table rows -->
                            <td><?php echo $user['full_name']; ?></td>
                            <td><?php echo $user['department'];?></td>
                            <td><?php echo $user['phone_number']; ?></td>
                            <td><?php echo $user['role']; ?></td>
                            <td><?php echo $user['account_status']; ?></td>
                            <td>
                                <!-- View User Details -->
                                <span class="ti ti-eye"
									  data-toggle="tooltip" data-placement="left" title="View Details"
                                    style="font-size: 24px; cursor: pointer; margin-right: 10px;"
                                    onclick="viewUserDetails('<?php echo $user['userId']; ?>')">
                                </span>

                                <!-- Disable/Enable User Account -->
                                <?php if ($user['account_status'] == 'disabled'): ?>
                                    <span class="ti ti-check"
										  data-toggle="tooltip" data-placement="left" title="Activate"
                                        style="font-size: 24px; cursor: pointer;"
                                        onclick="activateUserAccount('<?php echo $user['userId']; ?>')">
                                    </span>
                                <?php else: ?>
                                    <span class="ti ti-ban"
										  data-toggle="tooltip" data-placement="left" title="Disable"
                                        style="font-size: 24px; cursor: pointer;"
                                        onclick="disableUserAccount('<?php echo $user['userId']; ?>')">
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>  

            </div>
        </div>
    </div>
</div>

   <!-- User Details Modal -->
    <div class="modal fade" id="userDetailsModal" tabindex="-1" aria-labelledby="userDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userDetailsModalLabel">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="userDetailsContent">
                        <!-- User details will be populated here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <!-- Add any other footer buttons if needed -->
                </div>
            </div>
        </div>
    </div>



<script>
   function viewUserDetails(userId) {
    fetch('getUserDetails.inc.php?userId=' + userId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log(data);
            // Populate modal content with user details
            document.getElementById('userDetailsModalLabel').textContent = data.full_name + '\'s Details';
            document.getElementById('userDetailsContent').innerHTML = `
                <p><strong>Department:</strong> ${data.department}</p>
                <p><strong>Phone Number:</strong> ${data.phone_number}</p>
                <p><strong>Role:</strong> ${data.role}</p>
                <p><strong>Status:</strong> ${data.account_status}</p>
                <!-- Add more details as needed -->
            `;
            
            // Show the modal
            var modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error fetching user details:', error);
            // Optionally show an error message to the user
        });
}


    function disableUserAccount(userId) {
        // Send AJAX request to disable user account
        fetch('disableUserAcct.inc.php?userId=' + userId)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text(); // Assuming response is text
            })
            .then(data => {
                // Display success message or handle further actions
                console.log(data); // Log response message
                alert(data); // Show success message to the user
                // Optionally update UI or perform additional actions
            })
            .catch(error => {
                console.error('Error disabling account:', error);
                alert('Error disabling account: ' + error.message);
                // Optionally show an error message to the user
            });
    }


    function activateUserAccount(userId) {
        // Send AJAX request to activate user account
        fetch('activateUserAcct.inc.php?userId=' + userId)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text(); // Assuming response is text
            })
            .then(data => {
                // Display success message or handle further actions
                console.log(data); // Log response message
                alert(data); // Show success message to the user
                // Optionally update UI or perform additional actions
            })
            .catch(error => {
                console.error('Error activating account:', error);
                alert('Error activating account: ' + error.message);
                // Optionally show an error message to the user
            });
    }

    // JavaScript to handle dropdown filtering
    // document.addEventListener('DOMContentLoaded', function() {
    //     const departmentFilter = document.getElementById('department-filter');
    //     const roleFilter = document.getElementById('role-filter');
    //     const statusFilter = document.getElementById('status-filter');
    //     const userTable = document.getElementById('userDetailsTable').getElementsByTagName('tbody')[0];

    //     // Add event listeners to filters
    //     [departmentFilter, roleFilter, statusFilter].forEach(filter => {
    //         filter.addEventListener('change', filterTable);
    //     });

    //     // Function to filter the table based on selection
    //     function filterTable() {
    //         const departmentValue = departmentFilter.value.toLowerCase();
    //         const roleValue = roleFilter.value.toLowerCase();
    //         const statusValue = statusFilter.value.toLowerCase();

    //         Array.from(userTable.rows).slice(1).forEach(row => {
    //             const department = row.cells[1].textContent.toLowerCase();
    //             const role = row.cells[3].textContent.toLowerCase();
    //             const status = row.cells[4].textContent.toLowerCase();

    //             const departmentMatch = department.includes(departmentValue) || departmentValue === 'all';
    //             const roleMatch = role.includes(roleValue) || roleValue === 'all';
    //             const statusMatch = status.includes(statusValue) || statusValue === 'all';

    //             if (departmentMatch && roleMatch && statusMatch) {
    //                 row.style.display = '';
    //             } else {
    //                 row.style.display = 'none';
    //             }
    //         });
    //     }
    // });

        // JavaScript for filtering table
        document.addEventListener("DOMContentLoaded", function() {
            const departmentFilter = document.getElementById('department-filter');
            const roleFilter = document.getElementById('role-filter');
            const statusFilter = document.getElementById('status-filter');
            const tableRows = document.querySelectorAll('#userDetailsTable tbody tr');

            function applyFilters() {
                const departmentValue = departmentFilter.value.trim().toLowerCase();
                const roleValue = roleFilter.value.trim().toLowerCase();
                const statusValue = statusFilter.value.trim().toLowerCase();

                tableRows.forEach(row => {
                    const departmentText = row.children[1].textContent.trim().toLowerCase();
                    const roleText = row.children[3].textContent.trim().toLowerCase();
                    const statusText = row.children[4].textContent.trim().toLowerCase();

                    const departmentMatch = departmentValue === '' || departmentText === departmentValue;
                    const roleMatch = roleValue === '' || roleText === roleValue;
                    const statusMatch = statusValue === '' || statusText === statusValue;

                    if (departmentMatch && roleMatch && statusMatch) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            departmentFilter.addEventListener('change', applyFilters);
            roleFilter.addEventListener('change', applyFilters);
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
