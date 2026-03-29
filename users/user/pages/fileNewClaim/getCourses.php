<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../queries/claim.queries.php';

require_role(['user', 'claimant']);

$department = validated_str($_GET['department'] ?? '');

if ($department === '') {
    json_response([]);
}

$courses = db_get_courses_by_department($conn, $department);
json_response($courses);
