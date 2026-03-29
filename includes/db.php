<?php
declare(strict_types=1);

/**
 * Central database connection.
 *
 * Provides a single $conn (mysqli) instance loaded from environment variables.
 * Include this file instead of any role-specific conn.inc.php.
 *
 * Required .env keys: DB_HOST, DB_NAME, DB_USER, DB_PASS
 */

if (isset($conn)) {
    return; // Already connected — safe to include multiple times.
}

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad(); // safeLoad() does not throw if .env is missing (CI/prod may use real env vars).

$conn = new mysqli(
    $_ENV['DB_HOST'] ?? 'localhost',
    $_ENV['DB_USER'] ?? '',
    $_ENV['DB_PASS'] ?? '',
    $_ENV['DB_NAME'] ?? ''
);

if ($conn->connect_error) {
    error_log('[DB] Connection failed: ' . $conn->connect_error);
    http_response_code(503);
    exit('Service temporarily unavailable.');
}

$conn->set_charset('utf8mb4');
