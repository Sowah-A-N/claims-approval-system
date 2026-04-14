-- ============================================================
-- RMU Claims Approval System
-- Migration v1.1 — PART 1 of 5
-- Engine · Charset · Primary Keys · AUTO_INCREMENT
-- ============================================================
-- Applied against: phpMyAdmin dump dated 2026-04-14 (MySQL 8.4.7)
--
-- What this part fixes
--   A) Six MyISAM tables → InnoDB  (required for FK support in later parts)
--   B) All latin1 / utf8mb3 tables → utf8mb4  (uniform encoding)
--   C) Four PKs that exist but lack AUTO_INCREMENT
--   D) Seven tables that have no PRIMARY KEY at all
--
-- Safe to run on a populated database.
-- Re-running will error on duplicate PK/column — that is harmless
-- and means the step was already applied.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;


-- ============================================================
-- A) ENGINE CONVERSION  MyISAM → InnoDB
--    Done together with the charset conversion (one ALTER each).
--    CONVERT TO CHARACTER SET rewrites every text/blob column
--    in-place, which is safe for latin1 → utf8mb4.
-- ============================================================

-- admin_logs  (MyISAM, latin1)
ALTER TABLE `admin_logs`
    ENGINE  = InnoDB,
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- completed_claims  (MyISAM, latin1)
ALTER TABLE `completed_claims`
    ENGINE  = InnoDB,
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- flagged_claims  (MyISAM, latin1)
ALTER TABLE `flagged_claims`
    ENGINE  = InnoDB,
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- lecturer_rank_rate  (MyISAM, latin1)
ALTER TABLE `lecturer_rank_rate`
    ENGINE  = InnoDB,
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- roles  (MyISAM, latin1)
ALTER TABLE `roles`
    ENGINE  = InnoDB,
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- saved_claims  (MyISAM, latin1)
ALTER TABLE `saved_claims`
    ENGINE  = InnoDB,
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


-- ============================================================
-- B) CHARSET CONVERSION  latin1 / utf8mb3 → utf8mb4
--    Tables already on InnoDB that still carry latin1 or utf8mb3.
-- ============================================================

-- InnoDB tables still on latin1
ALTER TABLE `approver_details`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `approver_ranks`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `banks_branches`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `claim_approval_stages`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `claim_data`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `claim_details`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `login_details`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `settings`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `user_bank_details`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `user_details`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- InnoDB tables on utf8mb3 (MySQL alias for 3-byte utf8 — upgrade to full 4-byte)
ALTER TABLE `academic_year`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `class`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `course`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `department`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `programme`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- faculty is already utf8mb4 — just align the collation
ALTER TABLE `faculty`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


-- ============================================================
-- C) ADD AUTO_INCREMENT TO EXISTING PKs THAT LACK IT
--    PK column exists and has data; MODIFY adds the sequence
--    without disturbing existing values.
-- ============================================================

-- admin_logs.log_id  — PK declared, no AUTO_INCREMENT
ALTER TABLE `admin_logs`
    MODIFY COLUMN `log_id` INT NOT NULL AUTO_INCREMENT;

-- approver_ranks.id  — PK declared, no AUTO_INCREMENT
ALTER TABLE `approver_ranks`
    MODIFY COLUMN `id` INT NOT NULL AUTO_INCREMENT;

-- lecturer_rank_rate.rankId  — PK declared, no AUTO_INCREMENT
ALTER TABLE `lecturer_rank_rate`
    MODIFY COLUMN `rankId` INT NOT NULL AUTO_INCREMENT;

-- roles.role_id  — PK declared, no AUTO_INCREMENT
ALTER TABLE `roles`
    MODIFY COLUMN `role_id` INT NOT NULL AUTO_INCREMENT;


-- ============================================================
-- D) ADD PRIMARY KEYS TO TABLES THAT HAVE NONE
-- ============================================================

-- D1. approver_details
--     id INT NOT NULL exists but was never declared PK.
ALTER TABLE `approver_details`
    MODIFY COLUMN `id` INT NOT NULL AUTO_INCREMENT,
    ADD PRIMARY KEY (`id`);

-- D2. class
--     Natural key on code (VARCHAR 10).
ALTER TABLE `class`
    ADD PRIMARY KEY (`code`);

-- D3. course
--     Natural key on code (VARCHAR 10).
ALTER TABLE `course`
    ADD PRIMARY KEY (`code`);

-- D4. programme
--     Natural key on code (VARCHAR 10).
ALTER TABLE `programme`
    ADD PRIMARY KEY (`code`);

-- D5. user_bank_details
--     user_bank_details_id exists as INT NOT NULL but has no PK
--     and no AUTO_INCREMENT in the dump.
ALTER TABLE `user_bank_details`
    MODIFY COLUMN `user_bank_details_id` INT NOT NULL AUTO_INCREMENT,
    ADD PRIMARY KEY (`user_bank_details_id`);

-- D6. flagged_claims
--     No PK column at all — add a surrogate.
--     The existing flaggedId column (INT NOT NULL, never populated
--     by current PHP code) is renamed to approver_user_id and made
--     nullable in Part 2 before the FK is wired in Part 4.
ALTER TABLE `flagged_claims`
    ADD COLUMN `flagId` INT NOT NULL AUTO_INCREMENT FIRST,
    ADD PRIMARY KEY (`flagId`);

-- D7. completed_claims
--     No PK column at all — add a surrogate.
ALTER TABLE `completed_claims`
    ADD COLUMN `completionId` INT NOT NULL AUTO_INCREMENT FIRST,
    ADD PRIMARY KEY (`completionId`);


-- ============================================================
-- END OF PART 1
-- ============================================================
-- Verify with:
--   SELECT table_name, engine, table_collation
--   FROM information_schema.tables
--   WHERE table_schema = DATABASE()
--   ORDER BY table_name;
-- All rows should show ENGINE=InnoDB, COLLATION=utf8mb4_unicode_ci.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 1;
