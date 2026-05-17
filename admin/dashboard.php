<?php
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();
// Greeting always shows "Admin" rather than the stored full_name. The
// seeded admin's name is "System Administrator", so the old first-word
// logic produced "Welcome back, System" — which doesn't read naturally.
$firstName = 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin · Greenfield Institute</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body class="has-sidebar">
    <header class="navbar">
        <button class="navbar__hamburger" type="button" aria-label="Open menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <h1>
            <img class="navbar__logo" src="../assets/img/logo.png" alt="" />
            <span class="navbar__brand">
                <span class="navbar__title">GREENFIELD INSTITUTE</span>
                <span class="admin-label">ADMIN PANEL</span>
            </span>
        </h1>
    </header>

    <aside class="sidebar">
        <nav class="sidebar__nav">
            <a href="dashboard.php" class="active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Overview
            </a>
            <a href="students.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Students
            </a>
            <a href="manage_courses.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                Course catalogue
            </a>
            <a href="registrations.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                Registrations
            </a>
        </nav>
        <div class="sidebar__footer">
            <a href="../api/logout.php" class="logout">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </a>
        </div>
    </aside>

    <main class="main">
      <div class="container">
        <h2>Welcome back, <span class="user-name"><?= htmlspecialchars($firstName) ?></span></h2>
        <p style="color:var(--text-muted); margin-top:0;">
            Operational view of the Greenfield Institute system.
        </p>

        <!-- ============ TIER 1 — ACTION ITEMS ============ -->
        <section class="dash-section reveal">
            <div class="dash-section__header"><h3>Action items</h3></div>
            <div id="actionItems"></div>
        </section>

        <!-- ============ TIER 2 — STATS ============ -->
        <section class="dash-section reveal">
            <div class="dash-section__header"><h3>At a glance</h3></div>
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-label">Total students</div>
                    <div class="stat-value" id="statTotalStudents">0</div>
                    <div class="stat-sub"><span id="statActivated">0</span> signed up</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Courses</div>
                    <div class="stat-value" id="statCourses">0</div>
                    <div class="stat-sub">In the catalogue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Approved enrolments</div>
                    <div class="stat-value" id="statEnrolments">0</div>
                    <div class="stat-sub">Across all courses</div>
                </div>
            </div>
        </section>

        <!-- ============ TIER 3 — ACTIVITY FEED ============ -->
        <section class="dash-section reveal">
            <div class="dash-section__header">
                <h3>Recent activity</h3>
                <a href="registrations.php">All registrations →</a>
            </div>
            <div id="activityFeed" class="activity-list"></div>
        </section>

        <!-- ============ TIER 4 — CAPACITY ALERTS ============ -->
        <section class="dash-section reveal">
            <div class="dash-section__header">
                <h3>Courses near capacity</h3>
                <a href="manage_courses.php">Manage catalogue →</a>
            </div>
            <div id="capacityList" class="capacity-list"></div>
        </section>
      </div>
    </main>

    <script src="../assets/js/api.js"></script>
    <script src="../assets/js/nav.js"></script>
    <script src="../assets/js/reveal.js"></script>
    <script>
        function timeAgo(iso) {
            // MySQL DATETIME comes back as "YYYY-MM-DD HH:MM:SS" with no
            // timezone marker. The DB connection is forced to UTC (see
            // includes/db.php), so we tag the string with 'T' + 'Z' before
            // parsing — otherwise the browser interprets it as local time
            // and the "X ago" math is off by the user's UTC offset.
            const utcIso = iso.replace(' ', 'T') + 'Z';
            const now = Date.now();
            const then = new Date(utcIso).getTime();
            const sec = Math.round((now - then) / 1000);
            if (sec < 60)    return 'just now';
            if (sec < 3600)  return Math.floor(sec / 60)   + 'm ago';
            if (sec < 86400) return Math.floor(sec / 3600) + 'h ago';
            const days = Math.floor(sec / 86400);
            if (days < 30) return days + 'd ago';
            return new Date(utcIso).toLocaleDateString();
        }

        (async () => {
            const d = await api.get('../api/admin_overview.php');
            if (!d.ok) { return; }

            // --- Action items ---
            const ai = d.action_items;
            const aiEl = document.getElementById('actionItems');
            if (ai.pending_registrations === 0 && ai.unactivated_students === 0) {
                aiEl.innerHTML = '<div class="action-empty">✓ All caught up — no pending work.</div>';
            } else {
                aiEl.innerHTML = `
                    <div class="stats-row">
                        <a href="registrations.php" class="action-card ${ai.pending_registrations > 0 ? 'has-pending' : ''}">
                            <div class="badge-count">${ai.pending_registrations}</div>
                            <div class="action-card__body">
                                <div class="action-card__label">Pending registrations</div>
                                <div class="action-card__hint">Awaiting your approve / reject decision</div>
                            </div>
                        </a>
                        <a href="students.php" class="action-card ${ai.unactivated_students > 0 ? 'has-pending' : ''}">
                            <div class="badge-count">${ai.unactivated_students}</div>
                            <div class="action-card__body">
                                <div class="action-card__label">Students awaiting sign up</div>
                                <div class="action-card__hint">Admitted but haven't signed up yet</div>
                            </div>
                        </a>
                    </div>`;
            }

            // --- Stats ---
            document.getElementById('statTotalStudents').textContent = d.stats.total_students;
            document.getElementById('statActivated').textContent     = d.stats.activated_students;
            document.getElementById('statCourses').textContent       = d.stats.total_courses;
            document.getElementById('statEnrolments').textContent    = d.stats.approved_enrolments;

            // --- Activity feed ---
            const feed = document.getElementById('activityFeed');
            if (!d.activity.length) {
                feed.innerHTML = '<div class="activity-item"><div class="activity-text" style="color:var(--text-muted)">Nothing has happened yet.</div></div>';
            } else {
                feed.innerHTML = d.activity.map(ev => {
                    let text = '';
                    if (ev.type === 'admission') {
                        text = `<strong>${escapeHtml(ev.subject)}</strong> was admitted as a student`;
                    } else {
                        const statusBadge = `<span class="status-pill status-${escapeHtml(ev.status)}">${escapeHtml(ev.status)}</span>`;
                        text = `<strong>${escapeHtml(ev.subject)}</strong> registered for <code>${escapeHtml(ev.course_code)}</code> ${statusBadge}`;
                    }
                    return `
                        <div class="activity-item is-${escapeHtml(ev.type)}">
                            <div class="activity-dot"></div>
                            <div class="activity-text">${text}</div>
                            <div class="activity-time">${timeAgo(ev.at)}</div>
                        </div>`;
                }).join('');
            }

            // --- Capacity alerts ---
            const cap = document.getElementById('capacityList');
            if (!d.capacity_alerts.length) {
                cap.innerHTML = '<div class="capacity-item" style="color:var(--text-muted)">No courses are nearing capacity. Plenty of room.</div>';
            } else {
                cap.innerHTML = d.capacity_alerts.map(c => {
                    const fillClass = c.percent >= 100 ? 'is-full'
                                    : c.percent >= 90  ? 'is-warning'
                                    : '';
                    return `
                        <div class="capacity-item">
                            <div class="capacity-item__header">
                                <div>
                                    <span class="capacity-item__title">${escapeHtml(c.title)}</span>
                                    <span class="capacity-item__code"> · ${escapeHtml(c.course_code)}</span>
                                </div>
                                <div class="capacity-item__numbers">${c.enrolled} / ${c.capacity} · ${c.percent}%</div>
                            </div>
                            <div class="capacity-bar">
                                <div class="capacity-bar__fill ${fillClass}" style="width: ${Math.min(100, c.percent)}%;"></div>
                            </div>
                        </div>`;
                }).join('');
            }
        })();
    </script>
</body>
</html>
