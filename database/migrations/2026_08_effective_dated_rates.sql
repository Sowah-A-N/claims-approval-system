-- Effective-dated rank rates: a claim is paid at the rank rate that was in force
-- on each session's teaching date, so a later rate change never alters pay for
-- past work. The rank rate (by date) is the source of truth for claim amounts.
--
-- Run once. (MySQL/MariaDB will error if an object already exists — safe to skip.)

-- 1. Rate history per rank. Effective rate for (rank, date) = the row with the
--    greatest effective_from that is <= the date.
CREATE TABLE IF NOT EXISTS `rank_rate_history` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `rank`           VARCHAR(50)   NOT NULL,
    `rate`           DECIMAL(8,2)  NOT NULL,
    `effective_from` DATE          NOT NULL,
    `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_rank_eff` (`rank`, `effective_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Seed history from the current rates, effective far in the past, so every
--    existing/historical claim resolves to today's rate until a change is logged.
INSERT INTO `rank_rate_history` (`rank`, `rate`, `effective_from`)
SELECT `rank`, `rate`, '2000-01-01' FROM `lecturer_rank_rate`;

-- 3. Per-session rate snapshot on claim_data (the authoritative amount lives in
--    subTotal; rate records what per-period rate produced it).
ALTER TABLE `claim_data` ADD COLUMN `rate` DECIMAL(8,2) NULL AFTER `periods`;

-- 4. Backfill existing rows from their stored subtotal so historical amounts and
--    the per-row rate column stay consistent.
UPDATE `claim_data`
   SET `rate` = CASE WHEN `periods` > 0 THEN ROUND(`subTotal` / `periods`, 2) ELSE 0 END
 WHERE `rate` IS NULL;
