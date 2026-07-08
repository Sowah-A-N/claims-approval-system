<?php
require_once __DIR__ . '/../../../../includes/functions.php';
$pageTitle = 'Paid Claims';
?>
<!DOCTYPE html>
<html lang="en">
<?php include '../../assets/partials/head.php'; ?>
<body>

<?php
require_once __DIR__ . '/../../queries/finance.queries.php';

// Read & sanitise filters from the query string.
$filters = array(
    'from_date'  => validated_str(isset($_GET['from_date'])  ? $_GET['from_date']  : '', 10),
    'to_date'    => validated_str(isset($_GET['to_date'])    ? $_GET['to_date']    : '', 10),
    'department' => validated_str(isset($_GET['department']) ? $_GET['department'] : ''),
    'search'     => validated_str(isset($_GET['search'])     ? $_GET['search']     : '', 100),
);
$has_filters = (bool) array_filter($filters, function ($v) { return $v !== ''; });

$rows        = db_get_paid_claims($conn, $filters);
$departments = db_get_paid_claim_departments($conn);

$grand = 0.0;
foreach ($rows as $r) { $grand += (float) $r['grand_total']; }

// Query string for the CSV link (only non-empty filters).
$csv_qs = http_build_query(array_filter($filters, function ($v) { return $v !== ''; }));
?>

<?php include '../../assets/partials/sidebar.php'; ?>

<div class="rmu-main">

  <?php include '../../assets/partials/header.php'; ?>

  <div class="rmu-content">

    <div class="rmu-page-header" style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
      <div>
        <div class="rmu-page-header__title">Paid Claims</div>
        <div class="rmu-page-header__sub">Processed payments — reporting &amp; audit trail</div>
      </div>
      <a class="rmu-btn rmu-btn--success" href="../../exportPaidClaims.inc.php<?php echo $csv_qs ? '?' . h($csv_qs) : ''; ?>">
        <i class="ti ti-file-spreadsheet"></i> Export CSV
      </a>
    </div>

    <!-- Filters -->
    <div class="rmu-card" style="margin-bottom:20px;">
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
          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;align-items:flex-end;">
            <div class="rmu-form-group" style="margin:0;">
              <label class="rmu-label">Paid From</label>
              <input type="date" name="from_date" class="rmu-input" value="<?php echo h($filters['from_date']); ?>">
            </div>
            <div class="rmu-form-group" style="margin:0;">
              <label class="rmu-label">Paid To</label>
              <input type="date" name="to_date" class="rmu-input" value="<?php echo h($filters['to_date']); ?>">
            </div>
            <div class="rmu-form-group" style="margin:0;">
              <label class="rmu-label">Department</label>
              <select name="department" class="rmu-select">
                <option value="">All Departments</option>
                <?php foreach ($departments as $d): ?>
                <option value="<?php echo h($d); ?>" <?php echo ($filters['department'] === $d) ? 'selected' : ''; ?>>
                  <?php echo h($d); ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="rmu-form-group" style="margin:0;">
              <label class="rmu-label">Search (name or reference)</label>
              <input type="text" name="search" class="rmu-input" placeholder="Claimant or payment ref"
                     value="<?php echo h($filters['search']); ?>">
            </div>
            <div>
              <button type="submit" class="rmu-btn rmu-btn--primary" style="width:100%;">
                <i class="ti ti-search"></i> Apply
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Summary -->
    <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:20px;">
      <div class="rmu-card" style="flex:1;min-width:200px;">
        <div class="rmu-card__body" style="padding:18px 22px;">
          <div style="font-size:.78rem;color:var(--txt-muted);">Claims</div>
          <div style="font-size:1.5rem;font-weight:700;color:var(--txt-primary);"><?php echo count($rows); ?></div>
        </div>
      </div>
      <div class="rmu-card" style="flex:1;min-width:200px;">
        <div class="rmu-card__body" style="padding:18px 22px;">
          <div style="font-size:.78rem;color:var(--txt-muted);">Total Paid</div>
          <div style="font-size:1.5rem;font-weight:700;color:var(--txt-primary);">GH₵ <?php echo number_format($grand, 2); ?></div>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="rmu-card">
      <div class="rmu-card__header">
        <span class="rmu-card__title"><i class="ti ti-circle-check rmu-text-success"></i> Processed Payments</span>
        <span class="rmu-badge rmu-badge--success"><?php echo count($rows); ?> record<?php echo count($rows) !== 1 ? 's' : ''; ?></span>
      </div>
      <div class="rmu-card__body" style="padding:0;">
        <div class="rmu-table-wrap">
          <table class="rmu-table">
            <thead>
              <tr>
                <th>Claim ID</th>
                <th>Claimant</th>
                <th>Department</th>
                <th>Course</th>
                <th>Amount (GH₵)</th>
                <th>Payment Ref</th>
                <th>Processed By</th>
                <th>Paid On</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($rows)): ?>
              <tr><td colspan="8" style="text-align:center;color:var(--txt-muted);padding:30px 20px;">
                <i class="ti ti-receipt-off" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.4;"></i>
                No paid claims match the current filters.
              </td></tr>
              <?php else: foreach ($rows as $r): ?>
              <tr>
                <td><?php echo (int) $r['claimId']; ?></td>
                <td><?php echo h($r['full_name']); ?></td>
                <td><?php echo h($r['department']); ?></td>
                <td><?php echo h($r['course']); ?></td>
                <td><?php echo number_format((float) $r['grand_total'], 2); ?></td>
                <td>
                  <?php if ($r['payment_ref'] === null || $r['payment_ref'] === ''): ?>
                    <span style="color:var(--txt-muted);">—</span>
                  <?php else: ?>
                    <span class="rmu-badge rmu-badge--neutral"><?php echo h($r['payment_ref']); ?></span>
                  <?php endif; ?>
                </td>
                <td><?php echo h(trim((string) $r['paid_by_name'])); ?></td>
                <td style="white-space:nowrap;"><?php echo $r['time_paid'] ? h(date('d/m/Y, H:i', strtotime($r['time_paid']))) : '—'; ?></td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div><!-- .rmu-content -->
</div><!-- .rmu-main -->

</body>
</html>
