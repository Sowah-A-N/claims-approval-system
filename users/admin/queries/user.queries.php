<?php
declare(strict_types=1);

/**
 * Data-layer functions for admin user-management operations.
 *
 * No HTML, no $_POST, no session access lives here.
 */


// ── User listing ───────────────────────────────────────────────────────────────

/**
 * Return all users ordered by creation date (newest first).
 * Excludes the password column — never expose credentials to the view layer.
 *
 * @return array<int, array<string, mixed>>
 */
function db_get_all_users(mysqli $conn): array
{
    $result = $conn->query(
        'SELECT userId,
                CONCAT(first_name, \' \', last_name) AS full_name,
                email,
                department,
                phone_number,
                role,
                account_status,
                date_created
         FROM user_details
         ORDER BY date_created DESC'
    );
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Return a single user's details (without password), or null if not found.
 */
function db_get_user_by_id(mysqli $conn, int $userId): ?array
{
    $stmt = $conn->prepare(
        'SELECT userId, first_name, last_name, other_names, phone_number,
                gender, email, faculty, department, role, `rank`, rate,
                account_status, date_created,
                CONCAT(first_name, \' \', last_name) AS full_name
         FROM user_details
         WHERE userId = ?'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}


// ── Account status ─────────────────────────────────────────────────────────────

/**
 * Set a user's account_status to 'active' or 'disabled'.
 * Returns true if a row was actually updated.
 *
 * @param 'active'|'disabled' $status
 */
function db_set_account_status(mysqli $conn, int $userId, string $status): bool
{
    $allowed = ['active', 'disabled'];
    if (!in_array($status, $allowed, true)) {
        return false;
    }
    $stmt = $conn->prepare('UPDATE user_details SET account_status = ? WHERE userId = ?');
    $stmt->bind_param('si', $status, $userId);
    $stmt->execute();
    return $stmt->affected_rows > 0;
}
