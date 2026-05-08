<?php
// Admin view: every active registration in the system, joined with
// the student and course so the dashboard can display them in one table.
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$stmt = db()->query('
    SELECT r.registration_id, r.registered_at, r.status,
           u.user_id, u.full_name, u.email,
           c.course_id, c.course_code, c.title
      FROM registrations r
      JOIN users   u ON u.user_id   = r.user_id
      JOIN courses c ON c.course_id = r.course_id
     ORDER BY r.registered_at DESC
');
json_response(['ok' => true, 'registrations' => $stmt->fetchAll()]);
