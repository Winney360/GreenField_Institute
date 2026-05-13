<?php
// ---------------------------------------------------------------------
// Admin overview — one round-trip for the dashboard:
//   • action_items   — pending decisions the admin should act on
//   • stats          — system-wide counts
//   • activity       — recent events (admissions + registrations) merged
//   • capacity_alerts — courses ≥ 80% full
// ---------------------------------------------------------------------
require_once __DIR__ . '/../includes/auth.php';

require_admin();
$pdo = db();

// --- Tier 1 — action items ------------------------------------------
$pendingRegs = (int)$pdo->query(
    'SELECT COUNT(*) FROM registrations WHERE status = "pending"'
)->fetchColumn();

$unactivatedStudents = (int)$pdo->query(
    'SELECT COUNT(*) FROM users
      WHERE role = "student" AND password_hash IS NULL'
)->fetchColumn();

// --- Tier 2 — stats -------------------------------------------------
$totalStudents = (int)$pdo->query(
    'SELECT COUNT(*) FROM users WHERE role = "student"'
)->fetchColumn();

$activatedStudents = (int)$pdo->query(
    'SELECT COUNT(*) FROM users
      WHERE role = "student" AND password_hash IS NOT NULL'
)->fetchColumn();

$totalCourses = (int)$pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn();

$approvedEnrolments = (int)$pdo->query(
    'SELECT COUNT(*) FROM registrations WHERE status = "approved"'
)->fetchColumn();

// --- Tier 3 — activity feed -----------------------------------------
// We merge two streams: recent admissions + recent course registrations.
// The schema doesn't track approve/reject timestamps, so the feed is
// based on what we DO have: users.created_at and registrations.registered_at.

$admissions = $pdo->query(
    'SELECT full_name AS subject, created_at AS at, "admission" AS type,
            NULL AS course_code, NULL AS status
       FROM users
      WHERE role = "student"
      ORDER BY created_at DESC
      LIMIT 10'
)->fetchAll();

$regs = $pdo->query(
    'SELECT u.full_name AS subject, r.registered_at AS at, "registration" AS type,
            c.course_code, r.status
       FROM registrations r
       JOIN users   u ON u.user_id   = r.user_id
       JOIN courses c ON c.course_id = r.course_id
      ORDER BY r.registered_at DESC
      LIMIT 10'
)->fetchAll();

// Merge, sort by timestamp desc, keep the top 10.
$activity = array_merge($admissions, $regs);
usort($activity, fn($a, $b) => strcmp($b['at'], $a['at']));
$activity = array_slice($activity, 0, 10);

// --- Tier 4 — capacity alerts (≥ 80% full) --------------------------
$capacityAlerts = $pdo->query(
    'SELECT c.course_id, c.course_code, c.title, c.capacity,
            (SELECT COUNT(*) FROM registrations r
              WHERE r.course_id = c.course_id
                AND r.status IN ("approved","pending")) AS enrolled
       FROM courses c
       HAVING (enrolled / capacity) >= 0.8
        ORDER BY (enrolled / capacity) DESC, c.course_code
        LIMIT 5'
)->fetchAll();

// Compute percentage for the front-end.
foreach ($capacityAlerts as &$c) {
    $c['percent'] = (int)round(((int)$c['enrolled'] / max(1, (int)$c['capacity'])) * 100);
}
unset($c);

json_response([
    'ok' => true,
    'action_items' => [
        'pending_registrations' => $pendingRegs,
        'unactivated_students'  => $unactivatedStudents,
    ],
    'stats' => [
        'total_students'      => $totalStudents,
        'activated_students'  => $activatedStudents,
        'total_courses'       => $totalCourses,
        'approved_enrolments' => $approvedEnrolments,
    ],
    'activity'        => $activity,
    'capacity_alerts' => $capacityAlerts,
]);
