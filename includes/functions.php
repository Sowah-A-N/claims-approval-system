<?php
declare(strict_types=1);

/**
 * General-purpose utility functions.
 *
 * h()               — safe HTML output escaping
 * json_response()   — send JSON and exit
 * require_post()    — abort with 405 if not a POST request
 * validated_int()   — cast and validate an integer input; aborts on failure
 * validated_str()   — trim and cap a string input
 */


/**
 * Escape a value for safe output inside HTML.
 * Use on every database value echoed into a page.
 *
 * echo h($row['course']);
 */
function h(mixed $val): string
{
    return htmlspecialchars((string) $val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Send a JSON response and stop execution.
 *
 * json_response(['success' => true, 'message' => 'Done.']);
 * json_response(['success' => false, 'message' => 'Not found.'], 404);
 */
function json_response(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Abort with HTTP 405 if the current request is not POST.
 * Call at the top of every handler that mutates data.
 */
function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Method Not Allowed');
    }
}

/**
 * Validate that $val is a valid integer. Calls json_response(400) on failure.
 *
 * $claimId = validated_int($_POST['claimId'] ?? null, 'claimId');
 */
function validated_int(mixed $val, string $field = 'value'): int
{
    $result = filter_var($val, FILTER_VALIDATE_INT);
    if ($result === false || $val === null || $val === '') {
        json_response(['success' => false, 'message' => "Invalid or missing field: $field."], 400);
    }
    return (int) $result;
}

/**
 * Trim a string and cap its length. Never use for SQL — use prepared statements.
 * Use for sanitising display values or fields before storing.
 */
function validated_str(mixed $val, int $maxLen = 500): string
{
    return mb_substr(trim((string) $val), 0, $maxLen);
}
