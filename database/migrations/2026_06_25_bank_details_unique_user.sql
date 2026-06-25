-- ============================================================================
-- Migration: one bank-details row per user
-- Date:      2026-06-25
--
-- user_bank_details had no UNIQUE key on userId, so the save handler's
-- "INSERT ... ON DUPLICATE KEY UPDATE" never matched and every save inserted a
-- new row — the page then read the oldest row, so edits appeared lost.
--
-- Dedupe (keep the most recent row per user), then add the UNIQUE key.
-- Apply with: mysql -u <user> -p <database> < this_file.sql
-- ============================================================================

DELETE t1 FROM user_bank_details t1
JOIN user_bank_details t2
  ON t1.userId = t2.userId
 AND t1.user_bank_details_id < t2.user_bank_details_id;

-- Add the UNIQUE key only if it isn't already present.
SET @exists := (SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                  AND table_name = 'user_bank_details'
                  AND index_name = 'uq_ubd_user');
SET @ddl := IF(@exists = 0,
    'ALTER TABLE user_bank_details ADD UNIQUE KEY uq_ubd_user (userId)',
    'SELECT 1');
PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
