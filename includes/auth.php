<?php
/*
 * Authentication and session management.
 *
 * Replaces the duplicated session/role code that lived in every head partial.
 * Including this file automatically initialises the session.
 *
 * Functions:
 *   session_init()       - safe to call multiple times; enforces inactivity timeout
 *   is_logged_in()       - returns true if user_id is in session
 *   require_auth()       - redirects to / if not logged in
 *   require_role($roles) - require_auth + role check; 403 on mismatch
 *   current_user_id()    - returns session user_id as integer
 *   current_user_role()  - returns session role as string
 *   csrf_token()         - generates / returns the session CSRF token
 *   csrf_verify()        - exits with 403 JSON on token mismatch
 *
 * Backward-compatible aliases (used by existing head partials):
 *   isUserLoggedIn()
 *   checkUserRole($roles)
 */


function session_init() {
    if (session_status() !== PHP_SESSION_NONE) {
        return; // Already started.
    }

    ini_set('session.gc_maxlifetime', 1800);
    session_set_cookie_params(array(
        'lifetime' => 1800,
        'path'     => '/',
        'secure'   => false, // Set to true when HTTPS is in place.
        'httponly' => true,
        'samesite' => 'Lax',
    ));
    session_start();

    // Regenerate ID every 5 minutes to reduce session fixation window.
    if (!isset($_SESSION['_regen_at']) || (time() - $_SESSION['_regen_at']) > 300) {
        session_regenerate_id(true);
        $_SESSION['_regen_at'] = time();
    }

    // Enforce 30-minute inactivity timeout.
    if (isset($_SESSION['_active_at']) && (time() - $_SESSION['_active_at']) > 1800) {
        session_unset();
        session_destroy();
        header('Location: /?timeout=1');
        exit;
    }

    $_SESSION['_active_at'] = time();
}

// Auto-initialise on include.
session_init();


function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

function require_auth() {
    if (!is_logged_in()) {
        header('Location: /');
        exit;
    }
}

/*
 * Abort with 403 if the current user's role is not in $roles.
 * @param array $roles  e.g. array('admin', 'Admin')
 */
function require_role($roles) {
    require_auth();
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
    if (!in_array($role, $roles)) {
        http_response_code(403);
        $error_page = dirname(__DIR__) . '/error_pages/403.php';
        if (file_exists($error_page)) {
            include $error_page;
        } else {
            echo 'Forbidden.';
        }
        exit;
    }
}

function current_user_id() {
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
}

function current_user_role() {
    return isset($_SESSION['role']) ? (string) $_SESSION['role'] : '';
}


// ── CSRF ──────────────────────────────────────────────────────────────────────

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/*
 * Verify CSRF token from POST body or X-CSRF-Token header.
 * Exits with 403 JSON on mismatch.
 */
function csrf_verify() {
    $submitted = '';
    if (isset($_POST['csrf_token'])) {
        $submitted = $_POST['csrf_token'];
    } elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $submitted = $_SERVER['HTTP_X_CSRF_TOKEN'];
    }
    $expected = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';

    if (!hash_equals($expected, $submitted)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'message' => 'Invalid CSRF token.'));
        exit;
    }
}


// ── Backward-compatible aliases ───────────────────────────────────────────────

function isUserLoggedIn() {
    return is_logged_in();
}

function checkUserRole($allowedRole) {
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
    if (!is_logged_in() || !in_array($role, $allowedRole)) {
        header('Location: /');
        exit;
    }
}


// ── Login rate limiting ───────────────────────────────────────────────────────
//
// Requires the login_attempts table. Create once with:
//
//   CREATE TABLE IF NOT EXISTS login_attempts (
//       id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
//       ip_address   VARCHAR(45)  NOT NULL,
//       attempted_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
//       INDEX idx_ip_time (ip_address, attempted_at)
//   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
//
// All three functions degrade gracefully (rate limiting disabled) if the
// table does not yet exist.

define('LOGIN_MAX_ATTEMPTS',    5);
define('LOGIN_WINDOW_SECONDS', 900);  // 15 minutes

/*
 * Returns true if $ip has exceeded LOGIN_MAX_ATTEMPTS failures in the rolling
 * window. Returns false on any DB error (fail-open: never block legitimate users
 * due to a missing table or transient DB fault).
 */
function is_login_rate_limited($conn, $ip) {
    $window = date('Y-m-d H:i:s', time() - LOGIN_WINDOW_SECONDS);
    $stmt = mysqli_prepare($conn,
        'SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at >= ?'
    );
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 'ss', $ip, $window);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return (int) $count >= LOGIN_MAX_ATTEMPTS;
}

/*
 * Record a single failed login attempt for $ip.
 */
function record_failed_login($conn, $ip) {
    $stmt = mysqli_prepare($conn,
        'INSERT INTO login_attempts (ip_address, attempted_at) VALUES (?, NOW())'
    );
    if (!$stmt) return;
    mysqli_stmt_bind_param($stmt, 's', $ip);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/*
 * Remove all failed-login records for $ip (call on successful authentication).
 */
function clear_failed_logins($conn, $ip) {
    $stmt = mysqli_prepare($conn,
        'DELETE FROM login_attempts WHERE ip_address = ?'
    );
    if (!$stmt) return;
    mysqli_stmt_bind_param($stmt, 's', $ip);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
