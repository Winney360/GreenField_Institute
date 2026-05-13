-- ---------------------------------------------------------------------
-- Greenfield Institute — add student profile fields to users table
-- Run this ONCE per environment (local + InfinityFree) via phpMyAdmin's
-- Import or SQL tab. Safe to re-run only if the columns don't exist yet.
-- ---------------------------------------------------------------------

ALTER TABLE users
    ADD COLUMN registration_number VARCHAR(20)  NULL AFTER full_name,
    ADD COLUMN year_of_birth       INT          NULL AFTER registration_number,
    ADD COLUMN gender              ENUM('male','female','other','prefer_not_to_say') NULL AFTER year_of_birth,
    ADD COLUMN department          VARCHAR(80)  NULL AFTER gender,
    ADD COLUMN programme           VARCHAR(120) NULL AFTER department,
    ADD UNIQUE KEY uk_registration_number (registration_number);
