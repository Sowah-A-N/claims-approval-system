<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role(array('approver', 'Approver'));

$approverStage = isset($_SESSION['stage']) ? (int)$_SESSION['stage'] : 0;

// Fetch distinct submission dates for the approver's department
$dept = isset($_SESSION['dept']) ? (string)$_SESSION['dept'] : '';
$dates = [];

$d_stmt = mysqli_prepare($conn,
    "SELECT DISTINCT DATE(cd.time_submitted) AS sub_date
     FROM claim_details cd
     WHERE cd.department = ?
     ORDER BY sub_date DESC");
if ($d_stmt) {
    mysqli_stmt_bind_param($d_stmt, 's', $dept);
    mysqli_stmt_execute($d_stmt);
    $d_result = mysqli_stmt_get_result($d_stmt);
    while ($row = mysqli_fetch_assoc($d_result)) {
        $dates[] = date('d/m/Y', strtotime($row['sub_date']));
    }
    mysqli_stmt_close($d_stmt);
}

$pageTitle = "Reports";
?>
<!DOCTYPE html>
<html lang="en">
<?php include './assets/partials/head.php'; ?>
<body>
<div class="page-wrapper" id="main-wrapper">
    <?php include './assets/partials/sidebar.php'; ?>

    <div class="body-wrapper">
        <?php include './assets/partials/header.html'; ?>

        <div style="padding:28px 32px;">

            <div class="rmu-page-header">
                <div class="rmu-page-header__title">Reports</div>
                <div class="rmu-page-header__sub">Review claim activity for your department</div>
            </div>

            <!-- Filters -->
            <div class="rmu-card" style="margin-bottom:24px;">
                <div class="rmu-card__header">
                    <span class="rmu-card__title"><i class="ti ti-filter" style="margin-right:8px;"></i>Filter Claims</span>
                </div>
                <div class="rmu-card__body">
                    <div style="display:flex;align-items:flex-end;gap:16px;flex-wrap:wrap;">
                        <div class="rmu-form-group" style="margin-bottom:0;min-width:200px;">
                            <label class="rmu-label" for="dateSubmitted">Date Submitted</label>
                            <select id="dateSubmitted" class="rmu-select">
                                <option value="" selected disabled>— Select a date —</option>
                                <?php foreach ($dates as $d): ?>
                                <option value="<?php echo h($d); ?>"><?php echo h($d); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="rmu-form-group" style="margin-bottom:0;min-width:180px;">
                            <label class="rmu-label" for="actionFilter">Status</label>
                            <select id="actionFilter" class="rmu-select">
                                <option value="" selected disabled>— Select status —</option>
                                <option value="Flagged">Flagged</option>
                                <option value="Pending">Pending</option>
                                <option value="Approved">Approved</option>
                            </select>
                        </div>

                        <button type="button" id="clearFiltersBtn" class="rmu-btn rmu-btn--secondary">
                            <i class="ti ti-x"></i> Clear Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Results -->
            <div class="rmu-card">
                <div class="rmu-card__header">
                    <span class="rmu-card__title">Results</span>
                    <span class="rmu-badge rmu-badge--primary" id="resultCount" style="display:none;">0</span>
                </div>
                <div class="rmu-card__body" id="resultsContainer" style="padding:0;">
                    <div style="text-align:center;color:var(--txt-muted);padding:40px 20px;">
                        <i class="ti ti-filter" style="font-size:2rem;display:block;margin-bottom:10px;opacity:.4;"></i>
                        Select at least one filter above to load results.
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<script>
function escHtml(str) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(str != null ? String(str) : ''));
    return d.innerHTML;
}

document.addEventListener('DOMContentLoaded', function() {
    var dateSelect   = document.getElementById('dateSubmitted');
    var actionSelect = document.getElementById('actionFilter');
    var container    = document.getElementById('resultsContainer');
    var countBadge   = document.getElementById('resultCount');
    var clearBtn     = document.getElementById('clearFiltersBtn');

    function fetchResults() {
        var dateVal   = dateSelect.value;
        var actionVal = actionSelect.value;

        if (!dateVal && !actionVal) {
            container.innerHTML =
                '<div style="text-align:center;color:var(--txt-muted);padding:40px 20px;">' +
                '<i class="ti ti-filter" style="font-size:2rem;display:block;margin-bottom:10px;opacity:.4;"></i>' +
                'Select at least one filter above to load results.</div>';
            countBadge.style.display = 'none';
            return;
        }

        container.innerHTML =
            '<div style="text-align:center;padding:32px;color:var(--txt-muted);">' +
            '<i class="ti ti-loader" style="animation:spin .8s linear infinite;font-size:1.6rem;"></i>' +
            '</div>';

        var fd = new FormData();
        if (dateVal)   fd.append('dateSubmitted', dateVal);
        if (actionVal) fd.append('action', actionVal);

        fetch('fetchRecords.inc.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success || !data.results || data.results.length === 0) {
                    container.innerHTML =
                        '<div style="text-align:center;color:var(--txt-muted);padding:40px 20px;">' +
                        '<i class="ti ti-inbox" style="font-size:2rem;display:block;margin-bottom:10px;opacity:.4;"></i>' +
                        'No records found for the selected filters.</div>';
                    countBadge.style.display = 'none';
                    return;
                }

                var rows = data.results;
                var html = '<div class="rmu-table-wrap"><table class="rmu-table">' +
                    '<thead><tr>' +
                    '<th>#</th><th>Claim ID</th><th>Course</th><th>Claimant</th>' +
                    '<th>Stage</th><th>Status</th><th>Date Submitted</th>' +
                    '</tr></thead><tbody>';

                rows.forEach(function(r, idx) {
                    var d   = new Date(r.time_submitted);
                    var fmt = d.toLocaleDateString('en-GB', { day:'2-digit', month:'2-digit', year:'numeric' }); // dd/mm/yyyy

                    var statusCls = 'rmu-badge--neutral';
                    if (r.status === 'Approved') statusCls = 'rmu-badge--success';
                    if (r.status === 'Flagged')  statusCls = 'rmu-badge--danger';
                    if (r.status === 'Pending')  statusCls = 'rmu-badge--neutral';

                    html +=
                        '<tr>' +
                        '<td>' + (idx + 1) + '</td>' +
                        '<td style="font-family:monospace;">#' + escHtml(r.claimId) + '</td>' +
                        '<td>' + escHtml(r.course) + '</td>' +
                        '<td style="font-weight:500;">' + escHtml(r.full_name) + '</td>' +
                        '<td>' + escHtml(r.stage) + '</td>' +
                        '<td><span class="rmu-badge ' + statusCls + '">' + escHtml(r.status) + '</span></td>' +
                        '<td>' + escHtml(fmt) + '</td>' +
                        '</tr>';
                });

                html += '</tbody></table></div>';
                container.innerHTML = html;
                countBadge.textContent = rows.length + ' record' + (rows.length !== 1 ? 's' : '');
                countBadge.style.display = '';
            })
            .catch(function() {
                container.innerHTML =
                    '<div style="text-align:center;color:var(--txt-muted);padding:40px 20px;">' +
                    '<i class="ti ti-wifi-off" style="font-size:2rem;display:block;margin-bottom:10px;opacity:.4;"></i>' +
                    'Error loading results. Please try again.</div>';
                countBadge.style.display = 'none';
            });
    }

    dateSelect.addEventListener('change',   fetchResults);
    actionSelect.addEventListener('change', fetchResults);

    clearBtn.addEventListener('click', function() {
        dateSelect.value   = '';
        actionSelect.value = '';
        container.innerHTML =
            '<div style="text-align:center;color:var(--txt-muted);padding:40px 20px;">' +
            '<i class="ti ti-filter" style="font-size:2rem;display:block;margin-bottom:10px;opacity:.4;"></i>' +
            'Select at least one filter above to load results.</div>';
        countBadge.style.display = 'none';
    });
});
</script>

</body>
</html>
