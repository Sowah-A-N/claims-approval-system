<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_post();

$client_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';

if (is_login_rate_limited($conn, $client_ip)) {
    $_SESSION['message'] = 'Too many failed login attempts. Please try again in 15 minutes.';
    header('Location: ./index.php');
    exit;
}

$login_email = validated_str(isset($_POST['email']) ? $_POST['email'] : '');
$login_pw    = isset($_POST['pw']) ? $_POST['pw'] : '';

if ($login_email === '' || $login_pw === '') {
    $_SESSION['message'] = 'Email and password are required.';
    header('Location: ./index.php');
    exit;
}

// Fetch credentials by email only — password compared below, never in SQL.
$stmt = mysqli_prepare($conn, 'SELECT * FROM login_details WHERE email = ?');
if (!$stmt) {
    error_log('[login] prepare failed: ' . mysqli_error($conn));
    $_SESSION['message'] = 'A server error occurred. Please try again.';
    header('Location: ./index.php');
    exit;
}

mysqli_stmt_bind_param($stmt, 's', $login_email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row    = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$row) {
    record_failed_login($conn, $client_ip);
    $_SESSION['message'] = 'Invalid email or password.';
    header('Location: ./index.php');
    exit;
}

// Check bcrypt hash (v1.0.1+).
$password_ok = password_verify($login_pw, $row['password']);

// Legacy plaintext fallback for accounts registered before v1.0.1.
// On match: silently re-hash and continue so future logins use bcrypt.
if (!$password_ok && $login_pw === $row['password']) {
    $new_hash = password_hash($login_pw, PASSWORD_BCRYPT, array('cost' => 12));
    $upd = mysqli_prepare($conn, 'UPDATE login_details SET password = ? WHERE userId = ?');
    if ($upd) {
        mysqli_stmt_bind_param($upd, 'si', $new_hash, $row['userId']);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);
    }
    $password_ok = true;
}

if (!$password_ok) {
    record_failed_login($conn, $client_ip);
    $_SESSION['message'] = 'Invalid email or password.';
    header('Location: ./index.php');
    exit;
}

// Fetch profile data from user_details.
$profile_stmt = mysqli_prepare($conn, 'SELECT * FROM user_details WHERE email = ?');
mysqli_stmt_bind_param($profile_stmt, 's', $login_email);
mysqli_stmt_execute($profile_stmt);
$profile_result = mysqli_stmt_get_result($profile_stmt);
$profile        = mysqli_fetch_assoc($profile_result);
mysqli_stmt_close($profile_stmt);

if (!$profile) {
    $_SESSION['message'] = 'Account data is missing. Please contact support.';
    header('Location: ./index.php');
    exit;
}

if ($profile['account_status'] === 'disabled') {
    session_unset();
    session_destroy();
    header('Location: ./index.php?disabled=1');
    exit;
}

// Clear rate-limit counter on successful authentication.
clear_failed_logins($conn, $client_ip);

// Populate session.
$_SESSION['user_id']   = (int) $row['userId'];
$_SESSION['role']      = $row['role'];
$_SESSION['stage']     = isset($row['stage'])        ? $row['stage']           : '';
$_SESSION['full_name'] = trim(
    (isset($profile['last_name'])  ? $profile['last_name']  : '') . ', ' .
    (isset($profile['first_name']) ? $profile['first_name'] : '')
);
$_SESSION['rate']      = isset($profile['rate'])       ? $profile['rate']       : '';
$_SESSION['dept']      = isset($profile['department']) ? $profile['department'] : '';
$_SESSION['faculty']   = isset($profile['faculty'])    ? $profile['faculty']    : '';

if (strtolower($row['role']) === 'approver') {
    $_SESSION['approverId'] = (int) $row['userId'];
}

// Role-to-dashboard routing.
$role     = strtolower((string) $row['role']);
$redirect = null;

switch ($role) {
    case 'user':
    case 'claimant':
        $redirect = './users/user/';
        break;
    case 'approver':
        $redirect = './users/approver/';
        break;
    case 'admin':
        $redirect = './users/admin';
        break;
    case 'finance':
        $redirect = './users/finance';
        break;
    default:
        $redirect = null;
        break;
}

if ($redirect === null) {
    $_SESSION['message'] = 'Your account role is not recognised. Please contact support.';
    header('Location: ./index.php');
    exit;
}

header('Location: ' . $redirect);
exit;
