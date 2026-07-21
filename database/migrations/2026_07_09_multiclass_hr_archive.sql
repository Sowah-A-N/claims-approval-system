-- ============================================================================
-- Consolidated migration — 2026-07 feature batch
-- ============================================================================
-- Covers three features shipped together:
--   #5  Multi-class claims        (widen the class columns)
--   #1  HR role + employee list   (new 'hr' role + hr_employees register)
--   #2  Archive database          (separate <db>_archive store)
--
-- Idempotent: every statement is safe to run more than once.
-- Apply against the PRIMARY application database (e.g. `doc-app`).
-- ============================================================================


-- ── #5 Multi-class ──────────────────────────────────────────────────────────
-- A claim can cover several classes, stored as a comma-separated list
-- (e.g. "BIT27, BIT28"). Widen from VARCHAR(20) (a single code) to VARCHAR(255).
ALTER TABLE `claim_details` MODIFY `class` VARCHAR(255) NULL;
ALTER TABLE `saved_claims`  MODIFY `class` VARCHAR(255) NULL;

-- A single class entry may itself be a combined/joint class such as
-- "BIT27/BCS27/DIT25", so widen the individual class-code column too.
ALTER TABLE `classes` MODIFY `class_code` VARCHAR(60) NOT NULL;


-- ── #1 HR role + employee register ──────────────────────────────────────────
-- New 'hr' role. Registrants whose email is on hr_employees are auto-activated.
ALTER TABLE `user_details`
    MODIFY `role` ENUM('finance','admin','approver','claimant','hr')
    NOT NULL DEFAULT 'claimant';

-- Collation MUST match user_details (utf8mb4_unicode_ci) so the email joins used
-- for auto-activation and the "registered" flag don't hit an illegal mix of
-- collations.
CREATE TABLE IF NOT EXISTS `hr_employees` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `staff_id`     VARCHAR(40)  NULL,
    `first_name`   VARCHAR(50)  NOT NULL,
    `last_name`    VARCHAR(50)  NOT NULL,
    `other_names`  VARCHAR(50)  NULL,
    `email`        VARCHAR(120) NOT NULL,
    `phone_number` VARCHAR(20)  NULL,
    `gender`       VARCHAR(10)  NULL,
    `department`   VARCHAR(75)  NULL,
    `rank`         VARCHAR(40)  NULL,
    `added_by`     INT          NULL,
    `date_added`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_hr_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── #2 Archive database ─────────────────────────────────────────────────────
-- Archiving MOVES records out of the primary database into this parallel store.
-- The mirror tables (hr_employees, classes, banks_branches, audit_log) are
-- AUTO-PROVISIONED at runtime by includes/archive.php on first use — each is
-- created with CREATE TABLE ... LIKE, has its non-PRIMARY UNIQUE indexes dropped
-- (so archived rows never collide), and gains archived_at / archived_by columns.
--
-- NOTE: replace `doc-app_archive` with "<your primary db>_archive" if your
-- database is not named `doc-app`.
CREATE DATABASE IF NOT EXISTS `doc-app_archive`
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
