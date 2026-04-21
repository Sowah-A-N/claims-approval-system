<?php
  session_start();

$pageTitle = "My Claims";
include_once "../../assets/partials/_head.php";

$userId = current_user_id();

function outputFullName() {
    echo isset($_SESSION['full_name']) ? h($_SESSION['full_name']) : '';
}

function run_claim_query($conn, $sql, $userId) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

$results = [
    'flaggedClaims'   => run_claim_query($conn,
        "SELECT *, 'Flagged' AS status FROM claim_details WHERE userId = ? AND flagged = 1",
        $userId),
    'pendingClaims'   => run_claim_query($conn,
        "SELECT * FROM claim_details WHERE userId = ? AND flagged <> 1 AND completed <> 1 ORDER BY claimId DESC",
        $userId),
    'savedClaims'     => run_claim_query($conn,
        "SELECT *, 'Saved' AS status FROM saved_claims WHERE userId = ?",
        $userId),
    'completedClaims' => run_claim_query($conn,
        "SELECT *, 'Forwarded to Finance' AS status FROM claim_details WHERE userId = ? AND completed = 1",
        $userId),
];
?>

<body>
    <div class="container-scroller">
        <?php include "../../assets/partials/_navbar.php"; ?>

        <div class="container-fluid page-body-wrapper">
            <?php include "../../assets/partials/_sidebar.php"; ?>

            <div class="main-panel">
                <div class="content-wrapper">

                    <div class="rmu-page-header">
                        <div class="rmu-page-header__title">My Claims</div>
                        <div class="rmu-page-header__sub">View and manage all your submitted, saved and completed claims</div>
                    </div>

                    <?php

                    // ── Flagged Claims ──────────────────────────────────────────
                    echo '<div class="rmu-card" style="margin-bottom:24px;">
                            <div class="rmu-card__header">
                                <span class="rmu-card__title">Flagged Claims</span>
                            </div>
                            <div class="rmu-card__body" style="padding:0;">
                                <div class="rmu-table-wrap">
                                    <table class="rmu-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Department</th>
                                                <th>Programme</th>
                                                <th>Course</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>';

                    if ($results['flaggedClaims']->num_rows > 0) {
                        while ($row = $results['flaggedClaims']->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . h($row['claimId']) . '</td>';
                            echo '<td>' . h($row['department']) . '</td>';
                            echo '<td>' . h($row['programme']) . '</td>';
                            echo '<td>' . h($row['course']) . '</td>';
                            echo '<td><span class="rmu-badge rmu-badge--danger">Flagged</span></td>';
                            echo '<td>
                                    <button class="rmu-btn rmu-btn--secondary" style="padding:5px 9px;" onclick="viewClaimDetails(' . (int)$row['claimId'] . ')" title="View">
                                        <i class="ti ti-eye"></i>
                                    </button>
                                  </td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="6" style="text-align:center;color:var(--txt-muted);padding:20px;">No flagged claims</td></tr>';
                    }

                    echo '      </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>';

                    // ── Pending Claims ──────────────────────────────────────────
                    echo '<div class="rmu-card" style="margin-bottom:24px;">
                            <div class="rmu-card__header">
                                <span class="rmu-card__title">Pending Claims</span>
                            </div>
                            <div class="rmu-card__body" style="padding:0;">
                                <div class="rmu-table-wrap">
                                    <table class="rmu-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Department</th>
                                                <th>Programme</th>
                                                <th>Course</th>
                                                <th>Date Submitted</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>';

                    if ($results['pendingClaims']->num_rows > 0) {
                        $index = 1;
                        while ($row = $results['pendingClaims']->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . $index . '</td>';
                            echo '<td>' . h($row['department']) . '</td>';
                            echo '<td>' . h($row['programme']) . '</td>';
                            echo '<td>' . h($row['course']) . '</td>';
                            echo '<td>' . date('d M Y', strtotime($row['time_submitted'])) . '</td>';
                            echo '<td>
                                    <button class="rmu-btn rmu-btn--secondary" style="padding:5px 9px;" onclick="viewClaimDetails(' . (int)$row['claimId'] . ')" title="View">
                                        <i class="ti ti-eye"></i>
                                    </button>
                                  </td>';
                            echo '</tr>';
                            $index++;
                        }
                    } else {
                        echo '<tr><td colspan="6" style="text-align:center;color:var(--txt-muted);padding:20px;">No pending claims</td></tr>';
                    }

                    echo '      </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>';

                    // ── Saved Claims ────────────────────────────────────────────
                    echo '<div class="rmu-card" style="margin-bottom:24px;">
                            <div class="rmu-card__header">
                                <span class="rmu-card__title">Saved Claims</span>
                            </div>
                            <div class="rmu-card__body" style="padding:0;">
                                <div class="rmu-table-wrap">
                                    <table class="rmu-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Department</th>
                                                <th>Programme</th>
                                                <th>Course</th>
                                                <th>Date Saved</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>';

                    if ($results['savedClaims']->num_rows > 0) {
                        while ($row = $results['savedClaims']->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . h($row['claimTempId']) . '</td>';
                            echo '<td>' . ($row['department'] ? h($row['department']) : '<span style="color:var(--txt-muted);">—</span>') . '</td>';
                            echo '<td>' . ($row['programme']  ? h($row['programme'])  : '<span style="color:var(--txt-muted);">—</span>') . '</td>';
                            echo '<td>' . ($row['course']     ? h($row['course'])     : '<span style="color:var(--txt-muted);">—</span>') . '</td>';
                            echo '<td>' . date('d M Y', strtotime($row['date_saved'])) . '</td>';
                            echo '<td><span class="rmu-badge rmu-badge--neutral">' . h($row['status']) . '</span></td>';
                            echo '<td style="white-space:nowrap;">
                                    <button class="rmu-btn rmu-btn--secondary" style="padding:5px 9px;margin-right:4px;" onclick="editClaim(' . (int)$row['claimTempId'] . ')" title="Edit">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                    <button class="rmu-btn rmu-btn--danger" style="padding:5px 9px;" onclick="deleteClaim(' . (int)$row['claimTempId'] . ')" title="Delete">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                  </td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="7" style="text-align:center;color:var(--txt-muted);padding:20px;">No saved claims</td></tr>';
                    }

                    echo '      </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>';

                    // ── Completed Claims ────────────────────────────────────────
                    echo '<div class="rmu-card" style="margin-bottom:24px;">
                            <div class="rmu-card__header">
                                <span class="rmu-card__title">Completed Claims</span>
                            </div>
                            <div class="rmu-card__body" style="padding:0;">
                                <div class="rmu-table-wrap">
                                    <table class="rmu-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Department</th>
                                                <th>Programme</th>
                                                <th>Course</th>
                                                <th>Status</th>
                                                <th>Date Completed</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>';

                    if ($results['completedClaims']->num_rows > 0) {
                        while ($row = $results['completedClaims']->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . h($row['claimId']) . '</td>';
                            echo '<td>' . h($row['department']) . '</td>';
                            echo '<td>' . h($row['programme']) . '</td>';
                            echo '<td>' . h($row['course']) . '</td>';
                            echo '<td><span class="rmu-badge rmu-badge--success">' . h($row['status']) . '</span></td>';
                            echo '<td>' . date('d M Y', strtotime($row['time_submitted'])) . '</td>';
                            echo '<td style="white-space:nowrap;">
                                    <button class="rmu-btn rmu-btn--secondary" style="padding:5px 9px;margin-right:4px;" onclick="viewClaimDetails(' . (int)$row['claimId'] . ')" title="View">
                                        <i class="ti ti-eye"></i>
                                    </button>
                                    <button class="rmu-btn rmu-btn--secondary" style="padding:5px 9px;" onclick="downloadClaimDetails(' . (int)$row['claimId'] . ')" title="Download">
                                        <i class="ti ti-download"></i>
                                    </button>
                                  </td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="7" style="text-align:center;color:var(--txt-muted);padding:20px;">No completed claims</td></tr>';
                    }

                    echo '      </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>';

                    ?>

                    <!-- Claim Details Modal -->
                    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content" style="background:var(--bg-glass);backdrop-filter:blur(20px);border:var(--border-glass);color:var(--txt-primary);">
                                <div class="modal-header" style="border-bottom:var(--border-glass);">
                                    <h5 class="modal-title" id="detailsModalLabel">Claim Details</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body" id="detailsModalBody"></div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <?php include "../../assets/partials/_footer.php"; ?>
    </div>



<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<script>
   function editClaim(claimId) {
        // $.ajax({
        //     url: 'editClaimDetails.inc.php',
        //     type: 'GET',
        //     data: { claimId: claimId },
        //     success: function(response) {
        //         $('#detailsModal .modal-body').html(response);
        //         $('#detailsModal').modal('show');
        //     },
        //     error: function(xhr, status, error) {
        //         console.error(error);
        //         alert('An error occurred while fetching claim details.');
        //     }
        // });
        //console.log(window.location.pathname);
        window.location.assign("../fileNewClaim/index.php?claimTempId=" + claimId);
    }

    function addNewRow() {
        const newRow = `
            <tr>
                <td><input type="time" class="form-control" name="start_time[]"></td>
                <td><input type="time" class="form-control" name="end_time[]"></td>
                <td><input type="text" class="form-control" name="periods[]"></td>
                <td><button type="button" class="btn btn-danger btn-sm delete-row">Delete</button></td>
            </tr>`;
        $('#claimDataRows').append(newRow);
    }

    function saveClaimDetails() {
        $.ajax({
            url: 'saveClaimDetails.inc.php',
            type: 'POST',
            data: $('#claimDetailsForm').serialize(),
            success: function(response) {
                alert("Changes saved successfully!");
                $('#detailsModal').modal('hide');
            },
            error: function(xhr, status, error) {
                alert("An error occurred while saving the claim details.");
            }
        });
    }

    function submitClaimDetails() {
        $.ajax({
            url: 'submitClaimDetails.inc.php',
            type: 'POST',
            data: $('#claimDetailsForm').serialize(),
            success: function(response) {
                alert("Claim submitted successfully!");
                $('#detailsModal').modal('hide');
            },
            error: function(xhr, status, error) {
                alert("An error occurred while submitting the claim.");
            }
        });
    }

    function viewClaimDetails(claimId) {
        const body = document.getElementById('detailsModalBody');
        body.innerHTML = '<p style="text-align:center;padding:24px;color:var(--txt-muted);">'
            + '<i class="ti ti-loader" style="animation:spin .8s linear infinite;font-size:1.4rem;"></i>'
            + '</p>';
        const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
        modal.show();
        $.ajax({
            url: 'viewClaimDetails.inc.php',
            type: 'GET',
            data: { claimId: claimId },
            success: function(response) {
                body.innerHTML = response;
            },
            error: function() {
                body.innerHTML = '<p style="color:var(--txt-muted);text-align:center;padding:20px;">Error loading claim details.</p>';
            }
        });
    }

    function deleteClaim(claimId) {
        Swal.fire({
            title: 'Delete Draft?',
            text: 'This draft will be permanently removed.',
            icon: 'warning',
            background: '#0d1b2a', color: '#e2e8f0',
            showCancelButton: true,
            confirmButtonText: 'Yes, Delete',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: 'rgba(255,255,255,0.1)',
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: 'deleteClaim.inc.php',
                type: 'POST',
                dataType: 'json',
                data: { claimId: claimId },
                success: function(response) {
                    Swal.fire({
                        icon: 'success', title: 'Deleted',
                        text: response.success || 'Draft removed.',
                        background: '#0d1b2a', color: '#e2e8f0',
                        timer: 2000, showConfirmButton: false
                    }).then(function() { location.reload(); });
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error', title: 'Error',
                        text: 'Could not delete the draft. Please try again.',
                        background: '#0d1b2a', color: '#e2e8f0',
                    });
                }
            });
        });
    }
   
    //Function to download claim as filled out document
    function downloadClaimDetails(claimId) {
    $.ajax({
        url: 'downloadClaim.inc.php', // Your PHP script URL
        type: 'POST',
        data: { claimId: claimId },
        xhrFields: {
            responseType: 'blob' // Ensure the response is treated as a binary file
        },
        success: function(response, status, xhr) {
            var filename = xhr.getResponseHeader('Content-Disposition').split('filename=')[1].split(';')[0];
            var blob = new Blob([response], { type: xhr.getResponseHeader('Content-Type') });
            var link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = filename;
            link.click();
        },
        error: function(xhr, status, error) {
            alert("There was an error. Please try again later!");
        }
    });
}



</script>




    
</body>
</html>

    
