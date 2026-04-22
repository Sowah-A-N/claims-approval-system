<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
checkUserRole(['admin', 'Admin']);

// ── Filter values from GET ────────────────────────────────────────────────────
$f_dept       = validated_str(isset($_GET['department'])  ? $_GET['department']  : '');
$f_programme  = validated_str(isset($_GET['programme'])   ? $_GET['programme']   : '');
$f_course     = validated_str(isset($_GET['course'])      ? $_GET['course']      : '');
$f_status     = validated_str(isset($_GET['status'])      ? $_GET['status']      : '');
$f_start_date = validated_str(isset($_GET['start_date'])  ? $_GET['start_date']  : '');
$f_end_date   = validated_str(isset($_GET['end_date'])    ? $_GET['end_date']    : '');
$f_sort       = validated_str(isset($_GET['sort'])        ? $_GET['sort']        : 'claimId');
$f_order      = (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') ? 'ASC' : 'DESC';

$valid_sorts = ['claimId', 'full_name', 'department', 'time_submitted'];
if (!in_array($f_sort, $valid_sorts)) $f_sort = 'claimId';

// ── Filter options (for dropdowns) ───────────────────────────────────────────
function fetch_distinct($conn, $col, $table) {
    $stmt = mysqli_prepare($conn, "SELECT DISTINCT `$col` FROM `$table` WHERE `$col` IS NOT NULL AND `$col` <> '' ORDER BY `$col`");
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return array_column($rows, $col);
}
$dept_opts  = fetch_distinct($conn, 'department', 'claim_details');
$prog_opts  = fetch_distinct($conn, 'programme',  'claim_details');
$cours_opts = fetch_distinct($conn, 'course',     'claim_details');

// ── Build query ───────────────────────────────────────────────────────────────
$base = "SELECT cd.claimId,
                CONCAT(ud.first_name, ' ', ud.last_name) AS full_name,
                cd.department, cd.programme, cd.course,
                cd.flagged, cd.completed,
                cd.time_submitted,
                cas.stage  AS current_stage,
                cas.status AS current_status
         FROM claim_details cd
         INNER JOIN user_details ud ON cd.userId = ud.userId
         LEFT JOIN (
             SELECT claimId, MAX(stage) AS max_stage
             FROM claim_approval_stages
             GROUP BY claimId
         ) ms ON cd.claimId = ms.claimId
         LEFT JOIN claim_approval_stages cas
             ON cd.claimId = cas.claimId AND ms.max_stage = cas.stage
         WHERE 1=1";

$types  = '';
$params = [];

if ($f_dept !== '') {
    $base    .= ' AND cd.department = ?';
    $params[] = $f_dept;
    $types   .= 's';
}
if ($f_programme !== '') {
    $base    .= ' AND cd.programme = ?';
    $params[] = $f_programme;
    $types   .= 's';
}
if ($f_course !== '') {
    $base    .= ' AND cd.course = ?';
    $params[] = $f_course;
    $types   .= 's';
}
switch ($f_status) {
    case 'Pending':
        $base .= ' AND cd.completed = 0 AND cd.flagged = 0';
        break;
    case 'Flagged':
        $base .= ' AND cd.flagged = 1';
        break;
    case 'Completed':
        $base .= ' AND cd.completed = 1';
        break;
}
if ($f_start_date !== '') {
    $base    .= ' AND DATE(cd.time_submitted) >= ?';
    $params[] = $f_start_date;
    $types   .= 's';
}
if ($f_end_date !== '') {
    $base    .= ' AND DATE(cd.time_submitted) <= ?';
    $params[] = $f_end_date;
    $types   .= 's';
}

$base .= " ORDER BY `{$f_sort}` {$f_order}";

$stmt = mysqli_prepare($conn, $base);
$claims = [];
if ($stmt) {
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $claims = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}

$has_filters = ($f_dept || $f_programme || $f_course || $f_status || $f_start_date || $f_end_date);

// Helper: sort link URL
function sort_link($col, $cur_sort, $cur_order) {
    $params = $_GET;
    $params['sort']  = $col;
    $params['order'] = ($cur_sort === $col && $cur_order === 'DESC') ? 'asc' : 'desc';
    return '?' . http_build_query($params);
}
function sort_icon($col, $cur_sort, $cur_order) {
    if ($cur_sort !== $col) return '<i class="ti ti-arrows-sort" style="opacity:.35;font-size:.8rem;"></i>';
    return $cur_order === 'ASC'
        ? '<i class="ti ti-sort-ascending" style="font-size:.8rem;color:var(--accent);"></i>'
        : '<i class="ti ti-sort-descending" style="font-size:.8rem;color:var(--accent);"></i>';
}

$pageTitle = "Reports";
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
                <div class="rmu-page-header__title">Claims Reports</div>
                <div class="rmu-page-header__sub">Filter, sort and export claim records</div>
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
                                <label class="rmu-label">Department</label>
                                <select name="department" class="rmu-select">
                                    <option value="">All</option>
                                    <?php foreach ($dept_opts as $d): ?>
                                    <option value="<?php echo h($d); ?>"
                                        <?php echo ($f_dept === $d) ? 'selected' : ''; ?>>
                                        <?php echo h($d); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="rmu-form-group" style="margin-bottom:0;">
                                <label class="rmu-label">Programme</label>
                                <select name="programme" class="rmu-select">
                                    <option value="">All</option>
                                    <?php foreach ($prog_opts as $p): ?>
                                    <option value="<?php echo h($p); ?>"
                                        <?php echo ($f_programme === $p) ? 'selected' : ''; ?>>
                                        <?php echo h($p); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="rmu-form-group" style="margin-bottom:0;">
                                <label class="rmu-label">Course</label>
                                <select name="course" class="rmu-select">
                                    <option value="">All</option>
                                    <?php foreach ($cours_opts as $c): ?>
                                    <option value="<?php echo h($c); ?>"
                                        <?php echo ($f_course === $c) ? 'selected' : ''; ?>>
                                        <?php echo h($c); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="rmu-form-group" style="margin-bottom:0;">
                                <label class="rmu-label">Status</label>
                                <select name="status" class="rmu-select">
                                    <option value="">All</option>
                                    <option value="Pending"   <?php echo ($f_status === 'Pending')   ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Flagged"   <?php echo ($f_status === 'Flagged')   ? 'selected' : ''; ?>>Flagged</option>
                                    <option value="Completed" <?php echo ($f_status === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>

                            <div class="rmu-form-group" style="margin-bottom:0;">
                                <label class="rmu-label">From Date</label>
                                <input type="date" name="start_date" class="rmu-input"
                                       value="<?php echo h($f_start_date); ?>">
                            </div>

                            <div class="rmu-form-group" style="margin-bottom:0;">
                                <label class="rmu-label">To Date</label>
                                <input type="date" name="end_date" class="rmu-input"
                                       value="<?php echo h($f_end_date); ?>">
                            </div>

                        </div>
                        <input type="hidden" name="sort"  value="<?php echo h($f_sort); ?>">
                        <input type="hidden" name="order" value="<?php echo h(strtolower($f_order)); ?>">
                        <button type="submit" class="rmu-btn rmu-btn--primary">
                            <i class="ti ti-search"></i> Apply Filters
                        </button>
                    </form>
                </div>
            </div>

            <!-- Results -->
            <div class="rmu-card">
                <div class="rmu-card__header">
                    <span class="rmu-card__title">Results</span>
                    <span class="rmu-badge rmu-badge--primary"><?php echo count($claims); ?> record<?php echo count($claims) !== 1 ? 's' : ''; ?></span>
                </div>
                <div class="rmu-card__body" style="padding:0;">
                    <div class="rmu-table-wrap">
                        <table class="rmu-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>
                                        <a href="<?php echo h(sort_link('claimId', $f_sort, $f_order)); ?>"
                                           style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:4px;">
                                            ID <?php echo sort_icon('claimId', $f_sort, $f_order); ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="<?php echo h(sort_link('full_name', $f_sort, $f_order)); ?>"
                                           style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:4px;">
                                            Claimant <?php echo sort_icon('full_name', $f_sort, $f_order); ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="<?php echo h(sort_link('department', $f_sort, $f_order)); ?>"
                                           style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:4px;">
                                            Department <?php echo sort_icon('department', $f_sort, $f_order); ?>
                                        </a>
                                    </th>
                                    <th>Course</th>
                                    <th>
                                        <a href="<?php echo h(sort_link('time_submitted', $f_sort, $f_order)); ?>"
                                           style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:4px;">
                                            Submitted <?php echo sort_icon('time_submitted', $f_sort, $f_order); ?>
                                        </a>
                                    </th>
                                    <th>Stage</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($claims)):
                                    $i = 1;
                                    foreach ($claims as $row):
                                        if ($row['completed']) {
                                            $badge = '<span class="rmu-badge rmu-badge--success">Completed</span>';
                                        } elseif ($row['flagged']) {
                                            $badge = '<span class="rmu-badge rmu-badge--danger">Flagged</span>';
                                        } else {
                                            $status_cls = $row['current_status'] === 'Approved'
                                                ? 'rmu-badge--success'
                                                : 'rmu-badge--neutral';
                                            $badge = '<span class="rmu-badge ' . $status_cls . '">'
                                                . h($row['current_status'] ?? 'Pending') . '</span>';
                                        }
                                ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td style="font-family:monospace;">#<?php echo (int)$row['claimId']; ?></td>
                                    <td style="font-weight:500;"><?php echo h($row['full_name']); ?></td>
                                    <td><?php echo h($row['department']); ?></td>
                                    <td><?php echo h($row['course']); ?></td>
                                    <td><?php echo h(date('d M Y', strtotime($row['time_submitted']))); ?></td>
                                    <td><?php echo $row['current_stage'] !== null ? (int)$row['current_stage'] : '—'; ?></td>
                                    <td><?php echo $badge; ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align:center;color:var(--txt-muted);padding:40px 20px;">
                                        <i class="ti ti-search-off" style="font-size:2rem;display:block;margin-bottom:10px;opacity:.4;"></i>
                                        No claims match the selected filters.
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
