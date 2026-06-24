<?php
/*
 * General-purpose utility functions.
 *
 *   h($val)                  - escape a value for safe HTML output
 *   json_response($data)     - send JSON and exit
 *   require_post()           - exit 405 if not a POST request
 *   validated_int($val)      - validate an integer input; exits 400 on failure
 *   validated_str($val)      - trim and cap a string input
 */


/*
 * Escape a value for safe output inside HTML.
 * Use on every database value echoed into a page.
 *
 *   echo h($row['course']);
 */
function h($val) {
    return htmlspecialchars((string) $val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/*
 * Send a JSON response and stop execution.
 *
 *   json_response(array('success' => true, 'message' => 'Done.'));
 *   json_response(array('success' => false, 'message' => 'Not found.'), 404);
 */
function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/*
 * Abort with HTTP 405 if the current request is not POST.
 * Call at the top of every handler that mutates data.
 */
function require_post() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Method Not Allowed');
    }
}

/*
 * Validate that $val is an integer. Calls json_response(400) on failure.
 *
 *   $claimId = validated_int($_POST['claimId'], 'claimId');
 */
function validated_int($val, $field = 'value') {
    if ($val === null || $val === '') {
        json_response(array('success' => false, 'message' => 'Missing field: ' . $field . '.'), 400);
    }
    $result = filter_var($val, FILTER_VALIDATE_INT);
    if ($result === false) {
        json_response(array('success' => false, 'message' => 'Invalid field: ' . $field . '.'), 400);
    }
    return (int) $result;
}

/*
 * Trim a string and cap its length.
 * Never use as SQL protection — always use prepared statements for that.
 */
function validated_str($val, $max_len = 500) {
    return mb_substr(trim((string) $val), 0, $max_len);
}

/*
 * Neutralise CSV/spreadsheet formula injection.
 * A leading =, +, -, @, tab or CR can be interpreted as a formula by Excel /
 * Sheets; prefix such values with a single quote so they render as text.
 *
 *   fputcsv($out, array_map('csv_safe', $row));
 */
function csv_safe($val) {
    $val = (string) $val;
    if ($val !== '' && strpbrk($val[0], "=+-@\t\r") !== false) {
        return "'" . $val;
    }
    return $val;
}

/*
 * Append an entry to the audit_log table.
 *
 * Actor identity and IP are taken from the current session/request — callers
 * only supply what happened. Degrades gracefully (does nothing) if the
 * audit_log table is absent, so it can never break a request.
 *
 *   log_audit($conn, 'claim.approve', 'claim', $claimId, 'stage 2 -> 3');
 *
 * @param string      $action      machine action name, e.g. 'user.activate'
 * @param string|null $entity_type e.g. 'claim', 'user'
 * @param int|null    $entity_id   primary key of the affected entity
 * @param string|null $detail      free-text context (kept short)
 */
function log_audit($conn, $action, $entity_type = null, $entity_id = null, $detail = null) {
    $actor_id   = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    $actor_role = isset($_SESSION['role'])    ? (string) $_SESSION['role'] : null;
    $ip         = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    $entity_id  = ($entity_id === null || $entity_id === '') ? null : (int) $entity_id;
    // detail is a JSON column — encode scalars as a JSON value (NULL stays NULL).
    $detail_json = ($detail === null) ? null : json_encode(mb_substr((string) $detail, 0, 255));

    // Audit logging must never break the request that triggered it. Under
    // mysqli's exception mode (PHP 8.1+), the '@' operator does NOT suppress
    // thrown errors, so wrap the whole thing in a try/catch as the real guard.
    try {
        $stmt = mysqli_prepare($conn,
            'INSERT INTO audit_log
                (actor_id, actor_role, action, entity_type, entity_id, detail, ip_address, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        if (!$stmt) return;
        mysqli_stmt_bind_param($stmt, 'isssiss',
            $actor_id, $actor_role, $action, $entity_type, $entity_id, $detail_json, $ip);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } catch (\Throwable $e) {
        error_log('[audit] ' . $e->getMessage());
    }
}
