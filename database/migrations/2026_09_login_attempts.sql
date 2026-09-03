-- Login rate-limiting table (includes/auth.php).
-- Previously this schema lived only as a comment in auth.php, so on any DB where
-- it was never hand-created the limiter silently disabled itself (fail-open).
-- Ship it as a migration so throttling is actually active in every environment.
CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address   VARCHAR(45)  NOT NULL,
    attempted_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
