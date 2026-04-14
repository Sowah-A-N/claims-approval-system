-- ============================================================
-- RMU Claims Approval System
-- Migration v1.1 — PART 2 of 5
-- Column Types · Bug Fixes · Renames · Missing Columns
-- ============================================================
-- Prerequisite: Part 1 must be applied first.
--
-- What this part fixes
--   A) Data type corrections  (rate, subTotal, fuelComponent)
--   B) Bug: claim_details.time_submitted mutates on every UPDATE
--   C) Bug: trigger handles 'Rejected' but code writes 'Flagged'
--   D) Bug: flagged_claims.flaggedId is NOT NULL yet PHP never
--            inserts a value — rename + make nullable
--   E) Varchar columns too narrow to hold real data
--   F) login_details.role width inconsistency
--   G) Add every missing column identified in the audit
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;


-- ============================================================
-- A) DATA TYPE CORRECTIONS
-- ============================================================

-- A1. lecturer_rank_rate.rate
--     INT stores whole numbers only; rates are currency (GH₵).
--     Existing integer values promote to DECIMAL safely.
ALTER TABLE `lecturer_rank_rate`
    MODIFY COLUMN `rate` DECIMAL(8,2) NOT NULL DEFAULT 0.00;

-- A2. user_details.rate
--     Same issue — the per-user cached rate must match.
ALTER TABLE `user_details`
    MODIFY COLUMN `rate` DECIMAL(8,2) NOT NULL DEFAULT 0.00;

-- A3. claim_details.rate
--     Rate stamped on a claim at submission time.
ALTER TABLE `claim_details`
    MODIFY COLUMN `rate` DECIMAL(8,2) NOT NULL DEFAULT 0.00;

-- A4. claim_data.subTotal
--     DECIMAL(5,2) has a maximum of 999.99.
--     A multi-period claim at GH₵200/hr easily exceeds that.
ALTER TABLE `claim_data`
    MODIFY COLUMN `subTotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00;

-- A5. claim_data.fuelComponent
--     Declared VARCHAR(3) but PHP always stores integer 0 or 1
--     (bind-param type 'i' in db_insert_claim_data_row).
--     Pre-flight check before running:
--       SELECT id, fuelComponent FROM claim_data
--       WHERE fuelComponent IS NOT NULL
--         AND fuelComponent NOT REGEXP '^[01]$';
ALTER TABLE `claim_data`
    MODIFY COLUMN `fuelComponent` TINYINT(1) NOT NULL DEFAULT 0;


-- ============================================================
-- B) BUG FIX — claim_details.time_submitted mutates on UPDATE
--
--    Current definition:
--      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
--    This resets the submission timestamp every time an approver
--    advances the claim stage, making audit timestamps useless.
--    Fix: keep DEFAULT CURRENT_TIMESTAMP, drop ON UPDATE clause.
-- ============================================================

ALTER TABLE `claim_details`
    MODIFY COLUMN `time_submitted`
        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;


-- ============================================================
-- C) BUG FIX — claim_approval_stages triggers
--
--    The before_claim_update trigger checks for status = 'Rejected'
--    but db_flag_claim() in approval.queries.php writes 'Flagged'.
--    Result: flagged rows never get time_rejected set via UPDATE.
--    (Inserts bypass the UPDATE trigger entirely, so time_rejected
--    is set correctly when PHP uses INSERT … time_rejected = NOW().
--    The fix matters for any direct UPDATE path and keeps the
--    trigger semantics consistent with the application vocabulary.)
--
--    We also add 'Flagged' as a first-class handled status and
--    preserve backward-compat for any legacy 'Rejected' rows.
-- ============================================================

DROP TRIGGER IF EXISTS `before_claim_update`;

DELIMITER $$
CREATE TRIGGER `before_claim_update`
BEFORE UPDATE ON `claim_approval_stages`
FOR EACH ROW
BEGIN
    IF OLD.status = 'Pending'
       AND NEW.status IN ('Approved', 'Flagged', 'Rejected')
    THEN
        SET NEW.time_updated = NOW();

        IF NEW.status = 'Approved' THEN
            SET NEW.time_approved = NOW();
            SET NEW.time_rejected = NULL;

        ELSEIF NEW.status IN ('Flagged', 'Rejected') THEN
            SET NEW.time_rejected = NOW();
            SET NEW.time_approved = NULL;
        END IF;

    ELSE
        -- No status transition — preserve existing timestamp
        SET NEW.time_updated = OLD.time_updated;
    END IF;
END$$
DELIMITER ;


-- ============================================================
-- D) BUG FIX — flagged_claims.flaggedId is NOT NULL but PHP
--    never inserts a value (see db_flag_claim() in
--    approval.queries.php — flaggedId absent from column list).
--    MySQL was silently defaulting it to 0 in non-strict mode.
--
--    Fix: rename to approver_user_id (its actual meaning) and
--    make it nullable so existing 0-value rows are still valid.
--    Part 4 will wire the FK to user_details.userId.
-- ============================================================

ALTER TABLE `flagged_claims`
    CHANGE COLUMN `flaggedId`
        `approver_user_id` INT NULL DEFAULT NULL
        COMMENT 'userId of the approver who raised the flag';


-- ============================================================
-- E) VARCHAR COLUMNS TOO NARROW
-- ============================================================

-- completed_claims: all three varchar cols are VARCHAR(25).
-- claim_details uses VARCHAR(35) for the same fields;
-- department in user_details is VARCHAR(75). Widen to match.
ALTER TABLE `completed_claims`
    MODIFY COLUMN `department` VARCHAR(125) NOT NULL,
    MODIFY COLUMN `programme`  VARCHAR(125) NOT NULL,
    MODIFY COLUMN `course`     VARCHAR(125) NOT NULL;

-- flagged_claims: widen to match claim_details + room for messages
ALTER TABLE `flagged_claims`
    MODIFY COLUMN `department`  VARCHAR(125) NOT NULL,
    MODIFY COLUMN `programme`   VARCHAR(125) NOT NULL,
    MODIFY COLUMN `course`      VARCHAR(125) NOT NULL,
    MODIFY COLUMN `flagged_msg` VARCHAR(1000) DEFAULT NULL;


-- ============================================================
-- F) login_details.role WIDTH
--    VARCHAR(16) is inconsistent with user_details.role (ENUM).
--    'claimant' = 8 chars, all roles fit in 16, but widening
--    to 20 aligns with any future role names.
-- ============================================================

ALTER TABLE `login_details`
    MODIFY COLUMN `role` VARCHAR(20) DEFAULT NULL;


-- ============================================================
-- G) ADD MISSING COLUMNS
--    All use IF NOT EXISTS — safe to re-run.
-- ============================================================

-- G1. department: hierarchy link to faculty
--     Populate after adding:
--       UPDATE department d
--       JOIN   faculty f ON f.name = '<match expression>'
--       SET    d.faculty_id = f.id;
ALTER TABLE `department`
    ADD COLUMN IF NOT EXISTS `faculty_id` INT NULL DEFAULT NULL
        COMMENT 'FK → faculty.id (wired in Part 4)';

-- G2. saved_claims: faculty is submitted by the form but not stored
ALTER TABLE `saved_claims`
    ADD COLUMN IF NOT EXISTS `faculty` VARCHAR(125) NULL DEFAULT NULL
        AFTER `userId`;

-- G3. claim_details: academic year linkage (nullable — backward compat)
ALTER TABLE `claim_details`
    ADD COLUMN IF NOT EXISTS `academic_year_id` INT NULL DEFAULT NULL
        COMMENT 'FK → academic_year.id; NULL = pre-v1.1 submission';

-- G4. claim_details: optional free-text note from claimant
ALTER TABLE `claim_details`
    ADD COLUMN IF NOT EXISTS `claimant_note` VARCHAR(500) NULL DEFAULT NULL;

-- G5. claim_approval_stages: record who acted on each stage
--     PHP must pass the actor's userId when calling
--     db_advance_claim_stage() and db_flag_claim().
ALTER TABLE `claim_approval_stages`
    ADD COLUMN IF NOT EXISTS `approved_by` INT NULL DEFAULT NULL
        COMMENT 'userId of approver who approved this stage',
    ADD COLUMN IF NOT EXISTS `flagged_by`  INT NULL DEFAULT NULL
        COMMENT 'userId of approver who flagged this stage';

-- G6. completed_claims: completion actor + payment lifecycle
ALTER TABLE `completed_claims`
    ADD COLUMN IF NOT EXISTS `completed_by_user_id` INT NULL DEFAULT NULL
        COMMENT 'userId of final-stage approver',
    ADD COLUMN IF NOT EXISTS `payment_approved`     TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `payment_approved_at`  DATETIME   NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `payment_approved_by`  INT        NULL DEFAULT NULL
        COMMENT 'userId of finance officer';

-- G7. user_details: login activity for dashboard + security alerts
ALTER TABLE `user_details`
    ADD COLUMN IF NOT EXISTS `last_login`  DATETIME     NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `login_count` INT UNSIGNED NOT NULL DEFAULT 0;


-- ============================================================
-- END OF PART 2
-- ============================================================
-- Verify key changes with:
--
--   -- Rate columns are now DECIMAL
--   SHOW COLUMNS FROM lecturer_rank_rate LIKE 'rate';
--   SHOW COLUMNS FROM claim_details      LIKE 'rate';
--   SHOW COLUMNS FROM user_details       LIKE 'rate';
--
--   -- fuelComponent is TINYINT
--   SHOW COLUMNS FROM claim_data LIKE 'fuelComponent';
--
--   -- time_submitted has no ON UPDATE
--   SHOW COLUMNS FROM claim_details LIKE 'time_submitted';
--
--   -- flaggedId renamed
--   SHOW COLUMNS FROM flagged_claims LIKE 'approver_user_id';
--
--   -- New columns present
--   SHOW COLUMNS FROM saved_claims         LIKE 'faculty';
--   SHOW COLUMNS FROM claim_details        LIKE 'academic_year_id';
--   SHOW COLUMNS FROM claim_approval_stages LIKE 'approved_by';
-- ============================================================

SET FOREIGN_KEY_CHECKS = 1;
