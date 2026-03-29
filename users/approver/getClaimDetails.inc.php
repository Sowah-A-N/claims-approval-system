<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/queries/approval.queries.php';

require_role(['approver', 'Approver']);

$claimId = validated_int($_GET['claimId'] ?? null, 'claimId');

$claim = db_get_claim_details_for_approver($conn, $claimId);

if ($claim === null) {
    echo '<p>Claim not found.</p>';
    exit;
}

echo '<p><strong>Programme:</strong> ' . h($claim['programme']) . '</p>';
echo '<p><strong>Course:</strong> ' . h($claim['course']) . '</p>';

if (!empty($claim['rows'])) {
    echo '<table class="table">';
    echo '<thead class="thead-light"><tr>';
    echo '<th>Date</th><th>Start</th><th>End</th><th>Periods</th><th>Rate (GH₵)</th><th>Amount</th>';
    echo '</tr></thead><tbody>';

    foreach ($claim['rows'] as $row) {
        $amount = (float) $row['rate'] * (int) $row['periods'];
        echo '<tr>';
        echo '<td>' . h(date('d/m/Y', strtotime($row['date'])))  . '</td>';
        echo '<td>' . h($row['start_time'])                       . '</td>';
        echo '<td>' . h($row['end_time'])                         . '</td>';
        echo '<td>' . h($row['periods'])                          . '</td>';
        echo '<td>' . h($row['rate'])                             . '</td>';
        echo '<td>' . h(number_format($amount, 2))                . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
} else {
    echo '<p>No claim data rows found.</p>';
}
