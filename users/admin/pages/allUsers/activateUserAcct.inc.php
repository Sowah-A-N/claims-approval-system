<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../queries/user.queries.php';
require_once __DIR__ . '/../../../../includes/mailer.php';

require_post();
require_role(array('admin', 'Admin'));
csrf_verify();

$user_id = validated_int(isset($_POST['userId']) ? $_POST['userId'] : null, 'userId');

// Activates and auto-fills the rate from the user's rank if not yet set (#3).
$updated = db_activate_user($conn, $user_id);

if ($updated) {
    log_audit($conn, 'user.activate', 'user', $user_id);
    email_notify_user($conn, $user_id, 'account_activated');
    json_response(array('success' => true, 'message' => 'Account activated successfully.'));
} else {
    json_response(array('success' => false, 'message' => 'User not found or status unchanged.'), 404);
}
