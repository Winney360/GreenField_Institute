-- ---------------------------------------------------------------------
-- Greenfield Institute — drop the unused `credits` column from courses
--
-- Run this ONCE per environment via phpMyAdmin's SQL tab.
-- Application code no longer reads or writes this column.
-- ---------------------------------------------------------------------

ALTER TABLE courses DROP COLUMN credits;
