<?php
/*
 * Data-layer functions for claimant claim operations.
 *
 * Every function accepts $conn (procedural mysqli) and plain parameters.
 * No HTML, no $_POST, no session access lives here.
 * Callers pass already-validated input.
 */


// ── Course lookup ─────────────────────────────────────────────────────────────

/*
 * Return all non-archived courses for a department as an array.
 */
function db_get_courses_by_department($conn, $department) {
    $stmt = mysqli_prepare($conn, 'SELECT name FROM course WHERE department = ? AND archived = 0 ORDER BY name');
    mysqli_stmt_bind_param($stmt, 's', $department);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $courses = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $courses;
}


// ── Claim submission ──────────────────────────────────────────────────────────

/*
 * Insert a new claim_details row and return the generated claimId, or false on failure.
 */
function db_insert_claim($conn, $userId, $faculty, $department, $programme, $course, $rate) {
    $stmt = mysqli_prepare($conn,
        'INSERT INTO claim_details (userId, faculty, department, programme, course, rate)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 'issssd', $userId, $faculty, $department, $programme, $course, $rate);
    $ok = mysqli_stmt_execute($stmt);
    $id = $ok ? mysqli_insert_id($conn) : false;
    mysqli_stmt_close($stmt);
    return $id;
}

/*
 * Insert the initial claim_approval_stages row (stage 1, Pending).
 * Returns true on success, false on failure.
 */
function db_insert_initial_stage($conn, $claimId) {
    $stage = 1;
    $stmt  = mysqli_prepare($conn,
        "INSERT INTO claim_approval_stages (claimId, stage, status) VALUES (?, ?, 'Pending')"
    );
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 'ii', $claimId, $stage);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

/*
 * Insert a single claim_data row.
 * Returns true on success, false on failure.
 */
function db_insert_claim_data_row($conn, $claimId, $date, $start_time, $end_time, $periods, $sub_total, $fuel_component) {
    $stmt = mysqli_prepare($conn,
        'INSERT INTO claim_data (claimId, date, start_time, end_time, periods, subTotal, fuelComponent)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$stmt) return false;
    // i=claimId, s=date, s=start, s=end, i=periods, d=subTotal, i=fuelComponent
    mysqli_stmt_bind_param($stmt, 'isssidi', $claimId, $date, $start_time, $end_time, $periods, $sub_total, $fuel_component);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}


// ── Claim reads ───────────────────────────────────────────────────────────────

/*
 * Return a single claim_details row owned by $userId, or null.
 * Ownership check is in the WHERE clause — prevents IDOR.
 */
function db_get_claim_by_owner($conn, $claimId, $userId) {
    $stmt = mysqli_prepare($conn, 'SELECT * FROM claim_details WHERE claimId = ? AND userId = ?');
    if (!$stmt) return null;
    mysqli_stmt_bind_param($stmt, 'ii', $claimId, $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row    = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? $row : null;
}

/*
 * Return all claim_data rows for a claim, ordered by date.
 * Caller must have already verified ownership via db_get_claim_by_owner().
 */
function db_get_claim_data_rows($conn, $claimId) {
    $stmt = mysqli_prepare($conn, 'SELECT * FROM claim_data WHERE claimId = ? ORDER BY date');
    if (!$stmt) return array();
    mysqli_stmt_bind_param($stmt, 'i', $claimId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows   = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

/*
 * Return a saved (draft) claim owned by $userId, or null.
 */
function db_get_saved_claim_by_owner($conn, $claimId, $userId) {
    $stmt = mysqli_prepare($conn, 'SELECT * FROM saved_claims WHERE claimTempId = ? AND userId = ?');
    if (!$stmt) return null;
    mysqli_stmt_bind_param($stmt, 'ii', $claimId, $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row    = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? $row : null;
}

/*
 * Return all claims for a user with their latest approval stage and status.
 */
function db_get_user_claims($conn, $userId) {
    $stmt = mysqli_prepare($conn,
        'SELECT cd.*,
                cas.stage  AS current_stage,
                cas.status AS current_status
         FROM claim_details cd
         LEFT JOIN (
             SELECT claimId, MAX(stage) AS stage
             FROM claim_approval_stages
             GROUP BY claimId
         ) latest ON cd.claimId = latest.claimId
         LEFT JOIN claim_approval_stages cas
             ON cd.claimId = cas.claimId AND latest.stage = cas.stage
         WHERE cd.userId = ?
         ORDER BY cd.time_submitted DESC'
    );
    if (!$stmt) return array();
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows   = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

/*
 * Fetch all data needed to generate a downloadable claim Word document.
 * Enforces ownership via AND cd.userId = ?.
 */
function db_get_claim_download_data($conn, $claimId, $userId) {
    $stmt = mysqli_prepare($conn,
        'SELECT cd.claimId,
                ud.userId,
                ud.first_name,
                ud.last_name,
                ud.other_names,
                ud.phone_number,
                ud.department  AS user_department,
                ud.rank,
                ud.rate,
                cd.programme,
                cd.course,
                cdata.date     AS claim_date,
                cdata.start_time,
                cdata.end_time,
                cdata.periods,
                bd.bank_name,
                bd.bank_branch,
                bd.account_number,
                bd.account_name
         FROM claim_details cd
         JOIN user_details      ud    ON cd.userId  = ud.userId
         JOIN claim_data        cdata ON cd.claimId = cdata.claimId
         JOIN user_bank_details bd    ON ud.userId  = bd.userId
         WHERE cd.claimId = ? AND cd.userId = ?'
    );
    if (!$stmt) return array();
    mysqli_stmt_bind_param($stmt, 'ii', $claimId, $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows   = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}


// ── Claim deletion ────────────────────────────────────────────────────────────

/*
 * Delete a saved claim and its data rows.
 * Ownership is verified first — returns false if the claim does not belong to $userId.
 */
function db_delete_saved_claim($conn, $claimId, $userId) {
    // Verify ownership.
    if (db_get_saved_claim_by_owner($conn, $claimId, $userId) === null) {
        return false;
    }

    mysqli_begin_transaction($conn);

    $s1 = mysqli_prepare($conn, 'DELETE FROM saved_claims WHERE claimTempId = ? AND userId = ?');
    mysqli_stmt_bind_param($s1, 'ii', $claimId, $userId);
    $ok = mysqli_stmt_execute($s1);
    mysqli_stmt_close($s1);

    if ($ok) {
        $s2 = mysqli_prepare($conn, 'DELETE FROM claim_data WHERE claimId = ?');
        mysqli_stmt_bind_param($s2, 'i', $claimId);
        $ok = mysqli_stmt_execute($s2);
        mysqli_stmt_close($s2);
    }

    if ($ok) {
        mysqli_commit($conn);
    } else {
        mysqli_rollback($conn);
        error_log('[claim.queries] db_delete_saved_claim failed: ' . mysqli_error($conn));
    }

    return $ok;
}
