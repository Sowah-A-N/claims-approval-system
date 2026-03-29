<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../queries/claim.queries.php';

require_role(['user', 'claimant']);

$claimId = validated_int($_GET['claimId'] ?? null, 'claimId');
$userId  = current_user_id();

// Ownership enforced in query — returns null if claimId belongs to another user.
$claim = db_get_claim_by_owner($conn, $claimId, $userId);

if ($claim === null) {
    echo '<p>Claim not found.</p>';
    exit;
}

echo '<p><strong>Programme:</strong> ' . h($claim['programme']) . '</p>';
echo '<p><strong>Course:</strong> '    . h($claim['course'])    . '</p>';
echo '<p><strong>Rate: GH₵</strong> ' . h($claim['rate'])       . '</p>';

$rows = db_get_claim_data_rows($conn, $claimId);

if (!empty($rows)) {
    echo '<table class="table"><thead class="thead-light"><tr>';
    echo '<th>Date</th><th>Start</th><th>End</th><th>Periods</th><th>Sub Total</th>';
    echo '</tr></thead><tbody>';

    foreach ($rows as $row) {
        echo '<tr>';
        echo '<td>' . h(date('d-m-Y', strtotime($row['date'])))    . '</td>';
        echo '<td>' . h(date('g:iA', strtotime($row['start_time']))) . '</td>';
        echo '<td>' . h(date('g:iA', strtotime($row['end_time'])))   . '</td>';
        echo '<td>' . h($row['periods'])                             . '</td>';
        echo '<td>' . h($row['subTotal'])                            . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
} else {
    echo '<tr><td colspan="5">No claim data available.</td></tr>';
}
