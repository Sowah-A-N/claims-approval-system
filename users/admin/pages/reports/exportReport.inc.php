<?php
/*
 * Export the filtered claims report as CSV or a print-ready PDF page, honouring
 * the same filters/sort as the Reports viewer.
 *
 *   exportReport.inc.php?format=csv|pdf&department=...&status=...&sort=...&order=...
 */
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';

require_role(array('admin', 'Admin'));

$format = (isset($_GET['format']) && strtolower($_GET['format']) === 'pdf') ? 'pdf' : 'csv';

$f_dept       = validated_str(isset($_GET['department']) ? $_GET['department'] : '');
$f_programme  = validated_str(isset($_GET['programme'])  ? $_GET['programme']  : '');
$f_course     = validated_str(isset($_GET['course'])     ? $_GET['course']     : '');
$f_status     = validated_str(isset($_GET['status'])     ? $_GET['status']     : '');
$f_start_date = validated_str(isset($_GET['start_date']) ? $_GET['start_date'] : '');
$f_end_date   = validated_str(isset($_GET['end_date'])   ? $_GET['end_date']   : '');
$f_sort       = validated_str(isset($_GET['sort'])       ? $_GET['sort']       : 'claimId');
$f_order      = (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') ? 'ASC' : 'DESC';
if (!in_array($f_sort, array('claimId', 'full_name', 'department', 'time_submitted'), true)) $f_sort = 'claimId';

$base = "SELECT cd.claimId,
                CONCAT(ud.first_name, ' ', ud.last_name) AS full_name,
                cd.department, cd.programme, cd.course, cd.flagged, cd.completed,
                cd.time_submitted, cas.stage AS current_stage, cas.status AS current_status
         FROM claim_details cd
         INNER JOIN user_details ud ON cd.userId = ud.userId
         LEFT JOIN (SELECT claimId, MAX(stage) AS max_stage FROM claim_approval_stages GROUP BY claimId) ms
             ON cd.claimId = ms.claimId
         LEFT JOIN claim_approval_stages cas ON cd.claimId = cas.claimId AND ms.max_stage = cas.stage
         WHERE 1=1";
$types = ''; $params = array();
if ($f_dept !== '')      { $base .= ' AND cd.department = ?'; $types .= 's'; $params[] = $f_dept; }
if ($f_programme !== '') { $base .= ' AND cd.programme = ?';  $types .= 's'; $params[] = $f_programme; }
if ($f_course !== '')    { $base .= ' AND cd.course = ?';     $types .= 's'; $params[] = $f_course; }
if ($f_status === 'Pending')   $base .= ' AND cd.completed = 0 AND cd.flagged = 0';
elseif ($f_status === 'Flagged')   $base .= ' AND cd.flagged = 1';
elseif ($f_status === 'Completed') $base .= ' AND cd.completed = 1';
if ($f_start_date !== '') { $base .= ' AND DATE(cd.time_submitted) >= ?'; $types .= 's'; $params[] = $f_start_date; }
if ($f_end_date !== '')   { $base .= ' AND DATE(cd.time_submitted) <= ?'; $types .= 's'; $params[] = $f_end_date; }
$base .= " ORDER BY `{$f_sort}` {$f_order}";

$stmt = mysqli_prepare($conn, $base);
if (!$stmt) { http_response_code(500); exit('Could not generate export.'); }
if ($types !== '') mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

function rpt_status($r) {
    if ($r['completed']) return 'Completed';
    if ($r['flagged'])   return 'Flagged';
    return $r['current_status'] ? $r['current_status'] : 'Pending';
}

log_audit($conn, 'report.export', null, null, strtoupper($format) . ', ' . count($rows) . ' row(s)');

if ($format === 'csv') {
    $filename = 'claims_report_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array('Claimant', 'Department', 'Programme', 'Course', 'Date Submitted', 'Stage', 'Status'), ',', '"', '');
    foreach ($rows as $r) {
        fputcsv($out, array_map('csv_safe', array(
            $r['full_name'],
            $r['department'],
            $r['programme'],
            $r['course'],
            $r['time_submitted'] ? date('Y-m-d', strtotime($r['time_submitted'])) : '',
            $r['current_stage'] !== null ? (int) $r['current_stage'] : '',
            rpt_status($r),
        )), ',', '"', '');
    }
    fclose($out);
    exit;
}

// ── PDF (print-ready HTML) ───────────────────────────────────────────────────
?><!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8">
<title>Claims Report — <?php echo date('d M Y'); ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; color: #111; padding: 24px 28px; }
h1 { font-size: 15pt; margin-bottom: 2px; }
.sub { color: #555; font-size: 9pt; margin-bottom: 14px; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #bbb; padding: 5px 8px; text-align: left; }
th { background: #1d4ed8; color: #fff; font-size: 8.5pt; text-transform: uppercase; letter-spacing: .03em; }
td { font-size: 9pt; }
tr:nth-child(even) td { background: #f3f6fc; }
.no-print { text-align: right; margin-bottom: 14px; }
.btn { padding: 8px 16px; background: #1d4ed8; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-size: 10pt; }
@media print { .no-print { display: none; } body { padding: 0; } @page { margin: 14mm; } }
</style></head>
<body>
<div class="no-print"><button class="btn" onclick="window.print()">&#128438; Print / Save as PDF</button></div>
<h1>RMU Claims — Claims Report</h1>
<div class="sub">Generated <?php echo date('d M Y, H:i'); ?> &middot; <?php echo count($rows); ?> record<?php echo count($rows) !== 1 ? 's' : ''; ?></div>
<table>
  <thead><tr>
    <th>Claimant</th><th>Department</th><th>Programme</th><th>Course</th>
    <th>Submitted</th><th>Stage</th><th>Status</th>
  </tr></thead>
  <tbody>
  <?php if (empty($rows)): ?>
    <tr><td colspan="7" style="text-align:center;padding:18px;color:#777;">No claims match the selected filters.</td></tr>
  <?php else: foreach ($rows as $r): ?>
    <tr>
      <td><?php echo h($r['full_name']); ?></td>
      <td><?php echo h($r['department']); ?></td>
      <td><?php echo h($r['programme']); ?></td>
      <td><?php echo h($r['course']); ?></td>
      <td style="white-space:nowrap;"><?php echo h(date('d M Y', strtotime($r['time_submitted']))); ?></td>
      <td><?php echo $r['current_stage'] !== null ? (int) $r['current_stage'] : '—'; ?></td>
      <td><?php echo h(rpt_status($r)); ?></td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table>
<script>window.onload = function () { window.print(); };</script>
</body></html>
