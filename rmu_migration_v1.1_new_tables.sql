-- ============================================================
-- RMU Claims Approval System
-- Migration v1.1 — NEW TABLES
-- ============================================================
-- Prerequisite: Parts 1 and 2 applied.
--
-- Eight new tables:
--   1. login_attempts       rate-limit window (was a PHP comment)
--   2. password_reset_tokens replace legacy user_logs / password_reset_code
--   3. audit_log            immutable system audit trail
--   4. notifications        per-user in-app inbox
--   5. email_queue          async outbound email ledger
--   6. claim_attachments    supporting documents on claims
--   7. payment_records      formal finance payment ledger
--   8. workflow_config      runtime-tunable workflow parameters
--
-- All use CREATE TABLE IF NOT EXISTS — safe to re-run.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;


-- ============================================================
-- 1. login_attempts
--    Formalises the DDL that previously existed only as a
--    comment inside includes/auth.php.
--    is_login_rate_limited() / record_failed_login() /
--    clear_failed_logins() all target this table already.
-- ============================================================

CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ip_address`   VARCHAR(45)  NOT NULL,
    `email_tried`  VARCHAR(100) NULL     DEFAULT NULL
        COMMENT 'Email submitted — helps identify targeted attacks',
    `attempted_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_la_ip_time`  (`ip_address`, `attempted_at`),
    INDEX `idx_la_email`    (`email_tried`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Rate-limiting window. Rows older than LOGIN_WINDOW_SECONDS can be purged nightly.';


-- ============================================================
-- 2. password_reset_tokens
--    Replaces the incomplete legacy tables user_logs and
--    password_reset_code referenced in login/forgot_password/.
--    Store hash(token), never the raw token.
-- ============================================================

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
    `token_id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `userId`     INT          NOT NULL,
    `email`      VARCHAR(100) NOT NULL,
    `token_hash` VARCHAR(255) NOT NULL
        COMMENT 'SHA-256 or bcrypt hash of the raw token sent to the user',
    `expires_at` DATETIME     NOT NULL,
    `used_at`    DATETIME     NULL DEFAULT NULL
        COMMENT 'NULL = not yet consumed',
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`token_id`),
    INDEX `idx_prt_email`   (`email`),
    INDEX `idx_prt_expires` (`expires_at`),
    CONSTRAINT `fk_prt_user`
        FOREIGN KEY (`userId`)
        REFERENCES `user_details` (`userId`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='One-time password-reset tokens. Default TTL: 3600 s.';


-- ============================================================
-- 3. audit_log
--    Full immutable system audit trail.
--    Replaces the vestigial admin_logs table (log_id + timestamp).
--    Rule: never DELETE or UPDATE rows in this table.
-- ============================================================

CREATE TABLE IF NOT EXISTS `audit_log` (
    `audit_id`    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `actor_id`    INT             NULL DEFAULT NULL
        COMMENT 'userId of the person acting; NULL = system / cron',
    `actor_role`  VARCHAR(20)     NULL DEFAULT NULL,
    `action`      VARCHAR(80)     NOT NULL
        COMMENT 'Verb constant: CLAIM_SUBMITTED, CLAIM_APPROVED, ACCOUNT_ACTIVATED …',
    `entity_type` VARCHAR(40)     NULL DEFAULT NULL
        COMMENT 'Domain noun: claim, user, setting, payment …',
    `entity_id`   INT             NULL DEFAULT NULL
        COMMENT 'Primary key of the affected row',
    `detail`      JSON            NULL DEFAULT NULL
        COMMENT 'Structured before/after snapshot or context',
    `ip_address`  VARCHAR(45)     NULL DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`audit_id`),
    INDEX `idx_al_actor`   (`actor_id`),
    INDEX `idx_al_action`  (`action`),
    INDEX `idx_al_entity`  (`entity_type`, `entity_id`),
    INDEX `idx_al_created` (`created_at`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Append-only audit trail. Do NOT DELETE rows.';


-- ============================================================
-- 4. notifications
--    Per-user in-app notification inbox.
--    Triggered by claim state changes, account activation, etc.
-- ============================================================

CREATE TABLE IF NOT EXISTS `notifications` (
    `notification_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `userId`          INT          NOT NULL,
    `type`            VARCHAR(50)  NOT NULL
        COMMENT 'CLAIM_APPROVED | CLAIM_FLAGGED | ACCOUNT_ACTIVATED | PAYMENT_PROCESSED …',
    `subject`         VARCHAR(200) NOT NULL,
    `body`            TEXT         NULL DEFAULT NULL,
    `entity_type`     VARCHAR(40)  NULL DEFAULT NULL,
    `entity_id`       INT          NULL DEFAULT NULL,
    `is_read`         TINYINT(1)   NOT NULL DEFAULT 0,
    `read_at`         DATETIME     NULL DEFAULT NULL,
    `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`notification_id`),
    INDEX `idx_notif_user_unread` (`userId`, `is_read`),
    INDEX `idx_notif_created`     (`created_at`),
    CONSTRAINT `fk_notif_user`
        FOREIGN KEY (`userId`)
        REFERENCES `user_details` (`userId`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 5. email_queue
--    Outbound email ledger for async dispatch and retry logic.
--    A cron job reads status='queued', sends, marks status='sent'.
--    On failure: increments attempts, sets status='failed' after
--    exceeding the retry limit (recommend 3).
-- ============================================================

CREATE TABLE IF NOT EXISTS `email_queue` (
    `email_id`     INT UNSIGNED                              NOT NULL AUTO_INCREMENT,
    `recipient`    VARCHAR(100)                              NOT NULL,
    `subject`      VARCHAR(250)                              NOT NULL,
    `body_html`    MEDIUMTEXT                                NOT NULL,
    `status`       ENUM('queued','sent','failed','skipped')  NOT NULL DEFAULT 'queued',
    `attempts`     TINYINT UNSIGNED                          NOT NULL DEFAULT 0,
    `last_attempt` DATETIME                                  NULL DEFAULT NULL,
    `sent_at`      DATETIME                                  NULL DEFAULT NULL,
    `error_msg`    VARCHAR(500)                              NULL DEFAULT NULL,
    `related_type` VARCHAR(40)                               NULL DEFAULT NULL
        COMMENT 'Domain of the trigger event: claim, user …',
    `related_id`   INT                                       NULL DEFAULT NULL,
    `created_at`   DATETIME                                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`email_id`),
    INDEX `idx_eq_status`  (`status`),
    INDEX `idx_eq_created` (`created_at`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Async outbound email queue. Retry up to 3 times on failure.';


-- ============================================================
-- 6. claim_attachments
--    Supporting documents uploaded with a claim.
--    filename_disk is a UUID-based server-side name;
--    never expose it as a direct download path — route through
--    an ownership-checked PHP handler.
-- ============================================================

CREATE TABLE IF NOT EXISTS `claim_attachments` (
    `attachment_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `claimId`       INT          NOT NULL,
    `userId`        INT          NOT NULL
        COMMENT 'Uploader — used for ownership checks',
    `filename_orig` VARCHAR(255) NOT NULL
        COMMENT 'Original filename shown to the user',
    `filename_disk` VARCHAR(255) NOT NULL
        COMMENT 'UUID-based name stored on disk',
    `mime_type`     VARCHAR(100) NOT NULL,
    `file_size`     INT UNSIGNED NOT NULL
        COMMENT 'Size in bytes',
    `uploaded_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`attachment_id`),
    INDEX `idx_att_claim` (`claimId`),
    INDEX `idx_att_user`  (`userId`),
    CONSTRAINT `fk_att_claim`
        FOREIGN KEY (`claimId`)
        REFERENCES `claim_details` (`claimId`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_att_user`
        FOREIGN KEY (`userId`)
        REFERENCES `user_details` (`userId`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 7. payment_records
--    Formal payment ledger managed by the finance module.
--    Replaces the current alert() placeholder in
--    users/finance/index.php (approvePayment function).
-- ============================================================

CREATE TABLE IF NOT EXISTS `payment_records` (
    `payment_id`     INT UNSIGNED                         NOT NULL AUTO_INCREMENT,
    `claimId`        INT                                  NOT NULL,
    `userId`         INT                                  NOT NULL
        COMMENT 'Claimant receiving the payment',
    `amount`         DECIMAL(10,2)                        NOT NULL,
    `currency`       CHAR(3)                              NOT NULL DEFAULT 'GHS',
    `payment_ref`    VARCHAR(100)                         NULL DEFAULT NULL
        COMMENT 'Bank or transaction reference number',
    `payment_method` VARCHAR(50)                          NULL DEFAULT NULL
        COMMENT 'Bank Transfer | Cheque | Mobile Money …',
    `processed_by`   INT                                  NULL DEFAULT NULL
        COMMENT 'userId of the finance officer',
    `processed_at`   DATETIME                             NULL DEFAULT NULL,
    `status`         ENUM('pending','processed','failed') NOT NULL DEFAULT 'pending',
    `notes`          VARCHAR(500)                         NULL DEFAULT NULL,
    `created_at`     DATETIME                             NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`payment_id`),
    INDEX `idx_pr_claim`  (`claimId`),
    INDEX `idx_pr_user`   (`userId`),
    INDEX `idx_pr_status` (`status`),
    CONSTRAINT `fk_pr_claim`
        FOREIGN KEY (`claimId`)
        REFERENCES `claim_details` (`claimId`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_pr_user`
        FOREIGN KEY (`userId`)
        REFERENCES `user_details` (`userId`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_pr_processor`
        FOREIGN KEY (`processed_by`)
        REFERENCES `user_details` (`userId`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='One row per payment disbursement. Links back to the originating claim.';


-- ============================================================
-- 8. workflow_config
--    Key-value store for runtime-tunable workflow parameters.
--    Lets an admin change stage counts, lock-out thresholds, etc.
--    without a schema change.
-- ============================================================

CREATE TABLE IF NOT EXISTS `workflow_config` (
    `config_key`   VARCHAR(60)  NOT NULL,
    `config_value` VARCHAR(255) NOT NULL,
    `description`  VARCHAR(255) NULL DEFAULT NULL,
    `updated_at`   DATETIME     NOT NULL
                   DEFAULT CURRENT_TIMESTAMP
                   ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`config_key`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Runtime workflow parameters. Add rows freely; never rely on absent keys.';

-- Seed default values
INSERT IGNORE INTO `workflow_config`
    (`config_key`, `config_value`, `description`)
VALUES
    ('max_approval_stages',  '3',
     'Number of approval stages before a claim is marked complete'),
    ('login_lockout_limit',  '5',
     'Failed attempts before rate-limit engages — mirrors PHP LOGIN_MAX_ATTEMPTS'),
    ('login_lockout_window', '900',
     'Rate-limit window in seconds — mirrors PHP LOGIN_WINDOW_SECONDS'),
    ('password_reset_ttl',   '3600',
     'Password-reset token lifetime in seconds'),
    ('fuel_amount_ghc',      '0.00',
     'Flat fuel reimbursement per claim row in GH₵; 0 = feature off');

-- Ensure required settings rows exist
INSERT IGNORE INTO `settings` (`settingName`, `settingValue`)
VALUES
    ('fuelComponent', '0'),
    ('fuelAmount',    '0');


-- ============================================================
-- END — NEW TABLES
-- ============================================================
-- Verify:
--   SHOW TABLES LIKE 'login_attempts';
--   SHOW TABLES LIKE 'password_reset_tokens';
--   SHOW TABLES LIKE 'audit_log';
--   SHOW TABLES LIKE 'notifications';
--   SHOW TABLES LIKE 'email_queue';
--   SHOW TABLES LIKE 'claim_attachments';
--   SHOW TABLES LIKE 'payment_records';
--   SHOW TABLES LIKE 'workflow_config';
--   SELECT * FROM workflow_config;
-- ============================================================

SET FOREIGN_KEY_CHECKS = 1;
