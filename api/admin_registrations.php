<?php
// ---------------------------------------------------------------------
// Admin endpoint for registrations:
//   GET  → list every registration with student + course details
//   POST → approve or reject a specific registration row
// ---------------------------------------------------------------------
require_once __DIR__ . '/../includes/auth.php';

require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $b      = read_json_body();
    $action = $b['action']         ?? '';
    $regId  = (int)($b['registration_id'] ?? 0);

    if ($regId <= 0 || !in_array($action, ['approve', 'reject', 'drop'], true)) {
        json_response(['ok' => false, 'error' => 'Bad request.'], 400);
    }

    if ($action === 'drop') {
        // Admin drop — lets us back out a registration even after it's been
        // approved or rejected (e.g. student registered for the wrong
        // course by mistake). Only blocked if it's already dropped.
        $newStatus = 'dropped';
        $stmt = $pdo->prepare(
            'UPDATE registrations SET status = "dropped"
              WHERE registration_id = ? AND status <> "dropped"'
        );
        $stmt->execute([$regId]);
        $errorMsg = 'That registration is already dropped.';
    } else {
        // Approve / reject — only valid while the registration is pending.
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';
        $stmt = $pdo->prepare(
            'UPDATE registrations SET status = ?
              WHERE registration_id = ? AND status = "pending"'
        );
        $stmt->execute([$newStatus, $regId]);
        $errorMsg = 'That registration is no longer pending.';
    }

    if ($stmt->rowCount() === 0) {
        json_response(['ok' => false, 'error' => $errorMsg], 409);
    }
    json_response(['ok' => true, 'status' => $newStatus]);
}

// GET — list everything.
$stmt = $pdo->query('
    SELECT r.registration_id, r.registered_at, r.status,
           u.user_id, u.full_name, u.email,
           c.course_id, c.course_code, c.title
      FROM registrations r
      JOIN users   u ON u.user_id   = r.user_id
      JOIN courses c ON c.course_id = r.course_id
     ORDER BY
        CASE r.status
            WHEN "pending"  THEN 0
            WHEN "approved" THEN 1
            WHEN "rejected" THEN 2
            WHEN "dropped"  THEN 3
        END,
        r.registered_at DESC
');
json_response(['ok' => true, 'registrations' => $stmt->fetchAll()]);
