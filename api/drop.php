<?php
// Drop a course the student is currently enrolled in.
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$user      = require_login();
$body      = read_json_body();
$course_id = (int)($body['course_id'] ?? 0);

$stmt = db()->prepare(
    'UPDATE registrations SET status = "dropped"
      WHERE user_id = ? AND course_id = ? AND status IN ("approved","pending")'
);
$stmt->execute([$user['user_id'], $course_id]);

if ($stmt->rowCount() === 0) {
    json_response(['ok' => false, 'error' => 'No active registration found for that course.'], 404);
}
json_response(['ok' => true, 'message' => 'Course dropped.']);
