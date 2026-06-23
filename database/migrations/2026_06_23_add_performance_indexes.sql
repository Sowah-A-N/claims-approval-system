-- ============================================================================
-- Migration: add performance indexes (#30)
-- Date:      2026-06-23
--
-- Adds indexes on the foreign-key / filter columns that the claim listing,
-- approval, and dashboard queries hit on every page load. Without these,
-- MySQL does full table scans as the tables grow.
--
-- Idempotent: each index is added only if it does not already exist, so this
-- file is safe to re-run (MySQL < 8.0.29 has no CREATE INDEX IF NOT EXISTS).
--
-- Apply with:  mysql -u <user> -p <database> < this_file.sql
-- ============================================================================

DELIMITER //

DROP PROCEDURE IF EXISTS rmu_add_index //
CREATE PROCEDURE rmu_add_index(
    IN p_table  VARCHAR(64),
    IN p_index  VARCHAR(64),
    IN p_cols   VARCHAR(255)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name   = p_table
          AND index_name   = p_index
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `',
                          p_index, '` (', p_cols, ')');
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //

DELIMITER ;

-- Claim listing by owner (My Claims, dashboards).
CALL rmu_add_index('claim_details', 'idx_claim_details_userId', '`userId`');

-- Approval-stage lookups: current stage per claim + status filtering.
CALL rmu_add_index('claim_approval_stages', 'idx_cas_claim_stage_status',
                   '`claimId`, `stage`, `status`');

-- Claim data rows joined/aggregated by claim.
CALL rmu_add_index('claim_data', 'idx_claim_data_claimId', '`claimId`');

-- Draft lookups by owner.
CALL rmu_add_index('saved_claims', 'idx_saved_claims_userId', '`userId`');

DROP PROCEDURE IF EXISTS rmu_add_index;
