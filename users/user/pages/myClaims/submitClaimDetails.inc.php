<?php
/*
 * Submit a saved draft directly from My Claims.
 *
 * Reads the draft from saved_claims + claim_data, inserts proper rows into
 * claim_details / claim_approval_stages / claim_data, then deletes the draft —
 * all inside one transaction so the operation is atomic.
 *
 * Expected POST: claimId (int), csrf_token (string)
 * Returns JSON: { ok: bool, message: string }
 */

require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../queries/claim.queries.php';

require_post();
require_role(['user', 'claimant']);
csrf_verify();

$claimTempId = validated_int(isset($_POST['claimId']) ? $_POST['claimId'] : null, 'claimId');
$userId      = current_user_id();
$faculty     = isset($_SESSION['faculty']) ? (string) $_SESSION['faculty'] : '';

// Authoritative rate from the database — never trust client- or session-held
// copies. Mirrors the server-side rate fetch in multiClaimsSubmit (#23/#24).
$rate_stmt = mysqli_prepare($conn, 'SELECT rate FROM user_details WHERE userId = ?');
if (!$rate_stmt) {
    json_response(['ok' => false, 'message' => 'Database error.'], 500);
}
mysqli_stmt_bind_param($rate_stmt, 'i', $userId);
mysqli_stmt_execute($rate_stmt);
$rate_row = mysqli_fetch_assoc(mysqli_stmt_get_result($rate_stmt));
mysqli_stmt_close($rate_stmt);
$rate = isset($rate_row['rate']) ? (float) $rate_row['rate'] : 0.0;

// ── 1. Verify ownership ───────────────────────────────────────────────────────

$chk = mysqli_prepare($conn,
    'SELECT claimTempId, department, programme, course, class
     FROM saved_claims
     WHERE claimTempId = ? AND userId = ?'
);
if (!$chk) {
    json_response(['ok' => false, 'message' => 'Database error.'], 500);
}
mysqli_stmt_bind_param($chk, 'ii', $claimTempId, $userId);
mysqli_stmt_execute($chk);
$draft = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));
mysqli_stmt_close($chk);

if (!$draft) {
    json_response(['ok' => false, 'message' => 'Draft not found or permission denied.'], 404);
}

// ── 2. Load teaching-session rows ─────────────────────────────────────────────

$data_stmt = mysqli_prepare($conn,
    'SELECT date, start_time, end_time, periods, subTotal, fuelComponent
     FROM claim_data
     WHERE claimId = ?
     ORDER BY date'
);
if (!$data_stmt) {
    json_response(['ok' => false, 'message' => 'Database error.'], 500);
}
mysqli_stmt_bind_param($data_stmt, 'i', $claimTempId);
mysqli_stmt_execute($data_stmt);
$rows = mysqli_fetch_all(mysqli_stmt_get_result($data_stmt), MYSQLI_ASSOC);
mysqli_stmt_close($data_stmt);

if (empty($rows)) {
    json_response([
        'ok'      => false,
        'message' => 'This draft has no teaching sessions. Please edit it and add sessions before submitting.',
    ], 400);
}

// ── 3. Promote draft → submitted claim (atomic) ───────────────────────────────

mysqli_begin_transaction($conn);

// 3a. Insert the claim_details header row.
$ins_claim = mysqli_prepare($conn,
    'INSERT INTO claim_details (userId, faculty, department, programme, course, rate, class)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
);
if (!$ins_claim) {
    mysqli_rollback($conn);
    json_response(['ok' => false, 'message' => 'Submission failed. Please try again.'], 500);
}
$draft_class = isset($draft['class']) ? $draft['class'] : null;
mysqli_stmt_bind_param($ins_claim, 'issssds',
    $userId, $faculty,
    $draft['department'], $draft['programme'], $draft['course'],
    $rate, $draft_class
);
if (!mysqli_stmt_execute($ins_claim)) {
    mysqli_stmt_close($ins_claim);
    mysqli_rollback($conn);
    error_log('[submitClaimDetails] insert claim_details failed: ' . mysqli_error($conn));
    json_response(['ok' => false, 'message' => 'Submission failed. Please try again.'], 500);
}
$newClaimId = (int) mysqli_insert_id($conn);
mysqli_stmt_close($ins_claim);

// 3b. Create the first approval stage (Stage 1 — Pending).
$ins_stage = mysqli_prepare($conn,
    "INSERT INTO claim_approval_stages (claimId, stage, status) VALUES (?, 1, 'Pending')"
);
if (!$ins_stage) {
    mysqli_rollback($conn);
    json_response(['ok' => false, 'message' => 'Submission failed. Please try again.'], 500);
}
mysqli_stmt_bind_param($ins_stage, 'i', $newClaimId);
if (!mysqli_stmt_execute($ins_stage)) {
    mysqli_stmt_close($ins_stage);
    mysqli_rollback($conn);
    error_log('[submitClaimDetails] insert approval stage failed: ' . mysqli_error($conn));
    json_response(['ok' => false, 'message' => 'Submission failed. Please try again.'], 500);
}
mysqli_stmt_close($ins_stage);

// 3c. Copy each teaching-session row to the new claim.
$ins_data = mysqli_prepare($conn,
    'INSERT INTO claim_data (claimId, date, start_time, end_time, periods, subTotal, fuelComponent)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
);
if (!$ins_data) {
    mysqli_rollback($conn);
    json_response(['ok' => false, 'message' => 'Submission failed. Please try again.'], 500);
}
foreach ($rows as $dr) {
    // Recompute periods (1 period = 50 min) and subTotal server-side from the
    // stored session times and the authoritative DB rate. The draft's stored
    // periods/subTotal came from client input via saveDraft and must never be
    // trusted when promoting a draft to a real claim (#2).
    $start_ts = strtotime($dr['start_time']);
    $end_ts   = strtotime($dr['end_time']);
    if ($start_ts === false || $end_ts === false) {
        mysqli_stmt_close($ins_data);
        mysqli_rollback($conn);
        json_response(['ok' => false, 'message' => 'This draft contains an invalid session time. Please edit it and try again.'], 400);
    }
    $start_mins = (int) date('G', $start_ts) * 60 + (int) date('i', $start_ts);
    $end_mins   = (int) date('G', $end_ts)   * 60 + (int) date('i', $end_ts);
    $periods    = $end_mins > $start_mins ? (int) ceil(($end_mins - $start_mins) / 50) : 0;
    if ($periods === 0) {
        mysqli_stmt_close($ins_data);
        mysqli_rollback($conn);
        json_response(['ok' => false, 'message' => 'This draft has a session with no valid duration. Please edit it and try again.'], 400);
    }
    $sub_total = (float) $periods * $rate;

    // Reject sessions that overlap one the claimant has already submitted (#9).
    if (db_has_overlapping_session($conn, $userId, $dr['date'], $dr['start_time'], $dr['end_time'], $newClaimId)) {
        mysqli_stmt_close($ins_data);
        mysqli_rollback($conn);
        json_response(['ok' => false,
            'message' => 'A session on ' . $dr['date'] . ' overlaps a claim you have already submitted. Please edit the draft.'], 409);
    }

    mysqli_stmt_bind_param($ins_data, 'isssidi',
        $newClaimId,
        $dr['date'], $dr['start_time'], $dr['end_time'],
        $periods, $sub_total, $dr['fuelComponent']
    );
    if (!mysqli_stmt_execute($ins_data)) {
        mysqli_stmt_close($ins_data);
        mysqli_rollback($conn);
        error_log('[submitClaimDetails] insert claim_data row failed: ' . mysqli_error($conn));
        json_response(['ok' => false, 'message' => 'Submission failed. Please try again.'], 500);
    }
}
mysqli_stmt_close($ins_data);

// 3d. Delete the draft's claim_data rows.
$del_data = mysqli_prepare($conn, 'DELETE FROM claim_data WHERE claimId = ?');
if ($del_data) {
    mysqli_stmt_bind_param($del_data, 'i', $claimTempId);
    mysqli_stmt_execute($del_data);
    mysqli_stmt_close($del_data);
}

// 3e. Delete the saved_claims row (ownership re-checked in WHERE clause).
$del_draft = mysqli_prepare($conn,
    'DELETE FROM saved_claims WHERE claimTempId = ? AND userId = ?'
);
if ($del_draft) {
    mysqli_stmt_bind_param($del_draft, 'ii', $claimTempId, $userId);
    mysqli_stmt_execute($del_draft);
    mysqli_stmt_close($del_draft);
}

mysqli_commit($conn);

if (!empty($draft_class)) db_upsert_class($conn, $draft_class);

log_audit($conn, 'claim.submit', 'claim', $newClaimId, 'from draft #' . $claimTempId);

json_response(['ok' => true, 'message' => 'Claim submitted successfully and sent for approval.']);
