<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/hr.queries.php';

require_post();

// CSRF check — redirect-style (this is a form POST, not an AJAX endpoint).
$submitted_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
$expected_token  = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';
if ($expected_token === '' || !hash_equals($expected_token, $submitted_token)) {
    $_SESSION['message'] = 'Your session expired. Please try submitting the form again.';
    header('Location: ./registerApp.php');
    exit;
}

$first_name   = validated_str(isset($_POST['first_name'])   ? $_POST['first_name']   : '');
$last_name    = validated_str(isset($_POST['last_name'])    ? $_POST['last_name']    : '');
$other_names  = validated_str(isset($_POST['other_names'])  ? $_POST['other_names']  : '');
$phone_number = validated_str(isset($_POST['phone_number']) ? $_POST['phone_number'] : '');
$gender       = validated_str(isset($_POST['gender'])       ? $_POST['gender']       : '');
$email        = validated_str(isset($_POST['email'])        ? $_POST['email']        : '');
$raw_password =               isset($_POST['password'])     ? $_POST['password']     : '';
$faculty      = validated_str(isset($_POST['faculty'])      ? $_POST['faculty']      : '');
$department   = validated_str(isset($_POST['department'])   ? $_POST['department']   : '');
$rank         = validated_str(isset($_POST['rank'])         ? $_POST['rank']         : '');
$stage        = (int) (isset($_POST['stage'])               ? $_POST['stage']        : 0);

if ($first_name === '' || $last_name === '' || $email === '' || $raw_password === '') {
    $_SESSION['message'] = 'Please fill in all required fields.';
    header('Location: ./registerApp.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['message'] = 'Please enter a valid email address.';
    header('Location: ./registerApp.php');
    exit;
}

// Reject duplicate emails up front so the user gets a clear message rather
// than a generic failure when the UNIQUE constraint fires.
$dup = mysqli_prepare($conn, 'SELECT 1 FROM login_details WHERE email = ? LIMIT 1');
if ($dup) {
    mysqli_stmt_bind_param($dup, 's', $email);
    mysqli_stmt_execute($dup);
    $exists = mysqli_fetch_row(mysqli_stmt_get_result($dup)) !== null;
    mysqli_stmt_close($dup);
    if ($exists) {
        $_SESSION['message'] = 'An account with this email address already exists.';
        header('Location: ./registerApp.php');
        exit;
    }
}

// Clamp the approval stage to the configured range (1..max_approval_stages).
$max_stage = 5;
$cfg = mysqli_query($conn,
    "SELECT settingValue FROM settings WHERE settingName = 'max_approval_stages' LIMIT 1");
if ($cfg && ($cfg_row = mysqli_fetch_row($cfg)) && (int) $cfg_row[0] > 0) {
    $max_stage = (int) $cfg_row[0];
}
if ($stage < 1 || $stage > $max_stage) {
    $_SESSION['message'] = 'Please select a valid approval stage (1 to ' . $max_stage . ').';
    header('Location: ./registerApp.php');
    exit;
}

$password_hash  = password_hash($raw_password, PASSWORD_BCRYPT, array('cost' => 12));
$role           = 'approver';
$rate           = 0.0;
// Auto-activate when the email is on the HR employee register (#1).
$is_hr_employee = db_email_in_hr_list($conn, $email);
$account_status = $is_hr_employee ? 'active' : 'disabled';
$date_created   = date('Y-m-d H:i:s');

mysqli_begin_transaction($conn);
$ok = true;

$s1 = mysqli_prepare($conn,
    'INSERT INTO user_details
         (first_name, last_name, other_names, phone_number, gender, email,
          `password`, faculty, department, `role`, `rank`, `rate`, account_status, date_created)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
if ($s1) {
    mysqli_stmt_bind_param($s1, 'sssssssssssdss',
        $first_name, $last_name, $other_names, $phone_number, $gender,
        $email, $password_hash, $faculty, $department, $role, $rank,
        $rate, $account_status, $date_created
    );
    $ok      = mysqli_stmt_execute($s1);
    $user_id = $ok ? (int) mysqli_insert_id($conn) : 0;
    mysqli_stmt_close($s1);
} else {
    $ok = false;
}

if ($ok) {
    $s2 = mysqli_prepare($conn,
        'INSERT INTO login_details (userId, email, `password`, `role`, `rank`, stage)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    if ($s2) {
        mysqli_stmt_bind_param($s2, 'issssi', $user_id, $email, $password_hash, $role, $rank, $stage);
        $ok = mysqli_stmt_execute($s2);
        mysqli_stmt_close($s2);
    } else {
        $ok = false;
    }
}

if ($ok) {
    mysqli_commit($conn);
    log_audit($conn, 'user.register', 'user', $user_id,
        'approver self-registration, stage ' . $stage . ($is_hr_employee ? ' (auto-activated via HR register)' : ''));
    $_SESSION['message'] = $is_hr_employee
        ? 'Registration successful! Your account was verified against the HR register and is active — you can sign in now.'
        : 'Registration successful! You will be notified when your account is activated.';
    header('Location: ./index.php');
} else {
    mysqli_rollback($conn);
    error_log('[registerApp] failed: ' . mysqli_error($conn));
    $_SESSION['message'] = 'Registration failed. Please try again.';
    header('Location: ./registerApp.php');
}
exit;
