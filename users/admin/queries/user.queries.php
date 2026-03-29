<?php
/*
 * Data-layer functions for admin user-management operations.
 *
 * No HTML, no $_POST, no session access lives here.
 * The password column is never returned — callers receive no credentials.
 */


// ── User listing ──────────────────────────────────────────────────────────────

/*
 * Return all users ordered by creation date (newest first).
 */
function db_get_all_users($conn) {
    $result = mysqli_query($conn,
        "SELECT userId,
                CONCAT(first_name, ' ', last_name) AS full_name,
                email,
                department,
                phone_number,
                role,
                account_status,
                date_created
         FROM user_details
         ORDER BY date_created DESC"
    );
    if (!$result) return array();
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

/*
 * Return a single user's details (without password), or null if not found.
 */
function db_get_user_by_id($conn, $userId) {
    $stmt = mysqli_prepare($conn,
        "SELECT userId, first_name, last_name, other_names, phone_number,
                gender, email, faculty, department, role, `rank`, rate,
                account_status, date_created,
                CONCAT(first_name, ' ', last_name) AS full_name
         FROM user_details
         WHERE userId = ?"
    );
    if (!$stmt) return null;
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row    = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? $row : null;
}


// ── Account status ────────────────────────────────────────────────────────────

/*
 * Set account_status to 'active' or 'disabled'.
 * Returns true if a row was updated, false otherwise.
 */
function db_set_account_status($conn, $userId, $status) {
    $allowed = array('active', 'disabled');
    if (!in_array($status, $allowed)) {
        return false;
    }
    $stmt = mysqli_prepare($conn, 'UPDATE user_details SET account_status = ? WHERE userId = ?');
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 'si', $status, $userId);
    mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $affected > 0;
}
