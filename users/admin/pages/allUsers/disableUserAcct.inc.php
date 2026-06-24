<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../queries/user.queries.php';

require_post();
require_role(array('admin', 'Admin'));
csrf_verify();

$user_id = validated_int(isset($_POST['userId']) ? $_POST['userId'] : null, 'userId');

$updated = db_set_account_status($conn, $user_id, 'disabled');

if ($updated) {
    log_audit($conn, 'user.disable', 'user', $user_id);
    json_response(array('success' => true, 'message' => 'Account disabled successfully.'));
} else {
    json_response(array('success' => false, 'message' => 'User not found or status unchanged.'), 404);
}
