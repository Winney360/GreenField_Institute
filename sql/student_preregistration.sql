-- ---------------------------------------------------------------------
-- Greenfield Institute — switch to admin-pre-registration model
--
-- Run this ONCE per environment via phpMyAdmin's SQL tab.
-- Allows users.password_hash to be NULL so an admin can pre-register a
-- student (name + email + reg-number) before the student creates an
-- account. The student's account is "activated" when they sign up and
-- the password is filled in.
-- ---------------------------------------------------------------------

ALTER TABLE users MODIFY COLUMN password_hash VARCHAR(255) NULL;
