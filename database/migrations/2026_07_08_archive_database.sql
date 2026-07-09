-- #2 Archive database.
--
-- Archiving MOVES records out of the primary database into a parallel archive
-- database named "<primary_db>_archive" (e.g. `doc-app_archive`). Because the
-- rows physically leave the primary tables, every normal listing/dropdown stops
-- showing them with no read-path changes.
--
-- The archive database and its mirror tables are AUTO-PROVISIONED at runtime by
-- includes/archive.php (archive_ensure_schema) the first time the admin Archive
-- page or an archive action runs — each mirror table is created with
--   CREATE TABLE `<db>_archive`.`t` LIKE `t`
-- then its non-PRIMARY UNIQUE indexes are dropped (so the same class code /
-- email may recur over time) and `archived_at` / `archived_by` columns are added.
--
-- This file only creates the empty archive database up front for environments
-- that provision schemas via migrations. Replace `doc-app_archive` with your
-- own "<primary_db>_archive" name if your database is not named `doc-app`.

CREATE DATABASE IF NOT EXISTS `doc-app_archive`
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

-- Mirror tables are created automatically on first use. To pre-create them
-- manually, for each archivable table run (adjusting the primary db name):
--   CREATE TABLE `doc-app_archive`.`hr_employees`   LIKE `hr_employees`;
--   CREATE TABLE `doc-app_archive`.`classes`        LIKE `classes`;
--   CREATE TABLE `doc-app_archive`.`banks_branches` LIKE `banks_branches`;
--   CREATE TABLE `doc-app_archive`.`audit_log`      LIKE `audit_log`;
-- then drop non-PRIMARY unique indexes and
--   ALTER TABLE `doc-app_archive`.`<t>` ADD `archived_at` DATETIME NULL, ADD `archived_by` INT NULL;
