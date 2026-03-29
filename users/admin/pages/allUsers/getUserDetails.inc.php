<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../queries/user.queries.php';

require_role(['admin', 'Admin']);

$userId = validated_int($_GET['userId'] ?? null, 'userId');

$user = db_get_user_by_id($conn, $userId);

if ($user === null) {
    json_response(['error' => 'User not found.'], 404);
}

json_response($user);
