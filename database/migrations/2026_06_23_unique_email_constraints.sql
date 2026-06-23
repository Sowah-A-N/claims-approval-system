-- ============================================================================
-- Migration: enforce unique email addresses (#7)
-- Date:      2026-06-23
--
-- The app keeps credentials in login_details and the profile in user_details,
-- both keyed by email. Without a UNIQUE constraint, duplicate registrations can
-- create two accounts for the same email and let the two tables drift apart.
--
-- IMPORTANT: a UNIQUE index cannot be created while duplicate emails exist.
-- Run the diagnostic queries below FIRST and resolve any duplicates, then run
-- the rest. The index creation is idempotent (skipped if already present).
--
-- Apply with:  mysql -u <user> -p <database> < this_file.sql
-- ============================================================================

-- ── Diagnostics: list any existing duplicates (resolve before proceeding) ─────
SELECT email, COUNT(*) AS n FROM login_details GROUP BY email HAVING n > 1;
SELECT email, COUNT(*) AS n FROM user_details  GROUP BY email HAVING n > 1;

-- ── Idempotent unique-index creation ─────────────────────────────────────────
DELIMITER //

DROP PROCEDURE IF EXISTS rmu_add_unique //
CREATE PROCEDURE rmu_add_unique(
    IN p_table VARCHAR(64),
    IN p_index VARCHAR(64),
    IN p_col   VARCHAR(64)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name   = p_table
          AND index_name   = p_index
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD UNIQUE INDEX `',
                          p_index, '` (`', p_col, '`)');
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //

DELIMITER ;

CALL rmu_add_unique('login_details', 'uq_login_details_email', 'email');
CALL rmu_add_unique('user_details',  'uq_user_details_email',  'email');

DROP PROCEDURE IF EXISTS rmu_add_unique;
