# Greenfield Institute Course Registration System

A three-tier web application for the Greenfield Institute case study:
HTML/CSS/JS front-end · PHP business logic · MySQL database · XML import/export.

## Quick start

1. **Database** — start MySQL, then run:

   ```bash
   mysql -u root -p < sql/greenfield.sql
   ```

   The script creates `greenfield_db`, all tables, and seed data. Default
   password for every seeded account is `password123`.

2. **Configure** — edit [includes/config.php](includes/config.php) so the
   `DB_USER` / `DB_PASS` values match your local MySQL.

3. **Serve** — from the project directory:

   ```bash
   php -S localhost:8000
   ```

4. Open <http://localhost:8000/>.

## Default accounts

| Role          | Email                              | Password      |
|---------------|------------------------------------|---------------|
| Administrator | `admin@greenfield.edu`             | `password123` |
| Student       | `alice@student.greenfield.edu`     | `password123` |
| Student       | `brian@student.greenfield.edu`     | `password123` |

These accounts are seeded by `sql/greenfield.sql` with working bcrypt hashes — no extra setup step is needed.

## Adding another admin

Admins are created **only** by direct SQL — there's no signup flow for them. To add one:

1. Generate a bcrypt hash for the chosen password:
   ```bash
   php -r "echo password_hash('yourpassword', PASSWORD_DEFAULT);"
   ```
2. In phpMyAdmin → `greenfield_db` → SQL tab, run:
   ```sql
   INSERT INTO users (full_name, email, password_hash, role)
   VALUES ('Admin Name', 'admin@example.com', '<paste-hash-here>', 'admin');
   ```

## Adding a student

Admins admit students via the **Students** page in the admin console — that pre-registers them. The student then activates their account at signup using their email + registration number.

## Project layout

```
.
├── index.html             ← login
├── signup.html            ← student account creation
├── dashboard.html         ← student landing page
├── courses.html           ← browse / search / filter / register
├── my-courses.html        ← student's enrollments
├── admin/
│   ├── dashboard.php
│   ├── manage_courses.php ← full course CRUD with XML import/export
│   └── registrations.php
├── api/                   ← PHP business-logic endpoints (JSON)
├── assets/css/style.css
├── assets/js/api.js
├── data/courses_sample.xml
├── includes/              ← shared PHP modules
├── sql/greenfield.sql
└── REPORT.md              ← architecture report
```

For the full architectural write-up see [REPORT.md](REPORT.md).
