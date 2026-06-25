-- ============================================================================
-- Migration: class support
-- Date:      2026-06-25
--
-- Each claim is filed for a class (e.g. BIT27, BEE24). We store the class on the
-- claim and keep a master list in `classes` so the file-claim form can offer a
-- dropdown of classes that already exist.
--
-- Idempotent. Apply with: mysql -u <user> -p <database> < this_file.sql
-- ============================================================================

CREATE TABLE IF NOT EXISTS classes (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_code VARCHAR(20)  NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_class_code (class_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DELIMITER //
DROP PROCEDURE IF EXISTS rmu_add_col //
CREATE PROCEDURE rmu_add_col(IN p_table VARCHAR(64), IN p_col VARCHAR(64), IN p_def TEXT)
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = p_table AND column_name = p_col) THEN
        SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_col, '` ', p_def);
        PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
    END IF;
END //
DELIMITER ;

CALL rmu_add_col('claim_details', 'class', "VARCHAR(20) NULL DEFAULT NULL");
CALL rmu_add_col('saved_claims',  'class', "VARCHAR(20) NULL DEFAULT NULL");

DROP PROCEDURE IF EXISTS rmu_add_col;
