<?php
session_start();

// Initialize variables
$pageTitle = "My Claims";
$userId = $_SESSION['user_id'] ?? 0;

// Include head partial
include_once "../../assets/partials/head.php";


// Function to output full name stored in the session (if available)
function outputFullName() {
    echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
}

// Query for saved claims
$savedClaimsQuery = "SELECT *, 'saved' AS status
                     FROM saved_claims
                     WHERE userId = ?";
$savedClaimsStmt = $conn->prepare($savedClaimsQuery);
$savedClaimsStmt->bind_param('i', $userId);
$savedClaimsStmt->execute();
$savedClaimsResult = $savedClaimsStmt->get_result();

// Query for flagged claims
$flaggedClaimsQuery = "SELECT *, 'flagged' AS status
                       FROM flagged_claims
                       WHERE userId = ?";
$flaggedClaimsStmt = $conn->prepare($flaggedClaimsQuery);
$flaggedClaimsStmt->bind_param('i', $userId);
$flaggedClaimsStmt->execute();
$flaggedClaimsResult = $flaggedClaimsStmt->get_result();

// Query for pending claims
$pendingClaimsQuery = "SELECT * FROM claim_details
                       WHERE userId = ?";
$pendingClaimsStmt = $conn->prepare($pendingClaimsQuery);
$pendingClaimsStmt->bind_param('i', $userId);
$pendingClaimsStmt->execute();
$pendingClaimsResult = $pendingClaimsStmt->get_result();

// Query for completed claims
// $completedClaimsQuery = "SELECT *
//                          FROM completed_claims
//                          WHERE userId = ?";
// $completedClaimsStmt = $conn->prepare($completedClaimsQuery);
// $completedClaimsStmt->bind_param('i', $userId);
// $completedClaimsStmt->execute();
// $completedClaimsResult = $completedClaimsStmt->get_result();
// ?>

<body>
    <div class="container-scroller">
        <?php include "../../assets/partials/_sidebar.php" ?>
        <div class="container-fluid page-body-wrapper">
            <?php include "../../assets/partials/_navbar.php"; ?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <h2>My Claims</h2>

                    <?php
                    // Display Saved Claims
                    renderClaimsTable("Saved Claims", $savedClaimsResult);

                    // Display Completed Claims
                    renderClaimsTable("Completed Claims", $completedClaimsResult);

                    // Display Pending Claims
                    renderClaimsTable("Pending Claims", $pendingClaimsResult);

                    // Display Flagged Claims
                    renderClaimsTable("Flagged Claims", $flaggedClaimsResult);
                    ?>

                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Claim Details -->
    <div class="modal fade" id="detailsModal" tabindex="-1" role="dialog" aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel">Claim Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="claimId" class="col-form-label">Claim ID:</label>
                        <input type="text" class="form-control" id="claimId" readonly>
                    </div>
                    <div class="form-group">
                        <label for="programme" class="col-form-label">Programme:</label>
                        <input type="text" class="form-control" id="programme" readonly>
                    </div>
                    <div class="form-group">
                        <label for="course" class="col-form-label">Course:</label>
                        <input type="course" class="form-control" id="course" readonly>
                    </div>
                    <!-- Add more input fields for additional user details -->
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../../assets/vendors/js/vendor.bundle.base.js"></script>
    <script src="../../assets/vendors/progressbar.js/progressbar.min.js"></script>
    <script src="../../assets/vendors/jvectormap/jquery-jvectormap.min.js"></script>
    <script src="../../assets/vendors/jvectormap/jquery-jvectormap-world-mill-en.js"></script>
    <script src="../../assets/js/off-canvas.js"></script>
    <script src="../../assets/js/misc.js"></script>
    <script src="../../assets/js/settings.js"></script>
    <script src="../../assets/js/todolist.js"></script>

    <script>
        // Function to edit claim
        function editClaim(claimId) {
            $.ajax({
                url: 'editClaimDetails.inc.php',
                type: 'GET',
                data: { claimId: claimId },
                success: function(response) {
                    $('#detailsModal .modal-body').html(response);
                    $('#detailsModal').modal('show');
                },
                error: function(xhr, status, error) {
                    console.error(error);
                    alert('An error occurred while fetching claim details.');
                }
            });
        }

        // Function to delete a claim
        function deleteClaim(claimId) {
            $.ajax({
                url: 'deleteClaim.inc.php',
                type: 'POST',
                dataType: 'json',
                data: { claimId: claimId },
                success: function(response) {
                    alert(response.success); // Assuming the response contains a success message
                },
                error: function(xhr, status, error) {
                    alert("Error deleting claim: " + xhr.responseText);
                }
            });
        }
    </script>


    <?php include "../../assets/partials/_footer.html"; ?>
</body>
</html>

<?php
// Function to render claims table based on query result
function renderClaimsTable($title, $result) {
    if ($result->num_rows > 0) {
        echo <<<HTML
        <div class="col-lg-12 stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">$title</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-contextual">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Department</th>
                                    <th>Programme</th>
                                    <th>Course</th>
                                    <th>Date Saved/Stage</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
        HTML;

        while ($row = $result->fetch_assoc()) {
            $claimId = isset($row['claimId']) ? $row['claimId'] : $row['claimTempId']; // Use claimId if available, otherwise claimTempId
            echo '<tr id="' . $claimId . '">';
            echo '<td>' . $claimId . '</td>';
            echo '<td>' . $row['department'] . '</td>';
            echo '<td>' . $row['programme'] . '</td>';
            echo '<td>' . $row['course'] . '</td>';
            echo '<td>' . (isset($row['date_saved']) ? date('d-m-Y', strtotime($row['date_saved'])) : $row['stage']) . '</td>';
            echo '<td>' . $row['status'] . '</td>';
            echo '<td><span class="mdi mdi-eye-outline" style="font-size:1.8rem" onclick="viewClaimDetails(' . $claimId . ')"></span></td>';
            echo '</tr>';
        }

        echo <<<HTML
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    } else {
        echo "<h2>No Claims Found</h2>";
    }
}
?>
