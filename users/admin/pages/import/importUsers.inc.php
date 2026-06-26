<?php
/*
 * Bulk user import from CSV (#1).
 *
 * Expected header row (case-insensitive, order-independent):
 *   first_name, last_name, other_names, phone_number, gender, email,
 *   faculty, department, rank
 *
 * Each imported account is created as a DISABLED claimant with a generated
 * temporary password and a rate auto-filled from its rank (#3). The temp
 * passwords are returned so the admin can distribute them (no email needed).
 *
 * Returns JSON: { success, created:[{email,name,temp_password}], skipped:[{row,email,reason}], message }
 */
require_once __DIR__ . '/../../../../includes/auth.php';
require_once __DIR__ . '/../../../../includes/db.php';
require_once __DIR__ . '/../../../../includes/functions.php';
require_once __DIR__ . '/../../queries/user.queries.php';

require_post();
require_role(array('admin', 'Admin'));
csrf_verify();

if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
    json_response(array('success' => false, 'message' => 'No CSV file uploaded or upload failed.'), 400);
}
if ($_FILES['csv']['size'] > 2 * 1024 * 1024) {
    json_response(array('success' => false, 'message' => 'File too large (max 2 MB).'), 400);
}

$fh = fopen($_FILES['csv']['tmp_name'], 'r');
if (!$fh) {
    json_response(array('success' => false, 'message' => 'Could not read the uploaded file.'), 500);
}

// Map header names → column index.
$header = fgetcsv($fh, 0, ',', '"', '');
if (!$header) {
    fclose($fh);
    json_response(array('success' => false, 'message' => 'The CSV is empty.'), 400);
}
$col = array();
foreach ($header as $i => $name) {
    $col[strtolower(trim($name))] = $i;
}
$required = array('first_name', 'last_name', 'email', 'rank');
foreach ($required as $r) {
    if (!isset($col[$r])) {
        fclose($fh);
        json_response(array('success' => false,
            'message' => 'Missing required column: ' . $r . '.'), 400);
    }
}
function cell($row, $col, $key) {
    return isset($col[$key], $row[$col[$key]]) ? trim($row[$col[$key]]) : '';
}

$created = array();
$skipped = array();
$line    = 1; // header consumed
$MAX     = 500;

$dup_stmt = mysqli_prepare($conn, 'SELECT 1 FROM login_details WHERE email = ? LIMIT 1');

while (($row = fgetcsv($fh, 0, ',', '"', '')) !== false) {
    $line++;
    if ($line - 1 > $MAX) {
        $skipped[] = array('row' => $line, 'email' => '', 'reason' => 'Row limit (' . $MAX . ') reached.');
        break;
    }
    if (count(array_filter($row, function ($v) { return trim($v) !== ''; })) === 0) {
        continue; // blank line
    }

    $first = validated_str(cell($row, $col, 'first_name'), 25);
    $last  = validated_str(cell($row, $col, 'last_name'), 35);
    $email = validated_str(cell($row, $col, 'email'), 55);
    $rank  = validated_str(cell($row, $col, 'rank'), 25);
    $other = validated_str(cell($row, $col, 'other_names'), 25);
    $phone = validated_str(cell($row, $col, 'phone_number'), 15);
    $gen   = validated_str(cell($row, $col, 'gender'), 6);
    $fac   = validated_str(cell($row, $col, 'faculty'), 75);
    $dept  = validated_str(cell($row, $col, 'department'), 75);

    if ($first === '' || $last === '' || $email === '' || $rank === '') {
        $skipped[] = array('row' => $line, 'email' => $email, 'reason' => 'Missing required field.');
        continue;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $skipped[] = array('row' => $line, 'email' => $email, 'reason' => 'Invalid email.');
        continue;
    }
    mysqli_stmt_bind_param($dup_stmt, 's', $email);
    mysqli_stmt_execute($dup_stmt);
    if (mysqli_fetch_row(mysqli_stmt_get_result($dup_stmt)) !== null) {
        $skipped[] = array('row' => $line, 'email' => $email, 'reason' => 'Email already exists.');
        continue;
    }

    $temp_password = bin2hex(random_bytes(5)); // 10-char temporary password
    $hash          = password_hash($temp_password, PASSWORD_BCRYPT, array('cost' => 12));
    $role          = 'claimant';
    $status        = 'disabled';
    $rate          = db_rate_for_rank($conn, $rank);
    if ($rate === null) $rate = 0.0;
    $now           = date('Y-m-d H:i:s');

    mysqli_begin_transaction($conn);
    $ok = true;

    $s1 = mysqli_prepare($conn,
        'INSERT INTO user_details
            (first_name, last_name, other_names, phone_number, gender, email,
             `password`, faculty, department, `role`, `rank`, rate, account_status, date_created)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    if ($s1) {
        mysqli_stmt_bind_param($s1, 'sssssssssssdss',
            $first, $last, $other, $phone, $gen, $email, $hash, $fac, $dept,
            $role, $rank, $rate, $status, $now);
        $ok = mysqli_stmt_execute($s1);
        $new_id = $ok ? (int) mysqli_insert_id($conn) : 0;
        mysqli_stmt_close($s1);
    } else { $ok = false; }

    if ($ok) {
        $s2 = mysqli_prepare($conn,
            'INSERT INTO login_details (userId, email, `password`, `role`, `rank`) VALUES (?, ?, ?, ?, ?)');
        if ($s2) {
            mysqli_stmt_bind_param($s2, 'issss', $new_id, $email, $hash, $role, $rank);
            $ok = mysqli_stmt_execute($s2);
            mysqli_stmt_close($s2);
        } else { $ok = false; }
    }

    if ($ok) {
        mysqli_commit($conn);
        $created[] = array(
            'email'         => $email,
            'name'          => $first . ' ' . $last,
            'temp_password' => $temp_password,
        );
    } else {
        mysqli_rollback($conn);
        $skipped[] = array('row' => $line, 'email' => $email, 'reason' => 'Database error.');
    }
}
mysqli_stmt_close($dup_stmt);
fclose($fh);

if (count($created) > 0) {
    log_audit($conn, 'user.import', 'user', null, count($created) . ' account(s) imported');
}

json_response(array(
    'success' => count($created) > 0,
    'created' => $created,
    'skipped' => $skipped,
    'message' => count($created) . ' account(s) created, ' . count($skipped) . ' skipped.',
));
