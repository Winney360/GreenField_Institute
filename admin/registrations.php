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
            <a href="students.php">Students</a>
            <a href="manage_courses.php">Course catalogue</a>
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
                    <th>Status</th><th>Registered</th><th>Actions</th>
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
                rowsEl.innerHTML = '<tr><td colspan="6" style="color:var(--text-muted)">No registrations match.</td></tr>';
                return;
            }
            rowsEl.innerHTML = list.map(r => `
                <tr>
                    <td>${escapeHtml(r.full_name)}</td>
                    <td>${escapeHtml(r.email)}</td>
                    <td>${escapeHtml(r.course_code)} — ${escapeHtml(r.title)}</td>
                    <td><span class="status-pill status-${escapeHtml(r.status)}">${escapeHtml(r.status)}</span></td>
                    <td>${new Date(r.registered_at).toLocaleString()}</td>
                    <td>
                        ${r.status === 'pending' ? `
                            <button data-act="approve" data-id="${r.registration_id}">Approve</button>
                            <button class="btn-danger" data-act="reject" data-id="${r.registration_id}">Reject</button>
                        ` : '—'}
                    </td>
                </tr>
            `).join('');

            rowsEl.querySelectorAll('button[data-id]').forEach(b => {
                b.addEventListener('click', () => act(b.dataset.act, b.dataset.id));
            });
        }

        async function act(action, regId) {
            const verb = action === 'approve' ? 'approve' : 'reject';
            if (!confirm(`Are you sure you want to ${verb} this registration?`)) return;
            const res = await api.post('../api/admin_registrations.php', {
                action,
                registration_id: Number(regId),
            });
            if (res.ok) { flash('msg', `Registration ${res.status}.`, 'success'); load(); }
            else        { flash('msg', res.error || `${verb} failed.`, 'error'); }
        }

        load();
    </script>
</body>
</html>
