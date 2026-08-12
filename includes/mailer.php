<?php
/*
 * Transactional email for the RMU Claims System.
 *
 * Every message is first written to the `email_queue` table, then — if SMTP is
 * configured (SMTP_* in .env) AND PHPMailer is installed — sent immediately and
 * the row marked sent/failed. If SMTP isn't configured, the row is marked
 * 'skipped' and logged. Sending NEVER throws into the caller: email must never
 * break registration, approval, payment, etc.
 *
 * Required .env keys to actually send (see .env.example):
 *   SMTP_HOST, SMTP_PORT, SMTP_ENCRYPTION(tls|ssl|none), SMTP_USERNAME,
 *   SMTP_PASSWORD, SMTP_FROM, SMTP_FROM_NAME, SMTP_AUTH(true|false)
 */

require_once __DIR__ . '/db.php';        // loads .env into $_ENV and gives $conn
require_once __DIR__ . '/functions.php';

/* Read SMTP settings from the environment (.env). */
function email_smtp_config() {
    $g = function ($k, $d = '') { return isset($_ENV[$k]) && $_ENV[$k] !== '' ? $_ENV[$k] : $d; };
    return array(
        'host'      => $g('SMTP_HOST'),
        'port'      => (int) $g('SMTP_PORT', '0'),
        'enc'       => strtolower($g('SMTP_ENCRYPTION', 'tls')),
        'user'      => $g('SMTP_USERNAME'),
        'pass'      => $g('SMTP_PASSWORD'),
        'from'      => $g('SMTP_FROM', $g('SMTP_USERNAME')),
        'from_name' => $g('SMTP_FROM_NAME', 'RMU Claims System'),
        'auth'      => strtolower($g('SMTP_AUTH', 'true')) !== 'false',
    );
}

/* True when enough SMTP config exists to attempt delivery. */
function email_is_configured() {
    $c = email_smtp_config();
    return $c['host'] !== '' && $c['port'] > 0 && $c['from'] !== '';
}

/* Load PHPMailer from Composer if available. Returns true when usable. */
function email_phpmailer_available() {
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) return true;
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (is_file($autoload)) {
        require_once $autoload;
        return class_exists('PHPMailer\\PHPMailer\\PHPMailer');
    }
    return false;
}

/* Wrap body content in a simple, email-client-safe branded shell. */
function email_wrap($heading, $innerHtml) {
    $year = date('Y');
    return
    '<!DOCTYPE html><html><head><meta charset="utf-8">'
    . '<meta name="viewport" content="width=device-width,initial-scale=1"></head>'
    . '<body style="margin:0;padding:0;background:#f4fafc;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">'
    . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4fafc;padding:24px 0;"><tr><td align="center">'
    . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">'
    . '<tr><td style="background:#023047;padding:20px 28px;">'
    . '<div style="font-size:18px;font-weight:bold;color:#ffffff;">Regional Maritime University</div>'
    . '<div style="font-size:13px;color:#9fc7d6;">Claims System</div></td></tr>'
    . '<tr><td style="padding:26px 28px;">'
    . '<h1 style="margin:0 0 14px;font-size:19px;color:#023047;">' . h($heading) . '</h1>'
    . '<div style="font-size:14px;line-height:1.6;color:#1e293b;">' . $innerHtml . '</div>'
    . '</td></tr>'
    . '<tr><td style="padding:16px 28px;border-top:1px solid #e2e8f0;font-size:12px;color:#64748b;">'
    . 'This is an automated message from the RMU Claims System &middot; &copy; ' . $year . ' Regional Maritime University.'
    . '</td></tr>'
    . '</table></td></tr></table></body></html>';
}

/* Insert a queued row; returns email_id or 0. */
function _email_queue_insert($conn, $recipient, $subject, $bodyHtml, $relType, $relId) {
    try {
        $stmt = mysqli_prepare($conn,
            'INSERT INTO email_queue (recipient, subject, body_html, status, related_type, related_id)
             VALUES (?, ?, ?, ?, ?, ?)');
        if (!$stmt) return 0;
        $status = 'queued';
        $relId  = $relId !== null ? (int) $relId : null;
        mysqli_stmt_bind_param($stmt, 'sssssi', $recipient, $subject, $bodyHtml, $status, $relType, $relId);
        mysqli_stmt_execute($stmt);
        $id = (int) mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        return $id;
    } catch (Throwable $e) {
        error_log('[mailer] queue insert failed: ' . $e->getMessage());
        return 0;
    }
}

/* Update a queued row's status/outcome. */
function _email_queue_mark($conn, $id, $status, $error = null) {
    if ($id <= 0) return;
    try {
        $sent = ($status === 'sent') ? date('Y-m-d H:i:s') : null;
        $stmt = mysqli_prepare($conn,
            'UPDATE email_queue
                SET status = ?, attempts = attempts + 1, last_attempt = NOW(),
                    sent_at = ?, error_msg = ?
              WHERE email_id = ?');
        if (!$stmt) return;
        $err = $error !== null ? substr($error, 0, 500) : null;
        mysqli_stmt_bind_param($stmt, 'sssi', $status, $sent, $err, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } catch (Throwable $e) {
        error_log('[mailer] queue mark failed: ' . $e->getMessage());
    }
}

/*
 * Queue + (best-effort) send one email. Returns true only when actually sent.
 * Never throws.
 */
function email_send($conn, $toEmail, $toName, $subject, $bodyHtml, $relType = null, $relId = null) {
    $toEmail = trim((string) $toEmail);
    if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $qid = _email_queue_insert($conn, $toEmail, $subject, $bodyHtml, $relType, $relId);

    if (!email_is_configured()) {
        _email_queue_mark($conn, $qid, 'skipped', 'SMTP not configured');
        error_log('[mailer] SMTP not configured — queued/skipped: ' . $subject . ' -> ' . $toEmail);
        return false;
    }
    if (!email_phpmailer_available()) {
        // Configured but library missing — leave queued for a later run.
        _email_queue_mark($conn, $qid, 'queued', 'PHPMailer not installed');
        error_log('[mailer] PHPMailer missing — left queued: ' . $subject . ' -> ' . $toEmail);
        return false;
    }

    $c = email_smtp_config();
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $c['host'];
        $mail->Port       = $c['port'];
        $mail->SMTPAuth   = $c['auth'];
        if ($c['auth']) { $mail->Username = $c['user']; $mail->Password = $c['pass']; }
        if ($c['enc'] === 'ssl')      $mail->SMTPSecure = 'ssl';
        elseif ($c['enc'] === 'tls')  $mail->SMTPSecure = 'tls';
        else                          $mail->SMTPSecure = false;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($c['from'], $c['from_name']);
        $mail->addAddress($toEmail, $toName !== '' ? $toName : $toEmail);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;
        $mail->AltBody = trim(preg_replace('/\s+/', ' ', strip_tags($bodyHtml)));
        $mail->send();
        _email_queue_mark($conn, $qid, 'sent');
        return true;
    } catch (Throwable $e) {
        _email_queue_mark($conn, $qid, 'failed', $e->getMessage());
        error_log('[mailer] send failed to ' . $toEmail . ': ' . $e->getMessage());
        return false;
    }
}

/*
 * Build subject + body for a lifecycle event and send it.
 * $ctx may contain: name, claim_id, stage, amount, reason.
 */
function email_notify($conn, $event, $toEmail, $toName, $ctx = array()) {
    $name = isset($ctx['name']) && $ctx['name'] !== '' ? $ctx['name'] : ($toName !== '' ? $toName : 'there');
    $greet = '<p>Dear ' . h($name) . ',</p>';
    $signoff = '<p style="margin-top:18px;">Regards,<br>RMU Claims System</p>';
    $cid = isset($ctx['claim_id']) ? (int) $ctx['claim_id'] : null;
    $claimRef = $cid ? (' (Claim #' . $cid . ')') : '';

    switch ($event) {
        case 'registration_received':
            $subject = 'RMU Claims — registration received';
            $body = $greet . '<p>We\'ve received your registration. Your account is pending review and '
                  . 'you\'ll be notified once it\'s activated by an administrator.</p>' . $signoff;
            $head = 'Registration received';
            break;
        case 'account_active':
            $subject = 'RMU Claims — your account is active';
            $body = $greet . '<p>Your account has been verified and is <strong>active</strong>. '
                  . 'You can now sign in and file claims.</p>' . $signoff;
            $head = 'Your account is active';
            break;
        case 'account_activated':
            $subject = 'RMU Claims — your account has been activated';
            $body = $greet . '<p>Good news — an administrator has <strong>activated</strong> your account. '
                  . 'You can now sign in and file claims.</p>' . $signoff;
            $head = 'Account activated';
            break;
        case 'claim_submitted':
            $subject = 'RMU Claims — claim submitted' . $claimRef;
            $body = $greet . '<p>Your claim' . $claimRef . ' has been submitted and sent for approval. '
                  . 'You\'ll be notified as it progresses.</p>' . $signoff;
            $head = 'Claim submitted';
            break;
        case 'claim_approved':
            $subject = 'RMU Claims — claim approved' . $claimRef;
            $body = $greet . '<p>Your claim' . $claimRef . ' has been <strong>approved</strong>'
                  . (isset($ctx['stage']) ? ' at stage ' . (int) $ctx['stage'] : '') . '.</p>' . $signoff;
            $head = 'Claim approved';
            break;
        case 'claim_flagged':
            $subject = 'RMU Claims — claim needs changes' . $claimRef;
            $reason = isset($ctx['reason']) && $ctx['reason'] !== '' ? '<p>Note: ' . h($ctx['reason']) . '</p>' : '';
            $body = $greet . '<p>Your claim' . $claimRef . ' has been <strong>flagged</strong> and returned for changes. '
                  . 'Please review it in My Claims, correct the issue, and resubmit.</p>' . $reason . $signoff;
            $head = 'Claim needs changes';
            break;
        case 'claim_paid':
            $subject = 'RMU Claims — claim paid' . $claimRef;
            $amt = isset($ctx['amount']) ? ' (GH&cent; ' . h(number_format((float) $ctx['amount'], 2)) . ')' : '';
            $body = $greet . '<p>Your claim' . $claimRef . ' has been marked <strong>paid</strong>' . $amt . '. '
                  . 'Thank you.</p>' . $signoff;
            $head = 'Claim paid';
            break;
        default:
            return false;
    }
    return email_send($conn, $toEmail, $name, $subject, email_wrap($head, $body), 'user', $cid);
}

/* Look up a user's email + name by id and notify. */
function email_notify_user($conn, $userId, $event, $ctx = array()) {
    try {
        $stmt = mysqli_prepare($conn,
            'SELECT email, CONCAT(first_name, " ", last_name) AS full_name FROM user_details WHERE userId = ?');
        if (!$stmt) return false;
        $userId = (int) $userId;
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$row || empty($row['email'])) return false;
        if (!isset($ctx['name'])) $ctx['name'] = $row['full_name'];
        return email_notify($conn, $event, $row['email'], $row['full_name'], $ctx);
    } catch (Throwable $e) {
        error_log('[mailer] notify_user failed: ' . $e->getMessage());
        return false;
    }
}

/* Notify the claimant who owns a claim. */
function email_notify_claim_owner($conn, $claimId, $event, $ctx = array()) {
    try {
        $stmt = mysqli_prepare($conn,
            'SELECT ud.userId, ud.email, CONCAT(ud.first_name, " ", ud.last_name) AS full_name
             FROM claim_details cd JOIN user_details ud ON cd.userId = ud.userId
             WHERE cd.claimId = ?');
        if (!$stmt) return false;
        $claimId = (int) $claimId;
        mysqli_stmt_bind_param($stmt, 'i', $claimId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$row || empty($row['email'])) return false;
        $ctx['claim_id'] = $claimId;
        return email_notify($conn, $event, $row['email'], $row['full_name'], $ctx);
    } catch (Throwable $e) {
        error_log('[mailer] notify_claim_owner failed: ' . $e->getMessage());
        return false;
    }
}
