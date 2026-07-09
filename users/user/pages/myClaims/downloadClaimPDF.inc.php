<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../../../includes/claim_form_pdf.php';
require_once __DIR__ . '/../../queries/claim.queries.php';

require_role(array('user', 'claimant'));

$claim_id = isset($_GET['claimId']) ? (int) $_GET['claimId'] : 0;
$user_id  = current_user_id();

if ($claim_id <= 0) {
    http_response_code(400);
    exit('Invalid claim ID.');
}

// Ownership enforced in the query — returns empty when the claim isn't the user's.
$rows = db_get_claim_download_data($conn, $claim_id, $user_id);

if (empty($rows)) {
    http_response_code(404);
    exit('Claim not found or access denied.');
}

render_rmu_claim_form($rows);
