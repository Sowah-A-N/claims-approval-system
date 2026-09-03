<?php
/*
 * Password-reset request handler.
 *
 * Always responds with the same generic message so an attacker can't use it to
 * discover which emails have accounts. When the email does match an account we
 * store a single-use, one-hour token (only its SHA-256 hash is persisted) and
 * email a reset link.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mailer.php';

require_post();

// CSRF (redirect-style form, matching the login handler).
$submitted = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
$expected  = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';
if ($expected === '' || !hash_equals($expected, $submitted)) {
    $_SESSION['reset_notice'] = 'Your session expired. Please try again.';
    $_SESSION['reset_notice_type'] = 'warning';
    header('Location: ./forgot.php');
    exit;
}

$generic = 'If an account exists for that email, a password-reset link is on its way. Please check your inbox.';

$email = validated_str(isset($_POST['email']) ? $_POST['email'] : '');
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['reset_notice'] = 'Please enter a valid email address.';
    $_SESSION['reset_notice_type'] = 'warning';
    header('Location: ./forgot.php');
    exit;
}

// Look up the account. We still respond generically whether or not it exists.
$uid = 0; $fname = '';
$stmt = mysqli_prepare($conn,
    'SELECT ld.userId, ud.first_name
     FROM login_details ld
     LEFT JOIN user_details ud ON ud.userId = ld.userId
     WHERE ld.email = ? LIMIT 1');
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    if ($row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
        $uid   = (int) $row['userId'];
        $fname = isset($row['first_name']) ? (string) $row['first_name'] : '';
    }
    mysqli_stmt_close($stmt);
}

if ($uid > 0) {
    // Invalidate any earlier unused tokens for this user, then issue a fresh one.
    $inv = mysqli_prepare($conn, 'UPDATE password_resets SET used = 1 WHERE userId = ? AND used = 0');
    if ($inv) { mysqli_stmt_bind_param($inv, 'i', $uid); mysqli_stmt_execute($inv); mysqli_stmt_close($inv); }

    $token      = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);

    // Compute expiry with MySQL's own clock (DATE_ADD/NOW) so it shares the same
    // timezone as the NOW() used to validate the token — a PHP-side date() could
    // disagree with the DB timezone and make a token born expired (or long-lived).
    $ins = mysqli_prepare($conn,
        'INSERT INTO password_resets (userId, token_hash, expires_at)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))');
    if ($ins) {
        mysqli_stmt_bind_param($ins, 'is', $uid, $token_hash);
        mysqli_stmt_execute($ins);
        mysqli_stmt_close($ins);

        // Build an absolute reset link (scheme + host + app directory).
        $scheme = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
        $host   = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        $link   = $scheme . '://' . $host . $dir . '/reset.php?token=' . $token;

        $hello = $fname !== '' ? 'Hi ' . h($fname) . ',' : 'Hello,';
        $body  = email_wrap('Reset your password',
            '<p>' . $hello . '</p>'
          . '<p>We received a request to reset the password for your RMU Claims account. '
          . 'Click the button below to choose a new password. This link expires in one hour and can be used once.</p>'
          . '<p style="margin:24px 0;"><a href="' . h($link) . '" '
          . 'style="background:#219ebc;color:#ffffff;text-decoration:none;font-weight:bold;'
          . 'padding:12px 22px;border-radius:8px;display:inline-block;">Reset my password</a></p>'
          . '<p style="font-size:13px;color:#64748b;">If the button doesn\'t work, copy this link into your browser:<br>'
          . '<span style="word-break:break-all;">' . h($link) . '</span></p>'
          . '<p style="font-size:13px;color:#64748b;">If you didn\'t request this, you can safely ignore this email — '
          . 'your password won\'t change.</p>');

        email_send($conn, $email, $fname, 'Reset your RMU Claims password', $body, 'password_reset', $uid);
    }
}

$_SESSION['reset_notice'] = $generic;
$_SESSION['reset_notice_type'] = 'info';
header('Location: ./forgot.php');
exit;
