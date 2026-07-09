<?php
/*
 * Admin archive actions (#2): move records to the archive DB, restore them, or
 * permanently purge archived records. JSON endpoint. Bulk-capable via ids[].
 */
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../../../includes/archive.php';

require_post();
require_role(array('admin', 'Admin'));
csrf_verify();

$section = isset($_POST['section']) ? (string) $_POST['section'] : '';
$action  = isset($_POST['action'])  ? (string) $_POST['action']  : '';
$cfg = archive_section($section);
if (!$cfg) {
    json_response(array('success' => false, 'message' => 'Unknown section.'), 422);
}
if (!in_array($action, array('archive', 'restore', 'purge'), true)) {
    json_response(array('success' => false, 'message' => 'Unknown action.'), 422);
}

// Accept ids[] or a single id.
$ids = array();
if (isset($_POST['ids']) && is_array($_POST['ids'])) {
    $ids = $_POST['ids'];
} elseif (isset($_POST['id'])) {
    $ids = array($_POST['id']);
}
$ids = array_values(array_filter(array_map('trim', array_map('strval', $ids)), function ($v) { return $v !== ''; }));
if (empty($ids)) {
    json_response(array('success' => false, 'message' => 'No records selected.'), 422);
}
if (count($ids) > 2000) {
    json_response(array('success' => false, 'message' => 'Too many records in one request (max 2000).'), 422);
}

archive_ensure_schema($conn);

$ok = 0; $fail = 0;
foreach ($ids as $id) {
    $castId = !empty($cfg['pk_int']) ? (int) $id : $id;
    $done = ($action === 'archive')  ? archive_move($conn, $section, $castId, current_user_id())
          : (($action === 'restore') ? archive_restore($conn, $section, $castId)
                                     : archive_purge($conn, $section, $castId));
    if ($done) $ok++; else $fail++;
}

log_audit($conn, 'archive.' . $action, $cfg['table'], null, $ok . ' ok, ' . $fail . ' failed');

$verb = array('archive' => 'archived', 'restore' => 'restored', 'purge' => 'permanently deleted');
$msg  = $ok . ' record(s) ' . $verb[$action] . ($fail ? ', ' . $fail . ' failed' : '') . '.';
json_response(array('success' => $ok > 0, 'message' => $msg, 'ok' => $ok, 'failed' => $fail));
