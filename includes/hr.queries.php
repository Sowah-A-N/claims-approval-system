<?php
/*
 * HR employee register (#1).
 *
 * An HR user maintains a list of bona-fide employees. Registration checks the
 * registrant's email against this list and auto-activates matching accounts.
 * Email is the match key and is always compared case-insensitively.
 */

require_once __DIR__ . '/functions.php';

/* Lower-case + trim an email for consistent storage and matching. */
function hr_normalize_email($email) {
    return strtolower(trim((string) $email));
}

/*
 * True when $email appears in the HR employee register. Fail-safe: on any
 * query error it returns false so a glitch can never wrongly auto-activate.
 */
function db_email_in_hr_list($conn, $email) {
    $email = hr_normalize_email($email);
    if ($email === '') return false;
    $stmt = mysqli_prepare($conn, 'SELECT 1 FROM hr_employees WHERE LOWER(email) = ? LIMIT 1');
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $found = mysqli_fetch_row(mysqli_stmt_get_result($stmt)) !== null;
    mysqli_stmt_close($stmt);
    return $found;
}

/* Total employees on the register. */
function db_hr_count($conn) {
    $r = mysqli_query($conn, 'SELECT COUNT(*) FROM hr_employees');
    if (!$r) return 0;
    return (int) mysqli_fetch_row($r)[0];
}

/* How many registered accounts already match an HR employee (by email). */
function db_hr_linked_accounts($conn) {
    $r = mysqli_query($conn,
        'SELECT COUNT(*) FROM hr_employees he
         JOIN user_details ud ON LOWER(ud.email) = LOWER(he.email)');
    if (!$r) return 0;
    return (int) mysqli_fetch_row($r)[0];
}

/*
 * List employees, optionally filtered by a free-text search across name,
 * email, department and staff id. Each row is annotated with `registered`
 * (1 when an account already exists for that email).
 */
function db_hr_list($conn, $search = '') {
    $sql = "SELECT he.*,
                   (SELECT COUNT(*) FROM user_details ud
                    WHERE LOWER(ud.email) = LOWER(he.email)) AS registered
            FROM hr_employees he";
    $params = array(); $types = '';
    $search = trim((string) $search);
    if ($search !== '') {
        $like = '%' . $search . '%';
        $sql .= " WHERE he.first_name LIKE ? OR he.last_name LIKE ? OR he.email LIKE ?
                        OR he.department LIKE ? OR he.staff_id LIKE ?";
        $types = 'sssss';
        $params = array($like, $like, $like, $like, $like);
    }
    $sql .= ' ORDER BY he.last_name, he.first_name';
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return array();
    if ($types !== '') mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

/*
 * Insert or update one employee (keyed on email). Returns 'inserted',
 * 'updated', or false on error. first_name, last_name and a valid email are
 * required.
 */
function db_hr_upsert($conn, $data, $addedBy = null) {
    $email = hr_normalize_email($data['email'] ?? '');
    $first = trim((string) ($data['first_name'] ?? ''));
    $last  = trim((string) ($data['last_name'] ?? ''));
    if ($first === '' || $last === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $other = trim((string) ($data['other_names']  ?? ''));
    $staff = trim((string) ($data['staff_id']     ?? ''));
    $phone = trim((string) ($data['phone_number'] ?? ''));
    $gender= trim((string) ($data['gender']       ?? ''));
    $dept  = trim((string) ($data['department']   ?? ''));
    $rank  = trim((string) ($data['rank']         ?? ''));

    $exists = db_email_in_hr_list($conn, $email);
    $sql = 'INSERT INTO hr_employees
              (staff_id, first_name, last_name, other_names, email, phone_number, gender, department, `rank`, added_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
              staff_id=VALUES(staff_id), first_name=VALUES(first_name), last_name=VALUES(last_name),
              other_names=VALUES(other_names), phone_number=VALUES(phone_number), gender=VALUES(gender),
              department=VALUES(department), `rank`=VALUES(`rank`)';
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;
    $addedBy = $addedBy !== null ? (int) $addedBy : null;
    mysqli_stmt_bind_param($stmt, 'sssssssssi',
        $staff, $first, $last, $other, $email, $phone, $gender, $dept, $rank, $addedBy);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    if (!$ok) return false;
    return $exists ? 'updated' : 'inserted';
}

/* Delete one employee by id. */
function db_hr_delete($conn, $id) {
    $stmt = mysqli_prepare($conn, 'DELETE FROM hr_employees WHERE id = ?');
    if (!$stmt) return false;
    $id = (int) $id;
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

/*
 * Import employees from an uploaded CSV file. Expected header (case-insensitive,
 * order-independent): first_name,last_name,other_names,email,phone_number,
 * gender,department,rank,staff_id. Only first_name,last_name,email are required.
 * Returns array(inserted, updated, skipped, errors[]).
 */
function hr_import_csv($conn, $filePath, $addedBy = null) {
    $result = array('inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => array());
    $fh = @fopen($filePath, 'r');
    if (!$fh) { $result['errors'][] = 'Could not open the uploaded file.'; return $result; }

    $header = fgetcsv($fh, 0, ',', '"', '');
    if ($header === false) { fclose($fh); $result['errors'][] = 'The file is empty.'; return $result; }
    // Strip a UTF-8 BOM from the first header cell if present.
    if (isset($header[0])) $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
    $map = array();
    foreach ($header as $i => $col) {
        $key = strtolower(trim(str_replace(array(' ', '-'), '_', (string) $col)));
        $map[$key] = $i;
    }
    foreach (array('first_name', 'last_name', 'email') as $req) {
        if (!isset($map[$req])) {
            fclose($fh);
            $result['errors'][] = 'Missing required column: ' . $req;
            return $result;
        }
    }

    $line = 1;
    while (($row = fgetcsv($fh, 0, ',', '"', '')) !== false) {
        $line++;
        if (count(array_filter($row, fn($c) => trim((string) $c) !== '')) === 0) continue; // blank line
        $get = function ($k) use ($row, $map) {
            return isset($map[$k], $row[$map[$k]]) ? $row[$map[$k]] : '';
        };
        $data = array(
            'first_name'   => $get('first_name'),
            'last_name'    => $get('last_name'),
            'other_names'  => $get('other_names'),
            'email'        => $get('email'),
            'phone_number' => $get('phone_number'),
            'gender'       => $get('gender'),
            'department'   => $get('department'),
            'rank'         => $get('rank'),
            'staff_id'     => $get('staff_id'),
        );
        $outcome = db_hr_upsert($conn, $data, $addedBy);
        if ($outcome === 'inserted')      $result['inserted']++;
        elseif ($outcome === 'updated')   $result['updated']++;
        else {
            $result['skipped']++;
            if (count($result['errors']) < 12) {
                $result['errors'][] = 'Row ' . $line . ': missing name or invalid email.';
            }
        }
    }
    fclose($fh);
    return $result;
}
