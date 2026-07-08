-- #5 Multi-class claims: a claim may cover several class codes, stored as a
-- comma-separated list (e.g. "BIT27, BIT28"). Widen the class columns from
-- VARCHAR(20) (a single code) to VARCHAR(255) to hold the joined list.
-- Idempotent: MODIFY is safe to re-run.

ALTER TABLE `claim_details` MODIFY `class` VARCHAR(255) NULL;
ALTER TABLE `saved_claims`  MODIFY `class` VARCHAR(255) NULL;
