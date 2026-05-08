<?php
// Returns the list of courses the current student is registered in.
require_once __DIR__ . '/../includes/auth.php';

$user = require_login();

$stmt = db()->prepare('
    SELECT c.course_id, c.course_code, c.title, c.instructor, c.credits,
           c.department, r.registered_at, r.status
      FROM registrations r
      JOIN courses c ON c.course_id = r.course_id
     WHERE r.user_id = ?
     ORDER BY r.registered_at DESC
');
$stmt->execute([$user['user_id']]);
json_response(['ok' => true, 'registrations' => $stmt->fetchAll()]);
