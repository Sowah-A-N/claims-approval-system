<?php
/*
 * Apply a new password from a reset token.
 * Re-validates the token authoritatively, enforces password strength, updates
 * both auth tables (login_details is the source of truth, user_details mirrors
 * it), and consumes the token.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_post();

$token = isset($_POST['token']) ? preg_replace('/[^a-f0-9]/', '', (string) $_POST['token']) : '';

// CSRF.
$submitted = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
$expected  = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';
if ($expected === '' || !hash_equals($expected, $submitted)) {
    $_SESSION['reset_notice'] = 'Your session expired. Please try again.';
    header('Location: ./reset.php?token=' . urlencode($token));
    exit;
}

$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirm  = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

// Resolve the token to a user (single-use, unexpired).
$uid = 0; $reset_id = 0;
if ($token !== '' && strlen($token) === 64) {
    $token_hash = hash('sha256', $token);
    $stmt = mysqli_prepare($conn,
        'SELECT id, userId FROM password_resets
         WHERE token_hash = ? AND used = 0 AND expires_at > NOW() LIMIT 1');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $token_hash);
        mysqli_stmt_execute($stmt);
        if ($row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
            $reset_id = (int) $row['id'];
            $uid      = (int) $row['userId'];
        }
        mysqli_stmt_close($stmt);
    }
}

if ($uid === 0) {
    $_SESSION['reset_notice'] = 'This reset link is invalid or has expired. Please request a new one.';
    header('Location: ./forgot.php');
    exit;
}

// Password rules (mirror registration): >= 8 chars, a letter and a number.
if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
    $_SESSION['reset_notice'] = 'Password must be at least 8 characters and include a letter and a number.';
    header('Location: ./reset.php?token=' . urlencode($token));
    exit;
}
if ($password !== $confirm) {
    $_SESSION['reset_notice'] = 'The two passwords do not match.';
    header('Location: ./reset.php?token=' . urlencode($token));
    exit;
}

$hash = password_hash($password, PASSWORD_BCRYPT, array('cost' => 12));

mysqli_begin_transaction($conn);
try {
    $u1 = mysqli_prepare($conn, 'UPDATE login_details SET password = ? WHERE userId = ?');
    mysqli_stmt_bind_param($u1, 'si', $hash, $uid);
    mysqli_stmt_execute($u1);
    mysqli_stmt_close($u1);

    // Keep the mirrored copy in sync (best-effort — column exists on user_details).
    $u2 = mysqli_prepare($conn, 'UPDATE user_details SET password = ? WHERE userId = ?');
    if ($u2) {
        mysqli_stmt_bind_param($u2, 'si', $hash, $uid);
        mysqli_stmt_execute($u2);
        mysqli_stmt_close($u2);
    }

    // Consume this token and retire any other outstanding ones for the user.
    $u3 = mysqli_prepare($conn, 'UPDATE password_resets SET used = 1 WHERE userId = ?');
    mysqli_stmt_bind_param($u3, 'i', $uid);
    mysqli_stmt_execute($u3);
    mysqli_stmt_close($u3);

    mysqli_commit($conn);
} catch (Throwable $e) {
    mysqli_rollback($conn);
    error_log('[reset] failed for user ' . $uid . ': ' . $e->getMessage());
    $_SESSION['reset_notice'] = 'Something went wrong updating your password. Please try again.';
    header('Location: ./reset.php?token=' . urlencode($token));
    exit;
}

log_audit($conn, 'auth.password_reset', 'user', $uid);

$_SESSION['message'] = 'Your password has been updated. Please sign in with your new password.';
header('Location: ./index.php');
exit;
