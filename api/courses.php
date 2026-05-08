<?php
// List / search courses. Open to authenticated users.
// Supports ?q=keyword and ?department=Computing for server-side filtering.
require_once __DIR__ . '/../includes/auth.php';

require_login();

$q          = trim($_GET['q'] ?? '');
$department = trim($_GET['department'] ?? '');

$sql = '
    SELECT c.course_id, c.course_code, c.title, c.description, c.instructor,
           c.credits, c.capacity, c.department,
           (SELECT COUNT(*) FROM registrations r
              WHERE r.course_id = c.course_id AND r.status = "active") AS enrolled
      FROM courses c
     WHERE 1=1';
$params = [];

if ($q !== '') {
    $sql .= ' AND (c.title LIKE ? OR c.course_code LIKE ? OR c.description LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
if ($department !== '') {
    $sql .= ' AND c.department = ?';
    $params[] = $department;
}
$sql .= ' ORDER BY c.department, c.course_code';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Decorate with computed availability — business-logic concern.
foreach ($rows as &$r) {
    $r['available_seats'] = max(0, (int)$r['capacity'] - (int)$r['enrolled']);
    $r['is_full']         = $r['available_seats'] === 0;
}
unset($r);

json_response(['ok' => true, 'courses' => $rows]);
