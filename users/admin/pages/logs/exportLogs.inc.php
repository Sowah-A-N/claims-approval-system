<?php
/*
 * Export the audit log as CSV or a print-ready PDF page, honouring the same
 * filters as the Logs viewer (action, actor_role, entity_type, date range).
 *
 *   exportLogs.inc.php?format=csv|pdf&action=...&actor_role=...&...
 */
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';

require_role(array('admin', 'Admin'));

$format = (isset($_GET['format']) && strtolower($_GET['format']) === 'pdf') ? 'pdf' : 'csv';

$f_action      = validated_str(isset($_GET['action'])      ? $_GET['action']      : '');
$f_actor_role  = validated_str(isset($_GET['actor_role'])  ? $_GET['actor_role']  : '');
$f_entity_type = validated_str(isset($_GET['entity_type']) ? $_GET['entity_type'] : '');
$f_start_date  = validated_str(isset($_GET['start_date'])  ? $_GET['start_date']  : '');
$f_end_date    = validated_str(isset($_GET['end_date'])    ? $_GET['end_date']    : '');

$where  = ' WHERE 1=1';
$types  = '';
$params = array();
if ($f_action !== '')      { $where .= ' AND al.action = ?';      $types .= 's'; $params[] = $f_action; }
if ($f_actor_role !== '')  { $where .= ' AND al.actor_role = ?';  $types .= 's'; $params[] = $f_actor_role; }
if ($f_entity_type !== '') { $where .= ' AND al.entity_type = ?'; $types .= 's'; $params[] = $f_entity_type; }
if ($f_start_date !== '')  { $where .= ' AND DATE(al.created_at) >= ?'; $types .= 's'; $params[] = $f_start_date; }
if ($f_end_date !== '')    { $where .= ' AND DATE(al.created_at) <= ?'; $types .= 's'; $params[] = $f_end_date; }

$sql = "SELECT al.audit_id, al.created_at, al.actor_role, al.action, al.entity_type,
               al.entity_id, al.detail, al.ip_address,
               CONCAT(COALESCE(ud.first_name,''), ' ', COALESCE(ud.last_name,'')) AS actor_name, al.actor_id
        FROM audit_log al
        LEFT JOIN user_details ud ON al.actor_id = ud.userId"
       . $where . " ORDER BY al.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) { http_response_code(500); exit('Could not generate export.'); }
if ($types !== '') mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

function log_actor($r) {
    $a = trim((string) $r['actor_name']);
    if ($a !== '') return $a;
    return $r['actor_id'] ? '#' . $r['actor_id'] : 'System';
}
function log_detail($r) {
    if ($r['detail'] === null || $r['detail'] === '') return '';
    $d = json_decode($r['detail'], true);
    return is_scalar($d) ? (string) $d : (string) $r['detail'];
}

log_audit($conn, 'logs.export', null, null, strtoupper($format) . ', ' . count($rows) . ' row(s)');

if ($format === 'csv') {
    $filename = 'audit_log_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array('ID', 'Timestamp', 'Actor', 'Role', 'Action', 'Entity', 'Entity ID', 'Detail', 'IP'), ',', '"', '');
    foreach ($rows as $r) {
        fputcsv($out, array_map('csv_safe', array(
            $r['audit_id'],
            date('Y-m-d H:i:s', strtotime($r['created_at'])),
            log_actor($r),
            $r['actor_role'],
            audit_action_label($r['action']),
            $r['entity_type'],
            $r['entity_id'],
            log_detail($r),
            $r['ip_address'],
        )), ',', '"', '');
    }
    fclose($out);
    exit;
}

// ── PDF (print-ready HTML) ───────────────────────────────────────────────────
?><!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8">
<title>Audit Log — <?php echo date('d M Y'); ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; color: #111; padding: 24px 28px; }
h1 { font-size: 15pt; margin-bottom: 2px; }
.sub { color: #555; font-size: 9pt; margin-bottom: 14px; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #bbb; padding: 4px 7px; text-align: left; vertical-align: top; }
th { background: #1d4ed8; color: #fff; font-size: 8.5pt; text-transform: uppercase; letter-spacing: .03em; }
td { font-size: 8.5pt; }
tr:nth-child(even) td { background: #f3f6fc; }
.no-print { text-align: right; margin-bottom: 14px; }
.btn { padding: 8px 16px; background: #1d4ed8; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-size: 10pt; }
@media print { .no-print { display: none; } body { padding: 0; } @page { size: landscape; margin: 12mm; } }
</style></head>
<body>
<div class="no-print"><button class="btn" onclick="window.print()">&#128438; Print / Save as PDF</button></div>
<h1>RMU Claims — Audit Log</h1>
<div class="sub">Generated <?php echo date('d M Y, H:i'); ?> &middot; <?php echo count($rows); ?> entr<?php echo count($rows) !== 1 ? 'ies' : 'y'; ?></div>
<table>
  <thead><tr>
    <th>ID</th><th>Timestamp</th><th>Actor</th><th>Role</th><th>Action</th>
    <th>Entity</th><th>Entity&nbsp;ID</th><th>Detail</th><th>IP</th>
  </tr></thead>
  <tbody>
  <?php if (empty($rows)): ?>
    <tr><td colspan="9" style="text-align:center;padding:18px;color:#777;">No log entries for these filters.</td></tr>
  <?php else: foreach ($rows as $r): ?>
    <tr>
      <td><?php echo (int) $r['audit_id']; ?></td>
      <td style="white-space:nowrap;"><?php echo h(date('d M Y H:i:s', strtotime($r['created_at']))); ?></td>
      <td><?php echo h(log_actor($r)); ?></td>
      <td><?php echo h($r['actor_role']); ?></td>
      <td><?php echo h(audit_action_label($r['action'])); ?></td>
      <td><?php echo h($r['entity_type']); ?></td>
      <td><?php echo $r['entity_id'] ? (int) $r['entity_id'] : ''; ?></td>
      <td><?php echo h(log_detail($r)); ?></td>
      <td><?php echo h($r['ip_address']); ?></td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table>
<script>window.onload = function () { window.print(); };</script>
</body></html>
