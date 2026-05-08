# Greenfield Institute — Course Registration System
## Project Report

### 1. Problem Statement
Greenfield Institute previously processed course registrations through emails and
manually maintained spreadsheets. The result was duplicate registrations, stale
course-availability information, and a poor experience for both students and
administrators. This project replaces that workflow with a centralised, web-based
registration system built on a clean three-tier architecture.

### 2. Three-Tier Architecture

```
+----------------------------------------------+
|  PRESENTATION  TIER  (Browser)               |
|  HTML  +  CSS  +  JavaScript  (AJAX)         |
|  index.html, dashboard.html, courses.html …  |
+---------------------▲------------------------+
                      │ HTTPS / JSON / XML
+---------------------▼------------------------+
|  BUSINESS-LOGIC  TIER  (Web server)          |
|  PHP — authentication, validation, rules     |
|  api/login.php, api/enroll.php, api/…        |
+---------------------▲------------------------+
                      │ PDO prepared statements
+---------------------▼------------------------+
|  DATA  TIER  (MySQL)                         |
|  users, courses, registrations               |
|  sql/greenfield.sql                          |
+----------------------------------------------+
```

Each tier has a single responsibility and only talks to the tier directly below
it. The browser never speaks to MySQL; PHP never renders HTML for the dynamic
pages — it returns JSON (or XML), and the browser builds the DOM from it.

#### 2.1 Presentation tier — HTML, CSS, JavaScript
- `index.html`, `signup.html` — authentication screens.
- `dashboard.html` — the student landing page after login.
- `courses.html` — searchable, filterable catalogue, with AJAX live-search.
- `my-courses.html` — the student's active enrollments, with drop support.
- `assets/css/style.css` — a single responsive stylesheet (breakpoints at
  768 px and 480 px) using a green Greenfield colour palette.
- `assets/js/api.js` — a small AJAX helper that wraps `fetch()` and routes
  every 401 back to the login screen.

The pages are static HTML; all dynamic data is fetched through AJAX, which
keeps the UI snappy and decouples the front-end from the back-end.

#### 2.2 Business-logic tier — PHP
- `includes/db.php` — a single PDO connection helper with prepared statements
  enabled, emulation off, and exception-mode errors.
- `includes/auth.php` — `require_login()` / `require_admin()` guards plus
  small helpers for JSON responses and request-body parsing.
- `api/register.php`, `api/login.php`, `api/logout.php`, `api/me.php` — auth.
- `api/courses.php` — lists/searches/filters courses, joins the live
  enrollment count and computes `available_seats` and `is_full`.
- `api/enroll.php` — registers a student, enforcing all business rules
  (authenticated student, course exists, no duplicate, capacity not exceeded).
  The capacity check is performed inside a `SELECT … FOR UPDATE` transaction
  to remain race-free under concurrent registrations.
- `api/drop.php`, `api/my_courses.php` — student self-service.
- `api/admin_courses.php` — admin CRUD on courses.
- `api/admin_registrations.php` — admin view of every registration.
- `api/courses_xml.php` — XML export (see §3).
- `api/import_xml.php` — admin-only XML import.

The PHP tier is the **only** place where business rules live. Validation
happens server-side, since clients can be tampered with. SQL injection is
defeated by exclusively using parameterised PDO statements; cross-site
scripting is mitigated by escaping every dynamic value before it reaches the
DOM (`escapeHtml` in `api.js`, `htmlspecialchars` in PHP). Passwords are
stored as bcrypt hashes via `password_hash()` / `password_verify()`.

#### 2.3 Data tier — MySQL
Three tables (see [sql/greenfield.sql](sql/greenfield.sql)):

| Table          | Purpose                                                             |
|----------------|---------------------------------------------------------------------|
| `users`        | Students and administrators (single table, `role` column)           |
| `courses`      | Course catalogue                                                    |
| `registrations`| Many-to-many link between users and courses, with status            |

A composite `UNIQUE (user_id, course_id)` index on `registrations` enforces at
the database level the "no duplicate registration" rule. Foreign keys with
`ON DELETE CASCADE` keep the data consistent if a user or course is removed.

### 3. Use of XML
The case study calls for XML to demonstrate structured-data exchange. The
system supports XML in **both directions**:

- **Export.** `GET /api/courses_xml.php` builds a well-formed XML document
  from the live database using `DOMDocument` and serves it with a proper
  `application/xml` MIME type. This makes the catalogue consumable by
  partner systems, mobile apps, or RSS-style readers without changing the
  business-logic tier.
- **Import.** `POST /api/import_xml.php` (admin-only) parses
  `data/courses_sample.xml` (or an uploaded file) using `simplexml_load_file`
  and merges its `<course>` elements into the `courses` table with an upsert
  (`INSERT … ON DUPLICATE KEY UPDATE`). A bulk-load workflow that previously
  took spreadsheet copy-pasting is now one click in the admin dashboard.

### 4. Request Flow Example — "Register for CS210"
1. The student clicks **Register** in `courses.html`.
2. JavaScript calls `POST /api/enroll.php` with `{course_id: 2}` as JSON.
3. `enroll.php` checks the session (auth), opens a DB transaction, locks the
   course row, verifies the student is not already registered and the course
   is not full.
4. PDO inserts a row into `registrations`. The transaction commits.
5. PHP returns `{ok: true, message: "Successfully registered."}`.
6. The browser flashes the success banner and re-fetches `courses.php`,
   which now reports the updated enrollment count and available seats.

If any rule fails (course full, already registered, course missing), PHP
returns the appropriate HTTP status and a human-readable error, which the
browser surfaces in a flash banner. **No business decision is made in the
browser.**

### 5. Additional Features Implemented
- **Search & filter:** keyword + department filter on courses, with a 200 ms
  debounce so the server is hit at most once per typing burst.
- **AJAX everywhere:** no full page reloads after the initial HTML.
- **Responsive design:** tested down to 360 px width.
- **Secure password handling:** bcrypt via `password_hash` / `password_verify`,
  generic "invalid credentials" error to prevent user enumeration, session
  ID regeneration on login.
- **Role-based access control:** students cannot reach admin pages; admins
  are redirected to the admin dashboard on login.
- **Admin dashboard:** at-a-glance metrics (students, courses, active
  registrations), full course CRUD with a modal editor, registrations table
  with client-side filtering, and one-click XML import/export.

### 6. Setup Instructions
1. Start MySQL and run `sql/greenfield.sql` to create the database and seed
   data. The seeded password for every account is `password123`.
2. Edit `includes/config.php` so that `DB_HOST`, `DB_USER`, `DB_PASS` match
   your local MySQL credentials.
3. Serve the project root with PHP. From the project directory:

   ```
   php -S localhost:8000
   ```

4. Open <http://localhost:8000/> and log in.
   - Administrator: `admin@greenfield.edu` / `password123`
   - Student:       `alice@student.greenfield.edu` / `password123`

### 7. Submission Contents
| Path                     | Purpose                                       |
|--------------------------|-----------------------------------------------|
| `index.html` etc.        | HTML front-end pages                          |
| `assets/css/style.css`   | Stylesheet                                    |
| `assets/js/api.js`       | Shared AJAX client                            |
| `api/*.php`              | PHP business-logic endpoints                  |
| `admin/*.php`            | PHP-rendered admin pages                      |
| `includes/*.php`         | DB, config, auth shared modules               |
| `data/courses_sample.xml`| Sample XML used by the import endpoint        |
| `sql/greenfield.sql`     | Database schema + seed data                   |
| `REPORT.md`              | This report                                   |

### 8. Conclusion
The system replaces Greenfield Institute's manual, spreadsheet-driven
registration process with a maintainable web application. Splitting the
code along the three-tier boundary means each layer can evolve on its own:
the front-end could be re-skinned (or replaced with a mobile app) without
touching PHP; the business logic could be ported to another language
without touching the database; and the database can be migrated or scaled
without affecting any client. The XML import/export adds a cleanly defined
data-exchange surface that lets Greenfield interoperate with other systems
without exposing its internal database schema.
