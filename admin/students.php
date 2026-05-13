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
<body>
    <header class="navbar">
        <h1>Greenfield Admin</h1>
        <nav>
            <a href="dashboard.php">Overview</a>
            <a href="students.php" class="active">Students</a>
            <a href="manage_courses.php">Course catalogue</a>
            <a href="registrations.php">Registrations</a>
            <a href="../api/logout.php" class="logout">Logout</a>
        </nav>
    </header>

    <main class="container">
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
