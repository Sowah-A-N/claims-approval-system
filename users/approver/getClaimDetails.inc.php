<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/queries/approval.queries.php';

require_role(array('approver', 'Approver'));

$claim_id = validated_int(isset($_GET['claimId']) ? $_GET['claimId'] : null, 'claimId');

$claim = db_get_claim_details_for_approver($conn, $claim_id);

if ($claim === null) {
    echo '<p style="color:var(--txt-muted);text-align:center;padding:20px;">Claim not found.</p>';
    exit;
}
?>
<div style="padding:4px 0;">

    <!-- Claim meta -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px 28px;margin-bottom:20px;">
        <div>
            <div style="font-size:.72rem;color:var(--txt-muted);text-transform:uppercase;
                        letter-spacing:.06em;margin-bottom:4px;">Department</div>
            <div style="font-weight:500;color:var(--txt-primary);">
                <?php echo $claim['department'] ? h($claim['department']) : '<span style="color:var(--txt-muted);">—</span>'; ?>
            </div>
        </div>
        <div>
            <div style="font-size:.72rem;color:var(--txt-muted);text-transform:uppercase;
                        letter-spacing:.06em;margin-bottom:4px;">Programme</div>
            <div style="font-weight:500;color:var(--txt-primary);">
                <?php echo $claim['programme'] ? h($claim['programme']) : '<span style="color:var(--txt-muted);">—</span>'; ?>
            </div>
        </div>
        <div>
            <div style="font-size:.72rem;color:var(--txt-muted);text-transform:uppercase;
                        letter-spacing:.06em;margin-bottom:4px;">Course</div>
            <div style="font-weight:500;color:var(--txt-primary);">
                <?php echo $claim['course'] ? h($claim['course']) : '<span style="color:var(--txt-muted);">—</span>'; ?>
            </div>
        </div>
        <div>
            <div style="font-size:.72rem;color:var(--txt-muted);text-transform:uppercase;
                        letter-spacing:.06em;margin-bottom:4px;">Class</div>
            <div style="font-weight:500;color:var(--txt-primary);">
                <?php echo !empty($claim['class']) ? h($claim['class']) : '<span style="color:var(--txt-muted);">—</span>'; ?>
            </div>
        </div>
        <div>
            <div style="font-size:.72rem;color:var(--txt-muted);text-transform:uppercase;
                        letter-spacing:.06em;margin-bottom:4px;">Rate per Period</div>
            <div style="font-weight:500;color:var(--txt-primary);">
                GH&#8373; <?php echo h(number_format((float)$claim['rate'], 2)); ?>
            </div>
        </div>
    </div>

    <?php if (!empty($claim['rows'])):
        $grandTotal = 0;
        $showFuel   = false;
        foreach ($claim['rows'] as $r) {
            $grandTotal += (float)$r['rate'] * (int)$r['periods'];
            if (!empty($r['fuelComponent']) && (int)$r['fuelComponent']) $showFuel = true;
        }
    ?>
    <div class="rmu-table-wrap" style="border-radius:8px;overflow:hidden;margin-bottom:0;">
        <table class="rmu-table" style="margin:0;">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Periods</th>
                    <th>Rate (GH&#8373;)</th>
                    <th>Amount (GH&#8373;)</th>
                    <?php if ($showFuel): ?><th>Fuel</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($claim['rows'] as $r):
                    $amount = (float)$r['rate'] * (int)$r['periods'];
                ?>
                <tr>
                    <td style="white-space:nowrap;"><?php echo h(date('D d/m/Y', strtotime($r['date']))); ?></td>
                    <td><?php echo h(date('g:iA', strtotime($r['start_time']))); ?></td>
                    <td><?php echo h(date('g:iA', strtotime($r['end_time']))); ?></td>
                    <td><?php echo h($r['periods']); ?></td>
                    <td><?php echo h(number_format((float)$r['rate'], 2)); ?></td>
                    <td><strong><?php echo h(number_format($amount, 2)); ?></strong></td>
                    <?php if ($showFuel): ?>
                    <td>
                        <?php if (!empty($r['fuelComponent']) && (int)$r['fuelComponent']): ?>
                            <span class="rmu-badge rmu-badge--primary">Yes</span>
                        <?php else: ?>
                            <span style="color:var(--txt-muted);">—</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div style="display:flex;justify-content:flex-end;align-items:center;gap:14px;
                padding:14px 0 4px;border-top:1px solid var(--divider);margin-top:0;">
        <span style="color:var(--txt-secondary);font-size:.9rem;font-weight:500;">Grand Total</span>
        <span style="font-size:1.2rem;font-weight:700;color:var(--txt-primary);">
            GH&#8373; <?php echo h(number_format($grandTotal, 2)); ?>
        </span>
    </div>
    <?php else: ?>
    <p style="color:var(--txt-muted);text-align:center;padding:20px 0;">No session data found for this claim.</p>
    <?php endif; ?>

</div>
