<?php
    //Session include goes here
    $pageTitle = "Admin Dashboard";
?>

<!DOCTYPE html>
<html lang="en">

<?php
    include "./assets/partials/head.php";
      
    $disabledUserQuery = "SELECT * FROM user_details WHERE account_status = 'disabled';";
    $disabledUserResult = mysqli_query($conn, $disabledUserQuery);

    $totalUsersQuery = "SELECT COUNT(*) AS 'total_users' FROM user_details";
    $totalUsersResult = mysqli_query($conn, $totalUsersQuery);

    $activeUsersQuery = "SELECT COUNT(*) AS 'active_users' FROM user_details WHERE `account_status` = 'active';";
    $activeUsersResult = mysqli_query($conn, $activeUsersQuery);

    $disabledUsersQuery = "SELECT COUNT(*) AS 'disabled_users' FROM user_details WHERE `account_status` = 'disabled'; ";
    $disabledUsersResult = mysqli_query($conn, $disabledUsersQuery);

    $totalClaimsQuery = "SELECT COUNT(*) AS 'total_claims' FROM claim_details";
    $totalClaimsResult = mysqli_query($conn, $totalClaimsQuery);

    $flaggedClaimsQuery = "SELECT COUNT(*) AS 'flagged_claims' FROM claim_details WHERE `flagged` = 1";
    $flaggedClaimsResult = mysqli_query($conn, $flaggedClaimsQuery);

    $approverRanksQuery = "SELECT * FROM approver_ranks";
    $approverRanksResult = mysqli_query($conn, $approverRanksQuery);
    $approverRanks = array();
    while ($approverRankRow = mysqli_fetch_assoc($approverRanksResult)) {
        $approverRanks[] = $approverRankRow;
    }


?>

<body>
    <?php include './assets/partials/sidebar.php' ?>

    <!--Body Wrapper -->
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
        data-sidebar-position="fixed" data-header-position="fixed">


        <div class="body-wrapper">

                <?php
                    include './assets/partials/header.php';
                ?>

            <!--User Details Modal -->
            <div class="modal fade" id="userDetailsModal" tabindex="-1" role="dialog" aria-labelledby="userDetailsModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="userDetailsModalLabel">User Details</h5>
                            <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="first_name" class="col-form-label">First Name:</label>
                                <input type="text" class="form-control" id="first_name" readonly>
                                <!-- <p id="first_name"></p> -->
                            </div>
                            <div class="form-group">
                                <label for="last_name" class="col-form-label">Last Name:</label>
                                <input type="text" class="form-control" id="last_name" readonly>
                            </div>
                            <div class="form-group">
                                <label for="phone_number" class="col-form-label">Phone Number:</label>
                                <input type="text" class="form-control" id="phone_number" readonly>
                            </div>
                            <div class="form-group">
                                <label for="gender" class="col-form-label">Gender</label>
                                <input type="text" class="form-control" id="gender" readonly>
                            </div>
                            <div class="form-group">
                                <label for="email" class="col-form-label">Email:</label>
                                <input type="text" class="form-control" id="email" readonly>
                            </div>
                            <div class="form-group">
                                <label for="department" class="col-form-label">Department:</label>
                                <input type="text" class="form-control" id="department" readonly>
                            </div>
                            <div class="form-group">
                                <label for="role" class="col-form-label">Role:</label>
                                <input type="text" class="form-control" id="role" readonly>
                            </div>
                            <div class="form-group">
                                <label for="rank" class="col-form-label">Rank:</label>
                                <input type="text" class="form-control" id="rank" readonly>
                            </div>
                            <div class="form-group">
                                <label for="account_status" class="col-form-label">Account Status:</label>
                                <input type="text" class="form-control" id="account_status" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container-fluid">
    <div class="row">
    <div class="container-fluid">
    <div class="row">
    <div class="container-fluid">
    <div class="row">
        <?php
            // Define an array of queries and their corresponding display titles
            $queries = [
                [
                    'query' => $totalUsersQuery,
                    'field' => 'total_users',
                    'title' => 'Total Users'
                ],
                [
                    'query' => $activeUsersQuery,
                    'field' => 'active_users',
                    'title' => 'Active Users'
                ],
                [
                    'query' => $disabledUsersQuery,
                    'field' => 'disabled_users',
                    'title' => 'Disabled Users'
                ],
                [
                    'query' => $totalClaimsQuery,
                    'field' => 'total_claims',
                    'title' => 'Total Claims'
                ],
                [
                    'query' => $flaggedClaimsQuery,
                    'field' => 'flagged_claims',
                    'title' => 'Flagged Claims'
                ]
            ];

            // Loop through each query to fetch and display the data
            foreach ($queries as $queryInfo) {
                // Execute the query
                $result = mysqli_query($conn, $queryInfo['query']);

                // Check if the query was successful
                if (!$result) {
                    echo '<div class="col-12 col-sm-6 col-md-4 col-lg-2 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h1 class="card-title">Error</h1>
                                    <p class="card-text">Failed to retrieve ' . $queryInfo['title'] . '</p>
                                    <p class="text-danger">Error: ' . mysqli_error($conn) . '</p>
                                </div>
                            </div>
                        </div>';
                } else {
                    // Fetch the result and display the data
                    $row = mysqli_fetch_assoc($result);
                    if ($row) {
                        echo '<div class="col-12 col-sm-6 col-md-4 col-lg-2 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h1 class="card-title">' . htmlspecialchars($row[$queryInfo['field']]) . '</h1>
                                        <p class="card-text">' . htmlspecialchars($queryInfo['title']) . '</p>
                                    </div>
                                </div>
                            </div>';
                    } else {
                        echo '<div class="col-12 col-sm-6 col-md-4 col-lg-2 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h1 class="card-title">No Data</h1>
                                        <p class="card-text">No data available for ' . htmlspecialchars($queryInfo['title']) . '</p>
                                    </div>
                                </div>
                            </div>';
                    }
                }
            }
        ?>
    </div>
</div>

    </div>
</div>

    </div>
</div>


                <?php
                  if ($disabledUserResult->num_rows > 0) {
                    echo '<h5 class="card-title fw-semibold mb-4">Disabled Users</h5>';

                    echo '<div class="col-lg-8 d-flex w-100 align-items-stretch">';
                    echo '<div class="card w-100">';
                    echo '<div class="card-body p-4">';
                    echo '<div class="table-responsive">';
                    echo '<table class="table text-nowrap mb-0 align-middle">';
                    echo '<thead class="text-dark fs-4">';
                    echo '<tr>';
                    echo '<th class="border-bottom-0">User ID</th>';
                    echo '<th class="border-bottom-0">First Name</th>';
                    echo '<th class="border-bottom-0">Last Name</th>';
                    echo '<th class="border-bottom-0">Account Type</th>';
                    echo '<th class="border-bottom-0">Activate Account</th>';
                    echo '<th class="border-bottom-0">View Details</th>';
                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';
                
                    // Inside the while loop for disabled users
                    while ($row = mysqli_fetch_assoc($disabledUserResult)) {
                        echo "<tr id='". $row['userId'] . "'>";
                        echo "<td>" . $row['userId'] . "</td>";
                        echo "<td>" . $row['first_name'] . "</td>";
                        echo "<td>" . $row['last_name'] . "</td>";
                        echo "<td>" . $row['role'] . "</td>";
                       
                        echo "<td>" . 
                            "<span class='btn btn-outline-success m-1' style='cursor: pointer;' onclick='activateAccount(". $row['userId'] .")'
                                id='activate-btn-". $row['userId'] . "' data-user-id='" . $row['userId'] . "'>Activate</span>" . 
                            "</td>";

                        // Assuming you have another similar block for viewing account details
                        echo "<td>" . 
                            "<span class='btn btn-outline-primary m-1' style='cursor: pointer;' onclick='viewAcctDetails(". $row['userId'] .")'
                                id='view-btn-". $row['userId'] . "' data-user-id='" . $row['userId'] . "'>View Details</span>" . 
                            "</td>";

                        echo "</tr>";
                    }

                
                    echo '</tbody>';
                    echo '</table>';
                    echo '</div>'; // Close table-responsive
                    echo '</div>'; // Close card-body
                    echo '</div>'; // Close card
                    echo '</div>'; // Close col-lg-8
                
                }
                ?>
                <?php ?>
        </div>
    </div>    

 
</body>


<script>
    function activateAccount(userId) {
        alert("Activating user ID : " + userId);
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'index.inc.php', true); // Replace 'index.inc.php' with your actual PHP script URL
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                alert(xhr.responseText); // Show success message
                // You can perform any other actions here after successful update
                window.location.reload(); // Refresh the page

            } else {
                alert("Error activating account.");
            }
        };
        xhr.send('action=activateAccount&userId=' + userId);
    }

    function viewAcctDetails(userId) {
        //alert("Viewing details for user ID : " + userId);

        // AJAX request to fetch additional information about the user details
        $.ajax({
            url: 'index.inc.php', // Replace 'index.inc.php' with your actual PHP script URL
            type: 'POST',
            data: {
                action: 'viewAccountDetails',
                userId: userId
            },
            success: function(response) {
                // Assuming the response contains JSON data for user details
                var userDetails = JSON.parse(response);
                // Example: Update modal content with user details
                $('#first_name').val(userDetails.first_name);
                $('#last_name').val(userDetails.last_name);
                $('#phone_number').val(userDetails.phone_number);
                $('#gender').val(userDetails.gender);
                $('#email').val(userDetails.email);
                $('#department').val(userDetails.department);
                $('#role').val(userDetails.role);
                $('#rank').val(userDetails.rank);
                $('#account_status').val(userDetails.account_status);

                //$('#userDetailsModal .modal-body').html('<p>User Name: ' + userDetails.username + '</p>'); // Adjust this as per your response structure
                //$('#userDetailsModal .modal-body').html(userDetails);
                $('#userDetailsModal').modal('show');
                //console.log(response);
                //alert(userDetails);
            },
            error: function(xhr, status, error) {
                console.error(error);
                alert('An error occurred while fetching user details.');
            }
        });
    }   
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
             crossorigin="anonymous"></script>




</html>
