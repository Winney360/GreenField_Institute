<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Registrations · Greenfield Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <script src="../assets/js/theme.js"></script>
</head>
<body>
    <header class="navbar">
        <h1>Greenfield Admin</h1>
        <nav>
            <a href="dashboard.php">Overview</a>
            <a href="manage_courses.php">Courses</a>
            <a href="registrations.php" class="active">Registrations</a>
            <a href="../api/logout.php" class="logout">Logout</a>
            <button class="theme-toggle theme-toggle--inline" aria-label="Toggle light/dark mode" type="button">
                <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
            </button>
        </nav>
    </header>

    <main class="container">
        <h2>All registrations</h2>
        <div class="toolbar">
            <input type="search" id="filter" placeholder="Filter by student name, email, or course…" />
        </div>

        <div id="msg"></div>
        <table>
            <thead>
                <tr>
                    <th>Student</th><th>Email</th><th>Course</th>
                    <th>Status</th><th>Registered</th>
                </tr>
            </thead>
            <tbody id="rows"></tbody>
        </table>
    </main>

    <script src="../assets/js/api.js"></script>
    <script>
        let all = [];
        const rowsEl = document.getElementById('rows');
        const filter = document.getElementById('filter');

        filter.addEventListener('input', render);

        async function load() {
            const res = await api.get('../api/admin_registrations.php');
            if (!res.ok) { flash('msg', res.error || 'Load failed.', 'error'); return; }
            all = res.registrations;
            render();
        }

        function render() {
            const q = filter.value.trim().toLowerCase();
            const list = q
                ? all.filter(r =>
                    r.full_name.toLowerCase().includes(q) ||
                    r.email.toLowerCase().includes(q)     ||
                    r.title.toLowerCase().includes(q)     ||
                    r.course_code.toLowerCase().includes(q))
                : all;

            if (!list.length) {
                rowsEl.innerHTML = '<tr><td colspan="5" style="color:var(--grey-700)">No registrations match.</td></tr>';
                return;
            }
            rowsEl.innerHTML = list.map(r => `
                <tr>
                    <td>${escapeHtml(r.full_name)}</td>
                    <td>${escapeHtml(r.email)}</td>
                    <td>${escapeHtml(r.course_code)} — ${escapeHtml(r.title)}</td>
                    <td>${escapeHtml(r.status)}</td>
                    <td>${new Date(r.registered_at).toLocaleString()}</td>
                </tr>
            `).join('');
        }

        load();
    </script>
</body>
</html>
