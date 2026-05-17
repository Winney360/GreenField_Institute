# Greenfield Institute Course Registration System

A three-tier web application for the Greenfield Institute case study:
HTML/CSS/JS front-end · PHP business logic · MySQL database · XML import/export.

## Live Demo

- **Live site:** <https://greenfield-institute.infinityfreeapp.com/>
- **Video walkthrough:** <https://youtu.be/8QvgyfAVpz8>

> # ⚠️ IMPORTANT — OPEN THE LIVE SITE IN MICROSOFT EDGE ⚠️
>
> **Do NOT use Google Chrome.** Chrome's Safe Browsing service has flagged
> the shared InfinityFree hosting IP (`185.27.134.143`) because of unrelated
> phishing activity on other free-tier accounts that share the same IP.
> The flag is inherited by every domain pointing to that IP — including
> brand-new ones like ours.
>
> **This project itself contains no malware, phishing, or harmful content.**
> It is a course-registration demo built for an academic case study. The
> SSL certificate is valid (issued by Let's Encrypt) and the site is fully
> functional. A review request has already been submitted to Google.
>
> ✅ **Microsoft Edge** — opens the site normally with a valid SSL padlock.
>    Use this for marking.
>
> ✅ **Chrome Incognito Window** (Ctrl + Shift + N) — also works on a fresh
>    machine. Use this if Edge is unavailable.
>
> ❌ **Regular Chrome window** — will show a red "Dangerous site" page.
>    This is a Chrome-specific cosmetic issue and does not affect any
>    functionality of the system.

**Quick login for marking:**

| Role          | Email                         | Password      |
| ------------- | ----------------------------- | ------------- |
| Administrator | `admin@greenfield.edu`        | `password123` |
| Student       | `test@student.greenfield.edu` | `testing123`  |

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

## Default accounts (local install)

| Role          | Email                         | Password      |
| ------------- | ----------------------------- | ------------- |
| Administrator | `admin@greenfield.edu`        | `password123` |
| Student       | `test@student.greenfield.edu` | `testing123`  |

These accounts are seeded with working bcrypt hashes — no extra setup step needed. The same credentials apply to the live deployment.

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
