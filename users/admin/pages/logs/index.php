<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
checkUserRole(['admin', 'Admin']);

// ── Filters ───────────────────────────────────────────────────────────────────
$f_action      = validated_str(isset($_GET['action'])      ? $_GET['action']      : '');
$f_actor_role  = validated_str(isset($_GET['actor_role'])  ? $_GET['actor_role']  : '');
$f_entity_type = validated_str(isset($_GET['entity_type']) ? $_GET['entity_type'] : '');
$f_start_date  = validated_str(isset($_GET['start_date'])  ? $_GET['start_date']  : '');
$f_end_date    = validated_str(isset($_GET['end_date'])     ? $_GET['end_date']    : '');
$page          = max(1, (int)(isset($_GET['page']) ? $_GET['page'] : 1));
$per_page      = 50;
$offset        = ($page - 1) * $per_page;

$has_filters = ($f_action || $f_actor_role || $f_entity_type || $f_start_date || $f_end_date);

// Check if audit_log table exists
$table_exists = false;
$te = mysqli_query($conn, "SHOW TABLES LIKE 'audit_log'");
if ($te && mysqli_num_rows($te) > 0) $table_exists = true;

$logs       = [];
$total_rows = 0;
$action_opts      = [];
$actor_role_opts  = [];
$entity_type_opts = [];

if ($table_exists) {
    // Distinct filter values
    function al_distinct($conn, $col) {
        $stmt = mysqli_prepare($conn,
            "SELECT DISTINCT `$col` FROM audit_log WHERE `$col` IS NOT NULL AND `$col` <> '' ORDER BY `$col`");
        mysqli_stmt_execute($stmt);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        return array_column($rows, $col);
    }
    $action_opts      = al_distinct($conn, 'action');
    $actor_role_opts  = al_distinct($conn, 'actor_role');
    $entity_type_opts = al_distinct($conn, 'entity_type');

    // Build WHERE
    $where  = ' WHERE 1=1';
    $types  = '';
    $params = [];

    if ($f_action !== '') {
        $where .= ' AND action = ?';
        $params[] = $f_action; $types .= 's';
    }
    if ($f_actor_role !== '') {
        $where .= ' AND actor_role = ?';
        $params[] = $f_actor_role; $types .= 's';
    }
    if ($f_entity_type !== '') {
        $where .= ' AND entity_type = ?';
        $params[] = $f_entity_type; $types .= 's';
    }
    if ($f_start_date !== '') {
        $where .= ' AND DATE(created_at) >= ?';
        $params[] = $f_start_date; $types .= 's';
    }
    if ($f_end_date !== '') {
        $where .= ' AND DATE(created_at) <= ?';
        $params[] = $f_end_date; $types .= 's';
    }

    // Count
    $count_stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM audit_log" . $where);
    if ($count_stmt) {
        if ($types !== '') mysqli_stmt_bind_param($count_stmt, $types, ...$params);
        mysqli_stmt_execute($count_stmt);
        $total_rows = (int)mysqli_fetch_row(mysqli_stmt_get_result($count_stmt))[0];
        mysqli_stmt_close($count_stmt);
    }

    // Fetch page
    $limit_params   = $params;
    $limit_params[] = $per_page;
    $limit_params[] = $offset;
    $limit_types    = $types . 'ii';

    $data_stmt = mysqli_prepare($conn,
        "SELECT al.*, CONCAT(COALESCE(ud.first_name,''), ' ', COALESCE(ud.last_name,'')) AS actor_name
         FROM audit_log al
         LEFT JOIN user_details ud ON al.actor_id = ud.userId"
        . $where . " ORDER BY al.created_at DESC LIMIT ? OFFSET ?");
    if ($data_stmt) {
        mysqli_stmt_bind_param($data_stmt, $limit_types, ...$limit_params);
        mysqli_stmt_execute($data_stmt);
        $logs = mysqli_fetch_all(mysqli_stmt_get_result($data_stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($data_stmt);
    }
}

$total_pages = $total_rows > 0 ? (int)ceil($total_rows / $per_page) : 1;

function page_link($p) {
    $q = $_GET;
    $q['page'] = $p;
    return '?' . http_build_query($q);
}

// Current filters as a query string for the export links.
$qs = http_build_query(array_filter(array(
    'action'      => $f_action,
    'actor_role'  => $f_actor_role,
    'entity_type' => $f_entity_type,
    'start_date'  => $f_start_date,
    'end_date'    => $f_end_date,
), function ($v) { return $v !== ''; }));

$pageTitle = "Logs";
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

            <div class="rmu-page-header" style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
                <div>
                    <div class="rmu-page-header__title">System Logs</div>
                    <div class="rmu-page-header__sub">Immutable audit trail of system activity</div>
                </div>
                <?php if ($table_exists): ?>
                <div style="display:flex;gap:8px;">
                    <a class="rmu-btn rmu-btn--secondary" href="exportLogs.inc.php?format=csv<?php echo $qs ? '&' . h($qs) : ''; ?>">
                        <i class="ti ti-file-spreadsheet"></i> CSV
                    </a>
                    <a class="rmu-btn rmu-btn--secondary" href="exportLogs.inc.php?format=pdf<?php echo $qs ? '&' . h($qs) : ''; ?>" target="_blank">
                        <i class="ti ti-printer"></i> PDF
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!$table_exists): ?>
            <div class="rmu-alert rmu-alert--warning">
                <i class="ti ti-alert-triangle"></i>
                The <code>audit_log</code> table has not been created yet.
                Run the migration (<code>rmu_migration_v1.1_new_tables.sql</code>) to enable audit logging.
            </div>
            <?php else: ?>

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
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:14px;margin-bottom:16px;">

                            <div class="rmu-form-group" style="margin-bottom:0;">
                                <label class="rmu-label">Action</label>
                                <select name="action" class="rmu-select">
                                    <option value="">All</option>
                                    <?php foreach ($action_opts as $a): ?>
                                    <option value="<?php echo h($a); ?>"
                                        <?php echo ($f_action === $a) ? 'selected' : ''; ?>>
                                        <?php echo h(audit_action_label($a)); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="rmu-form-group" style="margin-bottom:0;">
                                <label class="rmu-label">Actor Role</label>
                                <select name="actor_role" class="rmu-select">
                                    <option value="">All</option>
                                    <?php foreach ($actor_role_opts as $r): ?>
                                    <option value="<?php echo h($r); ?>"
                                        <?php echo ($f_actor_role === $r) ? 'selected' : ''; ?>>
                                        <?php echo h($r); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="rmu-form-group" style="margin-bottom:0;">
                                <label class="rmu-label">Entity Type</label>
                                <select name="entity_type" class="rmu-select">
                                    <option value="">All</option>
                                    <?php foreach ($entity_type_opts as $e): ?>
                                    <option value="<?php echo h($e); ?>"
                                        <?php echo ($f_entity_type === $e) ? 'selected' : ''; ?>>
                                        <?php echo h($e); ?>
                                    </option>
                                    <?php endforeach; ?>
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
                        <button type="submit" class="rmu-btn rmu-btn--primary">
                            <i class="ti ti-search"></i> Apply Filters
                        </button>
                    </form>
                </div>
            </div>

            <!-- Log table -->
            <div class="rmu-card">
                <div class="rmu-card__header">
                    <span class="rmu-card__title">Audit Log</span>
                    <span class="rmu-badge rmu-badge--primary"><?php echo number_format($total_rows); ?> entr<?php echo $total_rows !== 1 ? 'ies' : 'y'; ?></span>
                </div>
                <div class="rmu-card__body" style="padding:0;">
                    <div class="rmu-table-wrap">
                        <table class="rmu-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Timestamp</th>
                                    <th>Actor</th>
                                    <th>Role</th>
                                    <th>Action</th>
                                    <th>Entity</th>
                                    <th>Entity ID</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($logs)):
                                    foreach ($logs as $log):
                                        $actor = trim($log['actor_name']);
                                        if ($actor === '') $actor = $log['actor_id'] ? '#' . $log['actor_id'] : 'System';
                                ?>
                                <tr>
                                    <td style="font-family:monospace;font-size:.8rem;"><?php echo (int)$log['audit_id']; ?></td>
                                    <td style="white-space:nowrap;font-size:.83rem;">
                                        <?php echo h(date('d M Y H:i:s', strtotime($log['created_at']))); ?>
                                    </td>
                                    <td><?php echo h($actor); ?></td>
                                    <td>
                                        <?php if ($log['actor_role']): ?>
                                        <span class="rmu-badge rmu-badge--neutral" style="font-size:.75rem;">
                                            <?php echo h($log['actor_role']); ?>
                                        </span>
                                        <?php else: echo '—'; endif; ?>
                                    </td>
                                    <td><?php echo h(audit_action_label($log['action'])); ?></td>
                                    <td><?php echo $log['entity_type'] ? h($log['entity_type']) : '—'; ?></td>
                                    <td><?php echo $log['entity_id']   ? (int)$log['entity_id'] : '—'; ?></td>
                                    <td style="font-family:monospace;font-size:.78rem;">
                                        <?php echo $log['ip_address'] ? h($log['ip_address']) : '—'; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align:center;color:var(--txt-muted);padding:40px 20px;">
                                        <i class="ti ti-database-off" style="font-size:2rem;display:block;margin-bottom:10px;opacity:.4;"></i>
                                        No log entries found.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if ($total_pages > 1): ?>
                <div style="display:flex;justify-content:center;align-items:center;gap:8px;padding:16px;">
                    <?php if ($page > 1): ?>
                    <a href="<?php echo h(page_link($page - 1)); ?>" class="rmu-btn rmu-btn--secondary" style="padding:5px 12px;">
                        <i class="ti ti-chevron-left"></i> Prev
                    </a>
                    <?php endif; ?>
                    <span style="color:var(--txt-muted);font-size:.88rem;">
                        Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                    </span>
                    <?php if ($page < $total_pages): ?>
                    <a href="<?php echo h(page_link($page + 1)); ?>" class="rmu-btn rmu-btn--secondary" style="padding:5px 12px;">
                        Next <i class="ti ti-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <?php endif; ?>

        </div>
    </div>
</div>
</body>
</html>
