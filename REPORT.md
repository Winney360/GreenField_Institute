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
+-------------------------------------------------------+
|  PRESENTATION  TIER  (Browser)                        |
|  HTML + CSS + JavaScript (fetch-based AJAX)           |
|  index.html, dashboard.html, courses.html,            |
|  my-courses.html, profile.html, settings.html,        |
|  admin/dashboard.php, admin/students.php, …           |
+--------------------------▲----------------------------+
                           │  HTTP — JSON (or XML)
+--------------------------▼----------------------------+
|  BUSINESS-LOGIC  TIER  (Web server)                   |
|  PHP — authentication, authorisation, validation,     |
|  business rules, JSON/XML serialisation               |
|  api/login.php, api/enroll.php, api/dashboard.php,    |
|  api/admin_registrations.php, api/courses_xml.php, …  |
+--------------------------▲----------------------------+
                           │  PDO + prepared statements
+--------------------------▼----------------------------+
|  DATA  TIER  (MySQL)                                  |
|  users, courses, registrations                        |
|  sql/greenfield.sql (+ migration scripts)             |
+-------------------------------------------------------+
```

Each tier has a single responsibility and only talks to the tier directly below
it. The browser never speaks to MySQL; PHP never renders HTML for the dynamic
pages — it returns JSON (or XML for the data-exchange endpoint), and the
browser builds the DOM from it.

#### 2.1 Presentation tier — HTML, CSS, JavaScript

**Pages**

| Page | Audience | Purpose |
|---|---|---|
| `index.html` | public | Combined login + signup with a 3.5 s diagonal-split animation on desktop. `signup.html` is a thin redirect kept for friendly URLs. |
| `dashboard.html` | student | Personalised landing — profile-completion nag, two stat cards, current registered courses (scrollable table with column headings), recommended courses card grid. |
| `courses.html` | student | Live-search catalogue with department filter (themed custom dropdown). |
| `my-courses.html` | student | Registered units in a horizontally-scrollable table with status pills and a Drop action. |
| `profile.html`, `settings.html` | student | Edit optional profile fields; change password. |
| `admin/dashboard.php` | admin | Action-items strip (pending registrations, capacity alerts), totals, activity feed. |
| `admin/students.php` | admin | Pre-register new students and view sign-up status. |
| `admin/manage_courses.php` | admin | Course CRUD with a modal editor, XML import (file upload), and dated XML export. |
| `admin/registrations.php` | admin | Approve / reject pending registrations and drop already-processed ones. |

**Shared client assets**

- `assets/css/style.css` — single responsive stylesheet using a mint
  Greenfield palette (`#2DD49B`) on an off-white background. Mobile-first
  breakpoints at **1199 px** (sidebar collapses to hamburger, navbar
  brand stacks), **640 px** (tighter list scrolling), and **480 px**
  (toolbar inputs stack to one column).
- `assets/js/api.js` — small AJAX helper wrapping `fetch()`, sending the
  `X-Requested-With: XMLHttpRequest` header so PHP can detect AJAX calls,
  and bouncing any `401` back to `index.html`.
- `assets/js/nav.js` — toggles the off-canvas sidebar on phones, manages
  the click-outside backdrop, and personalises the navbar greeting.
- `assets/js/dropdown.js` — custom themed listbox component used in
  place of `<select>` for the catalogue filter. Native `<select>` only
  permits styling the trigger; the option panel is rendered by the OS
  and cannot be themed, so the project ships an ARIA-compliant
  button + listbox replacement (keyboard nav, click-outside, focus
  management) that matches the rest of the brand.
- `assets/js/auth.js` — drives the login ↔ signup diagonal-split animation.

All page-specific JavaScript lives inline at the bottom of each HTML/PHP
file and renders the DOM from JSON returned by the PHP tier. There are
no full page reloads once the initial document has loaded — every
search keystroke, every Register click, every approval triggers a
single fetch call and updates a small region of the DOM.

**Responsive design**

- Sidebar navigation collapses to a hamburger-triggered slide-out drawer
  at ≤ 1199 px (with a dimming backdrop that closes the sidebar on click).
- Auth screens swap from a desktop diagonal-split layout to a stacked
  image-on-top / form-below layout at ≤ 1199 px.
- Wide data tables (current courses, my courses, admin students,
  admin course catalogue, admin registrations) are wrapped in a
  scroll container that handles horizontal overflow on phones while
  preserving column headers and chrome.
- Toolbar pills (search input + filter dropdown) stack to full-width
  controls at ≤ 480 px; above that they share a row so they match the
  width of the card grid below them.
- Stat cards and recommendation cards use the same `.card-foot`
  pattern so secondary metadata and the primary action sit on a single
  row that wraps when space runs out.

#### 2.2 Business-logic tier — PHP

**Shared modules**

- `includes/config.php` (gitignored; `config.example.php` is the template) —
  DB credentials and the BASE_PATH constant for InfinityFree.
- `includes/db.php` — single PDO factory: prepared statements enabled,
  emulation off, errors thrown as exceptions.
- `includes/auth.php` — session helpers (`require_login`, `require_admin`),
  JSON response helper, request-body parser, and the AJAX detection helper.

**API endpoints (JSON in, JSON out)**

| Endpoint | Purpose |
|---|---|
| `api/register.php` | Completes sign-up for a pre-registered student (matches email + reg-number, writes the bcrypt password hash). |
| `api/login.php` | Authenticates email + password, regenerates session ID on success. |
| `api/logout.php`, `api/me.php` | Session teardown and current-user lookup. |
| `api/dashboard.php` | Single round-trip that returns stats, current courses, recommendations, and the profile-completion percentage. |
| `api/courses.php` | Lists/searches/filters courses with live enrollment count and `is_full` flag. |
| `api/enroll.php` | Registers a student (see §4). |
| `api/drop.php`, `api/my_courses.php` | Student self-service. |
| `api/profile.php`, `api/change_password.php` | Profile edits + password change with current-password verification. |
| `api/admin_overview.php` | Powers the admin landing page: action items, totals, capacity alerts, activity feed. |
| `api/admin_students.php` | Pre-register, list, and delete student stubs. |
| `api/admin_courses.php` | Admin CRUD on courses. |
| `api/admin_registrations.php` | Approve, reject, and drop registrations (drop works on already-approved or already-rejected rows to fix mistakes). |
| `api/courses_xml.php` | XML export (see §3). |
| `api/import_xml.php` | Admin-only XML import (see §3). |

**The PHP tier is the only place where business rules live.** Validation
happens server-side because clients can be tampered with. SQL injection is
defeated by exclusively using parameterised PDO statements; cross-site
scripting is mitigated by escaping every dynamic value before it reaches
the DOM (`escapeHtml` in `api.js`, `htmlspecialchars` in PHP). Passwords
are stored as bcrypt hashes via `password_hash()` / `password_verify()`.
Login uses a generic "Invalid credentials" message to prevent user
enumeration. Session IDs are regenerated on login to prevent session
fixation.

**Role-based access control.** Every page that needs auth calls
`require_login()` or `require_admin()` at the top of the file. Admins
landing on the student dashboard are redirected to the admin dashboard,
and vice-versa, so each role only sees what it should.

#### 2.3 Data tier — MySQL

Three tables (defined in [sql/greenfield.sql](sql/greenfield.sql)):

| Table          | Purpose |
|----------------|---------|
| `users`        | Students and administrators in a single table with a `role` column. `password_hash` is nullable — admins create student stubs without a password and the student fills it in at sign up. Five optional profile fields (`registration_number`, `year_of_birth`, `gender`, `department`, `programme`) are added/edited over time. |
| `courses`      | Course catalogue: code, title, description, instructor, capacity, department. |
| `registrations`| Many-to-many link between users and courses. Status flow: **pending → approved / rejected → dropped**. A composite `UNIQUE (user_id, course_id)` index enforces "no duplicate registration" at the database level. Foreign keys with `ON DELETE CASCADE` keep the data consistent if a user or course is removed. Re-registering after a drop reuses the same row by flipping the status back to pending. |

Incremental schema changes are captured as named migration scripts in
the `sql/` folder (`add_profile_fields.sql`, `registration_status.sql`,
`drop_credits.sql`, `student_preregistration.sql`) so an existing
deployment can be brought forward without re-importing the whole
database.

### 3. Use of XML

The case study calls for XML to demonstrate structured-data exchange.
The system supports XML in **both directions**:

- **Export.** `GET /api/courses_xml.php` builds a well-formed XML
  document from the live database using `DOMDocument` and serves it
  with an `application/xml` MIME type. The admin's
  `manage_courses.php` page links to it as a one-click "Export XML"
  download; the file is also consumable by partner systems or backup
  scripts without changing the business-logic tier.
- **Import.** `POST /api/import_xml.php` (admin-only) accepts either an
  uploaded file or the bundled `data/courses_sample.xml`. It parses
  the document with `simplexml_load_file`, then merges each
  `<course>` node into the `courses` table with an upsert (`INSERT … ON
  DUPLICATE KEY UPDATE`). A bulk-load workflow that previously meant
  copy-pasting from a spreadsheet is now one click.

The XML structure is intentionally human-readable so a non-developer can
edit it in any text editor.

### 4. Request Flow Example — "Register for CS210"

1. The student clicks **Register** in `courses.html`.
2. JavaScript calls `POST /api/enroll.php` with `{course_id: 2}` as JSON
   through the `api.post()` helper in `assets/js/api.js`.
3. `enroll.php` checks the session (`require_login()`), opens a DB
   transaction, locks the course row with `SELECT … FOR UPDATE`, and
   verifies that:
   - the course exists,
   - the student is not already pending/approved for it,
   - `enrolled < capacity` (counting pending + approved seats).
4. PDO inserts a row into `registrations` with status `pending`. The
   transaction commits.
5. PHP returns
   `{ok: true, message: "Registration submitted — pending admin approval."}`.
6. The browser shows a flash banner and re-fetches `api/courses.php`,
   which now reports the updated enrollment count and available seats.
7. Later, an administrator visits `admin/registrations.php` and clicks
   **Approve**. `POST /api/admin_registrations.php` flips the status
   to `approved` (only if the row is still `pending` — a guard against
   double-approving). The admin can subsequently click **Drop** to undo
   an approved or rejected registration when a student picked the
   wrong course by mistake.

If any rule fails (course full, already registered, course missing),
PHP returns the appropriate HTTP status and a human-readable error,
which the browser surfaces in a flash banner. **No business decision
is made in the browser.**

### 5. Additional Features Implemented

- **Pre-registration & sign up.** Admins add admitted students
  (name + email + registration number) ahead of time; the student
  then signs up by entering their email,
  registration number, and a chosen password on the combined login/signup
  page. The sign-up flow writes the bcrypt password hash and lets
  the student log in immediately.
- **Approval workflow.** Every student registration enters as `pending`
  and waits for an admin decision (`approved` / `rejected`).
  Already-processed registrations can later be dropped by either the
  student or an admin.
- **Search & filter.** Keyword + department filter on the catalogue,
  with a 200 ms debounce so the server is hit at most once per typing
  burst. Department filter uses a custom themed dropdown so the open
  panel matches the brand (native `<select>` options can't be styled).
- **AJAX everywhere.** No full page reloads after the initial HTML —
  every interaction is a `fetch()` call returning JSON.
- **Responsive design.** Tested down to ~ 320 px viewport width.
  Includes an off-canvas sidebar, hamburger toggle, stacked auth
  layout, horizontally-scrollable data tables with column headings,
  and adaptive toolbars.
- **Profile completion tracking.** Five optional profile fields
  contribute to a percentage that drives a dashboard nag banner and a
  recommendation engine (the recommender prefers courses in the
  student's department, falling back to any available course).
- **Recommended-for-you list.** The dashboard shows up to three
  courses the student has not yet registered for, in their department
  if known, that still have seats.
- **Enhanced admin dashboard.** Action-items strip (pending
  registrations, students still to sign up), totals (students,
  courses, active registrations), an activity feed of recent events,
  and capacity-alert cards for courses that are full or filling up.
- **Secure password handling.** bcrypt via `password_hash` /
  `password_verify`, generic "invalid credentials" error to prevent
  user enumeration, session ID regeneration on login,
  current-password verification on password change.
- **Role-based access control.** Students cannot reach admin pages;
  admins are routed to the admin dashboard on login.

### 6. Setup Instructions

The project runs on a standard XAMPP install (Windows, macOS, or Linux).

1. **Install XAMPP** and start the **MySQL** module from the XAMPP
   control panel. (Apache is optional — you can use PHP's built-in
   web server instead, see step 4.)
2. **Create the database.** Open phpMyAdmin
   (<http://localhost/phpmyadmin>), create a database called
   `greenfield_db`, click into it, then on the **Import** tab choose
   `sql/greenfield.sql` and click **Go**. The schema and seed data
   load together. The seeded password for every demo account is
   `password123` — bcrypt hashes are baked into the SQL file.
3. **Configure credentials.** Copy `includes/config.example.php` to
   `includes/config.php` and edit `DB_HOST`, `DB_USER`, `DB_PASS`,
   `DB_NAME` if your MySQL setup differs from the XAMPP defaults
   (`localhost` / `root` / no password / `greenfield_db`).
4. **Serve the site.** From the project root run

   ```
   "C:\xampp\php\php.exe" -S localhost:8000
   ```

   (or any PHP-capable web server — Apache via `htdocs` works too).
5. **Open <http://localhost:8000/>** and log in.

| Role | Email | Password |
|---|---|---|
| Administrator | `admin@greenfield.edu` | `password123` |
| Student (test account) | `test@student.greenfield.edu` | `testing123` |

A production deployment is also live on InfinityFree shared hosting;
the same SQL file is imported into the hosted MySQL database, and
`includes/config.php` is updated with the InfinityFree credentials.

### 7. Submission Contents

| Path | Purpose |
|---|---|
| `index.html`, `signup.html` | Combined login + signup screen |
| `dashboard.html`, `courses.html`, `my-courses.html`, `profile.html`, `settings.html` | Student pages |
| `admin/*.php` | Admin pages (PHP-rendered shells, JS-rendered content) |
| `assets/css/style.css` | Responsive stylesheet |
| `assets/js/api.js` | Shared AJAX client |
| `assets/js/nav.js`, `dropdown.js`, `auth.js` | UI behaviour modules |
| `api/*.php` | PHP business-logic endpoints (JSON / XML) |
| `includes/*.php` | DB, config, auth shared modules |
| `data/courses_sample.xml` | Sample XML used by the import endpoint |
| `sql/greenfield.sql` | Database schema + seed data |
| `sql/*.sql` (other) | Migration scripts |
| `REPORT.md` | This report |

### 8. Conclusion

The system replaces Greenfield Institute's manual, spreadsheet-driven
registration process with a maintainable web application. Splitting the
code along the three-tier boundary means each layer can evolve on its
own: the front-end could be re-skinned (or replaced with a mobile app)
without touching PHP; the business logic could be ported to another
language without touching the database; and the database can be
migrated or scaled without affecting any client. The XML import/export
adds a cleanly defined data-exchange surface that lets Greenfield
interoperate with other systems without exposing its internal database
schema. The approval and pre-registration workflows close the loop on
the original problems (duplicate registrations, stale availability,
inaccurate records), and the responsive design pass means the same
system works on a phone in a corridor or on a desktop in the registrar's
office.
