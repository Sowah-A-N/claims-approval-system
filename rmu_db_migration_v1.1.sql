-- ============================================================
-- RMU Claims Approval System
-- Database Migration Script  v1.1
-- ============================================================
-- Purpose  : Upgrade the existing legacy schema in-place.
--            No data is destroyed; all changes are additive or
--            safe type promotions.
-- Target   : MySQL 8.0.19+  /  MariaDB 10.5+
-- Run once : Some ALTER statements will error on a second run
--            (e.g. "Duplicate column", "Duplicate key name").
--            That is expected — it means the step is already done.
-- Usage    : mysql -u <user> -p <dbname> < rmu_db_migration_v1.1.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ============================================================
-- SECTION 1 — Fix Primary Keys
-- Tables that lack a PK in the legacy schema.
-- ============================================================

-- 1a. academic_year — ensure id is AUTO_INCREMENT PK
ALTER TABLE academic_year
    MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT,
    ADD    PRIMARY KEY (id);

-- 1b. department — deptId as AUTO_INCREMENT PK
ALTER TABLE department
    MODIFY COLUMN deptId INT NOT NULL AUTO_INCREMENT,
    ADD    PRIMARY KEY (deptId);

-- 1c. course — varchar code PK
ALTER TABLE course
    ADD PRIMARY KEY (code);

-- 1d. programme — varchar code PK
ALTER TABLE programme
    ADD PRIMARY KEY (code);

-- 1e. class — varchar code PK
ALTER TABLE class
    ADD PRIMARY KEY (code);

-- 1f. user_bank_details — ensure AUTO_INCREMENT on existing PK column
ALTER TABLE user_bank_details
    MODIFY COLUMN user_bank_details_id INT NOT NULL AUTO_INCREMENT,
    ADD    PRIMARY KEY (user_bank_details_id);

-- 1g. flagged_claims — no PK existed; add flagId surrogate
ALTER TABLE flagged_claims
    ADD    COLUMN flagId INT NOT NULL AUTO_INCREMENT FIRST,
    ADD    PRIMARY KEY (flagId);

-- 1h. completed_claims — no PK existed; add completionId surrogate
ALTER TABLE completed_claims
    ADD    COLUMN completionId INT NOT NULL AUTO_INCREMENT FIRST,
    ADD    PRIMARY KEY (completionId);


-- ============================================================
-- SECTION 2 — Fix Column Data Types
-- ============================================================

-- 2a. lecturer_rank_rate.rate: INT(5) → DECIMAL(8,2)
--     Rates are currency; INT loses decimal precision.
ALTER TABLE lecturer_rank_rate
    MODIFY COLUMN rate DECIMAL(8,2) NOT NULL DEFAULT 0.00;

-- 2b. claim_details.rate: INT(5) → DECIMAL(8,2)
ALTER TABLE claim_details
    MODIFY COLUMN rate DECIMAL(8,2) NOT NULL DEFAULT 0.00;

-- 2c. claim_data.subTotal: DECIMAL(5,2) max is 999.99 — far too narrow.
--     GHS amounts for multi-period claims can exceed that easily.
ALTER TABLE claim_data
    MODIFY COLUMN subTotal DECIMAL(10,2) NOT NULL DEFAULT 0.00;

-- 2d. claim_data.fuelComponent: VARCHAR(3) → TINYINT(1)
--     Code always binds this as integer 0/1 (bind param type 'i').
--     Pre-check: SELECT claimId, fuelComponent FROM claim_data
--                WHERE fuelComponent NOT REGEXP '^[01]?$';
ALTER TABLE claim_data
    MODIFY COLUMN fuelComponent TINYINT(1) NOT NULL DEFAULT 0;

-- 2e. completed_claims: varchar columns too narrow, widen to match source
ALTER TABLE completed_claims
    MODIFY COLUMN department VARCHAR(125) NOT NULL,
    MODIFY COLUMN programme  VARCHAR(125) NOT NULL,
    MODIFY COLUMN course     VARCHAR(125) NOT NULL;

-- 2f. flagged_claims: widen varchar columns + expand flagged_msg
ALTER TABLE flagged_claims
    MODIFY COLUMN department  VARCHAR(125)  NOT NULL,
    MODIFY COLUMN programme   VARCHAR(125)  NOT NULL,
    MODIFY COLUMN course      VARCHAR(125)  NOT NULL,
    MODIFY COLUMN flagged_msg VARCHAR(1000) DEFAULT NULL;

-- 2g. login_details.role: varchar(16) → varchar(20) to match user_details
ALTER TABLE login_details
    MODIFY COLUMN role VARCHAR(20) DEFAULT NULL;


-- ============================================================
-- SECTION 3 — Add Missing Columns
-- ============================================================

-- 3a. department: link to faculty for proper hierarchy FK
ALTER TABLE department
    ADD COLUMN IF NOT EXISTS faculty_id INT NULL
        COMMENT 'FK → faculty.id. Backfill: UPDATE department d JOIN faculty f ON f.name LIKE CONCAT(''%'',d.dept_name,''%'') SET d.faculty_id = f.id';

-- 3b. saved_claims: missing faculty column (claim submission uses faculty)
ALTER TABLE saved_claims
    ADD COLUMN IF NOT EXISTS faculty VARCHAR(125) NULL AFTER userId;

-- 3c. claim_details: academic year linkage (nullable, backward-compatible)
ALTER TABLE claim_details
    ADD COLUMN IF NOT EXISTS academic_year_id INT NULL
        COMMENT 'FK → academic_year.id. NULL = submitted before v1.1';

-- 3d. claim_details: optional note from claimant at submission
ALTER TABLE claim_details
    ADD COLUMN IF NOT EXISTS claimant_note VARCHAR(500) NULL;

-- 3e. claim_approval_stages: record who acted on each stage
ALTER TABLE claim_approval_stages
    ADD COLUMN IF NOT EXISTS approved_by INT NULL
        COMMENT 'userId of approver who approved this stage',
    ADD COLUMN IF NOT EXISTS flagged_by  INT NULL
        COMMENT 'userId of approver who flagged this stage';

-- 3f. flagged_claims: record which approver raised the flag
ALTER TABLE flagged_claims
    ADD COLUMN IF NOT EXISTS approver_user_id INT NULL
        COMMENT 'userId of the approver who flagged this claim';

-- 3g. completed_claims: record who finalised + payment tracking
ALTER TABLE completed_claims
    ADD COLUMN IF NOT EXISTS completed_by_user_id INT NULL
        COMMENT 'userId of the approver at the final stage',
    ADD COLUMN IF NOT EXISTS payment_approved     TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS payment_approved_at  DATETIME NULL,
    ADD COLUMN IF NOT EXISTS payment_approved_by  INT NULL
        COMMENT 'userId of the finance officer';

-- 3h. user_details: login activity tracking
ALTER TABLE user_details
    ADD COLUMN IF NOT EXISTS last_login  DATETIME     NULL,
    ADD COLUMN IF NOT EXISTS login_count INT UNSIGNED NOT NULL DEFAULT 0;


-- ============================================================
-- SECTION 4 — Add Unique Constraints
-- ============================================================

ALTER TABLE user_details
    ADD UNIQUE KEY uq_ud_email     (email);

ALTER TABLE login_details
    ADD UNIQUE KEY uq_ld_email     (email),
    ADD UNIQUE KEY uq_ld_userid    (userId);

ALTER TABLE user_bank_details
    ADD UNIQUE KEY uq_bank_userid  (userId);

ALTER TABLE settings
    ADD UNIQUE KEY uq_setting_name (settingName);

ALTER TABLE lecturer_rank_rate
    ADD UNIQUE KEY uq_lrr_rank     (rank);

ALTER TABLE faculty
    ADD UNIQUE KEY uq_faculty_name (name);

ALTER TABLE department
    ADD UNIQUE KEY uq_dept_name    (dept_name);


-- ============================================================
-- SECTION 5 — Add Performance Indexes
-- ============================================================

-- claim_details
CREATE INDEX IF NOT EXISTS idx_cd_userId
    ON claim_details (userId);

CREATE INDEX IF NOT EXISTS idx_cd_status
    ON claim_details (flagged, completed);

CREATE INDEX IF NOT EXISTS idx_cd_submitted
    ON claim_details (time_submitted);

CREATE INDEX IF NOT EXISTS idx_cd_academic_year
    ON claim_details (academic_year_id);

-- claim_approval_stages
CREATE INDEX IF NOT EXISTS idx_cas_claimId
    ON claim_approval_stages (claimId);

CREATE INDEX IF NOT EXISTS idx_cas_stage_status
    ON claim_approval_stages (stage, status);

-- claim_data
CREATE INDEX IF NOT EXISTS idx_cdata_claimId
    ON claim_data (claimId);

CREATE INDEX IF NOT EXISTS idx_cdata_date
    ON claim_data (date);

-- saved_claims
CREATE INDEX IF NOT EXISTS idx_sc_userId
    ON saved_claims (userId);

-- flagged_claims
CREATE INDEX IF NOT EXISTS idx_fc_claimId
    ON flagged_claims (claimId);

-- completed_claims
CREATE INDEX IF NOT EXISTS idx_cc_userId
    ON completed_claims (userId);

CREATE INDEX IF NOT EXISTS idx_cc_claimId
    ON completed_claims (claimId);

-- user_details
CREATE INDEX IF NOT EXISTS idx_ud_account_status
    ON user_details (account_status);

CREATE INDEX IF NOT EXISTS idx_ud_role
    ON user_details (role(16));

CREATE INDEX IF NOT EXISTS idx_ud_department
    ON user_details (department(50));

-- department hierarchy
CREATE INDEX IF NOT EXISTS idx_dept_faculty
    ON department (faculty_id);

-- course → department
CREATE INDEX IF NOT EXISTS idx_course_dept
    ON course (department(50));


-- ============================================================
-- SECTION 6 — Foreign Key Constraints
-- Each FK is dropped first (IF EXISTS) then recreated, so the
-- block is safe to re-run.
-- ============================================================

-- 6a. department → faculty
ALTER TABLE department
    DROP   FOREIGN KEY IF EXISTS fk_dept_faculty;
ALTER TABLE department
    ADD    CONSTRAINT fk_dept_faculty
           FOREIGN KEY (faculty_id)
           REFERENCES faculty (id)
           ON DELETE SET NULL
           ON UPDATE CASCADE;

-- 6b. programme → department
ALTER TABLE programme
    DROP   FOREIGN KEY IF EXISTS fk_programme_dept;
ALTER TABLE programme
    ADD    CONSTRAINT fk_programme_dept
           FOREIGN KEY (fk_department)
           REFERENCES department (deptId)
           ON DELETE RESTRICT
           ON UPDATE CASCADE;

-- 6c. class → programme
ALTER TABLE class
    DROP   FOREIGN KEY IF EXISTS fk_class_programme;
ALTER TABLE class
    ADD    CONSTRAINT fk_class_programme
           FOREIGN KEY (fk_program)
           REFERENCES programme (code)
           ON DELETE RESTRICT
           ON UPDATE CASCADE;

-- 6d. login_details → user_details
ALTER TABLE login_details
    DROP   FOREIGN KEY IF EXISTS fk_ld_user;
ALTER TABLE login_details
    ADD    CONSTRAINT fk_ld_user
           FOREIGN KEY (userId)
           REFERENCES user_details (userId)
           ON DELETE CASCADE
           ON UPDATE CASCADE;

-- 6e. user_bank_details → user_details
ALTER TABLE user_bank_details
    DROP   FOREIGN KEY IF EXISTS fk_bank_user;
ALTER TABLE user_bank_details
    ADD    CONSTRAINT fk_bank_user
           FOREIGN KEY (userId)
           REFERENCES user_details (userId)
           ON DELETE CASCADE
           ON UPDATE CASCADE;

-- 6f. saved_claims → user_details
ALTER TABLE saved_claims
    DROP   FOREIGN KEY IF EXISTS fk_sc_user;
ALTER TABLE saved_claims
    ADD    CONSTRAINT fk_sc_user
           FOREIGN KEY (userId)
           REFERENCES user_details (userId)
           ON DELETE CASCADE
           ON UPDATE CASCADE;

-- 6g. claim_details → user_details
ALTER TABLE claim_details
    DROP   FOREIGN KEY IF EXISTS fk_cd_user;
ALTER TABLE claim_details
    ADD    CONSTRAINT fk_cd_user
           FOREIGN KEY (userId)
           REFERENCES user_details (userId)
           ON DELETE RESTRICT
           ON UPDATE CASCADE;

-- 6h. claim_details → academic_year (nullable)
ALTER TABLE claim_details
    DROP   FOREIGN KEY IF EXISTS fk_cd_academic_year;
ALTER TABLE claim_details
    ADD    CONSTRAINT fk_cd_academic_year
           FOREIGN KEY (academic_year_id)
           REFERENCES academic_year (id)
           ON DELETE SET NULL
           ON UPDATE CASCADE;

-- 6i. claim_data → claim_details (cascade: rows die with the claim)
ALTER TABLE claim_data
    DROP   FOREIGN KEY IF EXISTS fk_cdata_claim;
ALTER TABLE claim_data
    ADD    CONSTRAINT fk_cdata_claim
           FOREIGN KEY (claimId)
           REFERENCES claim_details (claimId)
           ON DELETE CASCADE
           ON UPDATE CASCADE;

-- 6j. claim_approval_stages → claim_details
ALTER TABLE claim_approval_stages
    DROP   FOREIGN KEY IF EXISTS fk_cas_claim;
ALTER TABLE claim_approval_stages
    ADD    CONSTRAINT fk_cas_claim
           FOREIGN KEY (claimId)
           REFERENCES claim_details (claimId)
           ON DELETE CASCADE
           ON UPDATE CASCADE;

-- 6k. claim_approval_stages → user_details (who acted)
ALTER TABLE claim_approval_stages
    DROP   FOREIGN KEY IF EXISTS fk_cas_approved_by,
    DROP   FOREIGN KEY IF EXISTS fk_cas_flagged_by;
ALTER TABLE claim_approval_stages
    ADD    CONSTRAINT fk_cas_approved_by
           FOREIGN KEY (approved_by)
           REFERENCES user_details (userId)
           ON DELETE SET NULL
           ON UPDATE CASCADE,
    ADD    CONSTRAINT fk_cas_flagged_by
           FOREIGN KEY (flagged_by)
           REFERENCES user_details (userId)
           ON DELETE SET NULL
           ON UPDATE CASCADE;

-- 6l. flagged_claims → claim_details
ALTER TABLE flagged_claims
    DROP   FOREIGN KEY IF EXISTS fk_fc_claim;
ALTER TABLE flagged_claims
    ADD    CONSTRAINT fk_fc_claim
           FOREIGN KEY (claimId)
           REFERENCES claim_details (claimId)
           ON DELETE CASCADE
           ON UPDATE CASCADE;

-- 6m. flagged_claims → user_details (approver who flagged)
ALTER TABLE flagged_claims
    DROP   FOREIGN KEY IF EXISTS fk_fc_approver;
ALTER TABLE flagged_claims
    ADD    CONSTRAINT fk_fc_approver
           FOREIGN KEY (approver_user_id)
           REFERENCES user_details (userId)
           ON DELETE SET NULL
           ON UPDATE CASCADE;

-- 6n. completed_claims → claim_details
ALTER TABLE completed_claims
    DROP   FOREIGN KEY IF EXISTS fk_cc_claim;
ALTER TABLE completed_claims
    ADD    CONSTRAINT fk_cc_claim
           FOREIGN KEY (claimId)
           REFERENCES claim_details (claimId)
           ON DELETE RESTRICT
           ON UPDATE CASCADE;

-- 6o. completed_claims → user_details (claimant)
ALTER TABLE completed_claims
    DROP   FOREIGN KEY IF EXISTS fk_cc_user;
ALTER TABLE completed_claims
    ADD    CONSTRAINT fk_cc_user
           FOREIGN KEY (userId)
           REFERENCES user_details (userId)
           ON DELETE RESTRICT
           ON UPDATE CASCADE;

-- 6p. completed_claims → user_details (finance officer)
ALTER TABLE completed_claims
    DROP   FOREIGN KEY IF EXISTS fk_cc_payment_by;
ALTER TABLE completed_claims
    ADD    CONSTRAINT fk_cc_payment_by
           FOREIGN KEY (payment_approved_by)
           REFERENCES user_details (userId)
           ON DELETE SET NULL
           ON UPDATE CASCADE;


-- ============================================================
-- SECTION 7 — New Tables
-- All use CREATE TABLE IF NOT EXISTS — safe to re-run.
-- ============================================================

-- 7a. login_attempts
--     Formalises the DDL that previously lived only in a PHP comment.
CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip_address   VARCHAR(45)  NOT NULL,
    email_tried  VARCHAR(100) NULL
        COMMENT 'Email submitted — useful for detecting targeted attacks',
    attempted_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    INDEX idx_la_ip_time  (ip_address, attempted_at),
    INDEX idx_la_email    (email_tried)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Rate-limiting window. Rows older than LOGIN_WINDOW_SECONDS may be purged nightly.';


-- 7b. password_reset_tokens
--     Replaces the legacy user_logs / password_reset_code tables.
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    token_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    userId     INT          NOT NULL,
    email      VARCHAR(100) NOT NULL,
    token_hash VARCHAR(255) NOT NULL
        COMMENT 'Store hash(token); never the raw token',
    expires_at DATETIME     NOT NULL,
    used_at    DATETIME     NULL
        COMMENT 'NULL = not yet consumed',
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (token_id),
    INDEX idx_prt_email   (email),
    INDEX idx_prt_expires (expires_at),
    CONSTRAINT fk_prt_user
        FOREIGN KEY (userId) REFERENCES user_details (userId)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='One-time password-reset tokens. Default TTL: 3600 seconds.';


-- 7c. audit_log
--     Full immutable system audit trail — replaces the thin admin_logs table.
--     Never run DELETE on this table.
CREATE TABLE IF NOT EXISTS audit_log (
    audit_id    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_id    INT             NULL
        COMMENT 'userId of the person acting; NULL = system/cron',
    actor_role  VARCHAR(20)     NULL,
    action      VARCHAR(80)     NOT NULL
        COMMENT 'Verb constant: CLAIM_SUBMITTED, CLAIM_APPROVED, ACCOUNT_ACTIVATED …',
    entity_type VARCHAR(40)     NULL
        COMMENT 'Table/domain: claim, user, setting, payment …',
    entity_id   INT             NULL
        COMMENT 'PK of the affected row',
    detail      JSON            NULL
        COMMENT 'Structured before/after or context snapshot',
    ip_address  VARCHAR(45)     NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (audit_id),
    INDEX idx_al_actor   (actor_id),
    INDEX idx_al_action  (action),
    INDEX idx_al_entity  (entity_type, entity_id),
    INDEX idx_al_created (created_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Append-only audit trail. Do not DELETE rows.';


-- 7d. notifications
--     Per-user in-app notification inbox.
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    userId          INT          NOT NULL,
    type            VARCHAR(50)  NOT NULL
        COMMENT 'CLAIM_APPROVED | CLAIM_FLAGGED | ACCOUNT_ACTIVATED | PAYMENT_PROCESSED …',
    subject         VARCHAR(200) NOT NULL,
    body            TEXT         NULL,
    entity_type     VARCHAR(40)  NULL,
    entity_id       INT          NULL,
    is_read         TINYINT(1)   NOT NULL DEFAULT 0,
    read_at         DATETIME     NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (notification_id),
    INDEX idx_notif_user_unread (userId, is_read),
    INDEX idx_notif_created     (created_at),
    CONSTRAINT fk_notif_user
        FOREIGN KEY (userId) REFERENCES user_details (userId)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- 7e. email_queue
--     Outbound email ledger for async dispatch and retry logic.
CREATE TABLE IF NOT EXISTS email_queue (
    email_id     INT UNSIGNED                             NOT NULL AUTO_INCREMENT,
    recipient    VARCHAR(100)                             NOT NULL,
    subject      VARCHAR(250)                             NOT NULL,
    body_html    MEDIUMTEXT                               NOT NULL,
    status       ENUM('queued','sent','failed','skipped') NOT NULL DEFAULT 'queued',
    attempts     TINYINT UNSIGNED                         NOT NULL DEFAULT 0,
    last_attempt DATETIME                                 NULL,
    sent_at      DATETIME                                 NULL,
    error_msg    VARCHAR(500)                             NULL,
    related_type VARCHAR(40)                              NULL
        COMMENT 'Domain of the trigger: claim, user …',
    related_id   INT                                      NULL,
    created_at   DATETIME                                 NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (email_id),
    INDEX idx_eq_status  (status),
    INDEX idx_eq_created (created_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Async outbound email queue. Failed rows are retried up to 3 times.';


-- 7f. claim_attachments
--     Supporting documents uploaded alongside a claim.
CREATE TABLE IF NOT EXISTS claim_attachments (
    attachment_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    claimId       INT          NOT NULL,
    userId        INT          NOT NULL
        COMMENT 'Uploader — used for ownership checks',
    filename_orig VARCHAR(255) NOT NULL
        COMMENT 'Original filename shown to users',
    filename_disk VARCHAR(255) NOT NULL
        COMMENT 'UUID-based name stored on disk — never expose this path directly',
    mime_type     VARCHAR(100) NOT NULL,
    file_size     INT UNSIGNED NOT NULL
        COMMENT 'File size in bytes',
    uploaded_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (attachment_id),
    INDEX idx_att_claim (claimId),
    INDEX idx_att_user  (userId),
    CONSTRAINT fk_att_claim
        FOREIGN KEY (claimId) REFERENCES claim_details (claimId)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_att_user
        FOREIGN KEY (userId) REFERENCES user_details (userId)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- 7g. payment_records
--     Formal payment ledger managed by the finance module.
--     Replaces the current alert() placeholder in finance/index.php.
CREATE TABLE IF NOT EXISTS payment_records (
    payment_id     INT UNSIGNED                       NOT NULL AUTO_INCREMENT,
    claimId        INT                                NOT NULL,
    userId         INT                                NOT NULL
        COMMENT 'Claimant receiving the payment',
    amount         DECIMAL(10,2)                      NOT NULL,
    currency       CHAR(3)                            NOT NULL DEFAULT 'GHS',
    payment_ref    VARCHAR(100)                       NULL
        COMMENT 'Bank or transaction reference number',
    payment_method VARCHAR(50)                        NULL
        COMMENT 'Bank Transfer | Cheque | Mobile Money …',
    processed_by   INT                                NULL
        COMMENT 'userId of the finance officer',
    processed_at   DATETIME                           NULL,
    status         ENUM('pending','processed','failed') NOT NULL DEFAULT 'pending',
    notes          VARCHAR(500)                       NULL,
    created_at     DATETIME                           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (payment_id),
    INDEX idx_pr_claim   (claimId),
    INDEX idx_pr_user    (userId),
    INDEX idx_pr_status  (status),
    CONSTRAINT fk_pr_claim
        FOREIGN KEY (claimId) REFERENCES claim_details (claimId)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pr_user
        FOREIGN KEY (userId) REFERENCES user_details (userId)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pr_processor
        FOREIGN KEY (processed_by) REFERENCES user_details (userId)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='One record per payment disbursement. claimId links back to the originating claim.';


-- 7h. workflow_config
--     Key-value store for workflow parameters.
--     Allows admin to tune stage counts, timeouts, etc. without schema changes.
CREATE TABLE IF NOT EXISTS workflow_config (
    config_key   VARCHAR(60)  NOT NULL,
    config_value VARCHAR(255) NOT NULL,
    description  VARCHAR(255) NULL,
    updated_at   DATETIME     NOT NULL
                 DEFAULT CURRENT_TIMESTAMP
                 ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (config_key)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Runtime-tunable workflow parameters. Add rows to extend; do not rely on absent rows.';


-- ============================================================
-- SECTION 8 — Seed / Fix Reference Data
-- INSERT IGNORE: safe to re-run; skips rows that already exist.
-- ============================================================

-- Required settings rows
INSERT IGNORE INTO settings (settingName, settingValue)
VALUES
    ('fuelComponent', '0'),
    ('fuelAmount',    '0');

-- Workflow config defaults
INSERT IGNORE INTO workflow_config (config_key, config_value, description)
VALUES
    ('max_approval_stages',  '3',    'Number of approval stages before a claim is marked complete'),
    ('final_stage_action',   'complete', 'Action at last stage approval: complete | notify_only'),
    ('login_lockout_limit',  '5',    'Failed attempts before rate-limit engages (matches PHP constant)'),
    ('login_lockout_window', '900',  'Rate-limit window in seconds — 900 = 15 minutes'),
    ('password_reset_ttl',   '3600', 'Password-reset token lifetime in seconds'),
    ('fuel_amount_ghc',      '0.00', 'Flat fuel reimbursement per claim row in GH₵ (0 = off)');


-- ============================================================
-- SECTION 9 — Restore FK enforcement
-- ============================================================

SET FOREIGN_KEY_CHECKS = 1;


-- ============================================================
-- CHANGE SUMMARY
-- ============================================================
--
-- SECTION 1 — Primary Keys added
--   academic_year, department, course, programme, class,
--   user_bank_details, flagged_claims (new col: flagId),
--   completed_claims  (new col: completionId)
--
-- SECTION 2 — Data type fixes
--   lecturer_rank_rate.rate          INT(5)       → DECIMAL(8,2)
--   claim_details.rate               INT(5)       → DECIMAL(8,2)
--   claim_data.subTotal              DECIMAL(5,2) → DECIMAL(10,2)
--   claim_data.fuelComponent         VARCHAR(3)   → TINYINT(1)
--   completed_claims varchar cols    VARCHAR(25)  → VARCHAR(125)
--   flagged_claims varchar cols      VARCHAR(25/50)→VARCHAR(125/1000)
--   login_details.role               VARCHAR(16)  → VARCHAR(20)
--
-- SECTION 3 — New columns
--   department.faculty_id            INT NULL  (hierarchy FK)
--   saved_claims.faculty             VARCHAR(125) NULL
--   claim_details.academic_year_id   INT NULL
--   claim_details.claimant_note      VARCHAR(500) NULL
--   claim_approval_stages.approved_by / flagged_by   INT NULL
--   flagged_claims.approver_user_id  INT NULL
--   completed_claims: completed_by_user_id, payment_approved,
--                     payment_approved_at, payment_approved_by
--   user_details: last_login DATETIME, login_count INT UNSIGNED
--
-- SECTION 4 — Unique constraints added
--   user_details.email, login_details.email,
--   login_details.userId, user_bank_details.userId,
--   settings.settingName, lecturer_rank_rate.rank, faculty.name,
--   department.dept_name
--
-- SECTION 5 — Indexes added  (16 indexes across 8 tables)
--
-- SECTION 6 — Foreign keys wired (16 constraints)
--   department→faculty, programme→department, class→programme,
--   login_details→user_details, user_bank_details→user_details,
--   saved_claims→user_details, claim_details→user_details,
--   claim_details→academic_year, claim_data→claim_details,
--   claim_approval_stages→claim_details (+approved_by+flagged_by),
--   flagged_claims→claim_details (+approver_user_id),
--   completed_claims→claim_details+user_details+payment_approved_by
--
-- SECTION 7 — New tables (7)
--   login_attempts      rate-limit window (was only a PHP comment)
--   password_reset_tokens  replaces user_logs/password_reset_code
--   audit_log           full immutable system audit trail
--   notifications       per-user in-app inbox
--   email_queue         async outbound email ledger
--   claim_attachments   supporting documents on claims
--   payment_records     formal finance payment ledger
--   workflow_config     runtime-tunable workflow parameters
--
-- SECTION 8 — Seed data
--   settings: fuelComponent, fuelAmount rows guaranteed
--   workflow_config: 6 default parameter rows
--
-- ============================================================
-- END OF MIGRATION v1.1
-- ============================================================
