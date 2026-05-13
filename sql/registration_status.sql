-- ---------------------------------------------------------------------
-- Greenfield Institute — add admin-approval workflow to registrations
--
-- Run this ONCE per environment via phpMyAdmin (SQL tab).
-- Safe even if existing rows have status = 'active' — they're migrated
-- to 'approved' as part of the change.
-- ---------------------------------------------------------------------

-- Temporarily widen the enum so old 'active' values stay valid while we migrate.
ALTER TABLE registrations
    MODIFY COLUMN status ENUM('pending','approved','rejected','dropped','active')
        NOT NULL DEFAULT 'pending';

-- Old approved-equivalent rows → 'approved'.
UPDATE registrations SET status = 'approved' WHERE status = 'active';

-- Narrow the enum to the final set of states.
ALTER TABLE registrations
    MODIFY COLUMN status ENUM('pending','approved','rejected','dropped')
        NOT NULL DEFAULT 'pending';
