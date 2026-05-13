-- Greenfield Institute — Course Registration System
-- Database schema for the data tier (MySQL)
--
-- HOW TO IMPORT
--   In phpMyAdmin:
--     1. Click your database name in the LEFT SIDEBAR first so it's
--        selected (e.g. greenfield_db on XAMPP, or
--        if0_xxxxxxxx_greenfield on InfinityFree).
--     2. Click the "Import" tab and choose this file.
--     3. Click "Go".
--
--   On a fresh XAMPP install you'll need to create the empty
--   database manually first (CREATE DATABASE greenfield_db; in the
--   SQL tab, or use the Databases tab).
--
--   After import, the seeded accounts (admin + alice + brian) can log
--   in immediately with password `password123` — real bcrypt hashes are
--   baked into the seed below.
--
-- HOW TO ADD A NEW ADMIN (no signup page for admins — created directly):
--   1. Generate a bcrypt hash for your chosen password by running:
--        & "C:\xampp\php\php.exe" -r "echo password_hash('yourpw', PASSWORD_DEFAULT);"
--      (Or use any online bcrypt generator with cost = 10.)
--   2. In phpMyAdmin → greenfield_db → SQL tab, run:
--        INSERT INTO users (full_name, email, password_hash, role)
--        VALUES ('Their Name', 'their@email.com', '<paste-hash-here>', 'admin');

-- ----------------------------------------------------------------------
-- Users (students + administrators) — single table, role column
--
-- Schema design notes:
--   • password_hash is NULLABLE. Admins pre-register admitted students
--     by inserting name + email + registration_number with no password;
--     the student then activates the account from the signup page,
--     which writes the password_hash. Login fails until that happens.
--   • registration_number is UNIQUE (with NULL allowed) so two students
--     can't claim the same admission ID, but admins and old rows can
--     omit it.
--   • The five optional profile fields (registration_number through
--     programme) are filled in over time — admin sets reg_number on
--     admission, students fill the rest from their profile page.
-- ----------------------------------------------------------------------
CREATE TABLE users (
    user_id             INT AUTO_INCREMENT PRIMARY KEY,
    full_name           VARCHAR(120) NOT NULL,
    registration_number VARCHAR(20)  NULL,
    year_of_birth       INT          NULL,
    gender              ENUM('male','female','other','prefer_not_to_say') NULL,
    department          VARCHAR(80)  NULL,
    programme           VARCHAR(120) NULL,
    email               VARCHAR(160) NOT NULL UNIQUE,
    password_hash       VARCHAR(255) NULL,
    role                ENUM('student','admin') NOT NULL DEFAULT 'student',
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_registration_number (registration_number)
);

-- ----------------------------------------------------------------------
-- Courses
-- ----------------------------------------------------------------------
CREATE TABLE courses (
    course_id      INT AUTO_INCREMENT PRIMARY KEY,
    course_code    VARCHAR(20)  NOT NULL UNIQUE,
    title          VARCHAR(160) NOT NULL,
    description    TEXT,
    instructor     VARCHAR(120) NOT NULL,
    capacity       INT UNSIGNED NOT NULL DEFAULT 30,
    department     VARCHAR(80)  NOT NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------------------------------------------------
-- Registrations — links students to courses
--
-- Status flow:
--   pending  → set when a student first registers; awaits admin review
--   approved → admin has confirmed enrolment
--   rejected → admin denied the request
--   dropped  → student or admin pulled the registration after approval
--
-- A composite UNIQUE (user_id, course_id) prevents a student from
-- having two rows for the same course. Re-registering after a drop
-- reuses the same row by flipping status back to pending.
-- ----------------------------------------------------------------------
CREATE TABLE registrations (
    registration_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    course_id       INT NOT NULL,
    registered_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status          ENUM('pending','approved','rejected','dropped') NOT NULL DEFAULT 'pending',
    UNIQUE KEY uniq_user_course (user_id, course_id),
    CONSTRAINT fk_reg_user   FOREIGN KEY (user_id)   REFERENCES users(user_id)   ON DELETE CASCADE,
    CONSTRAINT fk_reg_course FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE
);

-- ----------------------------------------------------------------------
-- Seed: one administrator + two pre-activated students for demos.
-- Default password for every seeded account is: password123
-- The same bcrypt hash is reused across all three since bcrypt verifies
-- against the password, not the hash bytes — fine for demo seed data.
-- ----------------------------------------------------------------------
INSERT INTO users (full_name, registration_number, email, password_hash, role) VALUES
('System Administrator', NULL,           'admin@greenfield.edu',
 '$2y$10$o1c2Mf2blVAjyAEA97gG6u.6AVvNhMreLf98U97Dl5x3iiP2wta0C', 'admin'),
('Alice Mwangi',         'GF2024-001',   'alice@student.greenfield.edu',
 '$2y$10$o1c2Mf2blVAjyAEA97gG6u.6AVvNhMreLf98U97Dl5x3iiP2wta0C', 'student'),
('Brian Otieno',         'GF2024-002',   'brian@student.greenfield.edu',
 '$2y$10$o1c2Mf2blVAjyAEA97gG6u.6AVvNhMreLf98U97Dl5x3iiP2wta0C', 'student');

-- Seed: a small course catalogue across a few departments.
INSERT INTO courses (course_code, title, description, instructor, capacity, department) VALUES
('CS101', 'Introduction to Computer Science',
 'Foundations of computing, problem solving, and programming with Python.',
 'Dr. J. Kamau', 60, 'Computing'),
('CS210', 'Data Structures & Algorithms',
 'Lists, trees, graphs, sorting, searching, and algorithmic complexity.',
 'Prof. M. Achieng', 45, 'Computing'),
('IT220', 'Web Technologies',
 'HTML, CSS, JavaScript, PHP and databases for full-stack web development.',
 'Mr. P. Njoroge', 50, 'Computing'),
('MA110', 'Calculus I',
 'Limits, differentiation, integration and applications.',
 'Dr. R. Wanjiku', 80, 'Mathematics'),
('BU150', 'Principles of Management',
 'Theory and practice of management in modern organizations.',
 'Mrs. L. Kiprop', 70, 'Business');
