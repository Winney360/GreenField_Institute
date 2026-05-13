<?php
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin · Greenfield Institute</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body>
    <header class="navbar">
        <h1>Greenfield Admin</h1>
        <nav>
            <a href="dashboard.php" class="active">Overview</a>
            <a href="students.php">Students</a>
            <a href="manage_courses.php">Course catalogue</a>
            <a href="registrations.php">Registrations</a>
            <a href="../api/logout.php" class="logout">Logout</a>
        </nav>
    </header>

    <main class="container">
        <h2>Welcome, <span class="user-name"><?= htmlspecialchars($admin['full_name']) ?></span></h2>
        <p style="color:var(--text-muted); margin-top:0;">
            Operational view of the Greenfield Institute system.
        </p>

        <!-- ============ TIER 1 — ACTION ITEMS ============ -->
        <section class="dash-section">
            <div class="dash-section__header"><h3>Action items</h3></div>
            <div id="actionItems"></div>
        </section>

        <!-- ============ TIER 2 — STATS ============ -->
        <section class="dash-section">
            <div class="dash-section__header"><h3>At a glance</h3></div>
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-label">Total students</div>
                    <div class="stat-value" id="statTotalStudents">0</div>
                    <div class="stat-sub"><span id="statActivated">0</span> activated</div>
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
        <section class="dash-section">
            <div class="dash-section__header">
                <h3>Recent activity</h3>
                <a href="registrations.php">All registrations →</a>
            </div>
            <div id="activityFeed" class="activity-list"></div>
        </section>

        <!-- ============ TIER 4 — CAPACITY ALERTS ============ -->
        <section class="dash-section">
            <div class="dash-section__header">
                <h3>Courses near capacity</h3>
                <a href="manage_courses.php">Manage catalogue →</a>
            </div>
            <div id="capacityList" class="capacity-list"></div>
        </section>
    </main>

    <script src="../assets/js/api.js"></script>
    <script>
        function timeAgo(iso) {
            const now = Date.now();
            const then = new Date(iso).getTime();
            const sec = Math.round((now - then) / 1000);
            if (sec < 60)    return 'just now';
            if (sec < 3600)  return Math.floor(sec / 60)   + 'm ago';
            if (sec < 86400) return Math.floor(sec / 3600) + 'h ago';
            const days = Math.floor(sec / 86400);
            if (days < 30) return days + 'd ago';
            return new Date(iso).toLocaleDateString();
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
                                <div class="action-card__label">Students awaiting activation</div>
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
