-- ============================================================================
-- Migration: payment tracking columns on claim_details (#15)
-- Date:      2026-06-24
--
-- Adds payment state so Finance can mark a completed claim as paid and clear
-- it from the payment queue, instead of the old no-op alert().
--
-- Idempotent: each column is added only if it does not already exist.
-- Apply with:  mysql -u <user> -p <database> < this_file.sql
-- ============================================================================

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

DELIMITER ;

CALL rmu_add_col('claim_details', 'paid',        'TINYINT(1) NOT NULL DEFAULT 0');
CALL rmu_add_col('claim_details', 'time_paid',   'TIMESTAMP NULL DEFAULT NULL');
CALL rmu_add_col('claim_details', 'paid_by',     'INT NULL DEFAULT NULL');
CALL rmu_add_col('claim_details', 'payment_ref', 'VARCHAR(50) NULL DEFAULT NULL');

DROP PROCEDURE IF EXISTS rmu_add_col;

-- Speeds up the finance queue (completed & unpaid) and paid-history views.
CREATE INDEX idx_claim_completed_paid ON claim_details (completed, paid);
