<?php
$pageTitle = "Approver Dashboard";

include "./assets/partials/head.php";
require_once '../../includes/functions.php';
require_once './queries/approval.queries.php';

$approverStage      = isset($_SESSION['stage']) ? (int) $_SESSION['stage'] : 0;
$approverDepartment = isset($_SESSION['dept'])  ? $_SESSION['dept']        : '';

$claims = db_get_pending_claims_for_stage($conn, $approverStage, $approverDepartment);
?>

<body>
    <!--Body Wrapper -->
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
        data-sidebar-position="fixed" data-header-position="fixed">

        <?php include './assets/partials/sidebar.php' ?>

        <div class="body-wrapper">
            <?php include './assets/partials/header.html'; ?>

			<div class="container-fluid">

                <br /><br />
                <h5 class="card-title fw-semibold mb-4">Pending Claims</h5>
                <?php if ($approverStage && $approverStage !== 1): ?>
                    <div class="mx-auto" style="width: 25%;">
                        <label for="departmentFilter">Filter by Department:</label>
                        <select id="departmentFilter" class="form-select" onchange="filterTable()">
                            <option value="">All Departments</option>
                            <?php
                            $departments = array_unique(array_column($claims, 'department'));
                            foreach ($departments as $department) {
                                echo "<option value='" . htmlspecialchars($department) . "'>" . htmlspecialchars($department) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="col-lg-12 d-flex align-items-stretch">
                    <div class="card">
                        <div class="card-body p-8">
                            <div class="table-responsive">
                                <table class="table text-nowrap mb-0 align-middle">
                                    <thead class="text-dark fs-4">
                                        <tr>
                                            <th class="border-bottom-0">S/N</th>
                                            <th class="border-bottom-0">Claimant</th>
                                            <th class="border-bottom-0">Course</th>
                                            <th class="border-bottom-0">Date Submitted</th>
                                            <th class="border-bottom-0">View Details</th>
                                            <th class="border-bottom-0">Approve</th>
                                            <th class="border-bottom-0">Flag</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($claims)) :
                                            $index = 1;
                                        ?>
                                        <?php foreach ($claims as $claim) : ?>
                                            <tr data-department="<?php echo htmlspecialchars($claim['department']); ?>" id="<?php echo $claim['claimId']; ?>">
                                                <td><?php echo $index; ?></td>
                                                <td><?php echo htmlspecialchars($claim['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($claim['course']); ?></td>
                                                <td><?php echo date('d-m-Y', strtotime($claim['time_submitted'])); ?></td>
                                                <td>
                                                    <button class="btn btn-info" onclick="viewDetails('<?php echo $claim['claimId']; ?>')">View Details</button>
                                                </td>
                                                <td>
                                                    <button class="btn btn-success" onclick="approve('<?php echo $claim['claimId']; ?>')">Approve</button>
                                                </td>
                                                <td>
                                                    <button class="btn btn-danger" onclick="openFlagModal('<?php echo $claim['claimId']; ?>')">Flag</button>
                                                </td>
                                            </tr>

                                            <!-- Per-row flag modal -->
                                            <div id="flagModal_<?php echo $claim['claimId']; ?>" class="modal" tabindex="-1" role="dialog">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Flag Claim</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Enter reason for flagging claim:</p>
                                                            <textarea id="flagReason_<?php echo $claim['claimId']; ?>" rows="4" class="form-control"></textarea>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                            <button type="button" class="btn btn-danger" onclick="flag('<?php echo $claim['claimId']; ?>')">Flag</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php
                                            $index++;
                                            endforeach;
                                        ?>

                                        <?php else : ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No claims found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div> <!-- Close table-responsive -->
                        </div> <!-- Close card-body -->
                    </div> <!-- Close card -->
                </div> <!-- Close col-lg-12 -->

		   </div> <!--Close container-fluid-->
        </div> <!-- Close body-wrapper -->
    </div> <!-- Close page-wrapper -->

    <!--Claim Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" role="dialog" aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel">Claim Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Details will be loaded here via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <script>
    var csrfToken = '<?php echo h(csrf_token()); ?>';

    function filterTable() {
        var filter = document.getElementById('departmentFilter').value;
        var rows = document.querySelectorAll('tbody tr');
        rows.forEach(function(row) {
            var dept = row.getAttribute('data-department');
            if (dept !== null) {
                row.style.display = (filter === '' || dept === filter) ? '' : 'none';
            }
        });
    }

    function viewDetails(claimId) {
        $.ajax({
            url: 'getClaimDetails.inc.php',
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

    function approve(claimId) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'approveClaim.inc.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send('claimId=' + encodeURIComponent(claimId) + '&csrf_token=' + encodeURIComponent(csrfToken));
        xhr.onload = function() {
            try {
                var res = JSON.parse(xhr.responseText);
                if (res.success) {
                    alert(res.message || 'Claim approved successfully!');
                    window.location.reload();
                } else {
                    alert('Error: ' + (res.message || xhr.statusText));
                }
            } catch (e) {
                alert('Unexpected server response. Please reload the page.');
            }
        };
        xhr.onerror = function() {
            alert('Network error occurred while trying to approve claim.');
        };
    }

    function openFlagModal(claimId) {
        $('#flagModal_' + claimId).modal('show');
    }

    function flag(claimId) {
        var flagReason = $('#flagReason_' + claimId).val();
        if (flagReason.trim() === '') {
            alert('Please enter a reason for flagging.');
            return;
        }
        var formData = new FormData();
        formData.append('claimId', claimId);
        formData.append('flagReason', flagReason);
        formData.append('csrf_token', csrfToken);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'flagClaim.inc.php', true);
        xhr.onload = function() {
            try {
                var res = JSON.parse(xhr.responseText);
                if (res.success) {
                    alert(res.message || 'Claim flagged successfully!');
                } else {
                    alert('Error: ' + (res.message || 'Unknown error.'));
                }
            } catch (e) {
                alert('Unexpected server response. Please reload the page.');
            }
            $('#flagModal_' + claimId).modal('hide');
            window.location.reload();
        };
        xhr.onerror = function() {
            alert('Network error occurred while trying to flag claim.');
        };
        xhr.send(formData);
    }
    </script>

    <script src="./assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="./assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/js/sidebarmenu.js"></script>
    <script src="./assets/js/app.min.js"></script>
    <script src="./assets/libs/simplebar/dist/simplebar.js"></script>
</body>
</html>
