<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_post();

// Ensure login_details.stage column exists (added after initial schema creation).
$col = mysqli_query($conn,
    "SELECT COUNT(*) AS n FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'login_details' AND COLUMN_NAME = 'stage'");
if ($col && mysqli_fetch_assoc($col)['n'] == 0) {
    mysqli_query($conn, 'ALTER TABLE login_details ADD COLUMN stage INT NOT NULL DEFAULT 0');
}

// Ensure user_details.faculty column exists.
$col2 = mysqli_query($conn,
    "SELECT COUNT(*) AS n FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_details' AND COLUMN_NAME = 'faculty'");
if ($col2 && mysqli_fetch_assoc($col2)['n'] == 0) {
    mysqli_query($conn, "ALTER TABLE user_details ADD COLUMN faculty VARCHAR(255) NOT NULL DEFAULT ''");
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

$password_hash  = password_hash($raw_password, PASSWORD_BCRYPT, array('cost' => 12));
$role           = 'approver';
$rate           = 0.0;
$account_status = 'disabled';
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
    $_SESSION['message'] = 'Registration successful! You will be notified when your account is activated.';
    header('Location: ./index.php');
} else {
    mysqli_rollback($conn);
    error_log('[registerApp] failed: ' . mysqli_error($conn));
    $_SESSION['message'] = 'Registration failed. Please try again.';
    header('Location: ./registerApp.php');
}
exit;
