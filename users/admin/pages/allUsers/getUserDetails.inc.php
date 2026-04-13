<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../queries/user.queries.php';

require_role(array('admin', 'Admin'));

$user_id = validated_int(isset($_GET['userId']) ? $_GET['userId'] : null, 'userId');

$user = db_get_user_by_id($conn, $user_id);

if ($user === null) {
    json_response(array('error' => 'User not found.'), 404);
}

json_response($user);
