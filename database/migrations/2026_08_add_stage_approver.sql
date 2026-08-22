-- #5(B): capture WHICH officer approved each stage, so the printed claim form can
-- show the approver's name (not just the role) on future claims. Idempotent-ish:
-- run once; MySQL/MariaDB will error if the column already exists (safe to ignore).
ALTER TABLE `claim_approval_stages` ADD COLUMN `approver_id` INT NULL AFTER `status`;
