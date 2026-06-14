<?php
/*
 * Data-layer functions for approver operations.
 *
 * No HTML, no $_POST, no session access lives here.
 * All stage-advancing and flagging operations use transactions.
 */


// ── Claim listing ─────────────────────────────────────────────────────────────

/*
 * Return pending claims at $stage, optionally scoped to $department.
 * Pass null or empty string for $department to return all departments.
 */
function db_get_pending_claims_for_stage($conn, $stage, $department) {
    $base =
        "SELECT cd.*, CONCAT(ud.first_name, ' ', ud.last_name) AS full_name
         FROM claim_details cd
         INNER JOIN (
             SELECT claimId, MAX(stage) AS max_stage
             FROM claim_approval_stages
             GROUP BY claimId
         ) ms ON cd.claimId = ms.claimId
         INNER JOIN claim_approval_stages cas
             ON cd.claimId = cas.claimId AND ms.max_stage = cas.stage
         INNER JOIN user_details ud ON cd.userId = ud.userId
         WHERE cas.stage = ?
           AND cas.status = 'Pending'
           AND cd.flagged = 0
           AND cd.completed = 0";

    if ($department !== null && $department !== '') {
        $stmt = mysqli_prepare($conn, $base . ' AND cd.department = ? ORDER BY cd.time_submitted ASC');
        if (!$stmt) return array();
        mysqli_stmt_bind_param($stmt, 'is', $stage, $department);
    } else {
        $stmt = mysqli_prepare($conn, $base . ' ORDER BY cd.time_submitted ASC');
        if (!$stmt) return array();
        mysqli_stmt_bind_param($stmt, 'i', $stage);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $claims = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $claims;
}


// ── Claim detail ──────────────────────────────────────────────────────────────

/*
 * Fetch claim_details + all claim_data rows for the approver view modal.
 * Returns null if the claim does not exist.
 */
function db_get_claim_details_for_approver($conn, $claimId) {
    $stmt = mysqli_prepare($conn, 'SELECT * FROM claim_details WHERE claimId = ?');
    if (!$stmt) return null;
    mysqli_stmt_bind_param($stmt, 'i', $claimId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $claim  = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$claim) return null;

    $stmt2 = mysqli_prepare($conn,
        'SELECT cdata.*, cd.rate
         FROM claim_data cdata
         JOIN claim_details cd ON cd.claimId = cdata.claimId
         WHERE cdata.claimId = ?
         ORDER BY cdata.date'
    );
    if (!$stmt2) {
        $claim['rows'] = array();
        return $claim;
    }
    mysqli_stmt_bind_param($stmt2, 'i', $claimId);
    mysqli_stmt_execute($stmt2);
    $result2       = mysqli_stmt_get_result($stmt2);
    $claim['rows'] = mysqli_fetch_all($result2, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt2);

    return $claim;
}

/*
 * Return the current (latest) stage number for a claim, or null if not found.
 */
function db_get_current_stage($conn, $claimId) {
    $stmt = mysqli_prepare($conn,
        "SELECT stage FROM claim_approval_stages WHERE claimId = ? AND status = 'Pending' ORDER BY stageId DESC LIMIT 1"
    );
    if (!$stmt) return null;
    mysqli_stmt_bind_param($stmt, 'i', $claimId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row    = mysqli_fetch_row($result);
    mysqli_stmt_close($stmt);
    return $row ? (int) $row[0] : null;
}

/*
 * Return the configured maximum approval stage.
 * Reads from the settings table (settingName = 'max_approval_stages').
 * Falls back to 5 if not configured.
 */
function db_get_max_approval_stage($conn) {
    $stmt = mysqli_prepare($conn,
        "SELECT settingValue FROM settings WHERE settingName = 'max_approval_stages' LIMIT 1");
    if ($stmt) {
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_row(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if ($row && (int) $row[0] > 0) return (int) $row[0];
    }
    return 5;
}


// ── Approve ───────────────────────────────────────────────────────────────────

/*
 * Approve the current stage and either advance to the next stage or complete
 * the claim, depending on whether $expected_stage is the final stage.
 *
 * Sets $completed = true when the claim reaches completion.
 * Returns true on success, false with $error set on failure.
 */
function db_advance_claim_stage($conn, $claimId, $expected_stage, &$error, &$completed = false) {
    $max_stage = db_get_max_approval_stage($conn);
    $completed = false;

    mysqli_begin_transaction($conn);

    // Verify the claim is genuinely at the expected pending stage.
    $check = mysqli_prepare($conn,
        "SELECT stageId FROM claim_approval_stages
         WHERE claimId = ? AND stage = ? AND status = 'Pending'
         LIMIT 1"
    );
    mysqli_stmt_bind_param($check, 'ii', $claimId, $expected_stage);
    mysqli_stmt_execute($check);
    $found = mysqli_num_rows(mysqli_stmt_get_result($check)) > 0;
    mysqli_stmt_close($check);

    if (!$found) {
        mysqli_rollback($conn);
        $error = 'Claim is not at the expected pending stage.';
        return false;
    }

    // Mark current stage Approved.
    $approve = mysqli_prepare($conn,
        "UPDATE claim_approval_stages
         SET status = 'Approved', time_approved = NOW()
         WHERE claimId = ? AND stage = ? AND status = 'Pending'"
    );
    mysqli_stmt_bind_param($approve, 'ii', $claimId, $expected_stage);
    mysqli_stmt_execute($approve);
    $affected = mysqli_stmt_affected_rows($approve);
    mysqli_stmt_close($approve);

    if ($affected <= 0) {
        mysqli_rollback($conn);
        $error = 'Failed to approve stage.';
        return false;
    }

    if ($expected_stage >= $max_stage) {
        // ── Final stage: mark claim complete ─────────────────────────────────
        $upd = mysqli_prepare($conn,
            'UPDATE claim_details SET completed = 1, time_completed = NOW() WHERE claimId = ?');
        mysqli_stmt_bind_param($upd, 'i', $claimId);
        mysqli_stmt_execute($upd);
        $done = mysqli_stmt_affected_rows($upd);
        mysqli_stmt_close($upd);

        if ($done <= 0) {
            mysqli_rollback($conn);
            $error = 'Failed to mark claim as complete.';
            return false;
        }

        // Record in completed_claims (SELECT-INSERT avoids re-fetching the row).
        $ins = mysqli_prepare($conn,
            'INSERT INTO completed_claims (claimId, userId, department, programme, course)
             SELECT claimId, userId, department, programme, course
             FROM claim_details WHERE claimId = ?'
        );
        mysqli_stmt_bind_param($ins, 'i', $claimId);
        mysqli_stmt_execute($ins);
        mysqli_stmt_close($ins);

        $completed = true;

    } else {
        // ── Intermediate stage: advance to next pending stage ─────────────────
        $next_stage = $expected_stage + 1;
        $insert     = mysqli_prepare($conn,
            "INSERT INTO claim_approval_stages (claimId, stage, status, time_updated)
             VALUES (?, ?, 'Pending', NOW())"
        );
        mysqli_stmt_bind_param($insert, 'ii', $claimId, $next_stage);
        mysqli_stmt_execute($insert);
        $inserted = mysqli_stmt_affected_rows($insert);
        mysqli_stmt_close($insert);

        if ($inserted <= 0) {
            mysqli_rollback($conn);
            $error = 'Failed to insert next stage.';
            return false;
        }
    }

    mysqli_commit($conn);
    return true;
}


// ── Flag ──────────────────────────────────────────────────────────────────────

/*
 * Flag a claim: set flagged=1, insert a Flagged approval row, record in flagged_claims.
 * All three writes are atomic.
 *
 * Returns true on success, false with $error set on failure.
 */
function db_flag_claim($conn, $claimId, $stage, $reason, &$error) {
    mysqli_begin_transaction($conn);

    $s1 = mysqli_prepare($conn,
        'UPDATE claim_details SET flagged = 1 WHERE claimId = ? AND flagged = 0'
    );
    mysqli_stmt_bind_param($s1, 'i', $claimId);
    mysqli_stmt_execute($s1);
    $affected = mysqli_stmt_affected_rows($s1);
    mysqli_stmt_close($s1);

    if ($affected <= 0) {
        mysqli_rollback($conn);
        $error = 'Claim not found or was already flagged.';
        return false;
    }

    $s2 = mysqli_prepare($conn,
        "INSERT INTO claim_approval_stages (claimId, stage, status, time_rejected)
         VALUES (?, ?, 'Flagged', NOW())"
    );
    mysqli_stmt_bind_param($s2, 'ii', $claimId, $stage);
    mysqli_stmt_execute($s2);
    mysqli_stmt_close($s2);

    $s3 = mysqli_prepare($conn,
        'INSERT INTO flagged_claims (claimId, flagged_at_stage, flagged_msg, date_flagged)
         VALUES (?, ?, ?, NOW())'
    );
    mysqli_stmt_bind_param($s3, 'iis', $claimId, $stage, $reason);
    mysqli_stmt_execute($s3);
    mysqli_stmt_close($s3);

    mysqli_commit($conn);
    return true;
}
