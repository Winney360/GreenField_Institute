<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Students · Greenfield Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body class="has-sidebar">
    <header class="navbar"><h1><img class="navbar__logo" src="../assets/img/logo.png" alt="" />GREENFIELD INSTITUTE <span class="admin-label">ADMIN PANEL</span></h1></header>

    <aside class="sidebar">
        <nav class="sidebar__nav">
            <a href="dashboard.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Overview
            </a>
            <a href="students.php" class="active">
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
        <h2>Students</h2>
        <p style="color:var(--text-muted); margin-top:0;">
            Add admitted students so they can activate their accounts using their school email and registration number.
        </p>

        <!-- Add-student form -->
        <div class="toolbar" style="flex-direction: column; align-items: stretch;">
            <form id="addStudentForm" style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                <input type="text"  id="newName"   placeholder="Full name"           required maxlength="120" style="flex: 1 1 200px;" />
                <input type="email" id="newEmail"  placeholder="School email"        required style="flex: 1 1 200px;" />
                <input type="text"  id="newRegNum" placeholder="Registration number" required maxlength="20" style="flex: 1 1 160px;" />
                <button type="submit" id="addBtn">Add student</button>
            </form>
        </div>

        <div id="msg"></div>

        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Reg number</th>
                    <th>Status</th>
                    <th>Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="rows"></tbody>
        </table>
      </div>
    </main>

    <script src="../assets/js/api.js"></script>
    <script>
        const rowsEl = document.getElementById('rows');
        const form   = document.getElementById('addStudentForm');
        const btn    = document.getElementById('addBtn');

        async function load() {
            const res = await api.get('../api/admin_students.php');
            if (!res.ok) { flash('msg', res.error || 'Failed to load.', 'error'); return; }
            if (!res.students.length) {
                rowsEl.innerHTML = '<tr><td colspan="6" style="color:var(--text-muted)">No students yet. Add one above.</td></tr>';
                return;
            }
            rowsEl.innerHTML = res.students.map(s => {
                const statusClass = Number(s.activated) === 1 ? 'status-approved' : 'status-pending';
                const statusText  = Number(s.activated) === 1 ? 'Activated' : 'Pending activation';
                return `
                    <tr>
                        <td>${escapeHtml(s.full_name)}</td>
                        <td>${escapeHtml(s.email)}</td>
                        <td>${escapeHtml(s.registration_number || '—')}</td>
                        <td><span class="status-pill ${statusClass}">${statusText}</span></td>
                        <td>${new Date(s.created_at).toLocaleDateString()}</td>
                        <td>
                            <button class="btn-danger" data-del="${s.user_id}">Delete</button>
                        </td>
                    </tr>`;
            }).join('');

            rowsEl.querySelectorAll('button[data-del]').forEach(b =>
                b.addEventListener('click', () => del(b.dataset.del))
            );
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            btn.disabled = true; btn.textContent = 'Adding…';
            const res = await api.post('../api/admin_students.php', {
                action:              'create',
                full_name:           document.getElementById('newName').value.trim(),
                email:               document.getElementById('newEmail').value.trim(),
                registration_number: document.getElementById('newRegNum').value.trim(),
            });
            if (res.ok) {
                flash('msg', 'Student added. They can now activate their account.', 'success');
                form.reset();
                load();
            } else {
                flash('msg', res.error || 'Failed to add student.', 'error');
            }
            btn.disabled = false; btn.textContent = 'Add student';
        });

        async function del(userId) {
            if (!confirm('Delete this student? Any registrations they have will also be removed.')) return;
            const res = await api.post('../api/admin_students.php', {
                action:  'delete',
                user_id: Number(userId),
            });
            if (res.ok) { flash('msg', 'Student deleted.', 'success'); load(); }
            else        { flash('msg', res.error || 'Delete failed.', 'error'); }
        }

        load();
    </script>
</body>
</html>
