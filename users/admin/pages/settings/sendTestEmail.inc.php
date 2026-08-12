<?php
/*
 * Admin: send a test email to confirm SMTP delivery. JSON endpoint.
 * POST: to (optional — defaults to the admin's own email), csrf_token.
 */
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../../../includes/mailer.php';

require_post();
require_role(array('admin', 'Admin'));
csrf_verify();

$to = validated_str(isset($_POST['to']) ? $_POST['to'] : '');

// Default to the signed-in admin's own address.
if ($to === '') {
    $stmt = mysqli_prepare($conn, 'SELECT email FROM user_details WHERE userId = ?');
    if ($stmt) {
        $uid = current_user_id();
        mysqli_stmt_bind_param($stmt, 'i', $uid);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_row(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if ($row) $to = (string) $row[0];
    }
}

if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    json_response(array('success' => false, 'status' => 'invalid',
        'title' => 'Invalid address', 'message' => 'Enter a valid email address to send the test to.'), 422);
}

$subject = 'RMU Claims — SMTP test';
$body = email_wrap('SMTP test', '<p>This is a test message from the RMU Claims System.</p>'
      . '<p>If you received it, outgoing email (SMTP) is configured correctly.</p>'
      . '<p style="color:#64748b;font-size:12px;">Sent ' . h(date('d/m/Y H:i')) . '.</p>');

email_send($conn, $to, '', $subject, $body, 'test', null);

// Report the precise outcome recorded on the queued row.
$status = 'unknown'; $err = null;
$q = mysqli_prepare($conn,
    'SELECT status, error_msg FROM email_queue WHERE recipient = ? AND related_type = "test"
     ORDER BY email_id DESC LIMIT 1');
if ($q) {
    mysqli_stmt_bind_param($q, 's', $to);
    mysqli_stmt_execute($q);
    $r = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
    mysqli_stmt_close($q);
    if ($r) { $status = $r['status']; $err = $r['error_msg']; }
}

if ($status === 'sent') {
    json_response(array('success' => true, 'status' => 'sent',
        'title' => 'Test email sent', 'message' => 'Delivered to ' . $to . '. Check the inbox (and spam).'));
} elseif ($status === 'skipped') {
    json_response(array('success' => false, 'status' => 'skipped',
        'title' => 'SMTP not configured',
        'message' => 'The message was queued but not delivered because SMTP is not set up. Add the SMTP_* keys to .env, then try again.'));
} else {
    json_response(array('success' => false, 'status' => $status,
        'title' => 'Send failed',
        'message' => 'Could not send: ' . ($err !== null && $err !== '' ? $err : 'check the SMTP settings and server log.')));
}
