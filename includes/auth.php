<?php
declare(strict_types=1);

/**
 * Authentication and session management.
 *
 * Replaces the duplicated session/role code that lived in every head partial.
 * Including this file automatically initialises the session.
 *
 * Public API:
 *   session_init()           — safe to call multiple times; enforces timeout
 *   is_logged_in(): bool
 *   require_auth()           — redirects to / if not logged in
 *   require_role(array)      — require_auth + role check; 403 on mismatch
 *   current_user_id(): int
 *   current_user_role(): string
 *   csrf_token(): string     — generates / returns session CSRF token
 *   csrf_verify()            — aborts with 403 JSON on token mismatch
 *
 * Backward-compatible aliases (used by existing head partials):
 *   isUserLoggedIn(): bool
 *   checkUserRole(array)
 */

// ── Session bootstrap ──────────────────────────────────────────────────────────

function session_init(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return; // Already started.
    }

    ini_set('session.gc_maxlifetime', '1800');
    session_set_cookie_params([
        'lifetime' => 1800,
        'path'     => '/',
        'secure'   => false, // Set true when HTTPS is enforced.
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    // Regenerate ID every 5 minutes to mitigate session fixation.
    if (!isset($_SESSION['_regen_at']) || time() - $_SESSION['_regen_at'] > 300) {
        session_regenerate_id(true);
        $_SESSION['_regen_at'] = time();
    }

    // Enforce 30-minute inactivity timeout.
    if (isset($_SESSION['_active_at']) && time() - $_SESSION['_active_at'] > 1800) {
        session_unset();
        session_destroy();
        header('Location: /?timeout=1');
        exit;
    }
    $_SESSION['_active_at'] = time();
}

// Auto-initialise on include.
session_init();


// ── Auth checks ────────────────────────────────────────────────────────────────

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function require_auth(): void
{
    if (!is_logged_in()) {
        header('Location: /');
        exit;
    }
}

/**
 * Abort with 403 if the current user's role is not in $roles.
 *
 * @param string[] $roles Allowed role strings, e.g. ['admin', 'Admin']
 */
function require_role(array $roles): void
{
    require_auth();
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        http_response_code(403);
        $errorPage = dirname(__DIR__) . '/error_pages/403.php';
        if (file_exists($errorPage)) {
            include $errorPage;
        } else {
            exit('Forbidden.');
        }
        exit;
    }
}

function current_user_id(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

function current_user_role(): string
{
    return (string) ($_SESSION['role'] ?? '');
}


// ── CSRF ───────────────────────────────────────────────────────────────────────

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token from POST body or X-CSRF-Token header.
 * Calls json_response() with 403 on failure — requires functions.php to be loaded first,
 * or falls back to a plain exit.
 */
function csrf_verify(): void
{
    $submitted = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $expected  = $_SESSION['csrf_token'] ?? '';

    if (!hash_equals($expected, $submitted)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
        exit;
    }
}


// ── Backward-compatible aliases ────────────────────────────────────────────────

/** @deprecated Use is_logged_in() */
function isUserLoggedIn(): bool
{
    return is_logged_in();
}

/**
 * Backward-compatible role check used by existing head partials.
 * Redirects to / on failure (matches original behaviour).
 *
 * @param string[] $allowedRole
 * @deprecated Use require_role()
 */
function checkUserRole(array $allowedRole): void
{
    if (!is_logged_in() || !in_array($_SESSION['role'] ?? '', $allowedRole, true)) {
        header('Location: /');
        exit;
    }
}
