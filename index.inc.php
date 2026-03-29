<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_post();

$login_email = validated_str($_POST['email'] ?? '');
$login_pw    = $_POST['pw'] ?? '';

if ($login_email === '' || $login_pw === '') {
    $_SESSION['message'] = 'Email and password are required.';
    header('Location: ./index.php');
    exit;
}

// Fetch credentials by email only — password is verified below, never in SQL.
$stmt = $conn->prepare('SELECT * FROM login_details WHERE email = ?');
if (!$stmt) {
    error_log('[login] prepare failed: ' . $conn->error);
    $_SESSION['message'] = 'A server error occurred. Please try again.';
    header('Location: ./index.php');
    exit;
}

$stmt->bind_param('s', $login_email);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row || !password_verify($login_pw, $row['password'])) {
    // Keep the error message generic to avoid username enumeration.
    $_SESSION['message'] = 'Invalid email or password.';
    header('Location: ./index.php');
    exit;
}

// Fetch profile data from user_details.
$profile_stmt = $conn->prepare('SELECT * FROM user_details WHERE email = ?');
$profile_stmt->bind_param('s', $login_email);
$profile_stmt->execute();
$profile = $profile_stmt->get_result()->fetch_assoc();

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

// Populate session.
$_SESSION['user_id']   = (int) $row['userId'];
$_SESSION['role']      = $row['role'];
$_SESSION['stage']     = $row['stage'] ?? '';
$_SESSION['full_name'] = trim(($profile['last_name'] ?? '') . ', ' . ($profile['first_name'] ?? ''));
$_SESSION['rate']      = $profile['rate']       ?? '';
$_SESSION['dept']      = $profile['department'] ?? '';
$_SESSION['faculty']   = $profile['faculty']    ?? '';

if ($row['role'] === 'approver') {
    $_SESSION['approverId'] = (int) $row['userId'];
}

$redirect = match (strtolower((string) $row['role'])) {
    'user', 'claimant' => './users/user/',
    'approver'         => './users/approver/',
    'admin'            => './users/admin',
    'finance'          => './users/finance',
    default            => null,
};

if ($redirect === null) {
    $_SESSION['message'] = 'Your account role is not recognised. Please contact support.';
    header('Location: ./index.php');
    exit;
}

header('Location: ' . $redirect);
exit;
