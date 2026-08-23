<?php
/*
 * Approval Timing report (#4).
 *
 * Measures how long claims spend at each approval stage, using only existing
 * data — no schema change. A claim's time at stage N is:
 *     stage 1 : time_approved(stage 1) − claim_details.time_submitted
 *     stage N : time_approved(stage N) − time_approved(stage N-1)
 * Claims still in the pipeline contribute a live "waiting" duration at their
 * current stage (now − entry time), which drives the bottleneck / SLA view.
 *
 * All computation is in PHP so the same figures feed the summary, the per-stage
 * table and the bottleneck list from one query.
 */
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
checkUserRole(['admin', 'Admin']);

// ── Filters ────────────────────────────────────────────────────────────────
$f_start = validated_str(isset($_GET['start_date']) ? $_GET['start_date'] : '');
$f_end   = validated_str(isset($_GET['end_date'])   ? $_GET['end_date']   : '');
$sla_days = isset($_GET['sla']) && is_numeric($_GET['sla']) ? max(1, (int) $_GET['sla']) : 3;
$sla_secs = $sla_days * 86400;
$has_filters = ($f_start !== '' || $f_end !== '' || (isset($_GET['sla']) && (int) $_GET['sla'] !== 3));

// ── Stage → role label map (from the workflow roles table) ───────────────────
$stage_label = array();
if ($rl = mysqli_query($conn, "SELECT stage, role_name FROM roles WHERE stage BETWEEN 1 AND 20")) {
    while ($x = mysqli_fetch_assoc($rl)) {
        $stage_label[(int) $x['stage']] = ucwords(str_replace('_', ' ', $x['role_name']));
    }
}
function stage_name($stage, $map) {
    return isset($map[$stage]) ? $map[$stage] : ('Stage ' . $stage);
}

// ── Pull claims + their approval stages in one pass ──────────────────────────
$sql = "SELECT cd.claimId, cd.time_submitted, cd.completed, cd.flagged,
               CONCAT(ud.first_name, ' ', ud.last_name) AS claimant,
               cd.department, cd.course,
               s.stage, s.status, s.time_approved
        FROM claim_details cd
        INNER JOIN user_details ud ON cd.userId = ud.userId
        LEFT JOIN claim_approval_stages s ON s.claimId = cd.claimId
        WHERE 1=1";
$types = '';
$params = array();
if ($f_start !== '') { $sql .= ' AND DATE(cd.time_submitted) >= ?'; $types .= 's'; $params[] = $f_start; }
if ($f_end   !== '') { $sql .= ' AND DATE(cd.time_submitted) <= ?'; $types .= 's'; $params[] = $f_end; }
$sql .= ' ORDER BY cd.claimId, s.stage';

$stmt = mysqli_prepare($conn, $sql);
$rows = array();
if ($stmt) {
    if ($types !== '') mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}

// ── Group rows into claims ───────────────────────────────────────────────────
$claims = array();
foreach ($rows as $r) {
    $id = (int) $r['claimId'];
    if (!isset($claims[$id])) {
        $claims[$id] = array(
            'claimId'   => $id,
            'submitted' => $r['time_submitted'] ? strtotime($r['time_submitted']) : null,
            'completed' => (int) $r['completed'],
            'flagged'   => (int) $r['flagged'],
            'claimant'  => $r['claimant'],
            'department'=> $r['department'],
            'course'    => $r['course'],
            'approved'  => array(),   // stage => unix ts of approval
        );
    }
    if ($r['stage'] !== null && $r['status'] === 'Approved' && $r['time_approved']) {
        $claims[$id]['approved'][(int) $r['stage']] = strtotime($r['time_approved']);
    }
}

// ── Compute per-stage stats + bottlenecks ────────────────────────────────────
$now = time();
$stage_stats = array();   // stage => ['sum','count','min','max','over']
$bottlenecks = array();   // currently-pending claims with live waiting time
$cycle_sum = 0; $cycle_count = 0;        // end-to-end, completed claims only
$total_claims = count($claims);

function stat_add(&$stats, $stage, $dur, $sla_secs) {
    if (!isset($stats[$stage])) {
        $stats[$stage] = array('sum' => 0, 'count' => 0, 'min' => null, 'max' => null, 'over' => 0);
    }
    $s = &$stats[$stage];
    $s['sum'] += $dur; $s['count']++;
    if ($s['min'] === null || $dur < $s['min']) $s['min'] = $dur;
    if ($s['max'] === null || $dur > $s['max']) $s['max'] = $dur;
    if ($dur > $sla_secs) $s['over']++;
}

foreach ($claims as $c) {
    $appr = $c['approved'];
    ksort($appr);

    // Completed-stage durations.
    foreach ($appr as $stage => $ts) {
        if ($stage == 1) {
            $entry = $c['submitted'];
        } else {
            $entry = isset($appr[$stage - 1]) ? $appr[$stage - 1] : null;
        }
        if ($entry !== null && $ts >= $entry) {
            stat_add($stage_stats, $stage, $ts - $entry, $sla_secs);
        }
    }

    // End-to-end cycle time for completed claims.
    if ($c['completed'] && $c['submitted'] && !empty($appr)) {
        $last = max($appr);
        if ($last >= $c['submitted']) { $cycle_sum += $last - $c['submitted']; $cycle_count++; }
    }

    // Live waiting time for claims still in the pipeline.
    if (!$c['completed'] && !$c['flagged']) {
        $max_appr = empty($appr) ? 0 : max(array_keys($appr));
        $cur_stage = $max_appr + 1;
        $entry = $max_appr > 0 ? $appr[$max_appr] : $c['submitted'];
        if ($entry !== null) {
            $bottlenecks[] = array(
                'claimId'  => $c['claimId'],
                'claimant' => $c['claimant'],
                'department' => $c['department'],
                'course'   => $c['course'],
                'stage'    => $cur_stage,
                'waiting'  => max(0, $now - $entry),
            );
        }
    }
}
ksort($stage_stats);
usort($bottlenecks, function ($a, $b) { return $b['waiting'] - $a['waiting']; });
$breaching = 0;
foreach ($bottlenecks as $b) { if ($b['waiting'] > $sla_secs) $breaching++; }

// ── Duration formatter: seconds → "2d 4h" / "5h 20m" / "12m" ─────────────────
function fmt_dur($secs) {
    $secs = (int) $secs;
    if ($secs < 60) return '<1m';
    $d = intdiv($secs, 86400); $secs %= 86400;
    $h = intdiv($secs, 3600);  $secs %= 3600;
    $m = intdiv($secs, 60);
    if ($d > 0) return $d . 'd' . ($h > 0 ? ' ' . $h . 'h' : '');
    if ($h > 0) return $h . 'h' . ($m > 0 ? ' ' . $m . 'm' : '');
    return $m . 'm';
}

$pageTitle = "Approval Timing";
?>
<!DOCTYPE html>
<html lang="en">
<?php include '../../assets/partials/head.php'; ?>
<body>
<div class="page-wrapper" id="main-wrapper">
    <?php include '../../assets/partials/sidebar.php'; ?>

    <div class="body-wrapper">
        <?php include '../../assets/partials/header.php'; ?>

        <div style="padding:28px 32px;">

            <div class="rmu-page-header">
                <div class="rmu-page-header__title">Approval Timing</div>
                <div class="rmu-page-header__sub">How long claims spend at each approval stage, and where they are stuck now</div>
            </div>

            <!-- Filters -->
            <div class="rmu-card" style="margin-bottom:24px;">
                <div class="rmu-card__header">
                    <span class="rmu-card__title"><i class="ti ti-filter" style="margin-right:8px;"></i>Filters</span>
                    <?php if ($has_filters): ?>
                    <a href="?" class="rmu-btn rmu-btn--secondary" style="padding:4px 12px;font-size:.82rem;">
                        <i class="ti ti-x"></i> Clear
                    </a>
                    <?php endif; ?>
                </div>
                <div class="rmu-card__body">
                    <form method="GET" action="">
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;margin-bottom:16px;">
                            <div class="rmu-form-group" style="margin-bottom:0;">
                                <label class="rmu-label">Submitted from</label>
                                <input type="date" name="start_date" class="rmu-input" value="<?php echo h($f_start); ?>">
                            </div>
                            <div class="rmu-form-group" style="margin-bottom:0;">
                                <label class="rmu-label">Submitted to</label>
                                <input type="date" name="end_date" class="rmu-input" value="<?php echo h($f_end); ?>">
                            </div>
                            <div class="rmu-form-group" style="margin-bottom:0;">
                                <label class="rmu-label">SLA threshold (days)</label>
                                <input type="number" name="sla" min="1" step="1" class="rmu-input" value="<?php echo (int) $sla_days; ?>">
                            </div>
                        </div>
                        <button type="submit" class="rmu-btn rmu-btn--primary">
                            <i class="ti ti-search"></i> Apply
                        </button>
                    </form>
                </div>
            </div>

            <!-- Summary stat cards -->
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-bottom:24px;">
                <div class="rmu-card"><div class="rmu-card__body">
                    <div style="font-size:.8rem;color:var(--txt-muted);">Claims in range</div>
                    <div style="font-size:1.8rem;font-weight:700;color:var(--accent);"><?php echo (int) $total_claims; ?></div>
                </div></div>
                <div class="rmu-card"><div class="rmu-card__body">
                    <div style="font-size:.8rem;color:var(--txt-muted);">Avg end-to-end (completed)</div>
                    <div style="font-size:1.8rem;font-weight:700;color:var(--accent);">
                        <?php echo $cycle_count ? h(fmt_dur($cycle_sum / $cycle_count)) : '—'; ?>
                    </div>
                    <div style="font-size:.75rem;color:var(--txt-muted);"><?php echo (int) $cycle_count; ?> completed</div>
                </div></div>
                <div class="rmu-card"><div class="rmu-card__body">
                    <div style="font-size:.8rem;color:var(--txt-muted);">In pipeline now</div>
                    <div style="font-size:1.8rem;font-weight:700;color:var(--accent);"><?php echo count($bottlenecks); ?></div>
                </div></div>
                <div class="rmu-card"><div class="rmu-card__body">
                    <div style="font-size:.8rem;color:var(--txt-muted);">Breaching SLA (&gt;<?php echo (int) $sla_days; ?>d)</div>
                    <div style="font-size:1.8rem;font-weight:700;color:<?php echo $breaching ? 'var(--danger)' : 'var(--accent)'; ?>;"><?php echo (int) $breaching; ?></div>
                </div></div>
            </div>

            <!-- Per-stage timing -->
            <div class="rmu-card" style="margin-bottom:24px;">
                <div class="rmu-card__header">
                    <span class="rmu-card__title">Time per approval stage</span>
                    <span class="rmu-badge rmu-badge--primary"><?php echo count($stage_stats); ?> stage<?php echo count($stage_stats) !== 1 ? 's' : ''; ?></span>
                </div>
                <div class="rmu-card__body" style="padding:0;">
                    <div class="rmu-table-wrap">
                        <table class="rmu-table">
                            <thead>
                                <tr>
                                    <th>Stage</th>
                                    <th>Role</th>
                                    <th>Approvals</th>
                                    <th>Average</th>
                                    <th>Fastest</th>
                                    <th>Slowest</th>
                                    <th>Over SLA</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($stage_stats)): foreach ($stage_stats as $stage => $s):
                                    $avg = $s['count'] ? $s['sum'] / $s['count'] : 0; ?>
                                <tr>
                                    <td style="font-weight:600;"><?php echo (int) $stage; ?></td>
                                    <td><?php echo h(stage_name($stage, $stage_label)); ?></td>
                                    <td><?php echo (int) $s['count']; ?></td>
                                    <td style="font-weight:600;"><?php echo h(fmt_dur($avg)); ?></td>
                                    <td style="color:var(--txt-muted);"><?php echo h(fmt_dur($s['min'])); ?></td>
                                    <td style="color:var(--txt-muted);"><?php echo h(fmt_dur($s['max'])); ?></td>
                                    <td>
                                        <?php if ($s['over'] > 0): ?>
                                            <span class="rmu-badge rmu-badge--danger"><?php echo (int) $s['over']; ?></span>
                                        <?php else: ?>
                                            <span style="color:var(--txt-muted);">0</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr>
                                    <td colspan="7" style="text-align:center;color:var(--txt-muted);padding:40px 20px;">
                                        <i class="ti ti-clock-off" style="font-size:2rem;display:block;margin-bottom:10px;opacity:.4;"></i>
                                        No approvals recorded in this range yet.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Bottlenecks: claims waiting now -->
            <div class="rmu-card">
                <div class="rmu-card__header">
                    <span class="rmu-card__title"><i class="ti ti-alert-triangle" style="margin-right:8px;"></i>Currently waiting</span>
                    <span class="rmu-badge <?php echo $breaching ? 'rmu-badge--danger' : 'rmu-badge--primary'; ?>">
                        <?php echo count($bottlenecks); ?> claim<?php echo count($bottlenecks) !== 1 ? 's' : ''; ?>
                    </span>
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
                                    <th>Waiting at</th>
                                    <th>Waiting for</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($bottlenecks)): foreach ($bottlenecks as $b):
                                    $over = $b['waiting'] > $sla_secs; ?>
                                <tr<?php echo $over ? ' style="background:rgba(214,40,40,.05);"' : ''; ?>>
                                    <td><?php echo (int) $b['claimId']; ?></td>
                                    <td style="font-weight:500;"><?php echo h($b['claimant']); ?></td>
                                    <td><?php echo h($b['department']); ?></td>
                                    <td><?php echo h($b['course']); ?></td>
                                    <td><?php echo (int) $b['stage'] . ' &middot; ' . h(stage_name($b['stage'], $stage_label)); ?></td>
                                    <td>
                                        <span class="rmu-badge <?php echo $over ? 'rmu-badge--danger' : 'rmu-badge--neutral'; ?>">
                                            <?php echo h(fmt_dur($b['waiting'])); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr>
                                    <td colspan="6" style="text-align:center;color:var(--txt-muted);padding:40px 20px;">
                                        <i class="ti ti-circle-check" style="font-size:2rem;display:block;margin-bottom:10px;opacity:.4;"></i>
                                        No claims are waiting — the pipeline is clear.
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
</body>
</html>
