<?php
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../queries/claim.queries.php';

require_role(array('user', 'claimant'));

$department = validated_str(isset($_GET['department']) ? $_GET['department'] : '');

if ($department === '') {
    json_response(array());
}

json_response(db_get_programmes_by_department($conn, $department));
