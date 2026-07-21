<?php
/*
 * Archive engine (#2).
 *
 * "Archiving" here means physically MOVING a record out of the primary database
 * into a parallel archive database (<dbname>_archive) — not a soft flag. Because
 * the row leaves the primary table, every normal listing/dropdown stops showing
 * it automatically; no read paths need to change. Restoring moves it back.
 *
 * Both databases live on the same MySQL server, so a single connection can do
 * the cross-schema INSERT ... SELECT + DELETE inside one transaction.
 *
 * All table/column names come from the whitelist registry below or from server
 * metadata (SHOW COLUMNS) — never from user input — so string interpolation of
 * identifiers is safe. Record ids are always bound as parameters.
 */

require_once __DIR__ . '/functions.php';

/* Registry of archivable sections. Add a section here to make it archivable. */
function archive_sections() {
    return array(
        'hr_employees' => array(
            'label'   => 'HR Employees',
            'table'   => 'hr_employees',
            'pk'      => 'id',
            'pk_int'  => true,
            'order'   => 'last_name, first_name',
            'search'  => array('first_name', 'last_name', 'email', 'department', 'staff_id'),
            'columns' => array(
                array('last_name', 'Last name'),
                array('first_name', 'First name'),
                array('email', 'Email'),
                array('department', 'Department'),
                array('rank', 'Rank'),
            ),
        ),
        'classes' => array(
            'label'   => 'Classes',
            'table'   => 'classes',
            'pk'      => 'id',
            'pk_int'  => true,
            'order'   => 'class_code',
            'search'  => array('class_code'),
            'columns' => array(
                array('class_code', 'Class code'),
                array('created_at', 'Added'),
            ),
        ),
        'banks_branches' => array(
            'label'   => 'Banks & Branches',
            'table'   => 'banks_branches',
            'pk'      => 'bank_branch_id',
            'pk_int'  => true,
            'order'   => 'bank_name, bank_branch',
            'search'  => array('bank_name', 'bank_branch', 'branch_code'),
            'columns' => array(
                array('bank_name', 'Bank'),
                array('bank_branch', 'Branch'),
                array('branch_code', 'Branch code'),
            ),
        ),
        'audit_log' => array(
            'label'   => 'Audit Logs',
            'table'   => 'audit_log',
            'pk'      => 'audit_id',
            'pk_int'  => true,
            'order'   => 'created_at DESC, audit_id DESC',
            'search'  => array('action', 'actor_role', 'entity_type'),
            'columns' => array(
                array('created_at', 'When'),
                array('action', 'Action'),
                array('actor_role', 'Role'),
                array('entity_type', 'Entity'),
                array('entity_id', 'Entity id'),
            ),
        ),
    );
}

/* Return the section config or null when the key is not whitelisted. */
function archive_section($key) {
    $s = archive_sections();
    return isset($s[$key]) ? $s[$key] : null;
}

/* Name of the archive database for the currently-connected primary database. */
function archive_db_name($conn) {
    $db = mysqli_fetch_row(mysqli_query($conn, 'SELECT DATABASE()'))[0];
    return $db . '_archive';
}

/* Column names of a primary table, in order (server metadata — safe). */
function archive_columns($conn, $table) {
    $cols = array();
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `" . $table . "`");
    if (!$res) return $cols;
    while ($row = mysqli_fetch_assoc($res)) $cols[] = $row['Field'];
    return $cols;
}

/*
 * Ensure the archive database and one mirror table per section exist. Mirror
 * tables are created with CREATE TABLE ... LIKE (so structure matches), then:
 *   - every non-PRIMARY UNIQUE index is dropped (the archive is a historical
 *     store, so the same class code / email may legitimately recur over time);
 *   - archived_at / archived_by bookkeeping columns are added.
 *
 * Returns true when the archive store is usable, false otherwise (e.g. a
 * least-privilege app user with no rights on the archive database). NEVER
 * throws — callers can degrade gracefully. Idempotent and cheap to call.
 *
 * In least-privilege deployments the app user may lack the server-level CREATE
 * DATABASE privilege, so we only attempt to create the database when it is
 * genuinely absent; a DBA can pre-create it and GRANT access instead (see the
 * migration scripts). Once the schema exists and is granted, the per-table
 * provisioning below runs with ordinary schema privileges.
 */
function archive_ensure_schema($conn) {
    $arch = archive_db_name($conn);
    try {
        $schemaRes = mysqli_query($conn, "SELECT 1 FROM information_schema.SCHEMATA
            WHERE SCHEMA_NAME = '" . mysqli_real_escape_string($conn, $arch) . "' LIMIT 1");
        $dbExists = $schemaRes && mysqli_num_rows($schemaRes) > 0;
        if (!$dbExists) {
            mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `" . $arch . "` DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci");
        }

        foreach (archive_sections() as $cfg) {
            $t = $cfg['table'];
            $exists = mysqli_query($conn, "SELECT 1 FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = '" . mysqli_real_escape_string($conn, $arch) . "'
                  AND TABLE_NAME = '" . mysqli_real_escape_string($conn, $t) . "' LIMIT 1");
            if ($exists && mysqli_num_rows($exists) > 0) continue;

            mysqli_query($conn, "CREATE TABLE `" . $arch . "`.`" . $t . "` LIKE `" . $t . "`");

            // Drop non-primary UNIQUE indexes so archived rows never collide.
            $idx = mysqli_query($conn, "SHOW INDEX FROM `" . $arch . "`.`" . $t . "`");
            $drop = array();
            if ($idx) {
                while ($row = mysqli_fetch_assoc($idx)) {
                    if ($row['Key_name'] !== 'PRIMARY' && (int) $row['Non_unique'] === 0) {
                        $drop[$row['Key_name']] = true;
                    }
                }
            }
            foreach (array_keys($drop) as $keyName) {
                mysqli_query($conn, "ALTER TABLE `" . $arch . "`.`" . $t . "` DROP INDEX `" . $keyName . "`");
            }

            mysqli_query($conn, "ALTER TABLE `" . $arch . "`.`" . $t . "`
                ADD COLUMN `archived_at` DATETIME NULL,
                ADD COLUMN `archived_by` INT NULL");
        }
        return true;
    } catch (Throwable $e) {
        error_log('[archive_ensure_schema] archive store unavailable: ' . $e->getMessage());
        return false;
    }
}

/* Build the bound-type letter for a section's primary key. */
function _archive_pk_type($cfg) {
    return !empty($cfg['pk_int']) ? 'i' : 's';
}

/*
 * Move one record from the primary table into the archive database.
 * Returns true on success, false otherwise.
 */
function archive_move($conn, $key, $id, $archivedBy = null) {
    $cfg = archive_section($key);
    if (!$cfg) return false;
    $arch = archive_db_name($conn);
    $t    = $cfg['table'];
    $pk   = $cfg['pk'];
    $cols = archive_columns($conn, $t);
    if (empty($cols)) return false;
    $colList = '`' . implode('`,`', $cols) . '`';

    $archivedBy = $archivedBy !== null ? (int) $archivedBy : null;

    mysqli_begin_transaction($conn);
    try {
        $ins = mysqli_prepare($conn,
            "INSERT INTO `$arch`.`$t` ($colList, `archived_at`, `archived_by`)
             SELECT $colList, NOW(), ? FROM `$t` WHERE `$pk` = ?");
        if (!$ins) throw new Exception('prepare insert failed');
        $bt = 'i' . _archive_pk_type($cfg);
        mysqli_stmt_bind_param($ins, $bt, $archivedBy, $id);
        if (!mysqli_stmt_execute($ins)) throw new Exception('insert failed');
        $moved = mysqli_stmt_affected_rows($ins);
        mysqli_stmt_close($ins);
        if ($moved < 1) throw new Exception('record not found');

        $del = mysqli_prepare($conn, "DELETE FROM `$t` WHERE `$pk` = ?");
        if (!$del) throw new Exception('prepare delete failed');
        mysqli_stmt_bind_param($del, _archive_pk_type($cfg), $id);
        if (!mysqli_stmt_execute($del)) throw new Exception('delete failed');
        mysqli_stmt_close($del);

        mysqli_commit($conn);
        return true;
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        error_log('[archive_move ' . $key . '] ' . $e->getMessage() . ' :: ' . mysqli_error($conn));
        return false;
    }
}

/* Move a record back from the archive into the primary table. */
function archive_restore($conn, $key, $id) {
    $cfg = archive_section($key);
    if (!$cfg) return false;
    $arch = archive_db_name($conn);
    $t    = $cfg['table'];
    $pk   = $cfg['pk'];
    $cols = archive_columns($conn, $t);           // primary-table columns only
    if (empty($cols)) return false;
    $colList = '`' . implode('`,`', $cols) . '`';

    mysqli_begin_transaction($conn);
    try {
        $ins = mysqli_prepare($conn,
            "INSERT INTO `$t` ($colList) SELECT $colList FROM `$arch`.`$t` WHERE `$pk` = ?");
        if (!$ins) throw new Exception('prepare insert failed');
        mysqli_stmt_bind_param($ins, _archive_pk_type($cfg), $id);
        if (!mysqli_stmt_execute($ins)) throw new Exception('insert failed');
        $moved = mysqli_stmt_affected_rows($ins);
        mysqli_stmt_close($ins);
        if ($moved < 1) throw new Exception('record not found');

        $del = mysqli_prepare($conn, "DELETE FROM `$arch`.`$t` WHERE `$pk` = ?");
        if (!$del) throw new Exception('prepare delete failed');
        mysqli_stmt_bind_param($del, _archive_pk_type($cfg), $id);
        if (!mysqli_stmt_execute($del)) throw new Exception('delete failed');
        mysqli_stmt_close($del);

        mysqli_commit($conn);
        return true;
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        error_log('[archive_restore ' . $key . '] ' . $e->getMessage() . ' :: ' . mysqli_error($conn));
        return false;
    }
}

/* Permanently delete a record from the archive (irreversible). */
function archive_purge($conn, $key, $id) {
    $cfg = archive_section($key);
    if (!$cfg) return false;
    $arch = archive_db_name($conn);
    try {
        $del = mysqli_prepare($conn, "DELETE FROM `$arch`.`{$cfg['table']}` WHERE `{$cfg['pk']}` = ?");
        if (!$del) return false;
        mysqli_stmt_bind_param($del, _archive_pk_type($cfg), $id);
        $ok = mysqli_stmt_execute($del);
        $aff = mysqli_stmt_affected_rows($del);
        mysqli_stmt_close($del);
        return $ok && $aff > 0;
    } catch (Throwable $e) {
        error_log('[archive_purge ' . $key . '] ' . $e->getMessage());
        return false;
    }
}

/*
 * List rows for a section. $from is 'active' (primary DB) or 'archived'
 * (archive DB). Returns array of associative rows limited to display columns
 * plus the pk (and archived_at when archived). $search filters across the
 * configured search columns.
 */
function archive_list($conn, $key, $from = 'active', $search = '', $limit = 500) {
    $cfg = archive_section($key);
    if (!$cfg) return array();
    $archived = ($from === 'archived');
    $tableRef = $archived ? "`" . archive_db_name($conn) . "`.`{$cfg['table']}`" : "`{$cfg['table']}`";

    $select = array('`' . $cfg['pk'] . '` AS __id');
    foreach ($cfg['columns'] as $c) $select[] = '`' . $c[0] . '`';
    if ($archived) $select[] = '`archived_at`';

    $sql = 'SELECT ' . implode(', ', $select) . ' FROM ' . $tableRef;
    $types = ''; $params = array();
    $search = trim((string) $search);
    if ($search !== '' && !empty($cfg['search'])) {
        $like = '%' . $search . '%';
        $ors  = array();
        foreach ($cfg['search'] as $col) { $ors[] = '`' . $col . '` LIKE ?'; $types .= 's'; $params[] = $like; }
        $sql .= ' WHERE ' . implode(' OR ', $ors);
    }
    $sql .= ' ORDER BY ' . ($archived ? '`archived_at` DESC' : $cfg['order']);
    $sql .= ' LIMIT ' . (int) $limit;

    // Archived rows live in the archive DB, which a least-privilege app user
    // may not be able to read — degrade to an empty list rather than throwing.
    try {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) return array();
        if ($types !== '') mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        return $rows;
    } catch (Throwable $e) {
        error_log('[archive_list ' . $key . '/' . $from . '] ' . $e->getMessage());
        return array();
    }
}

/* Count rows in a section's active (primary) or archived (archive DB) store. */
function archive_count($conn, $key, $from = 'active') {
    $cfg = archive_section($key);
    if (!$cfg) return 0;
    $tableRef = ($from === 'archived') ? "`" . archive_db_name($conn) . "`.`{$cfg['table']}`" : "`{$cfg['table']}`";
    try {
        $r = mysqli_query($conn, 'SELECT COUNT(*) FROM ' . $tableRef);
        if (!$r) return 0;
        return (int) mysqli_fetch_row($r)[0];
    } catch (Throwable $e) {
        return 0;
    }
}
