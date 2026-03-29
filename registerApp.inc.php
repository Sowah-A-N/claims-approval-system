<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_post();

$first_name   = validated_str($_POST['first_name']   ?? '');
$last_name    = validated_str($_POST['last_name']    ?? '');
$other_names  = validated_str($_POST['other_names']  ?? '');
$phone_number = validated_str($_POST['phone_number'] ?? '');
$gender       = validated_str($_POST['gender']       ?? '');
$email        = validated_str($_POST['email']        ?? '');
$raw_password = $_POST['password'] ?? '';
$department   = validated_str($_POST['department']   ?? '');
$rank         = validated_str($_POST['rank']         ?? '');

if ($first_name === '' || $last_name === '' || $email === '' || $raw_password === '') {
    $_SESSION['message'] = 'Please fill in all required fields.';
    header('Location: ./registerApp.php');
    exit;
}

$password_hash  = password_hash($raw_password, PASSWORD_BCRYPT, ['cost' => 12]);
$role           = 'approver';
$rate           = 0.0;
$account_status = 'disabled';
$date_created   = date('Y-m-d H:i:s');

$conn->begin_transaction();

try {
    $s1 = $conn->prepare(
        'INSERT INTO user_details
             (first_name, last_name, other_names, phone_number, gender, email,
              `password`, department, `role`, `rank`, `rate`, account_status, date_created)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $s1->bind_param(
        'ssssssssssdss',
        $first_name, $last_name, $other_names, $phone_number, $gender,
        $email, $password_hash, $department, $role, $rank,
        $rate, $account_status, $date_created
    );
    $s1->execute();
    $userId = (int) $conn->insert_id;

    $s2 = $conn->prepare(
        'INSERT INTO login_details (userId, email, `password`, `role`, `rank`)
         VALUES (?, ?, ?, ?, ?)'
    );
    $s2->bind_param('issss', $userId, $email, $password_hash, $role, $rank);
    $s2->execute();

    $conn->commit();

    $_SESSION['message'] = 'Registration successful! You will be notified when your account is activated.';
    header('Location: ./index.php');
    exit;

} catch (Exception $e) {
    $conn->rollback();
    error_log('[registerApp] failed: ' . $e->getMessage());
    $_SESSION['message'] = 'Registration failed. Please try again.';
    header('Location: ./registerApp.php');
    exit;
}
