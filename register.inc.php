<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_post();

$first_name     = validated_str(isset($_POST['first_name'])     ? $_POST['first_name']     : '');
$last_name      = validated_str(isset($_POST['last_name'])      ? $_POST['last_name']      : '');
$other_names    = validated_str(isset($_POST['other_names'])    ? $_POST['other_names']    : '');
$phone_number   = validated_str(isset($_POST['phone_number'])   ? $_POST['phone_number']   : '');
$gender         = validated_str(isset($_POST['gender'])         ? $_POST['gender']         : '');
$email          = validated_str(isset($_POST['email'])          ? $_POST['email']          : '');
$raw_password   =               isset($_POST['password'])       ? $_POST['password']       : '';
$faculty        = validated_str(isset($_POST['faculty'])        ? $_POST['faculty']        : '');
$department     = validated_str(isset($_POST['department'])     ? $_POST['department']     : '');
$rank           = validated_str(isset($_POST['rank'])           ? $_POST['rank']           : '');
// Rate is assigned by an administrator after the account is approved — it must
// never be self-declared at registration. Any client-supplied 'rate' is ignored.
$rate           = 0.0;
$bank_name      = validated_str(isset($_POST['bank_name'])      ? $_POST['bank_name']      : '');
$bank_branch    = validated_str(isset($_POST['bank_branch'])    ? $_POST['bank_branch']    : '');
$account_name   = validated_str(isset($_POST['account_name'])   ? $_POST['account_name']   : '');
$account_number = validated_str(isset($_POST['account_number']) ? $_POST['account_number'] : '');

if ($first_name === '' || $last_name === '' || $email === '' || $raw_password === '') {
    $_SESSION['message'] = 'Please fill in all required fields.';
    header('Location: ./register.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['message'] = 'Please enter a valid email address.';
    header('Location: ./register.php');
    exit;
}

// Hash the password — never store plaintext credentials.
$password_hash  = password_hash($raw_password, PASSWORD_BCRYPT, array('cost' => 12));
$role           = 'claimant';
$account_status = 'disabled';
$date_created   = date('Y-m-d H:i:s');

mysqli_begin_transaction($conn);
$ok = true;

// 1. Insert into user_details.
$s1 = mysqli_prepare($conn,
    'INSERT INTO user_details
         (first_name, last_name, other_names, phone_number, gender, email,
          `password`, faculty, department, `role`, `rank`, rate, account_status, date_created)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
if ($s1) {
    mysqli_stmt_bind_param($s1, 'sssssssssssdss',
        $first_name, $last_name, $other_names, $phone_number, $gender,
        $email, $password_hash, $faculty, $department, $role, $rank,
        $rate, $account_status, $date_created
    );
    $ok = mysqli_stmt_execute($s1);
    $user_id = $ok ? (int) mysqli_insert_id($conn) : 0;
    mysqli_stmt_close($s1);
} else {
    $ok = false;
}

// 2. Mirror credentials to login_details.
if ($ok) {
    $s2 = mysqli_prepare($conn,
        'INSERT INTO login_details (userId, email, `password`, `role`, `rank`)
         VALUES (?, ?, ?, ?, ?)'
    );
    if ($s2) {
        mysqli_stmt_bind_param($s2, 'issss', $user_id, $email, $password_hash, $role, $rank);
        $ok = mysqli_stmt_execute($s2);
        mysqli_stmt_close($s2);
    } else {
        $ok = false;
    }
}

// 3. Insert bank details.
if ($ok) {
    $s3 = mysqli_prepare($conn,
        'INSERT INTO user_bank_details (userId, bank_name, bank_branch, account_name, account_number)
         VALUES (?, ?, ?, ?, ?)'
    );
    if ($s3) {
        mysqli_stmt_bind_param($s3, 'issss', $user_id, $bank_name, $bank_branch, $account_name, $account_number);
        $ok = mysqli_stmt_execute($s3);
        mysqli_stmt_close($s3);
    } else {
        $ok = false;
    }
}

if ($ok) {
    mysqli_commit($conn);
    $_SESSION['message'] = 'Registration successful! You will be notified when your account is activated.';
    header('Location: ./index.php');
} else {
    mysqli_rollback($conn);
    error_log('[register] failed: ' . mysqli_error($conn));
    $_SESSION['message'] = 'Registration failed. Please try again.';
    header('Location: ./register.php');
}
exit;
