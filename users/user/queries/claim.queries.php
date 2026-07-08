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
    // DISTINCT guards against duplicate course names within a department.
    // TRIM both sides so a trailing space in department.dept_name vs
    // course.department (a real data discrepancy) doesn't hide every course.
    $stmt = mysqli_prepare($conn,
        'SELECT DISTINCT name FROM course
         WHERE TRIM(department) = TRIM(?) AND (archived = 0 OR archived IS NULL)
         ORDER BY name');
    mysqli_stmt_bind_param($stmt, 's', $department);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $courses = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $courses;
}

/*
 * Return non-archived programmes for a department (matched by department name
 * via programme.fk_department -> department.deptId). DISTINCT by name.
 */
function db_get_programmes_by_department($conn, $department) {
    $stmt = mysqli_prepare($conn,
        'SELECT DISTINCT p.name
         FROM programme p
         JOIN department d ON p.fk_department = d.deptId
         WHERE d.dept_name = ? AND (p.archived = 0 OR p.archived IS NULL)
         ORDER BY p.name');
    if (!$stmt) return array();
    mysqli_stmt_bind_param($stmt, 's', $department);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}


// ── Classes ───────────────────────────────────────────────────────────────────

/*
 * Normalise a class code: trim, collapse spaces, uppercase the letters.
 * e.g. " bit27 " -> "BIT27".
 */
function normalize_class_code($raw) {
    $code = strtoupper(trim((string) $raw));
    return preg_replace('/\s+/', ' ', $code);
}

/*
 * Normalise a comma-separated list of class codes (#5): split on commas,
 * normalise each, drop blanks, de-duplicate (order preserved), and re-join
 * as "BIT27, BIT28". Returns '' when no valid code is present.
 */
function normalize_class_list($raw) {
    $out = array();
    foreach (explode(',', (string) $raw) as $part) {
        $code = normalize_class_code($part);
        if ($code !== '' && !in_array($code, $out, true)) {
            $out[] = $code;
        }
    }
    return implode(', ', $out);
}

/* Split a stored/normalised class list back into individual codes. */
function class_list_to_array($list) {
    $out = array();
    foreach (explode(',', (string) $list) as $part) {
        $code = trim($part);
        if ($code !== '') $out[] = $code;
    }
    return $out;
}

/* Return every known class code (for the file-claim dropdown). */
function db_get_all_classes($conn) {
    $res = @mysqli_query($conn, 'SELECT class_code FROM classes ORDER BY class_code');
    if (!$res) return array();
    $out = array();
    while ($row = mysqli_fetch_row($res)) { $out[] = $row[0]; }
    return $out;
}

/* Insert a class code if it does not already exist (case-normalised by caller). */
function db_upsert_class($conn, $code) {
    $code = normalize_class_code($code);
    if ($code === '') return;
    $stmt = mysqli_prepare($conn, 'INSERT IGNORE INTO classes (class_code) VALUES (?)');
    if (!$stmt) return;
    mysqli_stmt_bind_param($stmt, 's', $code);
    @mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}


// ── Holidays ──────────────────────────────────────────────────────────────────

/*
 * Return all holiday dates as a flat array of 'YYYY-MM-DD' strings.
 * Degrades to an empty array if the holidays table is absent.
 */
function db_get_holiday_dates($conn) {
    $res = @mysqli_query($conn, 'SELECT holiday_date FROM holidays ORDER BY holiday_date');
    if (!$res) return array();
    $dates = array();
    while ($row = mysqli_fetch_row($res)) {
        $dates[] = $row[0];
    }
    return $dates;
}


// ── Overlap detection ─────────────────────────────────────────────────────────

/*
 * Return true if $userId already has a non-flagged claim with a teaching
 * session on $date whose time window overlaps [$start, $end).
 *
 * Two intervals overlap when existing.start < new.end AND existing.end > new.start.
 * Pass $excludeClaimId to ignore a specific claim (e.g. the one being edited).
 * Times are 'HH:MM' or 'HH:MM:SS' strings — MySQL compares them to TIME safely.
 */
function db_has_overlapping_session($conn, $userId, $date, $start, $end, $excludeClaimId = null) {
    $sql =
        'SELECT 1
         FROM claim_data cda
         JOIN claim_details cd ON cd.claimId = cda.claimId
         WHERE cd.userId = ?
           AND cd.flagged = 0
           AND cda.date = ?
           AND cda.start_time < ?
           AND cda.end_time   > ?';
    if ($excludeClaimId !== null) {
        $sql .= ' AND cd.claimId <> ?';
    }
    $sql .= ' LIMIT 1';

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false; // fail-open: never block on a query-build error
    if ($excludeClaimId !== null) {
        $excl = (int) $excludeClaimId;
        mysqli_stmt_bind_param($stmt, 'isssi', $userId, $date, $end, $start, $excl);
    } else {
        mysqli_stmt_bind_param($stmt, 'isss', $userId, $date, $end, $start);
    }
    mysqli_stmt_execute($stmt);
    $found = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
    mysqli_stmt_close($stmt);
    return $found;
}


// ── Claim submission ──────────────────────────────────────────────────────────

/*
 * Insert a new claim_details row and return the generated claimId, or false on failure.
 */
function db_insert_claim($conn, $userId, $faculty, $department, $programme, $course, $rate, $class = null) {
    $stmt = mysqli_prepare($conn,
        'INSERT INTO claim_details (userId, faculty, department, programme, course, rate, class)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 'issssds', $userId, $faculty, $department, $programme, $course, $rate, $class);
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
                cd.class,
                cdata.date     AS claim_date,
                cdata.start_time,
                cdata.end_time,
                cdata.periods,
                bd.bank_name,
                bd.bank_branch,
                bd.account_number,
                bd.account_name
         FROM claim_details cd
         JOIN      user_details      ud    ON cd.userId  = ud.userId
         JOIN      claim_data        cdata ON cd.claimId = cdata.claimId
         LEFT JOIN user_bank_details bd    ON ud.userId  = bd.userId
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
