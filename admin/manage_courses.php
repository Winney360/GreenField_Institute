<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manage courses · Greenfield Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <script src="../assets/js/theme.js"></script>
</head>
<body>
    <header class="navbar">
        <h1>Greenfield Admin</h1>
        <nav>
            <a href="dashboard.php">Overview</a>
            <a href="manage_courses.php" class="active">Courses</a>
            <a href="registrations.php">Registrations</a>
            <a href="../api/logout.php" class="logout">Logout</a>
            <button class="theme-toggle theme-toggle--inline" aria-label="Toggle light/dark mode" type="button">
                <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
            </button>
        </nav>
    </header>

    <main class="container">
        <h2>Course catalogue</h2>
        <div class="toolbar" style="justify-content:flex-end;">
            <button id="addBtn">+ New course</button>
            <button id="importBtn" class="btn-secondary">Import sample XML</button>
            <a class="btn btn-secondary" href="../api/courses_xml.php" target="_blank">Export XML</a>
        </div>

        <div id="msg"></div>

        <table>
            <thead>
                <tr>
                    <th>Code</th><th>Title</th><th>Department</th>
                    <th>Instructor</th><th>Credits</th><th>Capacity</th><th></th>
                </tr>
            </thead>
            <tbody id="rows"></tbody>
        </table>
    </main>

    <!-- Course editor modal -->
    <div class="modal-backdrop" id="modal">
        <div class="modal">
            <h3 id="modalTitle">New course</h3>
            <form id="courseForm" novalidate>
                <input type="hidden" id="course_id" />
                <div class="field">
                    <label>Course code</label>
                    <input id="course_code" required maxlength="20" />
                </div>
                <div class="field">
                    <label>Title</label>
                    <input id="title" required maxlength="160" />
                </div>
                <div class="field">
                    <label>Description</label>
                    <textarea id="description" rows="3"></textarea>
                </div>
                <div class="field">
                    <label>Instructor</label>
                    <input id="instructor" required maxlength="120" />
                </div>
                <div class="field" style="display:flex;gap:.5rem;">
                    <div style="flex:1">
                        <label>Department</label>
                        <input id="department" required maxlength="80" />
                    </div>
                    <div style="width:90px">
                        <label>Credits</label>
                        <input id="credits" type="number" min="1" max="6" value="3" required />
                    </div>
                    <div style="width:110px">
                        <label>Capacity</label>
                        <input id="capacity" type="number" min="1" max="1000" value="30" required />
                    </div>
                </div>
                <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem;">
                    <button type="button" class="btn-secondary" id="cancelBtn">Cancel</button>
                    <button type="submit" id="saveBtn">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/api.js"></script>
    <script>
        const rowsEl = document.getElementById('rows');
        const modal  = document.getElementById('modal');
        const form   = document.getElementById('courseForm');

        async function load() {
            const res = await api.get('../api/courses.php');
            if (!res.ok) { flash('msg', res.error || 'Load failed.', 'error'); return; }
            rowsEl.innerHTML = res.courses.map(c => `
                <tr>
                    <td>${escapeHtml(c.course_code)}</td>
                    <td>${escapeHtml(c.title)}</td>
                    <td>${escapeHtml(c.department)}</td>
                    <td>${escapeHtml(c.instructor)}</td>
                    <td>${c.credits}</td>
                    <td>${c.enrolled}/${c.capacity}</td>
                    <td>
                        <button class="btn-secondary" data-edit='${JSON.stringify(c)}'>Edit</button>
                        <button class="btn-danger"   data-del="${c.course_id}">Delete</button>
                    </td>
                </tr>
            `).join('');
            rowsEl.querySelectorAll('button[data-edit]').forEach(b =>
                b.addEventListener('click', () => openEdit(JSON.parse(b.dataset.edit))));
            rowsEl.querySelectorAll('button[data-del]').forEach(b =>
                b.addEventListener('click', () => del(b.dataset.del)));
        }

        function openCreate() {
            form.reset();
            document.getElementById('course_id').value = '';
            document.getElementById('modalTitle').textContent = 'New course';
            modal.classList.add('active');
        }
        function openEdit(c) {
            document.getElementById('modalTitle').textContent = 'Edit course';
            for (const k of ['course_id','course_code','title','description','instructor','department','credits','capacity']) {
                document.getElementById(k).value = c[k] ?? '';
            }
            modal.classList.add('active');
        }
        function close() { modal.classList.remove('active'); }

        document.getElementById('addBtn').onclick    = openCreate;
        document.getElementById('cancelBtn').onclick = close;
        modal.addEventListener('click', e => { if (e.target === modal) close(); });

        form.addEventListener('submit', async e => {
            e.preventDefault();
            const id = document.getElementById('course_id').value;
            const payload = {
                action: id ? 'update' : 'create',
                course_id: id ? Number(id) : null,
                course_code: document.getElementById('course_code').value.trim(),
                title:       document.getElementById('title').value.trim(),
                description: document.getElementById('description').value.trim(),
                instructor:  document.getElementById('instructor').value.trim(),
                department:  document.getElementById('department').value.trim(),
                credits:     Number(document.getElementById('credits').value),
                capacity:    Number(document.getElementById('capacity').value),
            };
            const res = await api.post('../api/admin_courses.php', payload);
            if (res.ok) { close(); flash('msg', 'Course saved.', 'success'); load(); }
            else        { flash('msg', res.error || 'Save failed.', 'error'); }
        });

        async function del(id) {
            if (!confirm('Delete this course? All registrations for it will also be removed.')) return;
            const res = await api.post('../api/admin_courses.php', { action: 'delete', course_id: Number(id) });
            if (res.ok) { flash('msg', 'Course deleted.', 'success'); load(); }
            else        { flash('msg', res.error || 'Delete failed.', 'error'); }
        }

        document.getElementById('importBtn').onclick = async () => {
            if (!confirm('Import courses from data/courses_sample.xml?')) return;
            const res = await api.post('../api/import_xml.php', {});
            if (res.ok) { flash('msg', `Imported/updated ${res.imported} courses.`, 'success'); load(); }
            else        { flash('msg', res.error || 'Import failed.', 'error'); }
        };

        load();
    </script>
</body>
</html>
