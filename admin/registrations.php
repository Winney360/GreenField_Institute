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
</head>
<body>
    <header class="navbar">
        <h1>Greenfield Admin</h1>
        <nav>
            <a href="dashboard.php">Overview</a>
            <a href="manage_courses.php">Courses</a>
            <a href="registrations.php" class="active">Registrations</a>
            <a href="../api/logout.php" class="logout">Logout</a>
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
