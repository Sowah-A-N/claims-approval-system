<?php
  session_start();

$pageTitle = "My Claims";
include_once "../../assets/partials/_head.php";

$userId = current_user_id();

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

// Counts power the overview cards and the filter tabs.
$counts = [
    'flagged'   => $results['flaggedClaims']->num_rows,
    'pending'   => $results['pendingClaims']->num_rows,
    'saved'     => $results['savedClaims']->num_rows,
    'completed' => $results['completedClaims']->num_rows,
];
$totalClaims = array_sum($counts);

/* Small helper for a value-or-dash cell. */
function cell($v) {
    return ($v !== null && $v !== '') ? h($v) : '<span class="mc-dash">—</span>';
}
?>

<body>
    <div class="container-scroller">
        <?php include "../../assets/partials/_navbar.php"; ?>

        <div class="container-fluid page-body-wrapper">
            <?php include "../../assets/partials/_sidebar.php"; ?>

            <div class="main-panel">
                <div class="content-wrapper">

                    <!-- Decorative frosted-glass background orbs -->
                    <div class="mc-orbs" aria-hidden="true"><span></span><span></span></div>

                    <div class="mc-page" id="mcPage">

                    <!-- Page header -->
                    <div class="mc-header">
                        <div>
                            <h1 class="rmu-page-header__title" style="margin:0;">My Claims</h1>
                            <p class="rmu-page-header__sub" style="margin:4px 0 0;">
                                Track, edit and submit your teaching claims in one place
                            </p>
                        </div>
                        <a class="rmu-btn rmu-btn--primary" href="../fileNewClaim/index.php">
                            <i class="ti ti-file-plus"></i> File New Claim
                        </a>
                    </div>

                    <!-- Overview: status counts (also act as filters) -->
                    <div class="mc-stats">
                        <button type="button" class="mc-glass mc-stat mc-stat--flagged" data-target="flagged"
                                aria-label="Show <?php echo $counts['flagged']; ?> flagged claims">
                            <span class="mc-stat__icon"><i class="ti ti-flag"></i></span>
                            <span>
                                <span class="mc-stat__count"><?php echo $counts['flagged']; ?></span>
                                <span class="mc-stat__label">Flagged</span>
                            </span>
                        </button>
                        <button type="button" class="mc-glass mc-stat mc-stat--pending" data-target="pending"
                                aria-label="Show <?php echo $counts['pending']; ?> pending claims">
                            <span class="mc-stat__icon"><i class="ti ti-clock-hour-4"></i></span>
                            <span>
                                <span class="mc-stat__count"><?php echo $counts['pending']; ?></span>
                                <span class="mc-stat__label">Pending</span>
                            </span>
                        </button>
                        <button type="button" class="mc-glass mc-stat mc-stat--saved" data-target="saved"
                                aria-label="Show <?php echo $counts['saved']; ?> saved drafts">
                            <span class="mc-stat__icon"><i class="ti ti-device-floppy"></i></span>
                            <span>
                                <span class="mc-stat__count"><?php echo $counts['saved']; ?></span>
                                <span class="mc-stat__label">Saved Drafts</span>
                            </span>
                        </button>
                        <button type="button" class="mc-glass mc-stat mc-stat--completed" data-target="completed"
                                aria-label="Show <?php echo $counts['completed']; ?> completed claims">
                            <span class="mc-stat__icon"><i class="ti ti-circle-check"></i></span>
                            <span>
                                <span class="mc-stat__count"><?php echo $counts['completed']; ?></span>
                                <span class="mc-stat__label">Completed</span>
                            </span>
                        </button>
                    </div>

                    <!-- Toolbar: filter tabs + search -->
                    <div class="mc-glass mc-toolbar">
                        <div class="mc-tabs" role="group" aria-label="Filter claims by status">
                            <button type="button" class="mc-tab is-active" data-target="all" aria-pressed="true">
                                All <span class="mc-tab__count"><?php echo $totalClaims; ?></span>
                            </button>
                            <button type="button" class="mc-tab" data-target="flagged" aria-pressed="false">
                                Flagged <span class="mc-tab__count"><?php echo $counts['flagged']; ?></span>
                            </button>
                            <button type="button" class="mc-tab" data-target="pending" aria-pressed="false">
                                Pending <span class="mc-tab__count"><?php echo $counts['pending']; ?></span>
                            </button>
                            <button type="button" class="mc-tab" data-target="saved" aria-pressed="false">
                                Saved <span class="mc-tab__count"><?php echo $counts['saved']; ?></span>
                            </button>
                            <button type="button" class="mc-tab" data-target="completed" aria-pressed="false">
                                Completed <span class="mc-tab__count"><?php echo $counts['completed']; ?></span>
                            </button>
                        </div>
                        <div class="mc-search">
                            <i class="ti ti-search" aria-hidden="true"></i>
                            <label for="mcSearch" class="rmu-sr-only">Search claims</label>
                            <input type="search" id="mcSearch" placeholder="Search course, class, department…"
                                   autocomplete="off">
                        </div>
                    </div>

                    <?php
                    // ── Flagged Claims ──────────────────────────────────────────
                    ?>
                    <section class="mc-glass mc-section mc-section--flagged" data-section="flagged">
                        <div class="mc-section__head">
                            <span class="mc-section__title"><i class="ti ti-flag"></i> Flagged Claims</span>
                            <span class="rmu-badge rmu-badge--warning"><?php echo $counts['flagged']; ?></span>
                        </div>
                        <div class="rmu-table-wrap">
                            <table class="rmu-table">
                                <thead>
                                    <tr>
                                        <th>#</th><th>Department</th><th>Course</th><th>Class</th>
                                        <th>Flagged At</th><th>Reason</th><th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if ($counts['flagged'] > 0): $fi = 1; while ($row = $results['flaggedClaims']->fetch_assoc()):
                                    $full = $row['flagged_msg'] ?? '';
                                    $short = $full !== ''
                                        ? '<span title="' . h($full) . '" style="cursor:help;">' . h(mb_substr($full, 0, 60) . (mb_strlen($full) > 60 ? '…' : '')) . '</span>'
                                        : '<span class="mc-dash">—</span>';
                                ?>
                                    <tr data-row>
                                        <td><?php echo $fi++; ?></td>
                                        <td><?php echo cell($row['department']); ?></td>
                                        <td><?php echo cell($row['course']); ?></td>
                                        <td><?php echo !empty($row['class']) ? '<span class="rmu-badge rmu-badge--neutral">' . h($row['class']) . '</span>' : '<span class="mc-dash">—</span>'; ?></td>
                                        <td><?php echo $row['flagged_at_stage'] !== null ? '<span class="rmu-badge rmu-badge--neutral">Stage ' . (int)$row['flagged_at_stage'] . '</span>' : '<span class="mc-dash">—</span>'; ?></td>
                                        <td style="max-width:260px;"><?php echo $short; ?></td>
                                        <td style="white-space:nowrap;">
                                            <button class="rmu-btn rmu-btn--secondary rmu-btn--sm" onclick="viewClaimDetails(<?php echo (int)$row['claimId']; ?>)" title="View details" aria-label="View claim details"><i class="ti ti-eye"></i></button>
                                            <button class="rmu-btn rmu-btn--primary rmu-btn--sm" onclick="resubmitClaim(<?php echo (int)$row['claimId']; ?>)" title="Edit &amp; resubmit"><i class="ti ti-send"></i> Resubmit</button>
                                        </td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr class="mc-empty"><td colspan="7"><?php echo mc_empty('ti-flag', 'No flagged claims', 'Anything an approver sends back will appear here.'); ?></td></tr>
                                <?php endif; ?>
                                    <tr class="mc-no-match" hidden><td colspan="7"><?php echo mc_nomatch(); ?></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <?php // ── Pending Claims ─────────────────────────────────────── ?>
                    <section class="mc-glass mc-section mc-section--pending" data-section="pending">
                        <div class="mc-section__head">
                            <span class="mc-section__title"><i class="ti ti-clock-hour-4"></i> Pending Claims</span>
                            <span class="rmu-badge rmu-badge--primary"><?php echo $counts['pending']; ?></span>
                        </div>
                        <div class="rmu-table-wrap">
                            <table class="rmu-table">
                                <thead>
                                    <tr>
                                        <th>#</th><th>Department</th><th>Programme</th><th>Course</th><th>Class</th>
                                        <th>Submitted</th><th>Approval Stage</th><th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if ($counts['pending'] > 0): $pi = 1; while ($row = $results['pendingClaims']->fetch_assoc()):
                                    $cs = max(1, (int)($row['current_stage'] ?? 1));
                                    $pct = round(($cs / $maxStage) * 100);
                                ?>
                                    <tr data-row>
                                        <td><?php echo $pi++; ?></td>
                                        <td><?php echo cell($row['department']); ?></td>
                                        <td><?php echo cell($row['programme']); ?></td>
                                        <td><?php echo cell($row['course']); ?></td>
                                        <td><?php echo !empty($row['class']) ? '<span class="rmu-badge rmu-badge--neutral">' . h($row['class']) . '</span>' : '<span class="mc-dash">—</span>'; ?></td>
                                        <td style="white-space:nowrap;"><?php echo date('d/m/Y', strtotime($row['time_submitted'])); ?></td>
                                        <td>
                                            <div class="mc-stage" title="Stage <?php echo $cs; ?> of <?php echo $maxStage; ?>">
                                                <div class="mc-stage__bar"><span style="width:<?php echo $pct; ?>%;"></span></div>
                                                <span class="mc-stage__txt">Stage <?php echo $cs; ?>/<?php echo $maxStage; ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <button class="rmu-btn rmu-btn--secondary rmu-btn--sm" onclick="viewClaimDetails(<?php echo (int)$row['claimId']; ?>)" title="View details" aria-label="View claim details"><i class="ti ti-eye"></i></button>
                                        </td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr class="mc-empty"><td colspan="8"><?php echo mc_empty('ti-clock-hour-4', 'No pending claims', 'Submitted claims awaiting approval will show here.'); ?></td></tr>
                                <?php endif; ?>
                                    <tr class="mc-no-match" hidden><td colspan="8"><?php echo mc_nomatch(); ?></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <?php // ── Saved Claims ───────────────────────────────────────── ?>
                    <section class="mc-glass mc-section mc-section--saved" data-section="saved">
                        <div class="mc-section__head">
                            <span class="mc-section__title"><i class="ti ti-device-floppy"></i> Saved Drafts</span>
                            <span class="rmu-badge rmu-badge--neutral"><?php echo $counts['saved']; ?></span>
                        </div>
                        <div class="rmu-table-wrap">
                            <table class="rmu-table">
                                <thead>
                                    <tr>
                                        <th>#</th><th>Department</th><th>Programme</th><th>Course</th><th>Class</th>
                                        <th>Saved</th><th>Sessions</th><th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if ($counts['saved'] > 0): $si = 1; while ($row = $results['savedClaims']->fetch_assoc()):
                                    $tempId = (int)$row['claimTempId'];
                                    $sc = (int)$row['session_count'];
                                    $hasData = $sc > 0;
                                ?>
                                    <tr data-row>
                                        <td><?php echo $si++; ?></td>
                                        <td><?php echo cell($row['department']); ?></td>
                                        <td><?php echo cell($row['programme']); ?></td>
                                        <td><?php echo cell($row['course']); ?></td>
                                        <td><?php echo !empty($row['class']) ? '<span class="rmu-badge rmu-badge--neutral">' . h($row['class']) . '</span>' : '<span class="mc-dash">—</span>'; ?></td>
                                        <td style="white-space:nowrap;"><?php echo date('d/m/Y', strtotime($row['date_saved'])); ?></td>
                                        <td>
                                            <?php if ($hasData): ?>
                                                <span class="rmu-badge rmu-badge--primary"><?php echo $sc . ' session' . ($sc !== 1 ? 's' : ''); ?></span>
                                            <?php else: ?>
                                                <span class="rmu-badge rmu-badge--warning" title="Add teaching sessions before submitting">No sessions</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="white-space:nowrap;">
                                            <button class="rmu-btn rmu-btn--secondary rmu-btn--sm" onclick="editClaim(<?php echo $tempId; ?>)" title="Edit draft" aria-label="Edit draft"><i class="ti ti-edit"></i></button>
                                            <?php if ($hasData): ?>
                                            <button class="rmu-btn rmu-btn--primary rmu-btn--sm" onclick="submitDraft(<?php echo $tempId; ?>)" title="Submit for approval"><i class="ti ti-send"></i> Submit</button>
                                            <?php else: ?>
                                            <button class="rmu-btn rmu-btn--primary rmu-btn--sm" disabled style="opacity:.45;cursor:not-allowed;" title="Add teaching sessions before submitting"><i class="ti ti-send"></i> Submit</button>
                                            <?php endif; ?>
                                            <button class="rmu-btn rmu-btn--danger rmu-btn--sm" onclick="deleteClaim(<?php echo $tempId; ?>)" title="Delete draft" aria-label="Delete draft"><i class="ti ti-trash"></i></button>
                                        </td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr class="mc-empty"><td colspan="8"><?php echo mc_empty('ti-device-floppy', 'No saved drafts', 'Start a claim and save it to continue later.'); ?></td></tr>
                                <?php endif; ?>
                                    <tr class="mc-no-match" hidden><td colspan="8"><?php echo mc_nomatch(); ?></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <?php // ── Completed Claims ───────────────────────────────────── ?>
                    <section class="mc-glass mc-section mc-section--completed" data-section="completed">
                        <div class="mc-section__head">
                            <span class="mc-section__title"><i class="ti ti-circle-check"></i> Completed Claims</span>
                            <span class="rmu-badge rmu-badge--success"><?php echo $counts['completed']; ?></span>
                        </div>
                        <div class="rmu-table-wrap">
                            <table class="rmu-table">
                                <thead>
                                    <tr>
                                        <th>#</th><th>Department</th><th>Programme</th><th>Course</th><th>Class</th>
                                        <th>Status</th><th>Completed</th><th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if ($counts['completed'] > 0): while ($row = $results['completedClaims']->fetch_assoc()): ?>
                                    <tr data-row>
                                        <td><?php echo (int)$row['claimId']; ?></td>
                                        <td><?php echo cell($row['department']); ?></td>
                                        <td><?php echo cell($row['programme']); ?></td>
                                        <td><?php echo cell($row['course']); ?></td>
                                        <td><?php echo !empty($row['class']) ? '<span class="rmu-badge rmu-badge--neutral">' . h($row['class']) . '</span>' : '<span class="mc-dash">—</span>'; ?></td>
                                        <td><span class="rmu-badge rmu-badge--success"><?php echo h($row['status']); ?></span></td>
                                        <td style="white-space:nowrap;"><?php echo date('d/m/Y', strtotime($row['time_submitted'])); ?></td>
                                        <td style="white-space:nowrap;">
                                            <button class="rmu-btn rmu-btn--secondary rmu-btn--sm" onclick="viewClaimDetails(<?php echo (int)$row['claimId']; ?>)" title="View details" aria-label="View claim details"><i class="ti ti-eye"></i></button>
                                            <button class="rmu-btn rmu-btn--secondary rmu-btn--sm" onclick="downloadClaimDetails(<?php echo (int)$row['claimId']; ?>)" title="Download form" aria-label="Download claim form"><i class="ti ti-download"></i></button>
                                            <button class="rmu-btn rmu-btn--primary rmu-btn--sm" onclick="cloneClaim(<?php echo (int)$row['claimId']; ?>)" title="Reuse as a new draft"><i class="ti ti-copy"></i> Clone</button>
                                        </td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr class="mc-empty"><td colspan="8"><?php echo mc_empty('ti-circle-check', 'No completed claims', 'Fully approved claims forwarded to Finance appear here.'); ?></td></tr>
                                <?php endif; ?>
                                    <tr class="mc-no-match" hidden><td colspan="8"><?php echo mc_nomatch(); ?></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    </div><!-- .mc-page -->

                    <!-- Claim Details Modal (rmu-glass) -->
                    <div class="rmu-modal-backdrop" id="detailsBackdrop">
                        <div class="rmu-modal" style="max-width:860px;width:calc(100% - 48px);">
                            <div class="rmu-modal__header">
                                <span class="rmu-modal__title">
                                    <i class="ti ti-file-description" style="margin-right:8px;"></i>Claim Details
                                </span>
                                <button class="rmu-modal__close" onclick="closeDetailsModal()" title="Close" aria-label="Close">
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

<?php
/* Rendered empty-state and no-match helpers (defined after use is fine in PHP). */
function mc_empty($icon, $title, $sub) {
    return '<div class="mc-emptywrap"><i class="ti ' . $icon . '"></i>'
         . '<div class="mc-emptywrap__title">' . h($title) . '</div>'
         . '<div class="mc-emptywrap__sub">' . h($sub) . '</div></div>';
}
function mc_nomatch() {
    return '<div class="mc-emptywrap mc-emptywrap--sm"><i class="ti ti-search-off"></i>'
         . '<div class="mc-emptywrap__title">No matching claims</div></div>';
}
?>

<style>
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Frosted-glass background orbs (white/blue) ─────────────────────────── */
.mc-orbs { position: fixed; inset: 0; overflow: hidden; pointer-events: none; z-index: 0; }
.mc-orbs span { position: absolute; border-radius: 50%; filter: blur(90px); opacity: .55; }
.mc-orbs span:nth-child(1) { width: 440px; height: 440px; background: rgba(59,130,246,0.22); top: -130px; left: -90px; }
.mc-orbs span:nth-child(2) { width: 380px; height: 380px; background: rgba(13,148,136,0.14); bottom: -120px; right: -70px; }
.mc-page { position: relative; z-index: 1; }

/* ── Glass surface ─────────────────────────────────────────────────────── */
.mc-glass {
    background: rgba(255,255,255,0.66);
    backdrop-filter: blur(16px) saturate(140%);
    -webkit-backdrop-filter: blur(16px) saturate(140%);
    border: 1px solid rgba(255,255,255,0.75);
    box-shadow: 0 10px 30px rgba(15,23,42,0.08);
    border-radius: 16px;
}

/* ── Header ────────────────────────────────────────────────────────────── */
.mc-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 16px; flex-wrap: wrap; margin-bottom: 22px;
}

/* ── Overview stat cards ───────────────────────────────────────────────── */
.mc-stats {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 16px; margin-bottom: 20px;
}
.mc-stat {
    display: flex; align-items: center; gap: 14px; padding: 16px 18px;
    cursor: pointer; font: inherit; text-align: left; color: inherit;
    transition: transform .18s ease, box-shadow .18s ease;
}
.mc-stat:hover { transform: translateY(-2px); box-shadow: 0 16px 36px rgba(15,23,42,0.13); }
.mc-stat.is-active { outline: 2px solid var(--clr-primary); outline-offset: 0; }
.mc-stat__icon {
    width: 46px; height: 46px; border-radius: 13px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 1.35rem;
}
.mc-stat__count { display: block; font-size: 1.7rem; font-weight: 700; line-height: 1; color: var(--txt-primary); letter-spacing: -.02em; }
.mc-stat__label { display: block; font-size: .76rem; color: var(--txt-secondary); text-transform: uppercase; letter-spacing: .05em; margin-top: 3px; }
.mc-stat--flagged   .mc-stat__icon { background: rgba(180,83,9,0.13);  color: #b45309; }
.mc-stat--pending   .mc-stat__icon { background: rgba(29,78,216,0.12); color: #1d4ed8; }
.mc-stat--saved     .mc-stat__icon { background: rgba(71,85,105,0.14); color: #475569; }
.mc-stat--completed .mc-stat__icon { background: rgba(4,120,87,0.13);  color: #047857; }

/* ── Toolbar (tabs + search) ───────────────────────────────────────────── */
.mc-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    gap: 14px; flex-wrap: wrap; padding: 12px 14px; margin-bottom: 20px;
}
.mc-tabs { display: flex; flex-wrap: wrap; gap: 6px; }
.mc-tab {
    display: inline-flex; align-items: center; gap: 7px; padding: 7px 14px;
    border: 1px solid transparent; border-radius: 999px; background: transparent;
    color: var(--txt-secondary); font: inherit; font-size: .83rem; font-weight: 500; cursor: pointer;
    transition: background .15s, color .15s;
}
.mc-tab:hover { background: rgba(15,23,42,0.05); color: var(--txt-primary); }
.mc-tab.is-active { background: var(--clr-primary); color: #fff; }
.mc-tab__count { font-size: .7rem; font-weight: 600; background: rgba(15,23,42,0.08); color: var(--txt-secondary); border-radius: 999px; padding: 1px 7px; }
.mc-tab.is-active .mc-tab__count { background: rgba(255,255,255,0.28); color: #fff; }
.mc-search { position: relative; }
.mc-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--txt-muted); font-size: 1rem; }
.mc-search input {
    padding: 9px 12px 9px 34px; border-radius: 10px; border: 1px solid var(--input-border);
    background: rgba(255,255,255,0.85); color: var(--txt-primary); font: inherit; font-size: .85rem;
    min-width: 240px; outline: none; transition: border-color .15s, box-shadow .15s;
}
.mc-search input:focus { border-color: var(--clr-primary); box-shadow: 0 0 0 3px rgba(29,78,216,0.18); }

/* ── Sections ──────────────────────────────────────────────────────────── */
.mc-section { margin-bottom: 20px; overflow: hidden; border-left: 3px solid transparent; }
.mc-section.is-hidden { display: none; }
.mc-section--flagged   { border-left-color: #b45309; }
.mc-section--pending   { border-left-color: #1d4ed8; }
.mc-section--saved     { border-left-color: #475569; }
.mc-section--completed { border-left-color: #047857; }
.mc-section__head {
    display: flex; align-items: center; gap: 10px;
    padding: 15px 20px; border-bottom: 1px solid var(--divider);
}
.mc-section__title { font-size: .95rem; font-weight: 600; color: var(--txt-primary); display: flex; align-items: center; gap: 8px; }
.mc-section--flagged   .mc-section__title i { color: #b45309; }
.mc-section--pending   .mc-section__title i { color: #1d4ed8; }
.mc-section--saved     .mc-section__title i { color: #475569; }
.mc-section--completed .mc-section__title i { color: #047857; }

/* tables sit on glass — keep cells transparent so the frost shows through */
.mc-section .rmu-table tbody td { border-bottom-color: rgba(15,23,42,0.06); }
.mc-section .rmu-table tbody tr:hover td { background: rgba(29,78,216,0.05); }
.mc-dash { color: var(--txt-muted); }

/* ── Stage progress bar ────────────────────────────────────────────────── */
.mc-stage { display: flex; align-items: center; gap: 8px; min-width: 150px; }
.mc-stage__bar { flex: 1; height: 6px; border-radius: 999px; background: rgba(15,23,42,0.10); overflow: hidden; }
.mc-stage__bar span { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, var(--clr-primary), #3b82f6); }
.mc-stage__txt { font-size: .72rem; color: var(--txt-secondary); white-space: nowrap; }

/* ── Empty / no-match states ───────────────────────────────────────────── */
.mc-emptywrap { text-align: center; padding: 34px 20px; color: var(--txt-muted); }
.mc-emptywrap i { font-size: 2rem; display: block; margin-bottom: 10px; opacity: .5; }
.mc-emptywrap__title { font-weight: 600; color: var(--txt-secondary); }
.mc-emptywrap__sub { font-size: .82rem; margin-top: 3px; }
.mc-emptywrap--sm { padding: 22px 20px; }
.mc-emptywrap--sm i { font-size: 1.5rem; }

@media (max-width: 600px) {
    .mc-search input { min-width: 0; width: 100%; }
    .mc-search { flex: 1 1 100%; }
}
</style>

<script>
const CSRF     = '<?php echo h(csrf_token()); ?>';
const swalOpts = { background: '#ffffff', color: '#0f2744' };

// ── Filter tabs + search ────────────────────────────────────────────────────
(function () {
    const tabs     = Array.from(document.querySelectorAll('.mc-tab'));
    const stats    = Array.from(document.querySelectorAll('.mc-stat'));
    const sections = Array.from(document.querySelectorAll('.mc-section'));
    const search   = document.getElementById('mcSearch');
    let activeTab  = 'all';

    function applySearch() {
        const q = (search.value || '').trim().toLowerCase();
        sections.forEach(sec => {
            if (sec.classList.contains('is-hidden')) return;
            let visible = 0;
            sec.querySelectorAll('tbody tr[data-row]').forEach(tr => {
                const match = !q || tr.textContent.toLowerCase().includes(q);
                tr.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            const total   = sec.querySelectorAll('tbody tr[data-row]').length;
            const noMatch = sec.querySelector('.mc-no-match');
            if (noMatch) noMatch.hidden = !(q && total > 0 && visible === 0);
        });
    }

    function setTab(target) {
        activeTab = target;
        tabs.forEach(b => {
            const on = b.dataset.target === target;
            b.classList.toggle('is-active', on);
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        stats.forEach(s => s.classList.toggle('is-active', target !== 'all' && s.dataset.target === target));
        sections.forEach(s => s.classList.toggle('is-hidden', target !== 'all' && s.dataset.section !== target));
        applySearch();
    }

    tabs.forEach(b => b.addEventListener('click', () => setTab(b.dataset.target)));
    stats.forEach(s => s.addEventListener('click', () => setTab(s.dataset.target)));
    if (search) search.addEventListener('input', applySearch);
})();

// ── Claim actions ───────────────────────────────────────────────────────────
function editClaim(claimId) {
    window.location.assign("../fileNewClaim/index.php?claimTempId=" + claimId);
}

function viewClaimDetails(claimId) {
    const body     = document.getElementById('detailsModalBody');
    const backdrop = document.getElementById('detailsBackdrop');
    body.innerHTML = '<p style="text-align:center;padding:32px;color:var(--txt-muted);">'
        + '<i class="ti ti-loader" style="animation:spin .8s linear infinite;font-size:1.6rem;"></i></p>';
    backdrop.classList.add('open');
    document.body.style.overflow = 'hidden';
    $.ajax({
        url: 'viewClaimDetails.inc.php', type: 'GET', data: { claimId: claimId },
        success: function (response) { body.innerHTML = response; },
        error: function () {
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

document.getElementById('detailsBackdrop').addEventListener('click', function (e) {
    if (e.target === this) closeDetailsModal();
});
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && document.getElementById('detailsBackdrop').classList.contains('open')) {
        closeDetailsModal();
    }
});

function deleteClaim(claimId) {
    Swal.fire(Object.assign({
        title: 'Delete Draft?', text: 'This draft will be permanently removed.', icon: 'warning',
        showCancelButton: true, confirmButtonText: 'Yes, Delete',
        confirmButtonColor: '#ef4444', cancelButtonColor: '#64748b',
    }, swalOpts)).then(function (result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: 'deleteClaim.inc.php', type: 'POST', dataType: 'json',
            data: { claimId: claimId, csrf_token: CSRF },
            success: function (response) {
                Swal.fire(Object.assign({ icon: 'success', title: 'Deleted',
                    text: response.success || 'Draft removed.', timer: 1800, showConfirmButton: false }, swalOpts))
                    .then(function () { location.reload(); });
            },
            error: function () {
                Swal.fire(Object.assign({ icon: 'error', title: 'Error',
                    text: 'Could not delete the draft. Please try again.' }, swalOpts));
            }
        });
    });
}

function resubmitClaim(claimId) {
    Swal.fire(Object.assign({
        title: 'Resubmit Claim?',
        text: 'A copy of this claim will open for editing so you can address the flagged issues before resubmitting.',
        icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, Edit & Resubmit',
        confirmButtonColor: '#1d4ed8', cancelButtonColor: '#64748b',
    }, swalOpts)).then(function (result) {
        if (!result.isConfirmed) return;
        var fd = new FormData();
        fd.append('claimId', claimId); fd.append('csrf_token', CSRF);
        fetch('resubmitFlaggedClaim.inc.php', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    window.location.assign('../fileNewClaim/index.php?claimTempId=' + res.claimTempId);
                } else {
                    Swal.fire(Object.assign({ icon: 'error', title: 'Error',
                        text: res.message || 'Could not prepare claim for resubmission.' }, swalOpts));
                }
            })
            .catch(function () {
                Swal.fire(Object.assign({ icon: 'error', title: 'Network Error',
                    text: 'Could not reach the server. Please try again.' }, swalOpts));
            });
    });
}

function submitDraft(claimId) {
    Swal.fire(Object.assign({
        title: 'Submit Claim?', text: 'Once submitted, the claim will be sent for approval and cannot be edited.',
        icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, Submit',
        confirmButtonColor: '#1d4ed8', cancelButtonColor: '#64748b',
    }, swalOpts)).then(function (result) {
        if (!result.isConfirmed) return;
        var fd = new FormData();
        fd.append('claimId', claimId); fd.append('csrf_token', CSRF);
        fetch('submitClaimDetails.inc.php', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.ok) {
                    Swal.fire(Object.assign({ icon: 'success', title: 'Submitted',
                        text: res.message || 'Claim submitted for approval.', timer: 2200, showConfirmButton: false }, swalOpts))
                        .then(function () { location.reload(); });
                } else {
                    Swal.fire(Object.assign({ icon: 'error', title: 'Submission Failed',
                        text: res.message || 'Please try again.' }, swalOpts));
                }
            })
            .catch(function () {
                Swal.fire(Object.assign({ icon: 'error', title: 'Network Error',
                    text: 'Could not reach the server. Please try again.' }, swalOpts));
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
        icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, Clone',
        confirmButtonColor: '#1d4ed8', cancelButtonColor: '#64748b',
    }, swalOpts)).then(function (result) {
        if (!result.isConfirmed) return;
        var fd = new FormData();
        fd.append('claimId', claimId); fd.append('csrf_token', CSRF);
        fetch('cloneClaim.inc.php', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    window.location.assign('../fileNewClaim/index.php?claimTempId=' + res.claimTempId);
                } else {
                    Swal.fire(Object.assign({ icon: 'error', title: 'Error',
                        text: res.message || 'Could not clone the claim.' }, swalOpts));
                }
            })
            .catch(function () {
                Swal.fire(Object.assign({ icon: 'error', title: 'Network Error',
                    text: 'Could not reach the server. Please try again.' }, swalOpts));
            });
    });
}
</script>

</body>
</html>
