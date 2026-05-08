-- Greenfield Institute — Course Registration System
-- Database schema for the data tier (MySQL)

DROP DATABASE IF EXISTS greenfield_db;
CREATE DATABASE greenfield_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE greenfield_db;

-- ----------------------------------------------------------------------
-- Users (students + administrators) — single table, role column
-- ----------------------------------------------------------------------
CREATE TABLE users (
    user_id        INT AUTO_INCREMENT PRIMARY KEY,
    full_name      VARCHAR(120) NOT NULL,
    email          VARCHAR(160) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    role           ENUM('student','admin') NOT NULL DEFAULT 'student',
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
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
    credits        TINYINT UNSIGNED NOT NULL DEFAULT 3,
    capacity       INT UNSIGNED NOT NULL DEFAULT 30,
    department     VARCHAR(80)  NOT NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------------------------------------------------
-- Registrations — links students to courses (composite uniqueness
-- guarantees a student cannot register for the same course twice)
-- ----------------------------------------------------------------------
CREATE TABLE registrations (
    registration_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    course_id       INT NOT NULL,
    registered_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status          ENUM('active','dropped') NOT NULL DEFAULT 'active',
    UNIQUE KEY uniq_user_course (user_id, course_id),
    CONSTRAINT fk_reg_user   FOREIGN KEY (user_id)   REFERENCES users(user_id)   ON DELETE CASCADE,
    CONSTRAINT fk_reg_course FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE
);

-- ----------------------------------------------------------------------
-- Seed: one administrator + a handful of students.
-- Default password for every seeded account is: password123
-- (hash generated with PHP password_hash() / PASSWORD_DEFAULT, bcrypt)
-- ----------------------------------------------------------------------
INSERT INTO users (full_name, email, password_hash, role) VALUES
('System Administrator', 'admin@greenfield.edu',
 '$2y$10$E8m3aD3o4cV2yJtGmqz0cuJjW/2T9G8h0P5Yw8NfL3J2xY4nQwG.G', 'admin'),
('Alice Mwangi',   'alice@student.greenfield.edu',
 '$2y$10$E8m3aD3o4cV2yJtGmqz0cuJjW/2T9G8h0P5Yw8NfL3J2xY4nQwG.G', 'student'),
('Brian Otieno',   'brian@student.greenfield.edu',
 '$2y$10$E8m3aD3o4cV2yJtGmqz0cuJjW/2T9G8h0P5Yw8NfL3J2xY4nQwG.G', 'student');

-- Seed: courses
INSERT INTO courses (course_code, title, description, instructor, credits, capacity, department) VALUES
('CS101', 'Introduction to Computer Science',
 'Foundations of computing, problem solving, and programming with Python.',
 'Dr. J. Kamau', 3, 60, 'Computing'),
('CS210', 'Data Structures & Algorithms',
 'Lists, trees, graphs, sorting, searching, and algorithmic complexity.',
 'Prof. M. Achieng', 4, 45, 'Computing'),
('IT220', 'Web Technologies',
 'HTML, CSS, JavaScript, PHP and databases for full-stack web development.',
 'Mr. P. Njoroge', 3, 50, 'Computing'),
('MA110', 'Calculus I',
 'Limits, differentiation, integration and applications.',
 'Dr. R. Wanjiku', 4, 80, 'Mathematics'),
('BU150', 'Principles of Management',
 'Theory and practice of management in modern organizations.',
 'Mrs. L. Kiprop', 3, 70, 'Business');
