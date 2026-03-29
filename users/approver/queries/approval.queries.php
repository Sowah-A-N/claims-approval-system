<?php
declare(strict_types=1);

/**
 * Data-layer functions for approver operations.
 *
 * No HTML, no $_POST, no session access lives here.
 * All stage-advancing and flagging operations are atomic (transactions).
 */


// ── Claim listing ──────────────────────────────────────────────────────────────

/**
 * Return all claims currently at $stage with status 'Pending', not flagged.
 * When $department is non-empty, results are scoped to that department.
 *
 * @return array<int, array<string, mixed>>
 */
function db_get_pending_claims_for_stage(mysqli $conn, int $stage, ?string $department): array
{
    $base = 'SELECT cd.*, CONCAT(ud.first_name, \' \', ud.last_name) AS full_name
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
                AND cas.status = \'Pending\'
                AND cd.flagged = 0';

    if ($department !== null && $department !== '') {
        $stmt = $conn->prepare($base . ' AND cd.department = ?');
        $stmt->bind_param('is', $stage, $department);
    } else {
        $stmt = $conn->prepare($base);
        $stmt->bind_param('i', $stage);
    }

    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}


// ── Claim detail ───────────────────────────────────────────────────────────────

/**
 * Fetch claim_details + all claim_data rows for display in the approver modal.
 * Does not enforce ownership — approvers may view any claim at their stage.
 * Stage ownership is validated at the handler level before displaying.
 */
function db_get_claim_details_for_approver(mysqli $conn, int $claimId): ?array
{
    $stmt = $conn->prepare('SELECT * FROM claim_details WHERE claimId = ?');
    $stmt->bind_param('i', $claimId);
    $stmt->execute();
    $claim = $stmt->get_result()->fetch_assoc();
    if ($claim === false || $claim === null) {
        return null;
    }

    $stmt2 = $conn->prepare(
        'SELECT cdata.*, cd.rate
         FROM claim_data cdata
         JOIN claim_details cd ON cd.claimId = cdata.claimId
         WHERE cdata.claimId = ?
         ORDER BY cdata.date'
    );
    $stmt2->bind_param('i', $claimId);
    $stmt2->execute();
    $claim['rows'] = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

    return $claim;
}

/**
 * Return the current (latest) stage of a claim, or null if claimId does not exist.
 */
function db_get_current_stage(mysqli $conn, int $claimId): ?int
{
    $stmt = $conn->prepare(
        'SELECT stage FROM claim_approval_stages WHERE claimId = ? ORDER BY stageId DESC LIMIT 1'
    );
    $stmt->bind_param('i', $claimId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_row();
    return $row ? (int) $row[0] : null;
}


// ── Approve ────────────────────────────────────────────────────────────────────

/**
 * Mark the current stage as Approved and insert the next Pending stage.
 * Validates that the claim is actually at $expectedStage with status Pending
 * before making any changes — prevents stage-skip attacks.
 *
 * @throws RuntimeException on validation failure or DB error.
 */
function db_advance_claim_stage(mysqli $conn, int $claimId, int $expectedStage): void
{
    $conn->begin_transaction();
    try {
        // Lock the row and verify the claim is genuinely at the expected stage.
        $check = $conn->prepare(
            'SELECT stageId FROM claim_approval_stages
             WHERE claimId = ? AND stage = ? AND status = \'Pending\'
             LIMIT 1
             FOR UPDATE'
        );
        $check->bind_param('ii', $claimId, $expectedStage);
        $check->execute();
        if ($check->get_result()->num_rows === 0) {
            throw new RuntimeException('Claim is not at the expected pending stage.');
        }

        $approve = $conn->prepare(
            'UPDATE claim_approval_stages
             SET status = \'Approved\', time_approved = NOW()
             WHERE claimId = ? AND stage = ? AND status = \'Pending\''
        );
        $approve->bind_param('ii', $claimId, $expectedStage);
        $approve->execute();
        if ($approve->affected_rows <= 0) {
            throw new RuntimeException('Failed to approve stage.');
        }

        $nextStage = $expectedStage + 1;
        $insert = $conn->prepare(
            'INSERT INTO claim_approval_stages (claimId, stage, status, time_updated)
             VALUES (?, ?, \'Pending\', NOW())'
        );
        $insert->bind_param('ii', $claimId, $nextStage);
        $insert->execute();
        if ($insert->affected_rows <= 0) {
            throw new RuntimeException('Failed to insert next stage.');
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}


// ── Flag ───────────────────────────────────────────────────────────────────────

/**
 * Flag a claim: set flagged=1 in claim_details, insert a Flagged approval row,
 * and record a flagged_claims entry.
 * All three writes are atomic.
 *
 * @throws RuntimeException on validation failure or DB error.
 */
function db_flag_claim(mysqli $conn, int $claimId, int $stage, string $reason): void
{
    $conn->begin_transaction();
    try {
        $flag = $conn->prepare(
            'UPDATE claim_details SET flagged = 1
             WHERE claimId = ? AND flagged = 0'
        );
        $flag->bind_param('i', $claimId);
        $flag->execute();
        if ($flag->affected_rows <= 0) {
            throw new RuntimeException('Claim not found or was already flagged.');
        }

        $stageRow = $conn->prepare(
            'INSERT INTO claim_approval_stages (claimId, stage, status, time_rejected)
             VALUES (?, ?, \'Flagged\', NOW())'
        );
        $stageRow->bind_param('ii', $claimId, $stage);
        $stageRow->execute();

        $log = $conn->prepare(
            'INSERT INTO flagged_claims (claimId, flagged_at_stage, flagged_msg, date_flagged)
             VALUES (?, ?, ?, NOW())'
        );
        $log->bind_param('iis', $claimId, $stage, $reason);
        $log->execute();

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}
