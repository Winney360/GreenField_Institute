<?php
// Admin CRUD for courses.
//   POST   action=create | update | delete
require_once __DIR__ . '/../includes/auth.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$body   = read_json_body();
$action = $body['action'] ?? '';
$pdo    = db();

switch ($action) {
    case 'create':
        $fields = validate_course_payload($body);
        $stmt = $pdo->prepare(
            'INSERT INTO courses
                (course_code, title, description, instructor, capacity, department)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        try {
            $stmt->execute([
                $fields['course_code'], $fields['title'], $fields['description'],
                $fields['instructor'],  $fields['capacity'], $fields['department']
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                json_response(['ok' => false, 'error' => 'A course with that code already exists.'], 409);
            }
            throw $e;
        }
        json_response(['ok' => true, 'course_id' => (int)$pdo->lastInsertId()]);
        // no break — exited above

    case 'update':
        $course_id = (int)($body['course_id'] ?? 0);
        if ($course_id <= 0) {
            json_response(['ok' => false, 'error' => 'course_id is required.'], 422);
        }
        $fields = validate_course_payload($body);
        $stmt = $pdo->prepare(
            'UPDATE courses SET course_code=?, title=?, description=?, instructor=?,
                                capacity=?, department=?
              WHERE course_id=?'
        );
        $stmt->execute([
            $fields['course_code'], $fields['title'], $fields['description'],
            $fields['instructor'],  $fields['capacity'],
            $fields['department'],  $course_id
        ]);
        json_response(['ok' => true]);

    case 'delete':
        $course_id = (int)($body['course_id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM courses WHERE course_id = ?');
        $stmt->execute([$course_id]);
        json_response(['ok' => true]);

    default:
        json_response(['ok' => false, 'error' => 'unknown_action'], 400);
}

function validate_course_payload(array $b): array {
    $code  = trim($b['course_code'] ?? '');
    $title = trim($b['title'] ?? '');
    $instr = trim($b['instructor'] ?? '');
    $dept  = trim($b['department'] ?? '');
    $desc  = trim($b['description'] ?? '');
    $cap   = (int)($b['capacity'] ?? 0);

    if ($code === '' || $title === '' || $instr === '' || $dept === '') {
        json_response(['ok' => false, 'error' => 'Code, title, instructor and department are required.'], 422);
    }
    if ($cap < 1 || $cap > 1000) {
        json_response(['ok' => false, 'error' => 'Capacity must be between 1 and 1000.'], 422);
    }
    return [
        'course_code' => $code, 'title' => $title, 'description' => $desc,
        'instructor'  => $instr,'capacity' => $cap,
        'department'  => $dept,
    ];
}
