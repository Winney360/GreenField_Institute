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
<body class="has-sidebar">
    <header class="navbar"><h1>GREENFIELD INSTITUTE</h1></header>

    <aside class="sidebar">
        <nav class="sidebar__nav">
            <a href="dashboard.php">
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
            <a href="registrations.php" class="active">
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
      </div>
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
