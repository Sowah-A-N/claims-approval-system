-- #1 HR role + employee register for auto-activation.
--
-- An HR user maintains a list of bona-fide employees. When someone registers
-- with an email that appears in this list, their account is activated
-- automatically instead of waiting for manual admin activation.
--
-- Idempotent where practical: the MODIFY re-states the full enum, and the
-- CREATE TABLE uses IF NOT EXISTS.

-- 1. Allow the new 'hr' role on both auth tables.
ALTER TABLE `user_details`
    MODIFY `role` ENUM('finance','admin','approver','claimant','hr')
    NOT NULL DEFAULT 'claimant';

-- 2. The HR employee register. `email` is the key matched at registration.
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
-- Collation must match user_details (utf8mb4_unicode_ci) so the email joins
-- used for auto-activation and the "registered" flag don't hit an illegal
-- mix-of-collations error.
