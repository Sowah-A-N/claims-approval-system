<?php
/*
 * Bulk activate / disable user accounts (#2).
 *
 * Activation auto-fills each user's rate from their rank (#3) via
 * db_activate_user(); disabling uses db_set_account_status().
 *
 * Expected POST: userIds[] (int array), status ('active'|'disabled'), csrf_token
 * Returns JSON: { success, changed, message }
 */
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../queries/user.queries.php';

require_post();
require_role(array('admin', 'Admin'));
csrf_verify();

$status = validated_str(isset($_POST['status']) ? $_POST['status'] : '');
if ($status !== 'active' && $status !== 'disabled') {
    json_response(array('success' => false, 'message' => 'Invalid status.'), 400);
}

$ids = isset($_POST['userIds']) && is_array($_POST['userIds']) ? $_POST['userIds'] : array();
if (empty($ids)) {
    json_response(array('success' => false, 'message' => 'No users selected.'), 400);
}

$self    = current_user_id();
$changed = 0;

foreach ($ids as $raw) {
    $uid = filter_var($raw, FILTER_VALIDATE_INT);
    if ($uid === false) continue;
    if ($status === 'disabled' && $uid === $self) continue; // never disable yourself

    $ok = ($status === 'active')
        ? db_activate_user($conn, $uid)
        : db_set_account_status($conn, $uid, 'disabled');

    if ($ok) {
        log_audit($conn, $status === 'active' ? 'user.activate' : 'user.disable', 'user', $uid, 'bulk');
        $changed++;
    }
}

json_response(array(
    'success' => $changed > 0,
    'changed' => $changed,
    'message' => $changed . ' account(s) ' . ($status === 'active' ? 'activated' : 'disabled') . '.',
));
