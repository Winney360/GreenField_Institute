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

3. **Set seed passwords** — the seed SQL inserts placeholder hashes; run
   this once to replace them with a real bcrypt hash of `password123`:

   ```bash
   php sql/seed_passwords.php
   ```

4. **Serve** — from the project directory:

   ```bash
   php -S localhost:8000
   ```

5. Open <http://localhost:8000/>.

## Default accounts

| Role          | Email                              | Password      |
|---------------|------------------------------------|---------------|
| Administrator | `admin@greenfield.edu`             | `password123` |
| Student       | `alice@student.greenfield.edu`     | `password123` |
| Student       | `brian@student.greenfield.edu`     | `password123` |

> The `sql/seed_passwords.php` step (above) is what makes these accounts
> actually log in — without running it, the SQL seed hashes are placeholders
> and the login form will reject every credential.

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
