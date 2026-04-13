<?php
/*
 * Central database connection.
 *
 * Provides a single $conn (procedural mysqli) loaded from .env.
 * Include this file instead of any role-specific conn.inc.php.
 *
 * Required .env keys: DB_HOST, DB_NAME, DB_USER, DB_PASS
 */

if (isset($conn)) {
    return; // Already connected — safe to include multiple times.
}

// Simple line-by-line .env loader — no third-party library required.
$_env_file = dirname(__DIR__) . '/.env';
if (file_exists($_env_file)) {
    $lines = file($_env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        list($key, $val) = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val);
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key]  = $val;
            putenv($key . '=' . $val);
        }
    }
}
unset($_env_file, $lines, $line, $key, $val);

$conn = mysqli_connect(
    isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : 'localhost',
    isset($_ENV['DB_USER']) ? $_ENV['DB_USER'] : '',
    isset($_ENV['DB_PASS']) ? $_ENV['DB_PASS'] : '',
    isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : ''
);

if (!$conn) {
    error_log('[DB] Connection failed: ' . mysqli_connect_error());
    http_response_code(503);
    exit('Service temporarily unavailable.');
}

mysqli_set_charset($conn, 'utf8mb4');
