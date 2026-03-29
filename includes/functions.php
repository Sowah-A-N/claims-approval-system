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
