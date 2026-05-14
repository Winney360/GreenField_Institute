<?php
// ---------------------------------------------------------------------
// Student dashboard data — one round-trip returns everything the
// dashboard page needs: quick stats, current courses, recommendations,
// and profile-completeness info.
// ---------------------------------------------------------------------
require_once __DIR__ . '/../includes/auth.php';

$user = require_login();
$pdo  = db();
$uid  = (int)$user['user_id'];

// --- Full user profile (used for completion check + recommendations) ---
$stmt = $pdo->prepare(
    'SELECT full_name, email, registration_number, year_of_birth,
            gender, department, programme
       FROM users WHERE user_id = ?'
);
$stmt->execute([$uid]);
$profile = $stmt->fetch() ?: [];

// --- Current enrolments (approved + pending) + status -----------
$stmt = $pdo->prepare(
    'SELECT c.course_id, c.course_code, c.title, c.instructor,
            c.department, r.status
       FROM registrations r
       JOIN courses c ON c.course_id = r.course_id
      WHERE r.user_id = ? AND r.status IN ("approved","pending")
      ORDER BY r.registered_at DESC
      LIMIT 5'
);
$stmt->execute([$uid]);
$currentCourses = $stmt->fetchAll();

// Stat totals — count only confirmed (approved) enrolments.
$stmt = $pdo->prepare(
    'SELECT COUNT(*) AS cnt
       FROM registrations r
      WHERE r.user_id = ? AND r.status = "approved"'
);
$stmt->execute([$uid]);
$totals = $stmt->fetch();

// --- Profile completion (5 optional fields) -------------------------
$optionalFields = ['registration_number', 'year_of_birth', 'gender', 'department', 'programme'];
$filled = 0;
foreach ($optionalFields as $f) {
    if (!empty($profile[$f])) $filled++;
}
$profileCompletion = (int)round(($filled / count($optionalFields)) * 100);

// --- Recommendations: courses in user's department that they haven't
// registered for and which still have seats. Falls back to any
// available course if the user hasn't set a department yet. ---------
$dept = $profile['department'] ?? null;
$params = [$uid];
$sql = 'SELECT c.course_id, c.course_code, c.title, c.department,
               c.instructor, c.capacity,
               (SELECT COUNT(*) FROM registrations r2
                  WHERE r2.course_id = c.course_id
                    AND r2.status IN ("approved","pending")) AS enrolled
          FROM courses c
         WHERE c.course_id NOT IN (
               SELECT course_id FROM registrations
                WHERE user_id = ? AND status IN ("approved","pending")
           )';
if ($dept !== null && $dept !== '') {
    $sql .= ' AND c.department = ?';
    $params[] = $dept;
}
$sql .= ' ORDER BY c.course_id LIMIT 10';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$candidates = $stmt->fetchAll();

// Filter to ones with seats available, take top 3.
$recommendations = [];
foreach ($candidates as $c) {
    if ((int)$c['enrolled'] < (int)$c['capacity']) {
        $recommendations[] = $c;
        if (count($recommendations) === 3) break;
    }
}

json_response([
    'ok'    => true,
    'stats' => [
        'enrolled_count'     => (int)$totals['cnt'],
        'profile_completion' => $profileCompletion,
    ],
    'current_courses'    => $currentCourses,
    'recommendations'    => $recommendations,
    'profile_incomplete' => $profileCompletion < 100,
    'user_name'          => $profile['full_name'] ?? '',
]);
