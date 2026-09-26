-- Force a password change on first login (A-06 / A-13).
-- Set for accounts created with an admin-generated temporary password so the
-- user must choose their own password before using the system.
ALTER TABLE login_details
    ADD COLUMN force_password_change TINYINT(1) NOT NULL DEFAULT 0;
