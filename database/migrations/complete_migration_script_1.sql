-- ============================================================================
-- complete_migration_script_1.sql
-- Consolidated schema migration for the RMU Claims Approval System.
--
-- Combines every DB change from this work cycle into one script:
--   1. New tables ............. classes, holidays
--   2. New columns ............ claim_details (paid, time_paid, paid_by,
--                               payment_ref, class), saved_claims (class)
--   3. Bank details ........... dedupe to one row per user + UNIQUE(userId)
--   4. Performance indexes .... claim_details, claim_approval_stages,
--                               claim_data, saved_claims, (completed, paid)
--   5. Unique email ........... login_details.email, user_details.email
--
-- SAFE TO RE-RUN: every step is idempotent (skips work already done).
-- NON-ABORTING: the email UNIQUE step is skipped automatically if duplicate
-- emails still exist, so it never stops the rest of the script. Resolve any
-- duplicates the diagnostics report, then re-run to enforce it.
--
-- BACK UP THE DATABASE FIRST. The bank-details step deletes older duplicate
-- rows (keeping the most recent per user).
--
-- Apply with:  mysql -u <user> -p <database> < complete_migration_script_1.sql
-- ============================================================================

-- ── Helper procedures ───────────────────────────────────────────────────────
DELIMITER //

DROP PROCEDURE IF EXISTS rmu_add_col //
CREATE PROCEDURE rmu_add_col(IN p_table VARCHAR(64), IN p_col VARCHAR(64), IN p_def TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = p_table AND column_name = p_col
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_col, '` ', p_def);
        PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
    END IF;
END //

DROP PROCEDURE IF EXISTS rmu_add_index //
CREATE PROCEDURE rmu_add_index(IN p_table VARCHAR(64), IN p_index VARCHAR(64), IN p_cols VARCHAR(255))
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE() AND table_name = p_table AND index_name = p_index
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` (', p_cols, ')');
        PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
    END IF;
END //

-- Adds a UNIQUE index only if it doesn't exist AND the column has no duplicate
-- values (so it can never abort the script on dirty data).
DROP PROCEDURE IF EXISTS rmu_add_unique //
CREATE PROCEDURE rmu_add_unique(IN p_table VARCHAR(64), IN p_index VARCHAR(64), IN p_col VARCHAR(64))
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE() AND table_name = p_table AND index_name = p_index
    ) THEN
        SET @cnt = CONCAT('SELECT COUNT(*) INTO @rmu_dups FROM ',
                          '(SELECT `', p_col, '` FROM `', p_table, '` ',
                          'WHERE `', p_col, '` IS NOT NULL ',
                          'GROUP BY `', p_col, '` HAVING COUNT(*) > 1) d');
        PREPARE c FROM @cnt; EXECUTE c; DEALLOCATE PREPARE c;
        IF @rmu_dups = 0 THEN
            SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD UNIQUE INDEX `', p_index, '` (`', p_col, '`)');
            PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
        END IF;
    END IF;
END //

DELIMITER ;


-- ── 1. New tables ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS classes (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_code VARCHAR(20)  NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_class_code (class_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS holidays (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    holiday_date DATE         NOT NULL,
    description  VARCHAR(100) NOT NULL DEFAULT '',
    UNIQUE KEY uq_holiday_date (holiday_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ghana public holidays (sample seed; adjust/extend as needed).
INSERT IGNORE INTO holidays (holiday_date, description) VALUES
    ('2026-01-01', "New Year's Day"),
    ('2026-01-07', 'Constitution Day'),
    ('2026-03-06', 'Independence Day'),
    ('2026-04-03', 'Good Friday'),
    ('2026-04-06', 'Easter Monday'),
    ('2026-05-01', 'May Day'),
    ('2026-07-01', 'Republic Day'),
    ('2026-08-04', 'Founders'' Day'),
    ('2026-09-21', 'Kwame Nkrumah Memorial Day'),
    ('2026-12-25', 'Christmas Day'),
    ('2026-12-26', 'Boxing Day');


-- ── 2. New columns ───────────────────────────────────────────────────────────
-- Payment tracking on claims (#15)
CALL rmu_add_col('claim_details', 'paid',        'TINYINT(1) NOT NULL DEFAULT 0');
CALL rmu_add_col('claim_details', 'time_paid',   'TIMESTAMP NULL DEFAULT NULL');
CALL rmu_add_col('claim_details', 'paid_by',     'INT NULL DEFAULT NULL');
CALL rmu_add_col('claim_details', 'payment_ref', 'VARCHAR(50) NULL DEFAULT NULL');

-- Class code on claims and drafts
CALL rmu_add_col('claim_details', 'class', 'VARCHAR(20) NULL DEFAULT NULL');
CALL rmu_add_col('saved_claims',  'class', 'VARCHAR(20) NULL DEFAULT NULL');


-- ── 3. Bank details: one row per user ────────────────────────────────────────
-- Remove older duplicates, keeping the most recent row per user.
DELETE t1 FROM user_bank_details t1
JOIN user_bank_details t2
  ON t1.userId = t2.userId
 AND t1.user_bank_details_id < t2.user_bank_details_id;

CALL rmu_add_unique('user_bank_details', 'uq_ubd_user', 'userId');


-- ── 4. Performance indexes ───────────────────────────────────────────────────
CALL rmu_add_index('claim_details',         'idx_claim_details_userId',   '`userId`');
CALL rmu_add_index('claim_approval_stages', 'idx_cas_claim_stage_status', '`claimId`, `stage`, `status`');
CALL rmu_add_index('claim_data',            'idx_claim_data_claimId',     '`claimId`');
CALL rmu_add_index('saved_claims',          'idx_saved_claims_userId',    '`userId`');
CALL rmu_add_index('claim_details',         'idx_claim_completed_paid',   '`completed`, `paid`');


-- ── 5. Unique email (auto-skipped if duplicates remain) ──────────────────────
-- Diagnostics — review the output; resolve any rows then re-run to enforce.
SELECT email, COUNT(*) AS duplicate_count FROM login_details GROUP BY email HAVING duplicate_count > 1;
SELECT email, COUNT(*) AS duplicate_count FROM user_details  GROUP BY email HAVING duplicate_count > 1;

CALL rmu_add_unique('login_details', 'uq_login_details_email', 'email');
CALL rmu_add_unique('user_details',  'uq_user_details_email',  'email');


-- ── Cleanup ──────────────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS rmu_add_col;
DROP PROCEDURE IF EXISTS rmu_add_index;
DROP PROCEDURE IF EXISTS rmu_add_unique;
