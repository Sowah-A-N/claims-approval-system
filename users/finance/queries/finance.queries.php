<?php
/*
 * Data-layer functions for finance reporting.
 *
 * No HTML, no $_POST, no session access lives here. Callers pass an associative
 * $filters array; only recognised keys are honoured. All queries are prepared.
 *
 * Recognised $filters keys (all optional):
 *   from_date   'YYYY-MM-DD'  paid on/after this date
 *   to_date     'YYYY-MM-DD'  paid on/before this date
 *   department  exact department match
 *   search      matches claimant name OR payment reference (LIKE)
 */


/*
 * Build the shared WHERE clause + bind params for paid-claim queries.
 * Returns array($where_sql, $types, $params).
 */
function _paid_claims_filter($filters) {
    $where  = ' WHERE cd.paid = 1';
    $types  = '';
    $params = array();

    if (!empty($filters['from_date'])) {
        $where   .= ' AND DATE(cd.time_paid) >= ?';
        $types   .= 's';
        $params[] = $filters['from_date'];
    }
    if (!empty($filters['to_date'])) {
        $where   .= ' AND DATE(cd.time_paid) <= ?';
        $types   .= 's';
        $params[] = $filters['to_date'];
    }
    if (!empty($filters['department'])) {
        $where   .= ' AND ud.department = ?';
        $types   .= 's';
        $params[] = $filters['department'];
    }
    if (!empty($filters['search'])) {
        $like     = '%' . $filters['search'] . '%';
        $where   .= ' AND (CONCAT(ud.first_name, " ", ud.last_name) LIKE ? OR cd.payment_ref LIKE ?)';
        $types   .= 'ss';
        $params[] = $like;
        $params[] = $like;
    }
    return array($where, $types, $params);
}

/*
 * Return all paid claims matching $filters, newest payment first.
 * Each row: claimId, full_name, department, course, grand_total,
 *           payment_ref, time_paid, paid_by_name.
 */
function db_get_paid_claims($conn, $filters = array()) {
    list($where, $types, $params) = _paid_claims_filter($filters);

    $sql =
        "SELECT cd.claimId,
                CONCAT(ud.last_name, ', ', ud.first_name) AS full_name,
                ud.department,
                cd.course,
                COALESCE(SUM(cdata.periods), 0) * ud.rate AS grand_total,
                cd.payment_ref,
                cd.time_paid,
                CONCAT(pb.first_name, ' ', pb.last_name) AS paid_by_name
         FROM claim_details cd
         JOIN user_details ud           ON cd.userId  = ud.userId
         LEFT JOIN claim_data cdata     ON cd.claimId = cdata.claimId
         LEFT JOIN user_details pb       ON cd.paid_by = pb.userId"
        . $where .
        " GROUP BY cd.claimId
          ORDER BY cd.time_paid DESC, cd.claimId DESC";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return array();
    if ($types !== '') mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

/*
 * Aggregate totals for the current filter set: claim count and summed amount.
 * Returns array('count' => int, 'total' => float).
 */
function db_get_paid_claims_summary($conn, $filters = array()) {
    $rows  = db_get_paid_claims($conn, $filters);
    $total = 0.0;
    foreach ($rows as $r) { $total += (float) $r['grand_total']; }
    return array('count' => count($rows), 'total' => $total);
}

/*
 * Distinct departments that appear among paid claims (for the filter dropdown).
 */
function db_get_paid_claim_departments($conn) {
    $sql =
        "SELECT DISTINCT ud.department
         FROM claim_details cd
         JOIN user_details ud ON cd.userId = ud.userId
         WHERE cd.paid = 1 AND ud.department IS NOT NULL AND ud.department <> ''
         ORDER BY ud.department";
    $res = mysqli_query($conn, $sql);
    if (!$res) return array();
    $out = array();
    while ($row = mysqli_fetch_row($res)) { $out[] = $row[0]; }
    return $out;
}
