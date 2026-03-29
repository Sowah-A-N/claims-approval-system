<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../queries/user.queries.php';

require_post();
require_role(['admin', 'Admin']);

$userId = validated_int($_POST['userId'] ?? null, 'userId');

$updated = db_set_account_status($conn, $userId, 'disabled');

if ($updated) {
    json_response(['success' => true, 'message' => 'Account disabled successfully.']);
} else {
    json_response(['success' => false, 'message' => 'User not found or status unchanged.'], 404);
}
