<?php
// Register the current student into a course.
// Enforces business rules:
//   • student must be authenticated
//   • course must exist
//   • cannot register twice (DB unique key + explicit check for clean error)
//   • cannot exceed course capacity
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$user = require_login();
if ($user['role'] !== 'student') {
    json_response(['ok' => false, 'error' => 'Only students can register for courses.'], 403);
}

$body      = read_json_body();
$course_id = (int)($body['course_id'] ?? 0);
if ($course_id <= 0) {
    json_response(['ok' => false, 'error' => 'A course_id is required.'], 422);
}

$pdo = db();
$pdo->beginTransaction();
try {
    // Lock the course row so capacity checks are race-free.
    $stmt = $pdo->prepare(
        'SELECT capacity,
                (SELECT COUNT(*) FROM registrations r
                  WHERE r.course_id = c.course_id AND r.status = "active") AS enrolled
           FROM courses c
          WHERE course_id = ? FOR UPDATE'
    );
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();
    if (!$course) {
        $pdo->rollBack();
        json_response(['ok' => false, 'error' => 'Course not found.'], 404);
    }

    // Already-registered check.
    $chk = $pdo->prepare(
        'SELECT status FROM registrations WHERE user_id = ? AND course_id = ?'
    );
    $chk->execute([$user['user_id'], $course_id]);
    $existing = $chk->fetch();
    if ($existing && $existing['status'] === 'active') {
        $pdo->rollBack();
        json_response(['ok' => false, 'error' => 'You are already registered for this course.'], 409);
    }

    if ((int)$course['enrolled'] >= (int)$course['capacity']) {
        $pdo->rollBack();
        json_response(['ok' => false, 'error' => 'This course is full.'], 409);
    }

    if ($existing) {
        // Re-activate a previously dropped registration rather than insert.
        $upd = $pdo->prepare(
            'UPDATE registrations SET status = "active", registered_at = NOW()
              WHERE user_id = ? AND course_id = ?'
        );
        $upd->execute([$user['user_id'], $course_id]);
    } else {
        $ins = $pdo->prepare(
            'INSERT INTO registrations (user_id, course_id) VALUES (?, ?)'
        );
        $ins->execute([$user['user_id'], $course_id]);
    }

    $pdo->commit();
    json_response(['ok' => true, 'message' => 'Successfully registered.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['ok' => false, 'error' => 'Server error: ' . $e->getMessage()], 500);
}
