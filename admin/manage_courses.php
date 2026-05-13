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
</head>
<body class="has-sidebar">
    <header class="navbar"><h1>GREENFIELD INSTITUTE <span class="admin-label">ADMIN PANEL</span></h1></header>

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
            <a href="manage_courses.php" class="active">
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
        <h2>Course catalogue</h2>
        <p style="color:var(--text-muted); margin-top:0;">The master list of courses Greenfield Institute offers. Create, edit, or remove courses below.</p>
        <div class="toolbar" style="justify-content:flex-end;">
            <button id="addBtn">+ New course</button>
            <!-- Hidden native file input — triggered by the styled button below. -->
            <input type="file" id="importFile" accept=".xml,application/xml,text/xml" hidden />
            <button id="importBtn" class="btn-secondary">Import XML</button>
            <a class="btn btn-secondary" href="../api/courses_xml.php" target="_blank">Export XML</a>
        </div>

        <div id="msg"></div>

        <table>
            <thead>
                <tr>
                    <th>Code</th><th>Title</th><th>Department</th>
                    <th>Instructor</th><th>Capacity</th><th></th>
                </tr>
            </thead>
            <tbody id="rows"></tbody>
        </table>
      </div>
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
                    <div style="width:120px">
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
            for (const k of ['course_id','course_code','title','description','instructor','department','capacity']) {
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

        // XML import: click → open native file picker → confirm → upload.
        // We use FormData / fetch directly here because api.js only handles
        // JSON requests and file uploads need multipart/form-data.
        const importBtn  = document.getElementById('importBtn');
        const importFile = document.getElementById('importFile');

        importBtn.onclick = () => importFile.click();

        importFile.addEventListener('change', async () => {
            const file = importFile.files[0];
            if (!file) return;
            if (!confirm(`Import courses from "${file.name}"? Existing courses with matching codes will be updated.`)) {
                importFile.value = '';  // allow re-selecting the same file later
                return;
            }

            const fd = new FormData();
            fd.append('xml', file);

            importBtn.disabled = true; importBtn.textContent = 'Importing…';
            try {
                const r = await fetch('../api/import_xml.php', {
                    method:  'POST',
                    body:    fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const res = await r.json();
                if (res.ok) {
                    flash('msg', `Imported/updated ${res.imported} courses.`, 'success');
                    load();
                } else {
                    flash('msg', res.error || 'Import failed.', 'error');
                }
            } catch (e) {
                flash('msg', 'Upload failed: ' + e.message, 'error');
            } finally {
                importBtn.disabled = false;
                importBtn.textContent = 'Import XML';
                importFile.value = '';
            }
        });

        load();
    </script>
</body>
</html>
