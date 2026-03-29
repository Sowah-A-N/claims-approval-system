<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../queries/claim.queries.php';

require_role(array('user', 'claimant'));

$claim_id = validated_int(isset($_GET['claimId']) ? $_GET['claimId'] : null, 'claimId');
$user_id  = current_user_id();

// Ownership enforced in query — returns null if claimId belongs to another user.
$claim = db_get_claim_by_owner($conn, $claim_id, $user_id);

if ($claim === null) {
    echo '<p>Claim not found.</p>';
    exit;
}

echo '<p><strong>Programme:</strong> ' . h($claim['programme']) . '</p>';
echo '<p><strong>Course:</strong> '    . h($claim['course'])    . '</p>';
echo '<p><strong>Rate: GH&#8373;</strong> ' . h($claim['rate']) . '</p>';

$rows = db_get_claim_data_rows($conn, $claim_id);

if (!empty($rows)) {
    echo '<table class="table"><thead class="thead-light"><tr>';
    echo '<th>Date</th><th>Start</th><th>End</th><th>Periods</th><th>Sub Total</th>';
    echo '</tr></thead><tbody>';

    foreach ($rows as $row) {
        echo '<tr>';
        echo '<td>' . h(date('d-m-Y', strtotime($row['date'])))      . '</td>';
        echo '<td>' . h(date('g:iA',  strtotime($row['start_time']))) . '</td>';
        echo '<td>' . h(date('g:iA',  strtotime($row['end_time'])))   . '</td>';
        echo '<td>' . h($row['periods'])                               . '</td>';
        echo '<td>' . h($row['subTotal'])                              . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
} else {
    echo '<p>No claim data available.</p>';
}
