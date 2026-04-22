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
<div class="page-wrapper" id="main-wrapper">
    <?php include './assets/partials/sidebar.php'; ?>

    <div class="body-wrapper">
        <?php include './assets/partials/header.html'; ?>

        <div style="padding:28px 32px;">

            <div class="rmu-page-header">
                <div class="rmu-page-header__title">Pending Claims</div>
                <div class="rmu-page-header__sub">
                    Claims awaiting your approval
                    <?php if ($approverStage): ?>
                        &mdash; Stage <?php echo $approverStage; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($approverStage && $approverStage !== 1 && !empty($claims)): ?>
            <div style="margin-bottom:20px;display:flex;align-items:center;gap:12px;">
                <label class="rmu-label" style="margin-bottom:0;white-space:nowrap;">Filter by Department:</label>
                <select id="departmentFilter" class="rmu-select" style="max-width:280px;" onchange="filterTable()">
                    <option value="">All Departments</option>
                    <?php
                    $departments = array_unique(array_column($claims, 'department'));
                    foreach ($departments as $dept):
                        if ($dept):
                    ?>
                    <option value="<?php echo h($dept); ?>"><?php echo h($dept); ?></option>
                    <?php endif; endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="rmu-card">
                <div class="rmu-card__header">
                    <span class="rmu-card__title">Claims Queue</span>
                    <span class="rmu-badge rmu-badge--primary"><?php echo count($claims); ?> pending</span>
                </div>
                <div class="rmu-card__body" style="padding:0;">
                    <div class="rmu-table-wrap">
                        <table class="rmu-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Claimant</th>
                                    <th>Department</th>
                                    <th>Course</th>
                                    <th>Date Submitted</th>
                                    <th style="text-align:center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($claims)):
                                    $index = 1;
                                    foreach ($claims as $claim): ?>
                                <tr data-department="<?php echo h($claim['department']); ?>"
                                    data-claim-id="<?php echo (int)$claim['claimId']; ?>">
                                    <td><?php echo $index; ?></td>
                                    <td style="font-weight:500;"><?php echo h($claim['full_name']); ?></td>
                                    <td><?php echo h($claim['department']); ?></td>
                                    <td><?php echo h($claim['course']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($claim['time_submitted'])); ?></td>
                                    <td style="white-space:nowrap;text-align:center;">
                                        <button class="rmu-btn rmu-btn--secondary"
                                                style="padding:5px 10px;margin-right:4px;"
                                                onclick="viewDetails(<?php echo (int)$claim['claimId']; ?>)"
                                                title="View Details">
                                            <i class="ti ti-eye"></i>
                                        </button>
                                        <button class="rmu-btn rmu-btn--primary"
                                                style="padding:5px 12px;margin-right:4px;"
                                                onclick="approve(<?php echo (int)$claim['claimId']; ?>)"
                                                title="Approve">
                                            <i class="ti ti-check"></i> Approve
                                        </button>
                                        <button class="rmu-btn rmu-btn--danger"
                                                style="padding:5px 12px;"
                                                onclick="openFlagModal(<?php echo (int)$claim['claimId']; ?>)"
                                                title="Flag">
                                            <i class="ti ti-flag"></i> Flag
                                        </button>
                                    </td>
                                </tr>
                                <?php $index++; endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align:center;color:var(--txt-muted);padding:40px 20px;">
                                        <i class="ti ti-inbox" style="font-size:2.2rem;display:block;margin-bottom:10px;opacity:.4;"></i>
                                        No pending claims at this stage.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Claim Details Modal -->
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

<!-- Flag Claim Modal -->
<div class="rmu-modal-backdrop" id="flagBackdrop">
    <div class="rmu-modal" style="max-width:480px;width:calc(100% - 48px);">
        <div class="rmu-modal__header">
            <span class="rmu-modal__title">
                <i class="ti ti-flag" style="margin-right:8px;color:#f87171;"></i>Flag Claim
            </span>
            <button class="rmu-modal__close" onclick="closeFlagModal()" title="Close">
                <i class="ti ti-x"></i>
            </button>
        </div>
        <div class="rmu-modal__body">
            <input type="hidden" id="flagClaimId" value="">
            <div class="rmu-form-group">
                <label class="rmu-label" for="flagReason">
                    Reason for Flagging <span class="required">*</span>
                </label>
                <textarea id="flagReason" class="rmu-input" rows="4"
                          style="resize:vertical;min-height:100px;"
                          placeholder="Describe why this claim is being flagged…"></textarea>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button type="button" class="rmu-btn rmu-btn--secondary"
                        onclick="closeFlagModal()">Cancel</button>
                <button type="button" class="rmu-btn rmu-btn--danger"
                        id="submitFlagBtn" onclick="submitFlag()">
                    <i class="ti ti-flag"></i> Submit Flag
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<script>
const CSRF = '<?php echo h(csrf_token()); ?>';

const swalOpts = { background: '#0d1b2a', color: '#e2e8f0' };

function swalErr(title, text) {
    Swal.fire(Object.assign({ icon: 'error', title, text }, swalOpts));
}

function swalSuccess(text, cb) {
    Swal.fire(Object.assign({
        icon: 'success', title: 'Done', text,
        timer: 2000, showConfirmButton: false
    }, swalOpts)).then(cb || function() {});
}

// ── Table filter ──────────────────────────────────────────────────────────────
function filterTable() {
    const filter = document.getElementById('departmentFilter').value;
    document.querySelectorAll('tbody tr[data-claim-id]').forEach(function(row) {
        const dept = row.getAttribute('data-department');
        row.style.display = (!filter || dept === filter) ? '' : 'none';
    });
}

// ── Row helpers ───────────────────────────────────────────────────────────────
function setRowBusy(claimId, busy) {
    const row = document.querySelector('tr[data-claim-id="' + claimId + '"]');
    if (!row) return;
    row.querySelectorAll('button').forEach(function(b) { b.disabled = busy; });
    row.style.opacity = busy ? '0.5' : '';
}

// ── Details Modal ─────────────────────────────────────────────────────────────
function viewDetails(claimId) {
    const body     = document.getElementById('detailsModalBody');
    const backdrop = document.getElementById('detailsBackdrop');
    body.innerHTML = '<p style="text-align:center;padding:32px;color:var(--txt-muted);">'
        + '<i class="ti ti-loader" style="animation:spin .8s linear infinite;font-size:1.6rem;"></i>'
        + '</p>';
    backdrop.classList.add('open');
    document.body.style.overflow = 'hidden';

    fetch('getClaimDetails.inc.php?claimId=' + encodeURIComponent(claimId))
        .then(function(r) { return r.text(); })
        .then(function(html) { body.innerHTML = html; })
        .catch(function() {
            body.innerHTML = '<p style="color:var(--txt-muted);text-align:center;padding:20px;">'
                + 'Error loading claim details. Please try again.</p>';
        });
}

function closeDetailsModal() {
    document.getElementById('detailsBackdrop').classList.remove('open');
    document.getElementById('detailsModalBody').innerHTML = '';
    document.body.style.overflow = '';
}

document.getElementById('detailsBackdrop').addEventListener('click', function(e) {
    if (e.target === this) closeDetailsModal();
});

// ── Flag Modal ────────────────────────────────────────────────────────────────
function openFlagModal(claimId) {
    document.getElementById('flagClaimId').value = claimId;
    document.getElementById('flagReason').value  = '';
    document.getElementById('flagBackdrop').classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(function() { document.getElementById('flagReason').focus(); }, 80);
}

function closeFlagModal() {
    document.getElementById('flagBackdrop').classList.remove('open');
    document.body.style.overflow = '';
}

document.getElementById('flagBackdrop').addEventListener('click', function(e) {
    if (e.target === this) closeFlagModal();
});

function submitFlag() {
    const claimId = document.getElementById('flagClaimId').value;
    const reason  = document.getElementById('flagReason').value.trim();

    if (!reason) {
        Swal.fire(Object.assign({ icon: 'warning', title: 'Required',
            text: 'Please enter a reason for flagging.' }, swalOpts));
        return;
    }

    const btn = document.getElementById('submitFlagBtn');
    btn.disabled  = true;
    btn.innerHTML = '<i class="ti ti-loader" style="animation:spin .8s linear infinite;"></i> Flagging…';

    const fd = new FormData();
    fd.append('claimId',    claimId);
    fd.append('flagReason', reason);
    fd.append('csrf_token', CSRF);

    function resetBtn() {
        btn.disabled  = false;
        btn.innerHTML = '<i class="ti ti-flag"></i> Submit Flag';
    }

    fetch('flagClaim.inc.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            closeFlagModal();
            if (res.success) {
                swalSuccess(res.message || 'Claim flagged successfully.',
                    function() { location.reload(); });
            } else {
                swalErr('Flag Failed', res.message || 'Could not flag the claim.');
                resetBtn();
            }
        })
        .catch(function() {
            closeFlagModal();
            swalErr('Network Error', 'Could not reach the server. Please try again.');
            resetBtn();
        });
}

// ── Approve ───────────────────────────────────────────────────────────────────
function approve(claimId) {
    Swal.fire(Object.assign({
        title: 'Approve Claim?',
        text:  'This will advance the claim to the next approval stage.',
        icon:  'question',
        showCancelButton:    true,
        confirmButtonText:   'Yes, Approve',
        confirmButtonColor:  '#22c55e',
        cancelButtonColor:   'rgba(255,255,255,0.1)',
    }, swalOpts)).then(function(result) {
        if (!result.isConfirmed) return;

        setRowBusy(claimId, true);

        const fd = new FormData();
        fd.append('claimId',    claimId);
        fd.append('csrf_token', CSRF);

        fetch('approveClaim.inc.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    swalSuccess(res.message || 'Claim approved.',
                        function() { location.reload(); });
                } else {
                    setRowBusy(claimId, false);
                    swalErr('Not Approved', res.message || 'Could not approve the claim.');
                }
            })
            .catch(function() {
                setRowBusy(claimId, false);
                swalErr('Network Error', 'Could not reach the server. Please try again.');
            });
    });
}

// ── Keyboard close ────────────────────────────────────────────────────────────
document.addEventListener('keydown', function(e) {
    if (e.key !== 'Escape') return;
    if (document.getElementById('detailsBackdrop').classList.contains('open')) closeDetailsModal();
    if (document.getElementById('flagBackdrop').classList.contains('open'))    closeFlagModal();
});
</script>
</body>
</html>
