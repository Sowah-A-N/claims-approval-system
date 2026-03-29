<?php
declare(strict_types=1);

/**
 * Data-layer functions for claimant claim operations.
 *
 * Every function accepts a mysqli $conn and typed parameters.
 * No HTML, no $_POST, no session access lives here.
 * Callers are responsible for passing validated input.
 */


// ── Course lookup ──────────────────────────────────────────────────────────────

/**
 * Return all non-archived courses for the given department.
 *
 * @return array<int, array{name: string}>
 */
function db_get_courses_by_department(mysqli $conn, string $department): array
{
    $stmt = $conn->prepare('SELECT name FROM course WHERE department = ? AND archived = 0 ORDER BY name');
    $stmt->bind_param('s', $department);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}


// ── Claim submission ───────────────────────────────────────────────────────────

/**
 * Insert a new claim_details row and return the generated claimId.
 */
function db_insert_claim(
    mysqli $conn,
    int    $userId,
    string $faculty,
    string $department,
    string $programme,
    string $course,
    float  $rate
): int {
    $stmt = $conn->prepare(
        'INSERT INTO claim_details (userId, faculty, department, programme, course, rate)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('issssd', $userId, $faculty, $department, $programme, $course, $rate);
    $stmt->execute();
    return (int) $conn->insert_id;
}

/**
 * Insert the initial claim_approval_stages row (stage 1, Pending).
 */
function db_insert_initial_stage(mysqli $conn, int $claimId): void
{
    $stage = 1;
    $stmt  = $conn->prepare(
        'INSERT INTO claim_approval_stages (claimId, stage, status) VALUES (?, ?, \'Pending\')'
    );
    $stmt->bind_param('ii', $claimId, $stage);
    $stmt->execute();
}

/**
 * Insert a single claim_data row.
 * Type string: i=claimId, s=date, s=start_time, s=end_time, i=periods, d=subTotal, i=fuelComponent
 */
function db_insert_claim_data_row(
    mysqli $conn,
    int    $claimId,
    string $date,
    string $startTime,
    string $endTime,
    int    $periods,
    float  $subTotal,
    int    $fuelComponent
): void {
    $stmt = $conn->prepare(
        'INSERT INTO claim_data (claimId, date, start_time, end_time, periods, subTotal, fuelComponent)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('isssidi', $claimId, $date, $startTime, $endTime, $periods, $subTotal, $fuelComponent);
    $stmt->execute();
}


// ── Claim read ─────────────────────────────────────────────────────────────────

/**
 * Return claim_details for a claim owned by $userId, or null if not found / not owned.
 * Ownership check is enforced in the WHERE clause to prevent IDOR.
 */
function db_get_claim_by_owner(mysqli $conn, int $claimId, int $userId): ?array
{
    $stmt = $conn->prepare('SELECT * FROM claim_details WHERE claimId = ? AND userId = ?');
    $stmt->bind_param('ii', $claimId, $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

/**
 * Return all claim_data rows for a claim.
 * Caller must have already verified ownership via db_get_claim_by_owner().
 *
 * @return array<int, array<string, mixed>>
 */
function db_get_claim_data_rows(mysqli $conn, int $claimId): array
{
    $stmt = $conn->prepare('SELECT * FROM claim_data WHERE claimId = ? ORDER BY date');
    $stmt->bind_param('i', $claimId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Return a saved (draft) claim owned by $userId, or null.
 */
function db_get_saved_claim_by_owner(mysqli $conn, int $claimId, int $userId): ?array
{
    $stmt = $conn->prepare('SELECT * FROM saved_claims WHERE claimTempId = ? AND userId = ?');
    $stmt->bind_param('ii', $claimId, $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

/**
 * Return all claims for $userId with their latest approval stage and status.
 *
 * @return array<int, array<string, mixed>>
 */
function db_get_user_claims(mysqli $conn, int $userId): array
{
    $stmt = $conn->prepare(
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
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Fetch all data needed to generate a downloadable claim Word document.
 * Enforces ownership via AND cd.userId = ?.
 *
 * @return array<int, array<string, mixed>>
 */
function db_get_claim_download_data(mysqli $conn, int $claimId, int $userId): array
{
    $stmt = $conn->prepare(
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
         JOIN user_details       ud    ON cd.userId    = ud.userId
         JOIN claim_data         cdata ON cd.claimId   = cdata.claimId
         JOIN user_bank_details  bd    ON ud.userId    = bd.userId
         WHERE cd.claimId = ? AND cd.userId = ?'
    );
    $stmt->bind_param('ii', $claimId, $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}


// ── Claim deletion ─────────────────────────────────────────────────────────────

/**
 * Delete a saved claim and its data rows.
 * Ownership is enforced; returns false if the claim does not belong to $userId.
 */
function db_delete_saved_claim(mysqli $conn, int $claimId, int $userId): bool
{
    // Verify ownership before deleting.
    if (db_get_saved_claim_by_owner($conn, $claimId, $userId) === null) {
        return false;
    }

    $conn->begin_transaction();
    try {
        $s1 = $conn->prepare('DELETE FROM saved_claims WHERE claimTempId = ? AND userId = ?');
        $s1->bind_param('ii', $claimId, $userId);
        $s1->execute();

        $s2 = $conn->prepare('DELETE FROM claim_data WHERE claimId = ?');
        $s2->bind_param('i', $claimId);
        $s2->execute();

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log('[claim.queries] db_delete_saved_claim failed: ' . $e->getMessage());
        return false;
    }
}
