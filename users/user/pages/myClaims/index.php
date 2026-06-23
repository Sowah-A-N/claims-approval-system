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

$maxStageRow = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT settingValue FROM settings WHERE settingName = 'max_approval_stages' LIMIT 1"));
$maxStage = $maxStageRow ? max(1, (int)$maxStageRow['settingValue']) : 5;

$results = [
    'flaggedClaims'   => run_claim_query($conn,
        "SELECT cd.*, fc.flagged_at_stage, fc.flagged_msg, 'Flagged' AS status
         FROM claim_details cd
         LEFT JOIN flagged_claims fc ON cd.claimId = fc.claimId
         WHERE cd.userId = ? AND cd.flagged = 1",
        $userId),
    'pendingClaims'   => run_claim_query($conn,
        "SELECT cd.*, COALESCE(cas.stage, 1) AS current_stage
         FROM claim_details cd
         LEFT JOIN (SELECT claimId, MAX(stage) AS ms FROM claim_approval_stages GROUP BY claimId) ls
             ON cd.claimId = ls.claimId
         LEFT JOIN claim_approval_stages cas ON cd.claimId = cas.claimId AND ls.ms = cas.stage
         WHERE cd.userId = ? AND cd.flagged <> 1 AND completed <> 1
         ORDER BY cd.claimId DESC",
        $userId),
    'savedClaims'     => run_claim_query($conn,
        "SELECT sc.*, 'Saved' AS status, COUNT(cd.claimId) AS session_count
         FROM saved_claims sc
         LEFT JOIN claim_data cd ON sc.claimTempId = cd.claimId
         WHERE sc.userId = ?
         GROUP BY sc.claimTempId
         ORDER BY sc.date_saved DESC",
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
                                                <th>Course</th>
                                                <th>Flagged At Stage</th>
                                                <th>Reason</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>';

                    if ($results['flaggedClaims']->num_rows > 0) {
                        $fi = 1;
                        while ($row = $results['flaggedClaims']->fetch_assoc()) {
                            $reason = $row['flagged_msg'] ? h($row['flagged_msg']) : '<span style="color:var(--txt-muted);">—</span>';
                            $short_reason = $row['flagged_msg']
                                ? '<span title="' . h($row['flagged_msg']) . '" style="cursor:help;">'
                                    . h(mb_substr($row['flagged_msg'], 0, 60) . (mb_strlen($row['flagged_msg']) > 60 ? '…' : ''))
                                    . '</span>'
                                : '<span style="color:var(--txt-muted);">—</span>';
                            echo '<tr>';
                            echo '<td>' . $fi++ . '</td>';
                            echo '<td>' . h($row['department']) . '</td>';
                            echo '<td>' . h($row['course']) . '</td>';
                            echo '<td>' . ($row['flagged_at_stage'] !== null
                                ? '<span class="rmu-badge rmu-badge--neutral">Stage ' . (int)$row['flagged_at_stage'] . '</span>'
                                : '<span style="color:var(--txt-muted);">—</span>') . '</td>';
                            echo '<td style="max-width:260px;">' . $short_reason . '</td>';
                            echo '<td style="white-space:nowrap;">
                                    <button class="rmu-btn rmu-btn--secondary" style="padding:5px 9px;margin-right:4px;" onclick="viewClaimDetails(' . (int)$row['claimId'] . ')" title="View">
                                        <i class="ti ti-eye"></i>
                                    </button>
                                    <button class="rmu-btn rmu-btn--primary" style="padding:5px 10px;" onclick="resubmitClaim(' . (int)$row['claimId'] . ')" title="Resubmit">
                                        <i class="ti ti-send"></i> Resubmit
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
                                                <th>Stage</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>';

                    if ($results['pendingClaims']->num_rows > 0) {
                        $index = 1;
                        while ($row = $results['pendingClaims']->fetch_assoc()) {
                            $cs   = max(1, (int)($row['current_stage'] ?? 1));
                            $dots = '';
                            for ($s = 1; $s <= $maxStage; $s++) {
                                $col   = $s <= $cs ? '#3b82f6' : 'rgba(255,255,255,0.18)';
                                $dots .= '<span style="font-size:11px;color:' . $col . ';">●</span>';
                            }
                            echo '<tr>';
                            echo '<td>' . $index . '</td>';
                            echo '<td>' . h($row['department']) . '</td>';
                            echo '<td>' . h($row['programme']) . '</td>';
                            echo '<td>' . h($row['course']) . '</td>';
                            echo '<td>' . date('d M Y', strtotime($row['time_submitted'])) . '</td>';
                            echo '<td><span title="Stage ' . $cs . ' of ' . $maxStage . '" style="letter-spacing:2px;">'
                               . $dots . '</span>'
                               . ' <span style="font-size:.72rem;color:var(--txt-muted);">' . $cs . '/' . $maxStage . '</span></td>';
                            echo '<td>
                                    <button class="rmu-btn rmu-btn--secondary" style="padding:5px 9px;" onclick="viewClaimDetails(' . (int)$row['claimId'] . ')" title="View">
                                        <i class="ti ti-eye"></i>
                                    </button>
                                  </td>';
                            echo '</tr>';
                            $index++;
                        }
                    } else {
                        echo '<tr><td colspan="7" style="text-align:center;color:var(--txt-muted);padding:20px;">No pending claims</td></tr>';
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
                                                <th>Sessions</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>';

                    if ($results['savedClaims']->num_rows > 0) {
                        $si = 1;
                        while ($row = $results['savedClaims']->fetch_assoc()) {
                            $tempId       = (int) $row['claimTempId'];
                            $sessionCount = (int) $row['session_count'];
                            $hasData      = $sessionCount > 0;

                            echo '<tr>';
                            echo '<td>' . $si++ . '</td>';
                            echo '<td>' . ($row['department'] ? h($row['department']) : '<span style="color:var(--txt-muted);">—</span>') . '</td>';
                            echo '<td>' . ($row['programme']  ? h($row['programme'])  : '<span style="color:var(--txt-muted);">—</span>') . '</td>';
                            echo '<td>' . ($row['course']     ? h($row['course'])     : '<span style="color:var(--txt-muted);">—</span>') . '</td>';
                            echo '<td>' . date('d M Y', strtotime($row['date_saved'])) . '</td>';
                            echo '<td>';
                            if ($hasData) {
                                echo '<span class="rmu-badge rmu-badge--primary">' . $sessionCount . ' session' . ($sessionCount !== 1 ? 's' : '') . '</span>';
                            } else {
                                echo '<span class="rmu-badge rmu-badge--warning" title="No sessions added yet — edit to add teaching sessions before submitting">No sessions</span>';
                            }
                            echo '</td>';
                            echo '<td><span class="rmu-badge rmu-badge--neutral">' . h($row['status']) . '</span></td>';
                            echo '<td style="white-space:nowrap;display:flex;gap:4px;align-items:center;">';
                            echo '<button class="rmu-btn rmu-btn--secondary" style="padding:5px 9px;" onclick="editClaim(' . $tempId . ')" title="Edit draft">
                                    <i class="ti ti-edit"></i>
                                  </button>';
                            if ($hasData) {
                                echo '<button class="rmu-btn rmu-btn--primary" style="padding:5px 10px;" onclick="submitDraft(' . $tempId . ')" title="Submit for approval">
                                        <i class="ti ti-send"></i> Submit
                                      </button>';
                            } else {
                                echo '<button class="rmu-btn rmu-btn--primary" style="padding:5px 10px;opacity:.45;cursor:not-allowed;" disabled title="Add teaching sessions before submitting">
                                        <i class="ti ti-send"></i> Submit
                                      </button>';
                            }
                            echo '<button class="rmu-btn rmu-btn--danger" style="padding:5px 9px;" onclick="deleteClaim(' . $tempId . ')" title="Delete draft">
                                    <i class="ti ti-trash"></i>
                                  </button>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="8" style="text-align:center;color:var(--txt-muted);padding:20px;">No saved claims</td></tr>';
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
                                    <button class="rmu-btn rmu-btn--secondary" style="padding:5px 9px;margin-right:4px;" onclick="downloadClaimDetails(' . (int)$row['claimId'] . ')" title="Download">
                                        <i class="ti ti-download"></i>
                                    </button>
                                    <button class="rmu-btn rmu-btn--primary" style="padding:5px 10px;" onclick="cloneClaim(' . (int)$row['claimId'] . ')" title="Reuse as a new draft">
                                        <i class="ti ti-copy"></i> Clone
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

                    <!-- Claim Details Modal (rmu-glass) -->
                    <div class="rmu-modal-backdrop" id="detailsBackdrop">
                        <div class="rmu-modal" style="max-width:860px;width:calc(100% - 48px);">
                            <div class="rmu-modal__header">
                                <span class="rmu-modal__title">
                                    <i class="ti ti-file-description" style="margin-right:8px;"></i>Claim Details
                                </span>
                                <button class="rmu-modal__close" onclick="closeDetailsModal()" title="Close">
                                    <i class="ti ti-x"></i>
                                </button>
                            </div>
                            <div class="rmu-modal__body" id="detailsModalBody"></div>
                        </div>
                    </div>

                </div>
                <?php include "../../assets/partials/_footer.php"; ?>
            </div>
        </div>
    </div>



<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<script>
const CSRF     = '<?php echo h(csrf_token()); ?>';
const swalOpts = { background: '#0d1b2a', color: '#e2e8f0' };

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

    function viewClaimDetails(claimId) {
        const body     = document.getElementById('detailsModalBody');
        const backdrop = document.getElementById('detailsBackdrop');
        body.innerHTML = '<p style="text-align:center;padding:32px;color:var(--txt-muted);">'
            + '<i class="ti ti-loader" style="animation:spin .8s linear infinite;font-size:1.6rem;"></i>'
            + '</p>';
        backdrop.classList.add('open');
        document.body.style.overflow = 'hidden';
        $.ajax({
            url: 'viewClaimDetails.inc.php',
            type: 'GET',
            data: { claimId: claimId },
            success: function(response) {
                body.innerHTML = response;
            },
            error: function() {
                body.innerHTML = '<p style="color:var(--txt-muted);text-align:center;padding:20px;">'
                    + 'Error loading claim details. Please try again.</p>';
            }
        });
    }

    function closeDetailsModal() {
        document.getElementById('detailsBackdrop').classList.remove('open');
        document.getElementById('detailsModalBody').innerHTML = '';
        document.body.style.overflow = '';
    }

    // Close on backdrop click or Escape key
    document.getElementById('detailsBackdrop').addEventListener('click', function(e) {
        if (e.target === this) closeDetailsModal();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('detailsBackdrop').classList.contains('open')) {
            closeDetailsModal();
        }
    });

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
                data: { claimId: claimId, csrf_token: CSRF },
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
   
    function resubmitClaim(claimId) {
        Swal.fire(Object.assign({
            title: 'Resubmit Claim?',
            text: 'A copy of this claim will open for editing so you can address the flagged issues before resubmitting.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Edit & Resubmit',
            confirmButtonColor: 'var(--accent)',
            cancelButtonColor: 'rgba(255,255,255,0.1)',
        }, swalOpts)).then(function(result) {
            if (!result.isConfirmed) return;

            var fd = new FormData();
            fd.append('claimId',    claimId);
            fd.append('csrf_token', CSRF);

            fetch('resubmitFlaggedClaim.inc.php', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        window.location.assign('../fileNewClaim/index.php?claimTempId=' + res.claimTempId);
                    } else {
                        Swal.fire(Object.assign({
                            icon: 'error', title: 'Error',
                            text: res.message || 'Could not prepare claim for resubmission.',
                        }, swalOpts));
                    }
                })
                .catch(function() {
                    Swal.fire(Object.assign({
                        icon: 'error', title: 'Network Error',
                        text: 'Could not reach the server. Please try again.',
                    }, swalOpts));
                });
        });
    }

    function submitDraft(claimId) {
        Swal.fire(Object.assign({
            title: 'Submit Claim?',
            text: 'Once submitted, the claim will be sent for approval and cannot be edited.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Submit',
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: 'rgba(255,255,255,0.1)',
        }, swalOpts)).then(function(result) {
            if (!result.isConfirmed) return;

            var fd = new FormData();
            fd.append('claimId',    claimId);
            fd.append('csrf_token', CSRF);

            fetch('submitClaimDetails.inc.php', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.ok) {
                        Swal.fire(Object.assign({
                            icon: 'success', title: 'Submitted',
                            text: res.message || 'Claim submitted for approval.',
                            timer: 2500, showConfirmButton: false,
                        }, swalOpts)).then(function() { location.reload(); });
                    } else {
                        Swal.fire(Object.assign({
                            icon: 'error', title: 'Submission Failed',
                            text: res.message || 'Please try again.',
                        }, swalOpts));
                    }
                })
                .catch(function() {
                    Swal.fire(Object.assign({
                        icon: 'error', title: 'Network Error',
                        text: 'Could not reach the server. Please try again.',
                    }, swalOpts));
                });
        });
    }

    function downloadClaimDetails(claimId) {
        window.open('downloadClaimPDF.inc.php?claimId=' + encodeURIComponent(claimId), '_blank');
    }

    function cloneClaim(claimId) {
        Swal.fire(Object.assign({
            title: 'Clone this claim?',
            text: 'A new editable draft will be created with the same course and sessions. The original claim is not affected.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Clone',
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: 'rgba(255,255,255,0.1)',
        }, swalOpts)).then(function(result) {
            if (!result.isConfirmed) return;

            var fd = new FormData();
            fd.append('claimId',    claimId);
            fd.append('csrf_token', CSRF);

            fetch('cloneClaim.inc.php', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        window.location.assign('../fileNewClaim/index.php?claimTempId=' + res.claimTempId);
                    } else {
                        Swal.fire(Object.assign({
                            icon: 'error', title: 'Error',
                            text: res.message || 'Could not clone the claim.',
                        }, swalOpts));
                    }
                })
                .catch(function() {
                    Swal.fire(Object.assign({
                        icon: 'error', title: 'Network Error',
                        text: 'Could not reach the server. Please try again.',
                    }, swalOpts));
                });
        });
    }



</script>




    
</body>
</html>

    
